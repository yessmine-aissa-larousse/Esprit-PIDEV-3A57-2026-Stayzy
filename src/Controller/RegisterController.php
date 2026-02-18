<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Attribute\Route;

class RegisterController extends AbstractController
{
    #[Route('/register/{role}', name: 'app_register', requirements: ['role' => 'client|proprietaire'])]
    public function register(
        Request $request,
        string $role,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        Security $security,
        MailerInterface $mailer
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();

        if ($role === 'client') {
            $user->setRoles(['ROLE_CLIENT']);
        } else {
            $user->setRoles(['ROLE_PROPRIETAIRE']);
            $user->setApprovalStatus(User::STATUS_PENDING);
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

            if ($role === 'client') {
                $security->login($user);
                $this->addFlash('success', 'Bienvenue sur StayZy ! Votre compte a été créé avec succès.');
                return $this->redirectToRoute('app_client_dashboard');
            } else {
                $adminEmail = 'admin@stayzy.com';
                $email = (new Email())
                    ->from('no-reply@stayzy.com')
                    ->to($adminEmail)
                    ->subject('Nouvelle demande inscription propriétaire')
                    ->html($this->renderView('emails/admin_new_proprietaire.html.twig', [
                        'user' => $user
                    ]));
                $mailer->send($email);

                $this->addFlash('success', 'Votre demande a été envoyée. Vous recevrez un email après validation.');
                return $this->redirectToRoute('app_home');
            }
        }

        return $this->render('frontOffice/register.html.twig', [
            'form' => $form,
            'role' => $role,
        ]);
    }
}