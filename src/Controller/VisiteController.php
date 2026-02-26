<?php

namespace App\Controller;

use App\Entity\Visite;
use App\Entity\User;
use App\Entity\Logement;
use App\Form\VisiteType;
use App\Repository\VisiteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\GoogleCalendarService;

#[Route('/visite')]
class VisiteController extends AbstractController
{

/* =========================================================
 * ==================== CLIENT FRONT =======================
 * ========================================================= */

// ➜ Demander une visite
#[Route('/client/new/{id}', name: 'client_visite_new')]
public function new(Logement $logement, Request $request, EntityManagerInterface $em): Response
{
    $visite = new Visite();

    // ⚠ USER FIXE POUR TEST (sera remplacé par getUser() plus tard)
    $client = $em->getRepository(User::class)->find(1);

    $visite->setClient($client);
    $visite->setProprietaire($logement->getProprietaire());
    $visite->setLogement($logement);
    $visite->setStatut('EN_ATTENTE');
    $visite->setCreatedAt(new \DateTimeImmutable());

    $form = $this->createForm(VisiteType::class, $visite);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {

        $today = new \DateTime('today');
        $now   = new \DateTime();

        $dateVisite  = $visite->getDateVisite();
        $heureVisite = $visite->getHeureVisite();

        // Date passée
        if ($dateVisite < $today) {
            $this->addFlash('danger', 'La date doit être aujourd’hui ou future.');
            return $this->redirectToRoute('client_visite_new', ['id'=>$logement->getId()]);
        }

        // Aujourd’hui mais heure passée
        if ($dateVisite->format('Y-m-d') === $today->format('Y-m-d')) {

            $datetimeVisite = new \DateTime(
                $dateVisite->format('Y-m-d').' '.$heureVisite->format('H:i:s')
            );

            if ($datetimeVisite <= $now) {
                $this->addFlash('danger', 'Choisissez une heure future.');
                return $this->redirectToRoute('client_visite_new', ['id'=>$logement->getId()]);
            }
        }

        // Double réservation
        $exists = $em->getRepository(Visite::class)->findOneBy([
            'logement' => $logement,
            'dateVisite' => $dateVisite,
            'heureVisite' => $heureVisite
        ]);

        if ($exists) {
            $this->addFlash('danger', 'Ce créneau est déjà réservé.');
            return $this->redirectToRoute('client_visite_new', ['id'=>$logement->getId()]);
        }

        $em->persist($visite);
        $em->flush();

        return $this->redirectToRoute('client_mes_visites');
    }

    return $this->render('frontOffice/visite/client/new.html.twig', [
        'form' => $form->createView(),
        'logement' => $logement
    ]);
}



// ➜ Mes visites
#[Route('/client/mes-visites', name: 'client_mes_visites')]
public function mesVisites(VisiteRepository $repo): Response
{
    // ⚠ ID fixe pour test
    $visites = $repo->findBy(['client' => 1], ['id' => 'DESC']);

    return $this->render('frontOffice/visite/client/mes_visites.html.twig', [
        'visites' => $visites
    ]);
}



// ➜ Modifier visite (seulement si EN_ATTENTE)
#[Route('/client/edit/{id}', name: 'client_visite_edit')]
public function edit(Request $request, Visite $visite, EntityManagerInterface $em): Response
{
    if ($visite->getStatut() !== 'EN_ATTENTE') {
        $this->addFlash('error', 'Modification impossible.');
        return $this->redirectToRoute('client_mes_visites');
    }

    $form = $this->createForm(VisiteType::class, $visite);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();
        return $this->redirectToRoute('client_mes_visites');
    }

    return $this->render('frontOffice/visite/client/edit.html.twig', [
        'form' => $form->createView()
    ]);
}



// ➜ Supprimer visite (si EN_ATTENTE uniquement)
#[Route('/client/delete/{id}', name: 'client_visite_delete')]
public function delete(Visite $visite, EntityManagerInterface $em): Response
{
    if ($visite->getStatut() === 'EN_ATTENTE') {
        $em->remove($visite);
        $em->flush();
    }

    return $this->redirectToRoute('client_mes_visites');
}



/* =========================================================
 * ================= PROPRIETAIRE FRONT =====================
 * ========================================================= */

// ➜ Liste des demandes reçues
#[Route('/proprietaire', name: 'prop_visites')]
public function demandes(VisiteRepository $repo): Response
{
    // ⚠ ID fixe propriétaire = 2
    $visites = $repo->createQueryBuilder('v')
        ->join('v.logement', 'l')
        ->where('l.proprietaire = 2')
        ->orderBy('v.id', 'DESC')
        ->getQuery()
        ->getResult();

    return $this->render('frontOffice/visite/proprietaire/index.html.twig', [
        'visites' => $visites
    ]);
}



// ➜ Accepter visite
#[Route('/proprietaire/accepter/{id}', name: 'prop_visite_accepter')]
public function accepter(
    Visite $visite,
    EntityManagerInterface $em,
    GoogleCalendarService $calendarService
): Response
{
    if ($visite->getStatut() !== 'EN_ATTENTE') {
        $this->addFlash('error', 'Déjà traitée.');
        return $this->redirectToRoute('prop_visites');
    }

    $visite->setStatut('ACCEPTEE');

    // ===============================
    // GOOGLE CALENDAR EVENT
    // ===============================

    $date = $visite->getDateVisite();
    $heure = $visite->getHeureVisite();

    $dateDebut = new \DateTime(
        $date->format('Y-m-d').' '.$heure->format('H:i:s')
    );

    // visite = 1h
    $dateFin = (clone $dateDebut)->modify('+1 hour');

    $calendarService->createEvent(
        'Visite logement - Stayzy',
        $dateDebut,
        $dateFin
    );

    $em->flush();

    $this->addFlash('success', 'Visite acceptée + ajoutée au calendrier ✅');

    return $this->redirectToRoute('prop_visites');
}


// ➜ Refuser visite
#[Route('/proprietaire/refuser/{id}', name: 'prop_visite_refuser')]
public function refuser(Visite $visite, EntityManagerInterface $em): Response
{
    if ($visite->getStatut() !== 'EN_ATTENTE') {
        $this->addFlash('error', 'Déjà traitée.');
        return $this->redirectToRoute('prop_visites');
    }

    $visite->setStatut('REFUSEE');
    $em->flush();

    $this->addFlash('success', 'Visite refusée.');

    return $this->redirectToRoute('prop_visites');
}



/* =========================================================
 * ====================== ADMIN =============================
 * ========================================================= */

// ➜ Dashboard admin
#[Route('/admin', name: 'admin_visites')]
public function admin(Request $request, VisiteRepository $repo): Response
{
    $qb = $repo->createQueryBuilder('v')
        ->join('v.logement', 'l')
        ->join('v.client', 'c')
        ->join('v.proprietaire', 'p');

    if ($request->get('statut')) {
        $qb->andWhere('v.statut = :statut')
           ->setParameter('statut', $request->get('statut'));
    }

    if ($request->get('date')) {
        $qb->andWhere('v.dateVisite = :date')
           ->setParameter('date', $request->get('date'));
    }

    if ($request->get('prop')) {
        $qb->andWhere('p.id = :prop')
           ->setParameter('prop', $request->get('prop'));
    }

    $order = $request->get('tri') === 'ASC' ? 'ASC' : 'DESC';
    $qb->orderBy('v.id', $order);

    $visites = $qb->getQuery()->getResult();

    return $this->render('backOffice/visite/admin/index.html.twig', [
        'visites' => $visites
    ]);
}

}