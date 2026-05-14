<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/proprietaire')]
class ProprietairePortalController extends AbstractController
{
    /**
     * Landing page (accessible sans connexion)
     */
    #[Route('', name: 'app_proprietaire_portal')]
    public function index(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_proprietaire_profile');
        }
        return $this->render('frontOffice/proprietaire.html.twig');
    }

    /**
     * Page d'information : compte EN ATTENTE de validation
     */
    #[Route('/en-attente', name: 'app_proprietaire_pending')]
    #[IsGranted('ROLE_PROPRIETAIRE')]
    public function pending(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isApproved()) {
            return $this->redirectToRoute('app_proprietaire_profile');
        }

        return $this->render('frontOffice/proprietaire_pending.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Page d'information : compte REJETÉ
     */
    #[Route('/rejete', name: 'app_proprietaire_rejected')]
    #[IsGranted('ROLE_PROPRIETAIRE')]
    public function rejected(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isApproved()) {
            return $this->redirectToRoute('app_proprietaire_profile');
        }

        return $this->render('frontOffice/proprietaire_rejected.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Profil propriétaire — APPROUVÉ uniquement
     */
    #[Route('/profile', name: 'app_proprietaire_profile')]
    #[IsGranted('ROLE_PROPRIETAIRE')]
    public function profile(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        if ($user->isPending()) {
            return $this->redirectToRoute('app_proprietaire_pending');
        }
        if ($user->isRejected()) {
            return $this->redirectToRoute('app_proprietaire_rejected');
        }

        $oldPassword = $user->getPassword();
        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $profilePictureFile = $request->files->get('profile_picture');
            if ($profilePictureFile) {
                $originalFilename = pathinfo($profilePictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $profilePictureFile->guessExtension();

                try {
                    /** @var string $projectDir */
                    $projectDir = $this->getParameter('kernel.project_dir');
                    $profilePictureFile->move(
                        $projectDir . '/public/uploads/profiles',
                        $newFilename
                    );
                    $user->setProfilePicture($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', "Erreur lors de l'upload de la photo.");
                }
            }

            $newPassword = $form->get('password')->getData();
            if ($newPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            } else {
                $user->setPassword($oldPassword);
            }

            $em->flush();
            $this->addFlash('success', 'Profil modifié avec succès !');
            return $this->redirectToRoute('app_proprietaire_profile');
        }

        return $this->render('frontOffice/profile/proprietaire_profile.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}