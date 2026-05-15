<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class FaceAuthController extends AbstractController
{
    private string $flaskUrl = 'http://127.0.0.1:5000';

    #[Route('/face/save', name: 'face_save', methods: ['POST'])]
    public function saveFace(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['success' => false, 'error' => 'Non connecté']);
        }

        $data = json_decode($request->getContent(), true);
        $imageBase64 = $data['image'] ?? null;

        if (!$imageBase64) {
            return new JsonResponse(['success' => false, 'error' => 'Pas d\'image reçue']);
        }

        $flaskResponse = $this->callFlask('/extract-descriptor', [
            'image' => $imageBase64
        ]);

        if (!($flaskResponse['success'] ?? false)) {
            return new JsonResponse([
                'success' => false,
                'error' => $flaskResponse['error'] ?? 'Visage non détecté'
            ]);
        }

        $user->setFaceDescriptor(json_encode($flaskResponse['descriptor']));
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/face/login', name: 'face_login', methods: ['POST'])]
    public function faceLogin(
        Request $request,
        UserRepository $userRepository,
        Security $security
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $imageBase64 = $data['image'] ?? null;

        if (!$imageBase64) {
            return new JsonResponse(['success' => false, 'error' => 'Pas d\'image reçue']);
        }

        $liveResponse = $this->callFlask('/extract-descriptor', [
            'image' => $imageBase64
        ]);

        if (!($liveResponse['success'] ?? false)) {
            return new JsonResponse(['success' => false, 'error' => 'Visage non détecté']);
        }

        $liveDescriptor = $liveResponse['descriptor'];

        $users = $userRepository->findAll();
        $bestMatch = null;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($users as $user) {
            if (!$user->getFaceDescriptor()) continue;

            $savedDescriptor = json_decode($user->getFaceDescriptor(), true);

            $compareResponse = $this->callFlask('/compare-faces', [
                'descriptor1' => $liveDescriptor,
                'descriptor2' => $savedDescriptor
            ]);

            if (!($compareResponse['success'] ?? false)) continue;

            if ($compareResponse['verified'] && $compareResponse['distance'] < $bestDistance) {
                $bestDistance = $compareResponse['distance'];
                $bestMatch = $user;
            }
        }

        if (!$bestMatch) {
            return new JsonResponse(['success' => false, 'error' => 'Visage non reconnu']);
        }

        if (!$bestMatch->isActive()) {
            return new JsonResponse(['success' => false, 'error' => 'Compte désactivé']);
        }

        // ✅ Connecter l'utilisateur
        $security->login($bestMatch, null, 'main');

        // ✅ Forcer la sauvegarde de la session
        $request->getSession()->save();

        // ✅ Même logique que SecurityController
        if (in_array('ROLE_ADMIN', $bestMatch->getRoles())) {
            $redirect = $this->generateUrl('admin_dashboard');
        } elseif (in_array('ROLE_PROPRIETAIRE', $bestMatch->getRoles())) {
            $redirect = $this->generateUrl('app_proprietaire_profile');
        } elseif (in_array('ROLE_CLIENT', $bestMatch->getRoles())) {
            $redirect = $this->generateUrl('app_client_profile'); // ✅ corrigé
        } else {
            $redirect = $this->generateUrl('app_home');
        }

        return new JsonResponse([
            'success'  => true,
            'redirect' => $redirect,
            'name'     => $bestMatch->getPrenom(),
            'distance' => $bestDistance
        ]);
    }

    private function callFlask(string $route, array $data): array
    {
        $ch = curl_init($this->flaskUrl . $route);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true) ?? ['success' => false];
    }
}