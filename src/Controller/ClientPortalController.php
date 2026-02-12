<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ClientPortalController extends AbstractController
{
    // Page d'accueil portail client (avant connexion)
    public function index(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_client_dashboard');
        }
        return $this->render('security/client.html.twig');
    }

    // Dashboard client (après connexion)
    public function dashboard(): Response
    {
        if (!$this->isGranted('ROLE_CLIENT')) {
            return $this->redirectToRoute('app_login');
        }
        
        /** @var User $user */
        $user = $this->getUser();
        
        return $this->render('client/dashboard.html.twig', [
            'user' => $user,
        ]);
    }

    // Profil client
    public function profile(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        if (!$this->isGranted('ROLE_CLIENT')) {
            return $this->redirectToRoute('app_login');
        }
        
        /** @var User $user */
        $user = $this->getUser();
        $oldPassword = $user->getPassword();
        
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();
            if ($newPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $newPassword);
                $user->setPassword($hashedPassword);
            } else {
                $user->setPassword($oldPassword);
            }

            $em->flush();

            $this->addFlash('success', 'Profil modifié avec succès!');
            return $this->redirectToRoute('app_client_profile');
        }

        return $this->render('client/profile.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}