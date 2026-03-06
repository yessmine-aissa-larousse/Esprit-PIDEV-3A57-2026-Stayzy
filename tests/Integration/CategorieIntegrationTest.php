<?php

namespace App\Tests\Integration;

use App\Entity\Categorie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Tests d'intégration pour l'entité Categorie
 * Vérifie que les opérations en base de données fonctionnent correctement
 */
class CategorieIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get('doctrine')->getManager();
    }

    // ✅ TEST 1 : Créer et sauvegarder une catégorie en base
    public function testCreerCategorie(): void
    {
        $categorie = new Categorie();
        $categorie->setNom('Appartement');
        $categorie->setDescription('Logement en appartement moderne');

        $this->em->persist($categorie);
        $this->em->flush();

        $this->assertNotNull($categorie->getId());
        $this->assertEquals('Appartement', $categorie->getNom());
        $this->assertEquals('Logement en appartement moderne', $categorie->getDescription());
    }

    // ✅ TEST 2 : Retrouver une catégorie par son ID
    public function testTrouverCategorieParId(): void
    {
        $categorie = new Categorie();
        $categorie->setNom('Villa');
        $categorie->setDescription('Grande villa avec piscine');
        $this->em->persist($categorie);
        $this->em->flush();

        $id = $categorie->getId();

        $this->em->clear();

        $categorieFound = $this->em->getRepository(Categorie::class)->find($id);

        $this->assertNotNull($categorieFound);
        $this->assertEquals('Villa', $categorieFound->getNom());
    }

    // ✅ TEST 3 : Modifier une catégorie existante
    public function testModifierCategorie(): void
    {
        $categorie = new Categorie();
        $categorie->setNom('Studio');
        $categorie->setDescription('Petit studio en centre ville');
        $this->em->persist($categorie);
        $this->em->flush();

        $categorie->setNom('Studio Luxe');
        $this->em->flush();

        $this->em->clear();
        $categorieModifie = $this->em->getRepository(Categorie::class)->find($categorie->getId());

        $this->assertEquals('Studio Luxe', $categorieModifie->getNom());
    }

    // ✅ TEST 4 : Supprimer une catégorie
    public function testSupprimerCategorie(): void
    {
        $categorie = new Categorie();
        $categorie->setNom('Chambre');
        $categorie->setDescription('Chambre meublée à louer');
        $this->em->persist($categorie);
        $this->em->flush();

        $id = $categorie->getId();

        $this->em->remove($categorie);
        $this->em->flush();

        $this->em->clear();
        $categorieSuppr = $this->em->getRepository(Categorie::class)->find($id);

        $this->assertNull($categorieSuppr);
    }

    // ✅ TEST 5 : Lister toutes les catégories
    public function testListerCategories(): void
    {
        $avant = count($this->em->getRepository(Categorie::class)->findAll());

        $cat1 = new Categorie();
        $cat1->setNom('Maison');
        $cat1->setDescription('Maison individuelle avec jardin');

        $cat2 = new Categorie();
        $cat2->setNom('Loft');
        $cat2->setDescription('Loft industriel rénové');

        $this->em->persist($cat1);
        $this->em->persist($cat2);
        $this->em->flush();

        $apres = count($this->em->getRepository(Categorie::class)->findAll());

        $this->assertEquals($avant + 2, $apres);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->em->close();
    }
}