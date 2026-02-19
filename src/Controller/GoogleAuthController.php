<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class GoogleAuthController extends AbstractController
{
    #[Route('/connect/google/check', name: 'connect_google_check', methods: ['POST'])]
    public function connectGoogleCheck(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        Security $security
    ): Response {
        $credential = $request->request->get('credential');

        if (!$credential) {
            $this->addFlash('error', 'Erreur de connexion avec Google.');
            return $this->redirectToRoute('app_login');
        }

        try {
            // Décoder le JWT Google (sans librairie externe)
            $parts = explode('.', $credential);
            if (count($parts) !== 3) {
                throw new \Exception('Token invalide');
            }

            $payload = json_decode(base64_decode(str_pad(
                strtr($parts[1], '-_', '+/'),
                strlen($parts[1]) % 4 === 0 ? strlen($parts[1]) : strlen($parts[1]) + 4 - strlen($parts[1]) % 4,
                '='
            )), true);

            if (!$payload || !isset($payload['email'])) {
                throw new \Exception('Email non trouvé dans le token');
            }

            $email = $payload['email'];
            $prenom = $payload['given_name'] ?? 'Google';
            $nom = $payload['family_name'] ?? 'User';

            // Chercher si l'utilisateur existe déjà
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if (!$user) {
                // Créer nouveau compte automatiquement
                $user = new User();
                $user->setEmail($email);
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setRoles(['ROLE_CLIENT']);
                $user->setPassword($passwordHasher->hashPassword($user, bin2hex(random_bytes(16))));
                $user->setIsActive(true);
                $user->setIsVerified(true);
                $em->persist($user);
                $em->flush();

                $this->addFlash('success', 'Bienvenue ' . $prenom . '! Compte créé avec Google.');
            } else {
                $this->addFlash('success', 'Bienvenue ' . $user->getPrenom() . '!');
            }

            // Connexion automatique
            $security->login($user);

            // Redirection selon le rôle
            if ($user->isAdmin()) {
                return $this->redirectToRoute('admin_dashboard');
            } elseif ($user->isProprietaire()) {
                return $this->redirectToRoute('app_proprietaire_profile');
            }
            return $this->redirectToRoute('app_client_dashboard');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur de connexion avec Google: ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }
}