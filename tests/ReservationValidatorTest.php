<?php

namespace App\Tests\Service;

use App\Entity\Reservation;
use App\Service\ReservationValidator;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour ReservationValidator
 */
class ReservationValidatorTest extends TestCase
{
    private ReservationValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ReservationValidator();
    }

    // ========== TEST 1 : Réservation valide ==========
    public function testValidReservation(): void
    {
        $reservation = new Reservation();
        $reservation->setDateDebut(new \DateTime('2026-06-01'));
        $reservation->setDateFin(new \DateTime('2026-06-10'));
        $reservation->setNombrePersonnes(2);
        $reservation->setPrixTotal(1500.0);
        $reservation->setStatus('pending');

        $this->assertTrue($this->validator->validate($reservation));
    }

    // ========== TEST 2 : Date fin avant date début ==========
    public function testReservationWithDateFinBeforeDateDebut(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début');

        $reservation = new Reservation();
        $reservation->setDateDebut(new \DateTime('2026-06-10'));
        $reservation->setDateFin(new \DateTime('2026-06-01')); // ❌ INVALIDE
        $reservation->setNombrePersonnes(2);
        $reservation->setPrixTotal(1500.0);
        $reservation->setStatus('pending');

        $this->validator->validate($reservation);
    }

    // ========== TEST 3 : Date fin égale à date début ==========
    public function testReservationWithSameDates(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('La date de fin doit être postérieure à la date de début');

        $reservation = new Reservation();
        $reservation->setDateDebut(new \DateTime('2026-06-10'));
        $reservation->setDateFin(new \DateTime('2026-06-10')); // ❌ Même date
        $reservation->setNombrePersonnes(2);
        $reservation->setPrixTotal(1500.0);

        $this->validator->validate($reservation);
    }

    // ========== TEST 4 : Nombre de personnes zéro ==========
    public function testReservationWithZeroPersonnes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre de personnes doit être supérieur à zéro');

        $reservation = new Reservation();
        $reservation->setDateDebut(new \DateTime('2026-06-01'));
        $reservation->setDateFin(new \DateTime('2026-06-10'));
        $reservation->setNombrePersonnes(0); // ❌ INVALIDE
        $reservation->setPrixTotal(1500.0);

        $this->validator->validate($reservation);
    }

    // ========== TEST 5 : Nombre de personnes négatif ==========
    public function testReservationWithNegativePersonnes(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nombre de personnes doit être supérieur à zéro');

        $reservation = new Reservation();
        $reservation->setDateDebut(new \DateTime('2026-06-01'));
        $reservation->setDateFin(new \DateTime('2026-06-10'));
        $reservation->setNombrePersonnes(-2); // ❌ INVALIDE
        $reservation->setPrixTotal(1500.0);

        $this->validator->validate($reservation);
    }

    // ========== TEST 6 : Message trop long ==========
    public function testReservationWithLongMessage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le message de demande ne peut pas dépasser 500 caractères');

        $reservation = new Reservation();
        $reservation->setDateDebut(new \DateTime('2026-06-01'));
        $reservation->setDateFin(new \DateTime('2026-06-10'));
        $reservation->setNombrePersonnes(2);
        $reservation->setPrixTotal(1500.0);
        $reservation->setMessageDemande(str_repeat('A', 501)); // ❌ 501 caractères

        $this->validator->validate($reservation);
    }

    // ========== TEST 7 : Test méthode areDatesValides ==========
    public function testAreDatesValidesWithValidDates(): void
    {
        $dateDebut = new \DateTime('2026-06-01');
        $dateFin = new \DateTime('2026-06-10');

        $this->assertTrue($this->validator->areDatesValides($dateDebut, $dateFin));
    }

    public function testAreDatesValidesWithInvalidDates(): void
    {
        $dateDebut = new \DateTime('2026-06-10');
        $dateFin = new \DateTime('2026-06-01');

        $this->assertFalse($this->validator->areDatesValides($dateDebut, $dateFin));
    }

    // ========== TEST 8 : Test méthode isNombrePersonnesValide ==========
    public function testIsNombrePersonnesValideWithPositive(): void
    {
        $this->assertTrue($this->validator->isNombrePersonnesValide(2));
    }

    public function testIsNombrePersonnesValideWithZero(): void
    {
        $this->assertFalse($this->validator->isNombrePersonnesValide(0));
    }

    // ========== TEST 9 : Calcul durée séjour ==========
    public function testCalculerDureeSejour(): void
    {
        $reservation = new Reservation();
        $reservation->setDateDebut(new \DateTime('2026-06-01'));
        $reservation->setDateFin(new \DateTime('2026-06-10'));

        $duree = $this->validator->calculerDureeSejour($reservation);
        $this->assertEquals(9, $duree); // 9 jours
    }
}