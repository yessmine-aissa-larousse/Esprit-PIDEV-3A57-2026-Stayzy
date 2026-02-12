<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/admin/user')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    #[Route('/', name: 'app_user_index')]
    public function index(Request $request, UserRepository $userRepository): Response
    {
        $search = $request->query->get('search');
        $role = $request->query->get('role');

        if ($search || $role) {
            $users = $userRepository->search($search, $role);
        } else {
            $users = $userRepository->findAll();
        }

        return $this->render('backOffice/user/index.html.twig', [
            'users' => $users,
            'search' => $search,
            'role' => $role,
        ]);
    }

    #[Route('/pending', name: 'app_user_pending')]
    public function pending(UserRepository $userRepository): Response
    {
        $users = $userRepository->findByRoleAndStatus('ROLE_PROPRIETAIRE', User::STATUS_PENDING);
        return $this->render('backOffice/user/pending.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/new', name: 'app_user_new')]
    public function new(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($password = $form->get('password')->getData()) {
                $hashedPassword = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($hashedPassword);
            }
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', 'User added successfully!');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('backOffice/user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', requirements: ['id' => '\d+'])]
    public function show(User $user): Response
    {
        return $this->render('backOffice/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit')]
    public function edit(Request $request, User $user, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $oldPassword = $user->getPassword();

        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
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

            $this->addFlash('success', 'User updated successfully!');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('backOffice/user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'User deleted successfully!');
        }
        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/{id}/toggle-status', name: 'app_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('toggle' . $user->getId(), $request->request->get('_token'))) {
            $user->setIsActive(!$user->isActive());
            $em->flush();
            $status = $user->isActive() ? 'activated' : 'deactivated';
            $this->addFlash('success', "User $status successfully!");
        }
        return $this->redirectToRoute('app_user_index');
    }

    #[Route('/{id}/approve', name: 'app_user_approve', methods: ['POST'])]
    public function approve(Request $request, User $user, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if (!$user->isProprietaire()) {
            throw $this->createNotFoundException('User is not a proprietaire');
        }
        if ($this->isCsrfTokenValid('approve' . $user->getId(), $request->request->get('_token'))) {
            $user->setApprovalStatus(User::STATUS_APPROVED);
            $user->setApprovalDate(new \DateTime());
            $em->flush();

            $email = (new Email())
                ->from('no-reply@stayzy.com')
                ->to($user->getEmail())
                ->subject('Your proprietaire account has been approved')
                ->html($this->renderView('emails/proprietaire_approved.html.twig', [
                    'user' => $user
                ]));
            $mailer->send($email);

            $this->addFlash('success', 'Proprietaire account approved successfully.');
        }
        return $this->redirectToRoute('app_user_pending');
    }

    #[Route('/{id}/reject', name: 'app_user_reject', methods: ['POST'])]
    public function reject(Request $request, User $user, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if (!$user->isProprietaire()) {
            throw $this->createNotFoundException('User is not a proprietaire');
        }
        $reason = $request->request->get('rejection_reason');
        if ($this->isCsrfTokenValid('reject' . $user->getId(), $request->request->get('_token'))) {
            $user->setApprovalStatus(User::STATUS_REJECTED);
            $user->setApprovalDate(new \DateTime());
            $user->setRejectionReason($reason);
            $em->flush();

            $email = (new Email())
                ->from('no-reply@stayzy.com')
                ->to($user->getEmail())
                ->subject('Your proprietaire registration has been rejected')
                ->html($this->renderView('emails/proprietaire_rejected.html.twig', [
                    'user' => $user,
                    'reason' => $reason
                ]));
            $mailer->send($email);

            $this->addFlash('success', 'Proprietaire account rejected.');
        }
        return $this->redirectToRoute('app_user_pending');
    }

    #[Route('/create-admin', name: 'app_create_admin')]
    public function createAdmin(UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $admin = $em->getRepository(User::class)->findOneBy(['email' => 'admin@stayzy.com']);
        if (!$admin) {
            $admin = new User();
            $admin->setEmail('admin@stayzy.com');
            $admin->setNom('Admin');
            $admin->setPrenom('StayZy');
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setPassword($passwordHasher->hashPassword($admin, 'admin123'));
            $admin->setIsActive(true);
            $admin->setIsVerified(true);
            $em->persist($admin);
            $em->flush();
            $this->addFlash('success', 'Admin account created successfully! Email: admin@stayzy.com, Password: admin123');
        } else {
            $this->addFlash('info', 'Admin account already exists.');
        }
        return $this->redirectToRoute('app_login');
    }

    #[Route('/export/csv', name: 'app_user_export_csv')]
    public function exportCsv(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        $csvContent = "ID;Email;Nom;Prenom;Telephone;Role;Statut;Date Creation\n";
        foreach ($users as $user) {
            $csvContent .= sprintf(
                "%d;%s;%s;%s;%s;%s;%s;%s\n",
                $user->getId(),
                $user->getEmail(),
                $user->getNom(),
                $user->getPrenom(),
                $user->getTel() ?? '',
                implode(', ', $user->getRoleNames()),
                $user->isActive() ? 'Active' : 'Inactive',
                $user->getCreatedAt()->format('Y-m-d H:i')
            );
        }
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="users_' . date('Y-m-d') . '.csv"');
        return $response;
    }
}