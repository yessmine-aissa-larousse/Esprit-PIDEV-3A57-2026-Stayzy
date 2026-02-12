<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PortalController extends AbstractController
{
    #[Route('/client', name: 'app_client_portal')]
    public function clientPortal(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        return $this->render('security/client.html.twig');
    }

    #[Route('/proprietaire', name: 'app_proprietaire_portal')]
    public function proprietairePortal(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        return $this->render('security/proprietaire.html.twig');
    }

    #[Route('/register/{role}', name: 'app_register', requirements: ['role' => 'client|proprietaire'])]
    public function register(
        Request $request, 
        string $role,
        EntityManagerInterface $em, 
        UserPasswordHasherInterface $passwordHasher
    ): Response 
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        
        if ($role === 'client') {
            $user->setRoles(['ROLE_CLIENT']);
        } else {
            $user->setRoles(['ROLE_PROPRIETAIRE']);
        }

        $form = $this->createForm(UserType::class, $user, ['is_edit' => false]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($password = $form->get('password')->getData()) {
                $user->setPassword($passwordHasher->hashPassword($user, $password));
            }
            $user->setIsActive(true);
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'Compte cree avec succes !');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/register.html.twig', [
            'form' => $form,
            'role' => $role,
        ]);
    }
}