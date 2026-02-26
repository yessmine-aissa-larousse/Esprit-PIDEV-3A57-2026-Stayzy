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

    // ─── Sauvegarder le descripteur via Flask ─────────────
    #[Route('/face/save', name: 'face_save', methods: ['POST'])]
    public function saveFace(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non connecté'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $imageBase64 = $data['image'] ?? null;

        if (!$imageBase64) {
            return new JsonResponse(['error' => 'Pas d\'image reçue'], 400);
        }

        // Envoyer l'image à Flask pour extraire le descriptor
        $flaskResponse = $this->callFlask('/extract-descriptor', [
            'image' => $imageBase64
        ]);

        if (!($flaskResponse['success'] ?? false)) {
            return new JsonResponse([
                'error' => $flaskResponse['error'] ?? 'Visage non détecté'
            ], 400);
        }

        // Sauvegarder le descriptor en base
        $user->setFaceDescriptor(json_encode($flaskResponse['descriptor']));
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    // ─── Login par visage via Flask ───────────────────────
    #[Route('/face/login', name: 'face_login', methods: ['POST'])]
    public function faceLogin(
        Request $request,
        UserRepository $userRepository,
        Security $security
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $imageBase64 = $data['image'] ?? null;

        if (!$imageBase64) {
            return new JsonResponse(['error' => 'Pas d\'image reçue'], 400);
        }

        // Extraire le descriptor du visage en temps réel via Flask
        $liveResponse = $this->callFlask('/extract-descriptor', [
            'image' => $imageBase64
        ]);

        if (!($liveResponse['success'] ?? false)) {
            return new JsonResponse(['error' => 'Visage non détecté'], 400);
        }

        $liveDescriptor = $liveResponse['descriptor'];

        // Comparer avec tous les users en base
        $users = $userRepository->findAll();
        $bestMatch = null;
        $bestDistance = PHP_FLOAT_MAX;

        foreach ($users as $user) {
            if (!$user->getFaceDescriptor()) continue;

            $savedDescriptor = json_decode($user->getFaceDescriptor(), true);

            // Comparer via Flask
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
            return new JsonResponse(['error' => 'Visage non reconnu'], 401);
        }

        if (!$bestMatch->isActive()) {
            return new JsonResponse(['error' => 'Compte désactivé'], 401);
        }

        // Connecter l'utilisateur
        $security->login($bestMatch);

        // Redirection selon le rôle
        if ($bestMatch->isAdmin()) {
            $redirect = $this->generateUrl('admin_dashboard');
        } elseif ($bestMatch->isProprietaire()) {
            $redirect = $this->generateUrl('app_proprietaire_profile');
        } else {
            $redirect = $this->generateUrl('app_client_dashboard');
        }

        return new JsonResponse([
            'success'  => true,
            'redirect' => $redirect,
            'name'     => $bestMatch->getPrenom(),
            'distance' => $bestDistance
        ]);
    }

    // ─── Helper : appel HTTP vers Flask ──────────────────
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