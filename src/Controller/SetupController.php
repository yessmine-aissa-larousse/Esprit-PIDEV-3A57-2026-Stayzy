<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class SetupController extends AbstractController
{
    #[Route('/create-admin', name: 'app_create_admin')]
    public function createAdmin(
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
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
        }
        return $this->redirectToRoute('app_login');
    }
}