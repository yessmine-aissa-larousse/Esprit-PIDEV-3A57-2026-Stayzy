<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Service\CaptchaVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class PortalController extends AbstractController
{
    #[Route('/client', name: 'app_client_portal')]
    public function clientPortal(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        return $this->render('frontOffice/client.html.twig');
    }

    #[Route('/proprietaire', name: 'app_proprietaire_portal')]
    public function proprietairePortal(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        return $this->render('proprietaire.html.twig');
    }

    #[Route('/register/{role}', name: 'app_register', requirements: ['role' => 'client|proprietaire'])]
    public function register(
        Request $request,
        string $role,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        Security $security,
        CaptchaVerifier $captcha
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        $user = new User();
        $user->setRoles($role === 'client' ? ['ROLE_CLIENT'] : ['ROLE_PROPRIETAIRE']);

        $form = $this->createForm(UserType::class, $user, ['is_edit' => false]);
        $form->handleRequest($request);

        // Hash password before isValid() so the entity NotNull constraint passes
        if ($form->isSubmitted()) {
            $rawPassword = $form->get('password')->getData();
            $user->setPassword(
                $rawPassword ? $passwordHasher->hashPassword($user, $rawPassword) : ''
            );
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // CAPTCHA verification
            $captchaInput = $request->request->get('captcha_code', '');
            if (!$captcha->verify($captchaInput)) {
                $this->addFlash('error', 'Code CAPTCHA incorrect. Veuillez réessayer.');
                return $this->render('frontOffice/register.html.twig', ['form' => $form, 'role' => $role]);
            }
            $user->setIsActive(true);

            if ($role === 'proprietaire') {
                $user->setApprovalStatus(User::STATUS_PENDING);
            }

            $em->persist($user);
            $em->flush();

            // Auto-login: LoginSuccessSubscriber handles role-based redirect
            // → client       → app_client_profile
            // → proprietaire → app_proprietaire_pending (if STATUS_PENDING)
            $loginResponse = $security->login($user, 'form_login', 'main');
            return $loginResponse ?? $this->redirectToRoute('app_home');
        }

        return $this->render('frontOffice/register.html.twig', [
            'form' => $form,
            'role' => $role,
        ]);
    }
}
