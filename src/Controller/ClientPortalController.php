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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class ClientPortalController extends AbstractController
{
    #[Route('/client', name: 'app_client_portal')]
    public function index(): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_client_dashboard');
        }
        return $this->render('frontOffice/client.html.twig');
    }

    #[Route('/client/dashboard', name: 'app_client_dashboard')]
    public function dashboard(): Response
    {
        if (!$this->isGranted('ROLE_CLIENT')) {
            return $this->redirectToRoute('app_login');
        }

        /** @var User $user */
        $user = $this->getUser();

        return $this->render('frontOffice/client/dashboard.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/client/profile', name: 'app_client_profile')]
    public function profile(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        SluggerInterface $slugger
    ): Response {
        if (!$this->isGranted('ROLE_CLIENT')) {
            return $this->redirectToRoute('app_login');
        }

        /** @var User $user */
        $user = $this->getUser();
        $oldPassword = $user->getPassword();

        $form = $this->createForm(UserType::class, $user, ['is_edit' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Gestion upload photo
            $profilePictureFile = $request->files->get('profile_picture');
            if ($profilePictureFile) {
                $originalFilename = pathinfo($profilePictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $profilePictureFile->guessExtension();

                try {
                    $profilePictureFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/profiles',
                        $newFilename
                    );
                    $user->setProfilePicture($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de la photo.');
                }
            }

            // Gestion mot de passe
            $newPassword = $form->get('password')->getData();
            if ($newPassword) {
                $user->setPassword($passwordHasher->hashPassword($user, $newPassword));
            } else {
                $user->setPassword($oldPassword);
            }

            $em->flush();
            $this->addFlash('success', 'Profil modifié avec succès!');
            return $this->redirectToRoute('app_client_profile');
        }

        return $this->render('frontOffice/client_profile.html.twig', [
            'form' => $form->createView(),
            'user' => $user,
        ]);
    }
}