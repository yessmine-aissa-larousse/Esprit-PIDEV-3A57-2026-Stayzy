<?php

namespace App\Controller;

use App\Entity\Notification;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NotificationController extends AbstractController
{
    // Liste toutes les notifications de l'admin
    #[Route('/admin/notifications', name: 'admin_notifications')]
    public function liste(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        $notifications = $em->getRepository(Notification::class)
            ->createQueryBuilder('n')
            ->where('n.destinataire = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        
        return $this->render('backOffice/notifications/liste.html.twig', [
            'notifications' => $notifications,
        ]);
    }
    
    // Voir une notification (et la marquer comme lue)
    #[Route('/admin/notification/{id}', name: 'admin_notification_voir')]
    public function voir(int $id, EntityManagerInterface $em): Response
    {
        $notification = $em->getRepository(Notification::class)->find($id);
        
        if (!$notification) {
            throw $this->createNotFoundException('Notification introuvable');
        }
        
        // Marquer comme lue
        if (!$notification->isLu()) {
            $notification->setLu(true);
            $em->flush();
        }
        
        // Rediriger vers le logement concerné
        if ($notification->getLogement()) {
            return $this->redirectToRoute('admin_logement_edit', [
                'id' => $notification->getLogement()->getId()
            ]);
        }
        
        return $this->redirectToRoute('admin_notifications');
    }
    
    // Marquer toutes comme lues
    #[Route('/admin/notifications/marquer-lues', name: 'admin_notifications_marquer_lues')]
    public function marquerToutesLues(EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        $em->createQueryBuilder()
            ->update(Notification::class, 'n')
            ->set('n.lu', ':lu')
            ->where('n.destinataire = :user')
            ->andWhere('n.lu = :nonLu')
            ->setParameter('lu', true)
            ->setParameter('nonLu', false)
            ->setParameter('user', $user)
            ->getQuery()
            ->execute();
        
        $this->addFlash('success', 'Toutes les notifications ont été marquées comme lues');
        return $this->redirectToRoute('admin_notifications');
    }
}