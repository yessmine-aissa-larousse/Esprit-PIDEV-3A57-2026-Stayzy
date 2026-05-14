<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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
        $search = (string) $request->query->get('search', '');
        $role   = (string) $request->query->get('role', '');

        // ✅ FIX : $users toujours défini
        if ($search !== '' || $role !== '') {
            $users = $userRepository->search($search ?: null, $role ?: null);
        } else {
            $users = $userRepository->findAll();
        }

        return $this->render('backOffice/user/index.html.twig', [
            'users'  => $users,
            'search' => $search,
            'role'   => $role,
        ]);
    }

    // ========== LISTE DES PROPRIÉTAIRES EN ATTENTE ==========
    #[Route('/pending', name: 'app_user_pending')]
    public function pending(UserRepository $userRepository): Response
    {
        $users = $userRepository->findByRoleAndStatus('ROLE_PROPRIETAIRE', User::STATUS_PENDING);
        return $this->render('backOffice/user/pending.html.twig', [
            'users' => $users,
        ]);
    }

    // ========== AJOUTER UN UTILISATEUR ==========
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

            $this->addFlash('success', 'Utilisateur ajouté avec succès!');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('backOffice/user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // ========== VOIR UN UTILISATEUR ==========
    #[Route('/{id}', name: 'app_user_show', requirements: ['id' => '\d+'])]
    public function show(User $user): Response
    {
        return $this->render('backOffice/user/show.html.twig', [
            'user' => $user,
        ]);
    }

    // ========== MODIFIER UN UTILISATEUR ==========
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

            $this->addFlash('success', 'Utilisateur modifié avec succès!');
            return $this->redirectToRoute('app_user_index');
        }

        return $this->render('backOffice/user/edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }

    // ========== SUPPRIMER UN UTILISATEUR ==========
    #[Route('/{id}/delete', name: 'app_user_delete', methods: ['POST'])]
    public function delete(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $user->getId(), (string) $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Utilisateur supprimé avec succès!');
        }
        return $this->redirectToRoute('app_user_index');
    }

    // ========== ACTIVER/DÉSACTIVER UN UTILISATEUR ==========
    #[Route('/{id}/toggle-status', name: 'app_user_toggle_status', methods: ['POST'])]
    public function toggleStatus(Request $request, User $user, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('toggle' . $user->getId(), (string) $request->request->get('_token'))) {
            $user->setIsActive(!$user->isActive());
            $em->flush();
            $status = $user->isActive() ? 'activé' : 'désactivé';
            $this->addFlash('success', "Utilisateur $status avec succès!");
        }
        return $this->redirectToRoute('app_user_index');
    }

    // ========== APPROUVER UN PROPRIÉTAIRE ==========
    #[Route('/{id}/approve', name: 'app_user_approve', methods: ['POST'])]
    public function approve(
        Request $request,
        User $user,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        if (!$user->isProprietaire()) {
            throw $this->createNotFoundException('Utilisateur non propriétaire');
        }
        if ($this->isCsrfTokenValid('approve' . $user->getId(), (string) $request->request->get('_token'))) {
            $user->setApprovalStatus(User::STATUS_APPROVED);
            $user->setApprovalDate(new \DateTime());
            $em->flush();

            try {
                $email = (new Email())
                    ->from('no-reply@stayzy.com')
                    ->to((string) $user->getEmail()) // ✅ un seul ->to() avec cast
                    ->subject('✅ Votre compte propriétaire a été approuvé - Stayzy')
                    ->html($this->renderView('emails/proprietaire_approved.html.twig', [
                        'user' => $user
                    ]));
                $mailer->send($email);
                $this->addFlash('success', 'Compte approuvé et email envoyé à ' . $user->getEmail());
            } catch (\Exception $e) {
                $this->addFlash('success', 'Compte approuvé avec succès.');
            }
        }
        return $this->redirectToRoute('app_user_pending');
    }

    // ========== REJETER UN PROPRIÉTAIRE ==========
    #[Route('/{id}/reject', name: 'app_user_reject', methods: ['POST'])]
    public function reject(
        Request $request,
        User $user,
        EntityManagerInterface $em,
        MailerInterface $mailer
    ): Response {
        if (!$user->isProprietaire()) {
            throw $this->createNotFoundException('Utilisateur non propriétaire');
        }
        $reason = $request->request->get('rejection_reason');
        if ($this->isCsrfTokenValid('reject' . $user->getId(), (string) $request->request->get('_token'))) {
            $user->setApprovalStatus(User::STATUS_REJECTED);
            $user->setApprovalDate(new \DateTime());
            $user->setRejectionReason((string) $request->request->get('rejection_reason'));
            $em->flush();

            try {
                $email = (new Email())
                    ->from('no-reply@stayzy.com')
                    ->to((string) $user->getEmail()) // ✅ cast string
                    ->subject('❌ Résultat de votre demande propriétaire - Stayzy')
                    ->html($this->renderView('emails/proprietaire_rejected.html.twig', [
                        'user'   => $user,
                        'reason' => $reason
                    ]));
                $mailer->send($email);
                $this->addFlash('success', 'Compte rejeté et email envoyé à ' . $user->getEmail());
            } catch (\Exception $e) {
                $this->addFlash('success', 'Compte rejeté avec succès.');
            }
        }
        return $this->redirectToRoute('app_user_pending');
    }

    // ========== EXPORTER EN CSV ==========
    #[Route('/export/csv', name: 'app_user_export_csv')]
    public function exportCsv(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        $csvContent = "ID;Email;Nom;Prénom;Téléphone;Rôle;Statut;Date Création\n";
        foreach ($users as $user) {
            $csvContent .= sprintf(
                "%d;%s;%s;%s;%s;%s;%s;%s\n",
                $user->getId(),
                $user->getEmail(),
                $user->getNom(),
                $user->getPrenom(),
                $user->getTel() ?? '',
                implode(', ', $user->getRoleNames()),
                $user->isActive() ? 'Actif' : 'Inactif',
                $user->getCreatedAt()->format('Y-m-d H:i')
            );
        }
        $response = new Response($csvContent);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="utilisateurs_' . date('Y-m-d') . '.csv"');
        return $response;
    }
}