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

    $client = $this->getUser();

    if (!$client instanceof User) {
        throw $this->createAccessDeniedException();
    }

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

        // date passée
        if ($dateVisite < $today) {
            $this->addFlash('danger', 'La date doit être future.');
            return $this->redirectToRoute('client_visite_new', [
                'id'=>$logement->getId()
            ]);
        }

        // heure passée aujourd’hui
        if ($dateVisite->format('Y-m-d') === $today->format('Y-m-d')) {

            $datetimeVisite = new \DateTime(
                $dateVisite->format('Y-m-d').' '.$heureVisite->format('H:i:s')
            );

            if ($datetimeVisite <= $now) {
                $this->addFlash('danger', 'Choisissez une heure future.');
                return $this->redirectToRoute('client_visite_new', [
                    'id'=>$logement->getId()
                ]);
            }
        }

        // double réservation
        $exists = $em->getRepository(Visite::class)->findOneBy([
            'logement' => $logement,
            'dateVisite' => $dateVisite,
            'heureVisite' => $heureVisite
        ]);

        if ($exists) {
            $this->addFlash('danger', 'Créneau déjà réservé.');
            return $this->redirectToRoute('client_visite_new', [
                'id'=>$logement->getId()
            ]);
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
    $visites = $repo->findBy(
        ['client' => $this->getUser()],
        ['id' => 'DESC']
    );

    return $this->render('frontOffice/visite/client/mes_visites.html.twig', [
        'visites' => $visites
    ]);
}


// ➜ Modifier
#[Route('/client/edit/{id}', name: 'client_visite_edit')]
public function edit(Request $request, Visite $visite, EntityManagerInterface $em): Response
{
    if ($visite->getStatut() !== 'EN_ATTENTE') {
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


// ➜ Supprimer
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
 * ================= PROPRIETAIRE ==========================
 * ========================================================= */

// demandes reçues
#[Route('/proprietaire', name: 'prop_visites')]
public function demandes(VisiteRepository $repo): Response
{
    $visites = $repo->createQueryBuilder('v')
        ->join('v.logement', 'l')
        ->where('l.proprietaire = :prop')
        ->setParameter('prop', $this->getUser())
        ->orderBy('v.id', 'DESC')
        ->getQuery()
        ->getResult();

    return $this->render('frontOffice/visite/proprietaire/index.html.twig', [
        'visites' => $visites
    ]);
}


// accepter visite ✅
#[Route('/proprietaire/accepter/{id}', name: 'prop_visite_accepter')]
public function accepter(
    Visite $visite,
    EntityManagerInterface $em
): Response
{
    if ($visite->getStatut() !== 'EN_ATTENTE') {
        return $this->redirectToRoute('prop_visites');
    }

    $visite->setStatut('ACCEPTEE');
    $em->flush();

    $this->addFlash('success', 'Visite acceptée ✅');

    return $this->redirectToRoute('prop_visites');
}


// refuser visite
#[Route('/proprietaire/refuser/{id}', name: 'prop_visite_refuser')]
public function refuser(
    Visite $visite,
    EntityManagerInterface $em
): Response
{
    if ($visite->getStatut() !== 'EN_ATTENTE') {
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

    $order = $request->get('tri') === 'ASC' ? 'ASC' : 'DESC';
    $qb->orderBy('v.id', $order);

    $visites = $qb->getQuery()->getResult();

    return $this->render('backOffice/visite/admin/index.html.twig', [
        'visites' => $visites
    ]);
}

}