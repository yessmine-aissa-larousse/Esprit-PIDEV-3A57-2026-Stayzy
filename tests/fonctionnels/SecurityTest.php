<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
    }

    // TEST 1 : La page login est accessible
    public function testLoginPageIsAccessible(): void
    {
        $client = static::createClient();
        $client->request('GET', '/login');

        $this->assertResponseIsSuccessful();
    }

    // TEST 2 : Un client peut se connecter
    public function testClientCanLogin(): void
    {
        $client = static::createClient();
        $this->createTestUser('client@test.com', 'password123', ['ROLE_CLIENT']);

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'client@test.com',
            '_password' => 'password123',
        ]);

        $client->submit($form);
        $this->assertResponseRedirects();
    }

    // TEST 3 : Mauvais mot de passe → redirigé vers login avec erreur
    public function testLoginWithWrongPassword(): void
    {
        $client = static::createClient();
        $this->createTestUser('test@test.com', 'correct_password', ['ROLE_CLIENT']);

        $crawler = $client->request('GET', '/login');
        $form = $crawler->selectButton('Se connecter')->form([
            '_username' => 'test@test.com',
            '_password' => 'wrong_password',
        ]);

        $client->submit($form);
        $client->followRedirect();

        // Reste sur /login après échec
        $this->assertStringContainsString('/login', $client->getRequest()->getUri());
    }

    // TEST 4 : Déconnexion fonctionne
    public function testUserCanLogout(): void
    {
        $client = static::createClient();
        $user = $this->createTestUser('logout@test.com', 'password123', ['ROLE_CLIENT']);

        $client->loginUser($user);
        $client->request('GET', '/logout');

        $this->assertResponseRedirects();
    }

    // TEST 5 : Propriétaire PENDING bloqué (redirigé)
    public function testPendingProprietaireCannotAccessProfile(): void
    {
        $client = static::createClient();
        $em = $this->getEntityManager();

        $proprio = $this->createTestUser('pending@test.com', 'password123', ['ROLE_PROPRIETAIRE']);
        $proprio->setApprovalStatus(User::STATUS_PENDING);
        $em->flush();

        $client->loginUser($proprio);
        $client->request('GET', '/proprietaire/profile');

        $this->assertResponseRedirects('/proprietaire/en-attente');
    }

    // ========== HELPERS ==========

    private function createTestUser(string $email, string $password, array $roles): User
    {
        $em = $this->getEntityManager();

        $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existing) {
            $em->remove($existing);
            $em->flush();
        }

        $user = new User();
        $user->setEmail($email);
        $user->setNom('Test');
        $user->setPrenom('User');
        $user->setRoles($roles);
        $user->setIsActive(true);

        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $em->persist($user);
        $em->flush();

        return $user;
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }
}