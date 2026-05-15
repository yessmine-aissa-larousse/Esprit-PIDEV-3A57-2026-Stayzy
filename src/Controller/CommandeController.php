<?php

namespace App\Controller;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Entity\Commande;
use App\Entity\Reservation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/commande', name: 'commande_')]
class CommandeController extends AbstractController
{
    // 🔹 Lister toutes les commandes pour le client
    #[Route('/', name: 'list')]
    public function list(EntityManagerInterface $em): Response
    {
        // 🔹 Remplacer par le user connecté si nécessaire
        $user = $this->getUser();

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
    public function success(Request $request, EntityManagerInterface $em): Response
    {
        $sessionId = $request->query->get('session_id');

        if ($sessionId) {
            try {
                \Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
                $session   = \Stripe\Checkout\Session::retrieve($sessionId);
                $commandeId = $session->metadata->commande_id ?? null;

                if ($commandeId) {
                    $commande = $em->getRepository(Commande::class)->find($commandeId);
                    if ($commande && $commande->getPaymentStatus() !== 'PAYÉ') {
                        $commande->setPaymentStatus('PAYÉ');
                        $commande->setStatus('CONFIRMEE');
                        $commande->setTransactionId($session->payment_intent);
                        $commande->setDateTransaction(new \DateTime());
                        $em->flush();
                    }
                }
            } catch (\Throwable $e) {
                // Log error silently — page still shows success
            }
        }

        return $this->render('frontOffice/commande/success.html.twig');
    }

    #[Route('/cancel', name: 'commande_cancel')]
    public function cancel(): Response
    {
        return $this->render('frontOffice/commande/cancel.html.twig');
    }

#[Route('/commande/{id}/facture', name: 'commande_facture')]
public function facture(Commande $commande): Response
{
    if ($commande->getPaymentStatus() !== 'PAYÉ') {
        throw $this->createAccessDeniedException();
    }

    $options = new Options();
    $options->set('defaultFont', 'Arial');

    $dompdf = new Dompdf($options);

    $html = $this->renderView('frontOffice/commande/facture.html.twig', [
    'commande' => $commande
]);

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return new Response(
        $dompdf->stream("facture_commande_".$commande->getId().".pdf", [
            "Attachment" => true
        ])
    );
}
}
