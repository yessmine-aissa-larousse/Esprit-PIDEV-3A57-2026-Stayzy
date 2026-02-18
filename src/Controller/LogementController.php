<?php

namespace App\Controller;

use App\Entity\Logement;
use App\Entity\Categorie;
use App\Entity\User;
use App\Form\LogementType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class LogementController extends AbstractController
{
    // =========================================================================
    // AJOUTER UN LOGEMENT
    // =========================================================================
    #[Route('/admin/logement/add', name: 'admin_logement_add')]
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

            // ⚠️ TEMPORAIRE : Propriétaire fixe (id=2) en attendant l'intégration
            $proprietaireTemporaire = $em->getRepository(User::class)->find(2);
            if (!$proprietaireTemporaire) {
                $this->addFlash('error', 'Utilisateur propriétaire (id=2) introuvable !');
                return $this->redirectToRoute('admin_logement_add');
            }
            $logement->setProprietaire($proprietaireTemporaire);

            // Sauvegarde
            $em->persist($logement);
            $em->flush();

            $this->addFlash('success', 'Logement ajouté avec succès !');
            return $this->redirectToRoute('admin_logement_list');
        }

        return $this->render('backOffice/logement/add.html.twig', [
            'form' => $form,
        ]);
    }

    // =========================================================================
    // MODIFIER UN LOGEMENT
    // =========================================================================
    #[Route('/admin/logement/edit/{id}', name: 'admin_logement_edit')]
    public function editLogement(int $id, Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $logement = $em->getRepository(Logement::class)->find($id);

        if (!$logement) {
            $this->addFlash('error', 'Logement introuvable !');
            return $this->redirectToRoute('admin_logement_list');
        }

        // Récupérer toutes les catégories pour le <select>
        $categories = $em->getRepository(Categorie::class)->findAll();
        $errors = [];

        if ($request->isMethod('POST')) {
            // Hydratation de l'entité 
            $logement->setTitre($request->request->get('titre', ''));
            $logement->setDescription($request->request->get('description'));
            $logement->setPrix((float) $request->request->get('prix', 0));
            $logement->setSuperficie((int) $request->request->get('superficie', 0));
            $logement->setNombreChambres((int) $request->request->get('nombreChambres', 0));
            $logement->setNombreSalleDeBain((int) $request->request->get('nombreSalleDeBain', 0));
            $logement->setDisponible((bool) $request->request->get('disponible'));

            // Adresse JSON
            $adresse = [
                'rue'        => $request->request->get('rue'),
                'ville'      => $request->request->get('ville'),
                'codePostal' => $request->request->get('codePostal'),
                'pays'       => $request->request->get('pays'),
            ];
            $logement->setAdresse($adresse);

            // Catégorie
            $categorieId = $request->request->get('categorie');
            $categorie = $em->getRepository(Categorie::class)->find($categorieId);
            $logement->setCategorie($categorie);

            // Aménités
            $amenites = $request->request->all('amenites') ?? [];
            $logement->setAmenites($amenites);

            // ⚠️ TEMPORAIRE : Si le logement n'a pas de propriétaire, lui assigner id=2
            if (!$logement->getProprietaire()) {
                $proprietaireTemporaire = $em->getRepository(User::class)->find(2);
                if ($proprietaireTemporaire) {
                    $logement->setProprietaire($proprietaireTemporaire);
                }
            }

            // VALIDATION CÔTÉ SERVEUR 
            $violations = $validator->validate($logement);

            if (count($violations) > 0) {
                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()] = $violation->getMessage();
                }

                // Re-render avec erreurs + valeurs saisies conservées
                return $this->render('backOffice/logement/edit.html.twig', [
                    'logement'   => $logement,
                    'categories' => $categories,
                    'errors'     => $errors,
                ]);
            }

            // Si valide : gestion des photos 
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/logements/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            // Nouvelle photo principale
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

            // Nouvelles photos supplémentaires
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

    #[Route('/admin/logement/list', name: 'admin_logement_list')]
    public function listLogement(Request $request, EntityManagerInterface $em): Response
    {
        // Récupérer les paramètres de filtrage
        $search = $request->query->get('search', '');
        $categorieId = $request->query->get('categorie', '');
        $prixMin = $request->query->get('prix_min', '');
        $prixMax = $request->query->get('prix_max', '');
        $disponible = $request->query->get('disponible', '');

        // Construction de la requête avec QueryBuilder
        $qb = $em->getRepository(Logement::class)->createQueryBuilder('l')
            ->leftJoin('l.categorie', 'c')
            ->addSelect('c');

        // Filtre par recherche (titre ou description)
        if (!empty($search)) {
            $qb->andWhere('l.titre LIKE :search OR l.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Filtre par catégorie
        if (!empty($categorieId)) {
            $qb->andWhere('c.id = :categorieId')
               ->setParameter('categorieId', $categorieId);
        }

        // Filtre par prix minimum
        if (!empty($prixMin) && is_numeric($prixMin)) {
            $qb->andWhere('l.prix >= :prixMin')
               ->setParameter('prixMin', (float) $prixMin);
        }

        // Filtre par prix maximum
        if (!empty($prixMax) && is_numeric($prixMax)) {
            $qb->andWhere('l.prix <= :prixMax')
               ->setParameter('prixMax', (float) $prixMax);
        }

        // Filtre par disponibilité
        if ($disponible !== '') {
            $qb->andWhere('l.disponible = :disponible')
               ->setParameter('disponible', (bool) $disponible);
        }

        // Tri par date de création (plus récent en premier)
        $qb->orderBy('l.createdAt', 'DESC');

        // Exécuter la requête
        $logements = $qb->getQuery()->getResult();

        // Récupérer toutes les catégories pour le select
        $categories = $em->getRepository(Categorie::class)->findAll();

        return $this->render('backOffice/logement/list.html.twig', [
            'logements' => $logements,
            'categories' => $categories,
        ]);
    }

    // =========================================================================
    // SUPPRIMER UN LOGEMENT
    // =========================================================================
    #[Route('/admin/logement/delete/{id}', name: 'admin_logement_delete')]
    public function deleteLogement(int $id, EntityManagerInterface $em): Response
    {
        $logement = $em->getRepository(Logement::class)->find($id);
        if (!$logement) {
            $this->addFlash('error', 'Logement introuvable !');
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
    public function deletePhoto(int $id, string $photoName, EntityManagerInterface $em): Response
    {
        $logement = $em->getRepository(Logement::class)->find($id);
        if (!$logement) {
            return $this->redirectToRoute('admin_logement_list');
        }

        $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/logements/';
        if ($logement->getPhotos()) {
            $photos = $logement->getPhotos();
            $key    = array_search($photoName, $photos);
            if ($key !== false) {
                unset($photos[$key]);
                $logement->setPhotos(array_values($photos));
                $path = $uploadDir . $photoName;
                if (file_exists($path)) {
                    unlink($path);
                }
                $em->flush();
            }
        }

        return $this->redirectToRoute('admin_logement_edit', ['id' => $id]);
    }

    // =========================================================================
    // SUPPRIMER LA PHOTO PRINCIPALE
    // =========================================================================
    #[Route('/admin/logement/photo-principale/delete/{id}', name: 'admin_logement_photo_principale_delete')]
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
            if (file_exists($path)) {
                unlink($path);
            }
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
    public function details(Logement $logement): Response
    {
        return $this->render('backOffice/logement/details.html.twig', [
            'logement' => $logement,
        ]);
    }
}