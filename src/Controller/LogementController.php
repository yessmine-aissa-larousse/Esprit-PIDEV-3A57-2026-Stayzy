<?php

namespace App\Controller;

use App\Entity\Logement;
use App\Entity\Categorie;
use App\Entity\Notification;
use App\Entity\User;
use App\Form\LogementType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class LogementController extends AbstractController
{
    // =========================================================================
    // AJOUTER UN LOGEMENT
    // =========================================================================
    #[Route('/admin/logement/add', name: 'admin_logement_add')]
    #[IsGranted('ROLE_PROPRIETAIRE')]  // ✅ Autorise admin ET propriétaire (ROLE_ADMIN > ROLE_PROPRIETAIRE)
    public function addLogement(Request $request, EntityManagerInterface $em): Response
    {
        $logement = new Logement();
        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $adresse = [
                'rue'        => $request->request->get('rue'),
                'ville'      => $request->request->get('ville'),
                'codePostal' => $request->request->get('codePostal'),
                'pays'       => $request->request->get('pays'),
            ];
            $logement->setAdresse($adresse);

            // Gestion des photos
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/logements/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Photo principale
            $photoPrincipaleFile = $form->get('photoPrincipale')->getData();
            if ($photoPrincipaleFile && $photoPrincipaleFile->isValid()) {
                $newFilename = uniqid() . '_principal.' . $photoPrincipaleFile->guessExtension();
                $photoPrincipaleFile->move($uploadDir, $newFilename);
                $logement->setPhotoPrincipale($newFilename);
            }

            // Photos supplémentaires
            $photosFiles = $form->get('photos')->getData();
            $photosArray = [];
            if ($photosFiles) {
                foreach ($photosFiles as $index => $file) {
                    if ($file && $file->isValid()) {
                        $newFilename = uniqid() . '.' . $file->guessExtension();
                        $file->move($uploadDir, $newFilename);
                        $photosArray[] = $newFilename;
                        if ($index === 0 && !$logement->getPhotoPrincipale()) {
                            $logement->setPhotoPrincipale($newFilename);
                        }
                    }
                }
            }
            $logement->setPhotos($photosArray);

            // Initialiser les champs calculés
            $logement->setNoteMoyenne(null);
            $logement->setTotalAvis(0);

            // ✅ Propriétaire = utilisateur connecté
            /** @var User $user */
            $user = $this->getUser();
            $logement->setProprietaire($user);


            // Sauvegarde
            $em->persist($logement);
            $em->flush();

            // ⭐ NOUVEAU : Créer notification pour l'admin
            $this->creerNotificationAdmin($em, $logement);


            $this->addFlash('success', 'Logement ajouté avec succès !');
            return $this->redirectToRoute('admin_logement_list');
        }

        return $this->render('backOffice/logement/add.html.twig', [
            'form' => $form,
        ]);
    }

    // ⭐ NOUVELLE MÉTHODE : Créer notification pour admin
    private function creerNotificationAdmin(EntityManagerInterface $em, Logement $logement): void
    {
        // Trouver l'admin (vous pouvez adapter selon votre système)
        $admin = $em->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        
        if ($admin) {
            $notification = new Notification();
            $notification->setMessage('Nouveau logement publié : ' . $logement->getTitre());
            $notification->setType('nouveau_logement');
            $notification->setDestinataire($admin);
            $notification->setLogement($logement);
            
            $em->persist($notification);
            $em->flush();
        }
    }

    // =========================================================================
    // MODIFIER UN LOGEMENT
    // =========================================================================
    #[Route('/admin/logement/edit/{id}', name: 'admin_logement_edit')]
    #[IsGranted('ROLE_PROPRIETAIRE')]
    public function editLogement(int $id, Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $logement = $em->getRepository(Logement::class)->find($id);

        if (!$logement) {
            $this->addFlash('error', 'Logement introuvable !');
            return $this->redirectToRoute('admin_logement_list');
        }

        // ✅ Sécurité : un propriétaire ne peut modifier que SES logements
        /** @var User $user */
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && $logement->getProprietaire() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres logements.');
            return $this->redirectToRoute('admin_logement_list');
        }

        $categories = $em->getRepository(Categorie::class)->findAll();
        $errors = [];

        if ($request->isMethod('POST')) {
            $logement->setTitre($request->request->get('titre', ''));
            $logement->setDescription($request->request->get('description'));
            $logement->setPrix((float) $request->request->get('prix', 0));
            $logement->setSuperficie((int) $request->request->get('superficie', 0));
            $logement->setNombreChambres((int) $request->request->get('nombreChambres', 0));
            $logement->setNombreSalleDeBain((int) $request->request->get('nombreSalleDeBain', 0));
            $logement->setDisponible((bool) $request->request->get('disponible'));

            $adresse = [
                'rue'        => $request->request->get('rue'),
                'ville'      => $request->request->get('ville'),
                'codePostal' => $request->request->get('codePostal'),
                'pays'       => $request->request->get('pays'),
            ];
            $logement->setAdresse($adresse);

            $categorieId = $request->request->get('categorie');
            $categorie = $em->getRepository(Categorie::class)->find($categorieId);
            $logement->setCategorie($categorie);

            $amenites = $request->request->all('amenites') ?? [];
            $logement->setAmenites($amenites);

            $violations = $validator->validate($logement);
            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()] = $violation->getMessage();
                }
                return $this->render('backOffice/logement/edit.html.twig', [
                    'logement'   => $logement,
                    'categories' => $categories,
                    'errors'     => $errors,
                ]);
            }

            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/logements/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $uploadedFiles = $request->files->all();
            if (isset($uploadedFiles['photoPrincipale']) && $uploadedFiles['photoPrincipale']->isValid()) {
                if ($logement->getPhotoPrincipale()) {
                    $old = $uploadDir . $logement->getPhotoPrincipale();
                    if (file_exists($old)) unlink($old);
                }
                $file = $uploadedFiles['photoPrincipale'];
                $newFilename = uniqid() . '_principal.' . $file->guessExtension();
                $file->move($uploadDir, $newFilename);
                $logement->setPhotoPrincipale($newFilename);
            }

            if (isset($uploadedFiles['photos']) && is_array($uploadedFiles['photos'])) {
                $photosArray = $logement->getPhotos() ?? [];
                foreach ($uploadedFiles['photos'] as $file) {
                    if ($file && $file->isValid()) {
                        $newFilename = uniqid() . '.' . $file->guessExtension();
                        $file->move($uploadDir, $newFilename);
                        $photosArray[] = $newFilename;
                    }
                }
                $logement->setPhotos($photosArray);
            }

            $em->flush();
            $this->addFlash('success', 'Logement modifié avec succès !');
            return $this->redirectToRoute('admin_logement_list');
        }

        return $this->render('backOffice/logement/edit.html.twig', [
            'logement'   => $logement,
            'categories' => $categories,
            'errors'     => [],
        ]);
    }

    // =========================================================================
    // LISTE DES LOGEMENTS
    // =========================================================================
    #[Route('/admin/logement/list', name: 'admin_logement_list')]
    #[IsGranted('ROLE_PROPRIETAIRE')]
    public function listLogement(Request $request, EntityManagerInterface $em): Response
    {
        $search      = $request->query->get('search', '');
        $categorieId = $request->query->get('categorie', '');
        $prixMin     = $request->query->get('prix_min', '');
        $prixMax     = $request->query->get('prix_max', '');
        $disponible  = $request->query->get('disponible', '');

        /** @var User $user */
        $user = $this->getUser();

        $qb = $em->getRepository(Logement::class)->createQueryBuilder('l')
            ->leftJoin('l.categorie', 'c')
            ->addSelect('c');

        // ✅ Si propriétaire : ne voir QUE ses propres logements
        if (!$this->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('l.proprietaire = :user')
               ->setParameter('user', $user);
        }

        if (!empty($search)) {
            $qb->andWhere('l.titre LIKE :search OR l.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        if (!empty($categorieId)) {
            $qb->andWhere('c.id = :categorieId')
               ->setParameter('categorieId', $categorieId);
        }
        if (!empty($prixMin) && is_numeric($prixMin)) {
            $qb->andWhere('l.prix >= :prixMin')
               ->setParameter('prixMin', (float) $prixMin);
        }
        if (!empty($prixMax) && is_numeric($prixMax)) {
            $qb->andWhere('l.prix <= :prixMax')
               ->setParameter('prixMax', (float) $prixMax);
        }
        if ($disponible !== '') {
            $qb->andWhere('l.disponible = :disponible')
               ->setParameter('disponible', (bool) $disponible);
        }

        $qb->orderBy('l.createdAt', 'DESC');
        $logements = $qb->getQuery()->getResult();
        $categories = $em->getRepository(Categorie::class)->findAll();

        return $this->render('backOffice/logement/list.html.twig', [
            'logements'  => $logements,
            'categories' => $categories,
        ]);
    }

    // =========================================================================
    // SUPPRIMER UN LOGEMENT
    // =========================================================================
    #[Route('/admin/logement/delete/{id}', name: 'admin_logement_delete')]
    #[IsGranted('ROLE_PROPRIETAIRE')]
    public function deleteLogement(int $id, EntityManagerInterface $em): Response
    {
        $logement = $em->getRepository(Logement::class)->find($id);
        if (!$logement) {
            $this->addFlash('error', 'Logement introuvable !');
            return $this->redirectToRoute('admin_logement_list');
        }

        // ✅ Sécurité : propriétaire ne peut supprimer que SES logements
        /** @var User $user */
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && $logement->getProprietaire() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres logements.');
            return $this->redirectToRoute('admin_logement_list');
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/logements/';

        if ($logement->getPhotoPrincipale()) {
            $path = $uploadDir . $logement->getPhotoPrincipale();
            if (file_exists($path)) unlink($path);
        }
        if ($logement->getPhotos()) {
            foreach ($logement->getPhotos() as $photo) {
                $path = $uploadDir . $photo;
                if (file_exists($path)) unlink($path);
            }
        }

        $em->remove($logement);
        $em->flush();

        $this->addFlash('success', 'Logement supprimé avec succès !');
        return $this->redirectToRoute('admin_logement_list');
    }

    // =========================================================================
    // SUPPRIMER UNE PHOTO
    // =========================================================================
    #[Route('/admin/logement/photo/delete/{id}/{photoName}', name: 'admin_logement_photo_delete')]
    #[IsGranted('ROLE_PROPRIETAIRE')]
    public function deletePhoto(int $id, string $photoName, EntityManagerInterface $em): Response
    {
        $logement = $em->getRepository(Logement::class)->find($id);
        if (!$logement) {
            return $this->redirectToRoute('admin_logement_list');
        }

        if ($logement->getPhotos()) {
            $photos = $logement->getPhotos();
            $key    = array_search($photoName, $photos);
            if ($key !== false) {
                unset($photos[$key]);
                $logement->setPhotos(array_values($photos));
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/logements/';
                $path = $uploadDir . $photoName;
                if (file_exists($path)) unlink($path);
                $em->flush();
            }
        }

        return $this->redirectToRoute('admin_logement_edit', ['id' => $id]);
    }

    // =========================================================================
    // SUPPRIMER LA PHOTO PRINCIPALE
    // =========================================================================
    #[Route('/admin/logement/photo-principale/delete/{id}', name: 'admin_logement_photo_principale_delete')]
    #[IsGranted('ROLE_PROPRIETAIRE')]
    public function deletePhotoPrincipale(int $id, EntityManagerInterface $em): Response
    {
        $logement = $em->getRepository(Logement::class)->find($id);
        if (!$logement) {
            $this->addFlash('error', 'Logement introuvable !');
            return $this->redirectToRoute('admin_logement_list');
        }

        if ($logement->getPhotoPrincipale()) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/logements/';
            $path = $uploadDir . $logement->getPhotoPrincipale();
            if (file_exists($path)) unlink($path);
            $logement->setPhotoPrincipale(null);
            $em->flush();
            $this->addFlash('success', 'Photo principale supprimée avec succès !');
        } else {
            $this->addFlash('error', 'Aucune photo principale à supprimer !');
        }

        return $this->redirectToRoute('admin_logement_edit', ['id' => $id]);
    }

    // =========================================================================
    // DÉTAILS D'UN LOGEMENT (BACKOFFICE)
    // =========================================================================
    #[Route('/admin/logement/{id}/details', name: 'admin_logement_details')]
    #[IsGranted('ROLE_PROPRIETAIRE')]
    public function details(Logement $logement): Response
    {
        return $this->render('backOffice/logement/details.html.twig', [
            'logement' => $logement,
        ]);
    }
}
