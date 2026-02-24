<?php

namespace App\Controller;

use App\Entity\Commande;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/commande', name: 'commande_')]
class CommandeController extends AbstractController
{
    // 🔹 Lister toutes les commandes pour le client
    #[Route('/', name: 'list')]
    public function list(EntityManagerInterface $em): Response
    {
        // 🔹 Remplacer par le user connecté si nécessaire
        $user = $em->getRepository(User::class)->find(1);

        $commandes = $em->getRepository(Commande::class)->findBy(['user' => $user]);

        // ✅ Clé publique Stripe
        $stripePublicKey = $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? null;

        return $this->render('frontOffice/commande/list.html.twig', [
            'commandes' => $commandes,
            'stripe_public_key' => $stripePublicKey,
        ]);
    }

    // 🔹 Générer une commande à partir d'une réservation confirmée
    public function createFromReservation(Reservation $reservation, EntityManagerInterface $em): void
    {
        $existing = $em->getRepository(Commande::class)->findOneBy(['reservation' => $reservation]);
        if ($existing) return;

        $commande = new Commande();
        $commande->setDateDebut($reservation->getDateDebut());
        $commande->setDateFin($reservation->getDateFin());
        $commande->setPrixTotal($reservation->getPrixTotal());
        $commande->setStatus('EN_COURS');
        $commande->setPaymentMethode('Carte (Stripe)');
        $commande->setPaymentStatus('EN_ATTENTE');
        $commande->setDateTransaction(new \DateTime());
        $commande->setReservation($reservation);
        $commande->setUser($reservation->getUser());

        $em->persist($commande);
        $em->flush();
    }

    #[Route('/success', name: 'commande_success')]
    public function success(): Response
    {
        return $this->render('frontOffice/commande/success.html.twig');
    }

    #[Route('/cancel', name: 'commande_cancel')]
    public function cancel(): Response
    {
        return $this->render('frontOffice/commande/cancel.html.twig');
    }
}
