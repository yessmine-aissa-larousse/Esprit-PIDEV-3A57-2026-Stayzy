<?php

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserValidator;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires pour UserValidator
 */
class UserValidatorTest extends TestCase
{
    private UserValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new UserValidator();
    }

    // ========== TEST 1 : User valide ==========
    public function testValidUser(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setPassword('password123');
        $user->setTel('12345678');

        $this->assertTrue($this->validator->validate($user));
    }

    // ========== TEST 2 : Email invalide (sans @) ==========
    public function testUserWithInvalidEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'email n\'est pas valide');

        $user = new User();
        $user->setEmail('email_invalide'); // ❌ PAS DE @
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setPassword('password123');

        $this->validator->validate($user);
    }

    // ========== TEST 3 : Email vide ==========
    public function testUserWithEmptyEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'email n\'est pas valide');

        $user = new User();
        $user->setEmail('');
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setPassword('password123');

        $this->validator->validate($user);
    }

    // ========== TEST 4 : Nom trop court ==========
    public function testUserWithShortNom(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le nom doit contenir au moins 2 caractères');

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setNom('D'); // ❌ 1 seul caractère
        $user->setPrenom('Jean');
        $user->setPassword('password123');

        $this->validator->validate($user);
    }

    // ========== TEST 5 : Prénom trop court ==========
    public function testUserWithShortPrenom(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le prénom doit contenir au moins 2 caractères');

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setNom('Dupont');
        $user->setPrenom('J'); // ❌ 1 seul caractère
        $user->setPassword('password123');

        $this->validator->validate($user);
    }

    // ========== TEST 6 : Téléphone trop court ==========
    public function testUserWithShortTelephone(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le téléphone doit contenir au moins 8 caractères');

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setPassword('password123');
        $user->setTel('123'); // ❌ Seulement 3 caractères

        $this->validator->validate($user);
    }

    // ========== TEST 7 : Téléphone invalide (caractères non autorisés) ==========
    public function testUserWithInvalidTelephone(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le numéro de téléphone est invalide');

        $user = new User();
        $user->setEmail('test@example.com');
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setPassword('password123');
        $user->setTel('abc12345'); // ❌ Contient des lettres

        $this->validator->validate($user);
    }

    // ========== TEST 8 : User valide sans téléphone ==========
    public function testUserWithoutTelephone(): void
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setNom('Dupont');
        $user->setPrenom('Jean');
        $user->setPassword('password123');
        // Pas de téléphone (optionnel)

        $this->assertTrue($this->validator->validate($user));
    }

    // ========== TEST 9 : Test méthode isEmailValide ==========
    public function testIsEmailValideWithValidEmail(): void
    {
        $this->assertTrue($this->validator->isEmailValide('test@example.com'));
    }

    public function testIsEmailValideWithInvalidEmail(): void
    {
        $this->assertFalse($this->validator->isEmailValide('email_invalide'));
    }

    // ========== TEST 10 : Test méthode isNomValide ==========
    public function testIsNomValideWithValidNom(): void
    {
        $this->assertTrue($this->validator->isNomValide('Dupont'));
    }

    public function testIsNomValideWithShortNom(): void
    {
        $this->assertFalse($this->validator->isNomValide('D'));
    }

    // ========== TEST 11 : Test méthode isTelephoneValide ==========
    public function testIsTelephoneValideWithValidTel(): void
    {
        $this->assertTrue($this->validator->isTelephoneValide('12345678'));
        $this->assertTrue($this->validator->isTelephoneValide('+216 12 345 678'));
    }

    public function testIsTelephoneValideWithInvalidTel(): void
    {
        $this->assertFalse($this->validator->isTelephoneValide('123')); // Trop court
        $this->assertFalse($this->validator->isTelephoneValide('abc12345')); // Caractères invalides
    }

    public function testIsTelephoneValideWithNull(): void
    {
        $this->assertTrue($this->validator->isTelephoneValide(null)); // Optionnel
    }

    // ========== TEST 12 : Test méthode getFullName ==========
    public function testGetFullName(): void
    {
        $user = new User();
        $user->setPrenom('Jean');
        $user->setNom('Dupont');

        $this->assertEquals('Jean Dupont', $this->validator->getFullName($user));
    }
}