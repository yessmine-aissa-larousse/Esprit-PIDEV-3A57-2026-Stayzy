<?php

namespace App\Tests\Service;

use App\Entity\Logement;
use App\Entity\Categorie;
use App\Service\LogementValidator;
use PHPUnit\Framework\TestCase;

class LogementValidatorTest extends TestCase
{
    private LogementValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new LogementValidator();
    }

    // ========== TEST 1 : Logement valide ==========
    public function testLogementValide(): void
    {
        $logement = new Logement();
        $logement->setTitre('Appartement cosy');
        $logement->setPrix(150.0);
        $logement->setSuperficie(75);
        $logement->setNombreChambres(2);
        $logement->setNombreSalleDeBain(1);
        $logement->setDisponible(true);

        $categorie = new Categorie();
        $logement->setCategorie($categorie);

        $this->assertTrue($this->validator->validate($logement));
    }

    // ========== TEST 2 : Prix négatif ==========
    public function testPrixNegatif(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prix doit être supérieur à 0');

        $logement = new Logement();
        $logement->setTitre('Appartement');
        $logement->setPrix(-50.0); // ❌ Invalide
        $logement->setSuperficie(75);
        $logement->setNombreChambres(2);
        $logement->setNombreSalleDeBain(1);

        $this->validator->validate($logement);
    }

    // ========== TEST 3 : Prix = 0 ==========
    public function testPrixZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $logement = new Logement();
        $logement->setTitre('Appartement');
        $logement->setPrix(0); // ❌ Invalide
        $logement->setSuperficie(75);
        $logement->setNombreChambres(2);
        $logement->setNombreSalleDeBain(1);

        $this->validator->validate($logement);
    }

    // ========== TEST 4 : Superficie négative ==========
    public function testSuperficieNegative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La superficie doit être supérieure à 0');

        $logement = new Logement();
        $logement->setTitre('Appartement');
        $logement->setPrix(150.0);
        $logement->setSuperficie(-10); // ❌ Invalide
        $logement->setNombreChambres(2);
        $logement->setNombreSalleDeBain(1);

        $this->validator->validate($logement);
    }

    // ========== TEST 5 : Titre trop court ==========
    public function testTitreTropCourt(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le titre doit contenir au moins 5 caractères');

        $logement = new Logement();
        $logement->setTitre('App'); // ❌ Seulement 3 caractères
        $logement->setPrix(150.0);
        $logement->setSuperficie(75);
        $logement->setNombreChambres(2);
        $logement->setNombreSalleDeBain(1);

        $this->validator->validate($logement);
    }

    // ========== TEST 6 : Nombre de chambres = 0 ==========
    public function testNombreChambresZero(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $logement = new Logement();
        $logement->setTitre('Appartement');
        $logement->setPrix(150.0);
        $logement->setSuperficie(75);
        $logement->setNombreChambres(0); // ❌ Invalide
        $logement->setNombreSalleDeBain(1);

        $this->validator->validate($logement);
    }

    // ========== TEST 7 : Test méthode isolée isPrixValide ==========
    public function testIsPrixValideAvecPrixPositif(): void
    {
        $this->assertTrue($this->validator->isPrixValide(100.0));
    }

    public function testIsPrixValideAvecPrixNegatif(): void
    {
        $this->assertFalse($this->validator->isPrixValide(-50.0));
    }
}