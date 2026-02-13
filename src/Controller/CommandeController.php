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
    // 🔹 Lister toutes les commandes (pour test ou admin)
    #[Route('/', name: 'list')]
    public function list(EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find(1); // utilisateur test (client)
        $commandes = $em->getRepository(Commande::class)->findBy(['user' => $user]);

        return $this->render('backOffice/commande/list.html.twig', [
            'commandes' => $commandes,
        ]);
    }

    // 🔹 Générer une commande automatiquement lors d’une réservation confirmée
    public function createFromReservation(Reservation $reservation, EntityManagerInterface $em): void
    {
        // Vérifie si commande déjà créée pour cette réservation
        $existing = $em->getRepository(Commande::class)->findOneBy(['reservation' => $reservation]);
        if ($existing) return;

        $commande = new Commande();
        $commande->setDateDebut($reservation->getDateDebut());
        $commande->setDateFin($reservation->getDateFin());
        $commande->setPrixTotal($reservation->getPrixTotal());
        $commande->setStatus('EN_COURS'); // statut par défaut
        $commande->setPaymentMethode('Paiement à la livraison'); // temporaire
        $commande->setPaymentStatus('EN_ATTENTE');
        $commande->setDateTransaction(new \DateTime());
        $commande->setReservation($reservation);
        $commande->setUser($reservation->getUser());

        $em->persist($commande);
        $em->flush();
    }
}
