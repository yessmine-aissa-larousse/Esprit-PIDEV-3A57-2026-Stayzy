<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class FaceAuthController extends AbstractController
{
    // Sauvegarder le descripteur facial lors de l'inscription
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
        $descriptor = $data['descriptor'] ?? null;

        if (!$descriptor) {
            return new JsonResponse(['error' => 'Pas de visage détecté'], 400);
        }

        $user->setFaceDescriptor(json_encode($descriptor));
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    // Vérifier le visage lors du login
    #[Route('/face/login', name: 'face_login', methods: ['POST'])]
    public function faceLogin(
        Request $request,
        UserRepository $userRepository,
        EntityManagerInterface $em,
        Security $security
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $descriptor = $data['descriptor'] ?? null;

        if (!$descriptor) {
            return new JsonResponse(['error' => 'Pas de visage détecté'], 400);
        }

        // Chercher tous les users avec un face descriptor
        $users = $userRepository->findAll();
        $bestMatch = null;
        $bestDistance = 0.6; // seuil de similarité

        foreach ($users as $user) {
            if (!$user->getFaceDescriptor()) continue;

            $savedDescriptor = json_decode($user->getFaceDescriptor(), true);
            $distance = $this->euclideanDistance($descriptor, $savedDescriptor);

            if ($distance < $bestDistance) {
                $bestDistance = $distance;
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
            'success' => true,
            'redirect' => $redirect,
            'name' => $bestMatch->getPrenom()
        ]);
    }

    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0;
        foreach ($a as $i => $val) {
            $sum += ($val - $b[$i]) ** 2;
        }
        return sqrt($sum);
    }
}