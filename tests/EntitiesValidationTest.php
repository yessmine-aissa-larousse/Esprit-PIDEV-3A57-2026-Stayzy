<?php

namespace App\Tests\Entity;

use App\Entity\Logement;
use App\Entity\Reservation;
use App\Entity\User;
use App\Entity\Categorie;
use App\Entity\Promotion;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * TEST GÉNÉRAL - Validation de TOUTES les entités
 * Ce fichier teste automatiquement toutes les contraintes #[Assert\...] 
 * de toutes vos entités en un seul endroit
 */
class EntitiesValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    // ============================================================================
    // TESTS LOGEMENT
    // ============================================================================

    public function testLogementValide(): void
    {
        $logement = new Logement();
        $logement->setTitre('Appartement moderne et spacieux');
        $logement->setPrix(150.0);
        $logement->setSuperficie(75);
        $logement->setNombreChambres(2);
        $logement->setNombreSalleDeBain(1);
        $logement->setDisponible(true);

        $categorie = $this->creerCategorieValide();
        $logement->setCategorie($categorie);

        $errors = $this->validator->validate($logement);
        $this->assertCount(0, $errors, 'Le logement valide ne devrait avoir aucune erreur');
    }

    public function testLogementPrixNegatif(): void
    {
        $logement = new Logement();
        $logement->setTitre('Appartement');
        $logement->setPrix(-50.0); // ❌ INVALIDE
        $logement->setSuperficie(75);
        $logement->setNombreChambres(2);
        $logement->setNombreSalleDeBain(1);

        $categorie = $this->creerCategorieValide();
        $logement->setCategorie($categorie);

        $errors = $this->validator->validate($logement);
        $this->assertGreaterThan(0, count($errors), 'Le prix négatif devrait générer une erreur');
    }

    public function testLogementPrixZero(): void
    {
        $logement = new Logement();
        $logement->setTitre('Appartement');
        $logement->setPrix(0); // ❌ INVALIDE
        $logement->setSuperficie(75);
        $logement->setNombreChambres(2);
        $logement->setNombreSalleDeBain(1);

        $categorie = $this->creerCategorieValide();
        $logement->setCategorie($categorie);

        $errors = $this->validator->validate($logement);
        $this->assertGreaterThan(0, count($errors), 'Le prix à zéro devrait générer une erreur');
    }

    public function testLogementSuperficieNegative(): void
    {
        $logement = new Logement();
        $logement->setTitre('Appartement');
        $logement->setPrix(150.0);
        $logement->setSuperficie(-10); // ❌ INVALIDE
        $logement->setNombreChambres(2);
        $logement->setNombreSalleDeBain(1);

        $categorie = $this->creerCategorieValide();
        $logement->setCategorie($categorie);

        $errors = $this->validator->validate($logement);
        $this->assertGreaterThan(0, count($errors), 'La superficie négative devrait générer une erreur');
    }

    public function testLogementTitreTropCourt(): void
    {
        $logement = new Logement();
        $logement->setTitre('App'); // ❌ Seulement 3 caractères (min = 5)
        $logement->setPrix(150.0);
        $logement->setSuperficie(75);
        $logement->setNombreChambres(2);
        $logement->setNombreSalleDeBain(1);

        $categorie = $this->creerCategorieValide();
        $logement->setCategorie($categorie);

        $errors = $this->validator->validate($logement);
        $this->assertGreaterThan(0, count($errors), 'Le titre trop court devrait générer une erreur');
    }

    public function testLogementNombreChambresZero(): void
    {
        $logement = new Logement();
        $logement->setTitre('Appartement');
        $logement->setPrix(150.0);
        $logement->setSuperficie(75);
        $logement->setNombreChambres(0); // ❌ INVALIDE
        $logement->setNombreSalleDeBain(1);

        $categorie = $this->creerCategorieValide();
        $logement->setCategorie($categorie);

        $errors = $this->validator->validate($logement);
        $this->assertGreaterThan(0, count($errors), 'Nombre de chambres à zéro devrait générer une erreur');
    }

    // ============================================================================
    // TESTS RESERVATION
    // ============================================================================

    public function testReservationValide(): void
    {
        $reservation = new Reservation();
        $reservation->setDateDebut(new \DateTime('2026-06-01'));
        $reservation->setDateFin(new \DateTime('2026-06-10'));
        $reservation->setNombrePersonnes(2);
        $reservation->setPrixTotal(1500.0);
        $reservation->setStatus('pending');

        $errors = $this->validator->validate($reservation);
        $this->assertCount(0, $errors, 'La réservation valide ne devrait avoir aucune erreur');
    }

    public function testReservationDateFinAvantDateDebut(): void
    {
        $reservation = new Reservation();
        $reservation->setDateDebut(new \DateTime('2026-06-10'));
        $reservation->setDateFin(new \DateTime('2026-06-01')); // ❌ INVALIDE
        $reservation->setNombrePersonnes(2);
        $reservation->setPrixTotal(1500.0);
        $reservation->setStatus('pending');

        $errors = $this->validator->validate($reservation);
        $this->assertGreaterThan(0, count($errors), 'Date fin avant date début devrait générer une erreur');
    }

    public function testReservationNombrePersonnesZero(): void
    {
        $reservation = new Reservation();
        $reservation->setDateDebut(new \DateTime('2026-06-01'));
        $reservation->setDateFin(new \DateTime('2026-06-10'));
        $reservation->setNombrePersonnes(0); // ❌ INVALIDE
        $reservation->setPrixTotal(1500.0);
        $reservation->setStatus('pending');

        $errors = $this->validator->validate($reservation);
        $this->assertGreaterThan(0, count($errors), 'Nombre de personnes à zéro devrait générer une erreur');
    }

    public function testReservationNombrePersonnesNegatif(): void
    {
        $reservation = new Reservation();
        $reservation->setDateDebut(new \DateTime('2026-06-01'));
        $reservation->setDateFin(new \DateTime('2026-06-10'));
        $reservation->setNombrePersonnes(-2); // ❌ INVALIDE
        $reservation->setPrixTotal(1500.0);
        $reservation->setStatus('pending');

        $errors = $this->validator->validate($reservation);
        $this->assertGreaterThan(0, count($errors), 'Nombre de personnes négatif devrait générer une erreur');
    }

    // ============================================================================
    // TESTS USER
    // ============================================================================

    public function testUserValide(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setPassword('password123');
        $user->setTel('12345678');

        $errors = $this->validator->validate($user);
        $this->assertCount(0, $errors, 'L\'utilisateur valide ne devrait avoir aucune erreur');
    }

    public function testUserEmailInvalide(): void
    {
        $user = new User();
        $user->setEmail('email_invalide'); // ❌ PAS DE @
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setPassword('password123');

        $errors = $this->validator->validate($user);
        $this->assertGreaterThan(0, count($errors), 'Email invalide devrait générer une erreur');
    }

    public function testUserNomTropCourt(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setNom('D'); // ❌ Seulement 1 caractère (min = 2)
        $user->setPrenom('Jean');
        $user->setPassword('password123');

        $errors = $this->validator->validate($user);
        $this->assertGreaterThan(0, count($errors), 'Nom trop court devrait générer une erreur');
    }

    public function testUserTelephoneTropCourt(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setPassword('password123');
        $user->setTel('123'); // ❌ Seulement 3 caractères (min = 8)

        $errors = $this->validator->validate($user);
        $this->assertGreaterThan(0, count($errors), 'Téléphone trop court devrait générer une erreur');
    }

    // ============================================================================
    // TESTS CATEGORIE
    // ============================================================================

    public function testCategorieValide(): void
    {
        $categorie = new Categorie();
        $categorie->setNom('Maison');
        $categorie->setDescription('Catégorie pour les maisons familiales');

        $errors = $this->validator->validate($categorie);
        $this->assertCount(0, $errors, 'La catégorie valide ne devrait avoir aucune erreur');
    }

    public function testCategorieNomTropCourt(): void
    {
        $categorie = new Categorie();
        $categorie->setNom('AB'); // ❌ Seulement 2 caractères (min = 3)
        $categorie->setDescription('Une description valide');

        $errors = $this->validator->validate($categorie);
        $this->assertGreaterThan(0, count($errors), 'Nom trop court devrait générer une erreur');
    }

    public function testCategorieDescriptionTropCourte(): void
    {
        $categorie = new Categorie();
        $categorie->setNom('Maison');
        $categorie->setDescription('Court'); // ❌ Seulement 5 caractères (min = 10)

        $errors = $this->validator->validate($categorie);
        $this->assertGreaterThan(0, count($errors), 'Description trop courte devrait générer une erreur');
    }

    // ============================================================================
    // TESTS PROMOTION
    // ============================================================================

    public function testPromotionValide(): void
    {
        $promotion = new Promotion();
        $promotion->setTitre('Promo été');
        $promotion->setPourcentage(20);
        $promotion->setDateDebut(new \DateTime('2026-06-01'));
        $promotion->setDateFin(new \DateTime('2026-06-30'));

        $errors = $this->validator->validate($promotion);
        $this->assertCount(0, $errors, 'La promotion valide ne devrait avoir aucune erreur');
    }

    public function testPromotionPourcentageTropEleve(): void
    {
        $promotion = new Promotion();
        $promotion->setTitre('Promo été');
        $promotion->setPourcentage(150); // ❌ > 99
        $promotion->setDateDebut(new \DateTime('2026-06-01'));
        $promotion->setDateFin(new \DateTime('2026-06-30'));

        $errors = $this->validator->validate($promotion);
        $this->assertGreaterThan(0, count($errors), 'Pourcentage > 99 devrait générer une erreur');
    }

    public function testPromotionPourcentageZero(): void
    {
        $promotion = new Promotion();
        $promotion->setTitre('Promo été');
        $promotion->setPourcentage(0); // ❌ < 1
        $promotion->setDateDebut(new \DateTime('2026-06-01'));
        $promotion->setDateFin(new \DateTime('2026-06-30'));

        $errors = $this->validator->validate($promotion);
        $this->assertGreaterThan(0, count($errors), 'Pourcentage à zéro devrait générer une erreur');
    }

    public function testPromotionDateFinAvantDateDebut(): void
    {
        $promotion = new Promotion();
        $promotion->setTitre('Promo été');
        $promotion->setPourcentage(20);
        $promotion->setDateDebut(new \DateTime('2026-06-30'));
        $promotion->setDateFin(new \DateTime('2026-06-01')); // ❌ INVALIDE

        $errors = $this->validator->validate($promotion);
        $this->assertGreaterThan(0, count($errors), 'Date fin avant date début devrait générer une erreur');
    }

    public function testPromotionCodePromoInvalide(): void
    {
        $promotion = new Promotion();
        $promotion->setTitre('Promo été');
        $promotion->setPourcentage(20);
        $promotion->setDateDebut(new \DateTime('2026-06-01'));
        $promotion->setDateFin(new \DateTime('2026-06-30'));
        $promotion->setCodePromo('code-invalide'); // ❌ Contient des minuscules et tiret

        $errors = $this->validator->validate($promotion);
        $this->assertGreaterThan(0, count($errors), 'Code promo invalide devrait générer une erreur');
    }

    // ============================================================================
    // HELPERS - Méthodes pour créer des entités valides
    // ============================================================================

    private function creerCategorieValide(): Categorie
    {
        $categorie = new Categorie();
        $categorie->setNom('Appartement');
        $categorie->setDescription('Catégorie pour les appartements modernes');
        return $categorie;
    }
}