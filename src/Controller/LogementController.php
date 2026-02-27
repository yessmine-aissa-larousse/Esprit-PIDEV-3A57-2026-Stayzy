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
    // AJOUTER UN LOGEMENT (backoffice admin)
    // =========================================================================
    #[Route('/admin/logement/add', name: 'admin_logement_add')]
    #[IsGranted('ROLE_PROPRIETAIRE')]
    public function addLogement(Request $request, EntityManagerInterface $em): Response
    {
        $logement = new Logement();
        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ── ÉTAPE 6 : Récupérer lat/lng envoyés par Leaflet ──
            $adresse = [
                'rue'        => $request->request->get('rue'),
                'ville'      => $request->request->get('ville'),
                'codePostal' => $request->request->get('codePostal'),
                'pays'       => $request->request->get('pays'),
                'latitude'   => $request->request->get('latitude'),   // ← nouveau
                'longitude'  => $request->request->get('longitude'),  // ← nouveau
            ];
            $logement->setAdresse($adresse);

            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/logements/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $photoPrincipaleFile = $form->get('photoPrincipale')->getData();
            if ($photoPrincipaleFile && $photoPrincipaleFile->isValid()) {
                $newFilename = uniqid() . '_principal.' . $photoPrincipaleFile->guessExtension();
                $photoPrincipaleFile->move($uploadDir, $newFilename);
                $logement->setPhotoPrincipale($newFilename);
            }

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
            $logement->setNoteMoyenne(null);
            $logement->setTotalAvis(0);

            /** @var User $user */
            $user = $this->getUser();
            $logement->setProprietaire($user);

            $em->persist($logement);
            $em->flush();
            $this->creerNotificationAdmin($em, $logement);

            $this->addFlash('success', 'Logement ajouté avec succès !');
            return $this->redirectToRoute('admin_logement_list');
        }

        return $this->render('backOffice/logement/add.html.twig', ['form' => $form]);
    }

    // =========================================================================
    // MODIFIER UN LOGEMENT (backoffice)
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

            // ── ÉTAPE 6 ──
            $adresse = [
                'rue'        => $request->request->get('rue'),
                'ville'      => $request->request->get('ville'),
                'codePostal' => $request->request->get('codePostal'),
                'pays'       => $request->request->get('pays'),
                'latitude'   => $request->request->get('latitude'),
                'longitude'  => $request->request->get('longitude'),
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
                    'logement' => $logement, 'categories' => $categories, 'errors' => $errors,
                ]);
            }

            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/logements/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
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
            'logement' => $logement, 'categories' => $categories, 'errors' => [],
        ]);
    }

    // =========================================================================
    // LISTE (backoffice)
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
            ->leftJoin('l.categorie', 'c')->addSelect('c');

        if (!$this->isGranted('ROLE_ADMIN')) {
            $qb->andWhere('l.proprietaire = :user')->setParameter('user', $user);
        }
        if (!empty($search)) {
            $qb->andWhere('l.titre LIKE :search OR l.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        if (!empty($categorieId)) {
            $qb->andWhere('c.id = :categorieId')->setParameter('categorieId', $categorieId);
        }
        if (!empty($prixMin) && is_numeric($prixMin)) {
            $qb->andWhere('l.prix >= :prixMin')->setParameter('prixMin', (float)$prixMin);
        }
        if (!empty($prixMax) && is_numeric($prixMax)) {
            $qb->andWhere('l.prix <= :prixMax')->setParameter('prixMax', (float)$prixMax);
        }
        if ($disponible !== '') {
            $qb->andWhere('l.disponible = :disponible')->setParameter('disponible', (bool)$disponible);
        }
        $qb->orderBy('l.createdAt', 'DESC');

        return $this->render('backOffice/logement/list.html.twig', [
            'logements'  => $qb->getQuery()->getResult(),
            'categories' => $em->getRepository(Categorie::class)->findAll(),
        ]);
    }

    // =========================================================================
    // SUPPRIMER (backoffice)
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
    // SUPPRIMER UNE PHOTO / PHOTO PRINCIPALE (backoffice)
    // =========================================================================
    #[Route('/admin/logement/photo/delete/{id}/{photoName}', name: 'admin_logement_photo_delete')]
    #[IsGranted('ROLE_PROPRIETAIRE')]
    public function deletePhoto(int $id, string $photoName, EntityManagerInterface $em): Response
    {
        $logement = $em->getRepository(Logement::class)->find($id);
        if (!$logement) return $this->redirectToRoute('admin_logement_list');

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
    // DÉTAILS (backoffice)
    // =========================================================================
    #[Route('/admin/logement/{id}/details', name: 'admin_logement_details')]
    #[IsGranted('ROLE_PROPRIETAIRE')]
    public function details(Logement $logement): Response
    {
        return $this->render('backOffice/logement/details.html.twig', ['logement' => $logement]);
    }

    // =========================================================================
    // FRONT OFFICE — LISTE
    // =========================================================================
    #[Route('/proprietaire/logements', name: 'proprietaire_logement_list')]
    #[IsGranted('ROLE_PROPRIETAIRE')]
    public function proprietaireList(Request $request, EntityManagerInterface $em): Response
    {
        /** @var User $user */
        $user   = $this->getUser();
        $search = $request->query->get('search', '');

        $qb = $em->getRepository(Logement::class)->createQueryBuilder('l')
            ->leftJoin('l.categorie', 'c')->addSelect('c')
            ->where('l.proprietaire = :user')->setParameter('user', $user);

        if (!empty($search)) {
            $qb->andWhere('l.titre LIKE :search')->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy('l.createdAt', 'DESC');
        return $this->render('frontOffice/logement/list.html.twig', [
            'logements' => $qb->getQuery()->getResult(),
        ]);
    }

    // =========================================================================
    // FRONT OFFICE — AJOUTER (avec Leaflet — ÉTAPE 6)
    // =========================================================================
    #[Route('/proprietaire/logement/publier', name: 'proprietaire_logement_add')]
    #[IsGranted('ROLE_PROPRIETAIRE')]
    public function proprietaireAdd(Request $request, EntityManagerInterface $em): Response
    {
        $logement = new Logement();
        $form = $this->createForm(LogementType::class, $logement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ── ÉTAPE 6 : lat/lng depuis les champs hidden Leaflet ──
            $adresse = [
                'rue'        => $request->request->get('rue'),
                'ville'      => $request->request->get('ville'),
                'codePostal' => $request->request->get('codePostal'),
                'pays'       => $request->request->get('pays'),
                'latitude'   => $request->request->get('latitude'),   // ← Leaflet
                'longitude'  => $request->request->get('longitude'),  // ← Leaflet
            ];
            $logement->setAdresse($adresse);

            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/logements/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $photoPrincipaleFile = $form->get('photoPrincipale')->getData();
            if ($photoPrincipaleFile && $photoPrincipaleFile->isValid()) {
                $newFilename = uniqid() . '_principal.' . $photoPrincipaleFile->guessExtension();
                $photoPrincipaleFile->move($uploadDir, $newFilename);
                $logement->setPhotoPrincipale($newFilename);
            }

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
            $logement->setNoteMoyenne(null);
            $logement->setTotalAvis(0);
            $logement->setProprietaire($this->getUser());

            $em->persist($logement);
            $em->flush();
            $this->creerNotificationAdmin($em, $logement);

            $this->addFlash('success', 'Votre logement a été publié avec succès !');
            return $this->redirectToRoute('proprietaire_logement_list');
        }

        return $this->render('frontOffice/logement/add.html.twig', ['form' => $form]);
    }

    // =========================================================================
    // FRONT OFFICE — MODIFIER
    // =========================================================================
    #[Route('/proprietaire/logement/modifier/{id}', name: 'proprietaire_logement_edit')]
    #[IsGranted('ROLE_PROPRIETAIRE')]
    public function proprietaireEdit(int $id, Request $request, EntityManagerInterface $em, ValidatorInterface $validator): Response
    {
        $logement = $em->getRepository(Logement::class)->find($id);
        if (!$logement) {
            $this->addFlash('error', 'Logement introuvable !');
            return $this->redirectToRoute('proprietaire_logement_list');
        }

        $user = $this->getUser();
        if ($logement->getProprietaire() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez modifier que vos propres logements.');
            return $this->redirectToRoute('proprietaire_logement_list');
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

            // ── ÉTAPE 6 ──
            $adresse = [
                'rue'        => $request->request->get('rue'),
                'ville'      => $request->request->get('ville'),
                'codePostal' => $request->request->get('codePostal'),
                'pays'       => $request->request->get('pays'),
                'latitude'   => $request->request->get('latitude'),
                'longitude'  => $request->request->get('longitude'),
            ];
            $logement->setAdresse($adresse);

            $categorieId = $request->request->get('categorie');
            $categorie   = $em->getRepository(Categorie::class)->find($categorieId);
            $logement->setCategorie($categorie);
            $amenites = $request->request->all('amenites') ?? [];
            $logement->setAmenites($amenites);

            $uploadDir     = $this->getParameter('kernel.project_dir') . '/public/uploads/logements/';
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

            $em->flush();
            $this->addFlash('success', 'Logement modifié avec succès !');
            return $this->redirectToRoute('proprietaire_logement_list');
        }

        return $this->render('frontOffice/logement/edit.html.twig', [
            'logement' => $logement, 'categories' => $categories, 'errors' => $errors,
        ]);
    }

    // =========================================================================
    // FRONT OFFICE — DÉTAILS + SUPPRIMER
    // =========================================================================
    #[Route('/proprietaire/logement/{id}', name: 'proprietaire_logement_details')]
    #[IsGranted('ROLE_PROPRIETAIRE')]
    public function proprietaireDetails(int $id, EntityManagerInterface $em): Response
    {
        $logement = $em->getRepository(Logement::class)->find($id);
        if (!$logement) {
            $this->addFlash('error', 'Logement introuvable !');
            return $this->redirectToRoute('proprietaire_logement_list');
        }

        $user = $this->getUser();
        if ($logement->getProprietaire() !== $user) {
            $this->addFlash('error', 'Accès non autorisé.');
            return $this->redirectToRoute('proprietaire_logement_list');
        }

        return $this->render('frontOffice/logement/details.html.twig', ['logement' => $logement]);
    }

    #[Route('/proprietaire/logement/supprimer/{id}', name: 'proprietaire_logement_delete')]
    #[IsGranted('ROLE_PROPRIETAIRE')]
    public function proprietaireDelete(int $id, EntityManagerInterface $em): Response
    {
        $logement = $em->getRepository(Logement::class)->find($id);
        if (!$logement) {
            $this->addFlash('error', 'Logement introuvable !');
            return $this->redirectToRoute('proprietaire_logement_list');
        }

        $user = $this->getUser();
        if ($logement->getProprietaire() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez supprimer que vos propres logements.');
            return $this->redirectToRoute('proprietaire_logement_list');
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
        return $this->redirectToRoute('proprietaire_logement_list');
    }

    // =========================================================================
    // MÉTHODE PRIVÉE — Notification admin
    // =========================================================================
    private function creerNotificationAdmin(EntityManagerInterface $em, Logement $logement): void
    {
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
    // AJAX — FILTRAGE MULTICRITÈRES — retourne du JSON pur
    // Ajouter avant la } finale de LogementController
    // =========================================================================
    #[Route('/proprietaire/logements/filter', name: 'proprietaire_logement_filter', methods: ['POST'])]
    #[IsGranted('ROLE_PROPRIETAIRE')]
    public function filter(Request $request, EntityManagerInterface $em): \Symfony\Component\HttpFoundation\JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        $data = json_decode($request->getContent(), true) ?? [];

        $search        = trim($data['search']        ?? '');
        $disponible    = $data['disponible']          ?? 'all';
        $avecPromo     = $data['avecPromo']           ?? 'all';
        $prixMin       = isset($data['prixMin'])       && $data['prixMin'] !== '' ? (float)$data['prixMin']       : null;
        $prixMax       = isset($data['prixMax'])       && $data['prixMax'] !== '' ? (float)$data['prixMax']       : null;
        $chambresMin   = isset($data['chambresMin'])   && $data['chambresMin'] !== '' ? (int)$data['chambresMin'] : null;
        $superficieMin = isset($data['superficieMin']) && $data['superficieMin'] !== '' ? (float)$data['superficieMin'] : null;
        $superficieMax = isset($data['superficieMax']) && $data['superficieMax'] !== '' ? (float)$data['superficieMax'] : null;
        $ville         = trim($data['ville']          ?? '');
        $sortBy        = $data['sortBy']              ?? 'date_desc';

        $qb = $em->getRepository(Logement::class)
            ->createQueryBuilder('l')
            ->where('l.proprietaire = :user')
            ->setParameter('user', $user);

        if ($search !== '') {
            $qb->andWhere('l.titre LIKE :search OR l.description LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        if ($disponible === '1') {
            $qb->andWhere('l.disponible = true');
        } elseif ($disponible === '0') {
            $qb->andWhere('l.disponible = false');
        }
        if ($prixMin !== null) {
            $qb->andWhere('l.prix >= :prixMin')->setParameter('prixMin', $prixMin);
        }
        if ($prixMax !== null) {
            $qb->andWhere('l.prix <= :prixMax')->setParameter('prixMax', $prixMax);
        }
        if ($chambresMin !== null) {
            $qb->andWhere('l.nombreChambres >= :chambresMin')->setParameter('chambresMin', $chambresMin);
        }
        if ($superficieMin !== null) {
            $qb->andWhere('l.superficie >= :superficieMin')->setParameter('superficieMin', $superficieMin);
        }
        if ($superficieMax !== null) {
            $qb->andWhere('l.superficie <= :superficieMax')->setParameter('superficieMax', $superficieMax);
        }

        match ($sortBy) {
            'prix_asc'   => $qb->orderBy('l.prix', 'ASC'),
            'prix_desc'  => $qb->orderBy('l.prix', 'DESC'),
            'titre_asc'  => $qb->orderBy('l.titre', 'ASC'),
            'titre_desc' => $qb->orderBy('l.titre', 'DESC'),
            default      => $qb->orderBy('l.createdAt', 'DESC'),
        };

        $logements = $qb->getQuery()->getResult();

        // Ville : adresse = JSON array, pas une relation → filtre PHP
        if ($ville !== '') {
            $villeLower = strtolower($ville);
            $logements = array_filter($logements, function($l) use ($villeLower) {
                $adr = $l->getAdresse();
                if (!$adr) return false;
                $v = is_array($adr) ? ($adr['ville'] ?? '') : '';
                return str_contains(strtolower($v), $villeLower);
            });
        }

        // Promo active → filtre PHP
        if ($avecPromo === '1') {
            $logements = array_filter($logements, fn($l) => $l->getPromoActive() !== null);
        } elseif ($avecPromo === '0') {
            $logements = array_filter($logements, fn($l) => $l->getPromoActive() === null);
        }

        // Sérialiser en JSON simple
        $result = [];
        foreach (array_values($logements) as $l) {
            $promo = $l->getPromoActive();
            $adr   = $l->getAdresse();

            $result[] = [
                'id'              => $l->getId(),
                'titre'           => $l->getTitre(),
                'prix'            => $l->getPrix(),
                'prixFinal'       => $promo ? $promo->getPrixPromo() : $l->getPrix(),
                'pourcentage'     => $promo ? $promo->getPourcentage() : null,
                'disponible'      => $l->isDisponible(),
                'nombreChambres'  => $l->getNombreChambres(),
                'nombreSalleDeBain' => $l->getNombreSalleDeBain(),
                'superficie'      => $l->getSuperficie(),
                'photoPrincipale' => $l->getPhotoPrincipale(),
                'ville'           => is_array($adr) ? ($adr['ville'] ?? '') : '',
                'codePostal'      => is_array($adr) ? ($adr['codePostal'] ?? '') : '',
                'createdAt'       => $l->getCreatedAt()?->format('d/m/Y'),
                'hasPromo'        => $promo !== null,
                'urlVoir'         => $this->generateUrl('proprietaire_logement_details', ['id' => $l->getId()]),
                'urlModifier'     => $this->generateUrl('proprietaire_logement_edit', ['id' => $l->getId()]),
                'urlPromotion'    => $this->generateUrl('promotion_list', ['logementId' => $l->getId()]),
                'urlSupprimer'    => $this->generateUrl('proprietaire_logement_delete', ['id' => $l->getId()]),
            ];
        }

        return $this->json($result);
    }

    
}