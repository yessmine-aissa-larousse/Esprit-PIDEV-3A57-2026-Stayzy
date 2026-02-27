<?php

namespace App\Controller;

use App\Entity\Categorie;
use App\Form\CategorieType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CategorieController extends AbstractController
{
    #[Route('/admin/categorie/list', name: 'admin_categorie_list')]
    public function listCategorie(EntityManagerInterface $em): Response
    {
        $categories = $em->getRepository(Categorie::class)->findAll();
        
        return $this->render('backOffice/categorie/list.html.twig', [
            'categories' => $categories
        ]);
    }

    #[Route('/admin/categorie/add', name: 'admin_categorie_add')]
    public function addCategorie(Request $request, EntityManagerInterface $em): Response
    {
        $categorie = new Categorie();
        // Créer le formulaire
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        // Si le formulaire est soumis ET valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Gestion de l'upload de l'icône
            $iconeFile = $form->get('icone')->getData();
            if ($iconeFile && $iconeFile->isValid()) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/categories/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                $newFilename = uniqid() . '.' . $iconeFile->guessExtension();
                $iconeFile->move($uploadDir, $newFilename);
                $categorie->setIcone($newFilename);
            }
            // Sauvegarder
            $em->persist($categorie);
            $em->flush();
            $this->addFlash('success', 'Catégorie ajoutée avec succès !');
            return $this->redirectToRoute('admin_categorie_list');
        }
        return $this->render('backOffice/categorie/add.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/admin/categorie/edit/{id}', name: 'admin_categorie_edit')]
    public function editCategorie(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $categorie = $em->getRepository(Categorie::class)->find($id);

        if (!$categorie) {
            $this->addFlash('error', 'Catégorie introuvable !');
            return $this->redirectToRoute('admin_categorie_list');
        }

        // Créer le formulaire
        $form = $this->createForm(CategorieType::class, $categorie);
        $form->handleRequest($request);

        // Si le formulaire est soumis ET valide
        if ($form->isSubmitted() && $form->isValid()) {
            
            // Gestion de l'upload de la nouvelle icône
            $iconeFile = $form->get('icone')->getData();
            
            if ($iconeFile && $iconeFile->isValid()) {
                $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/categories/';
                
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                // Supprimer l'ancienne icône
                if ($categorie->getIcone()) {
                    $oldIconPath = $uploadDir . $categorie->getIcone();
                    if (file_exists($oldIconPath)) {
                        unlink($oldIconPath);
                    }
                }
                $newFilename = uniqid() . '.' . $iconeFile->guessExtension();
                $iconeFile->move($uploadDir, $newFilename);
                
                $categorie->setIcone($newFilename);
            }
            
            // Sauvegarder
            $em->flush();
            
            $this->addFlash('success', 'Catégorie modifiée avec succès !');
            return $this->redirectToRoute('admin_categorie_list');
        }

        return $this->render('backOffice/categorie/edit.html.twig', [
            'form'      => $form,
            'categorie' => $categorie,
        ]);
    }

    #[Route('/admin/categorie/delete/{id}', name: 'admin_categorie_delete')]
    public function deleteCategorie(int $id, EntityManagerInterface $em): Response
    {
        $categorie = $em->getRepository(Categorie::class)->find($id);

        if (!$categorie) {
            $this->addFlash('error', 'Catégorie introuvable !');
            return $this->redirectToRoute('admin_categorie_list');
        }

        // Vérifier si la catégorie a des logements associés
        if (count($categorie->getLogements()) > 0) {
            $this->addFlash('error', 'Impossible de supprimer cette catégorie car elle contient ' . count($categorie->getLogements()) . ' logement(s) !');
            return $this->redirectToRoute('admin_categorie_list');
        }

        // Supprimer l'icône du serveur
        if ($categorie->getIcone()) {
            $uploadDir = $this->getParameter('kernel.project_dir') . '/public/uploads/categories/';
            $iconPath = $uploadDir . $categorie->getIcone();
            if (file_exists($iconPath)) {
                unlink($iconPath);
            }
        }

        // Supprimer la catégorie
        $em->remove($categorie);
        $em->flush();

        $this->addFlash('success', 'Catégorie supprimée avec succès !');
        return $this->redirectToRoute('admin_categorie_list');
    }
}