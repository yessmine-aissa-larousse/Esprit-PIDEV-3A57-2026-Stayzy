<?php

namespace App\Controller;

use App\Entity\Commande;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Stripe\Webhook;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/stripe', name: 'stripe_')]
class StripeController extends AbstractController
{
    #[Route('/create-session/{id}', name: 'create_session', methods: ['POST'])]
    public function createSession(int $id, EntityManagerInterface $em): JsonResponse
    {
        $commande = $em->getRepository(Commande::class)->find($id);

        if (!$commande) {
            return new JsonResponse(['error' => 'Commande introuvable'], 404);
        }

        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $session = Session::create([
            'line_items' => [[
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => 'Commande #' . $commande->getId(),
                    ],
                    'unit_amount' => (int)($commande->getPrixTotal() * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',

            // 🔥 IMPORTANT
            'metadata' => [
                'commande_id' => $commande->getId(),
            ],

            'success_url' => 'http://127.0.0.1:8000/commande/success',
            'cancel_url' => 'http://127.0.0.1:8000/commande/cancel',
        ]);

        return new JsonResponse([
            'id' => $session->id,
            'url' => $session->url
        ]);
    }

    // 🔥 WEBHOOK
    #[Route('/webhook', name: 'webhook', methods: ['POST'])]
    public function webhook(Request $request, EntityManagerInterface $em): Response
    {
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        $endpointSecret = $_ENV['STRIPE_WEBHOOK_SECRET'];

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $endpointSecret
            );
        } catch (\Exception $e) {
            return new Response('Signature invalide', 400);
        }

        if ($event->type === 'checkout.session.completed') {

            $session = $event->data->object;
            $commandeId = $session->metadata->commande_id ?? null;

            if ($commandeId) {
                $commande = $em->getRepository(Commande::class)->find($commandeId);

                if ($commande && $commande->getPaymentStatus() !== 'PAYÉ') {

                    $commande->setPaymentStatus('PAYÉ');
                    $commande->setStatus('TERMINÉE');
                    $commande->setTransactionId($session->payment_intent);
                    $commande->setDateTransaction(new \DateTime());

                    $em->persist($commande);
                    $em->flush();
                }
            }
        }

        return new Response('Webhook traité', 200);
    }
}