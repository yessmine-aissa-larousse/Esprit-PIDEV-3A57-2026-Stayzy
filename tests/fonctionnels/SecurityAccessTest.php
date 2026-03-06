<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SecurityAccessTest extends WebTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        self::ensureKernelShutdown();
    }

    private function getEntityManager(): EntityManagerInterface
    {
        return static::getContainer()->get('doctrine')->getManager();
    }

    // TEST 1 : Client ne peut pas accéder au dashboard admin
    public function testClientCannotAccessAdminDashboard(): void
    {
        $client = static::createClient();
        $user = $this->createTestUser('client@test.com', 'password123', ['ROLE_CLIENT']);

        $client->loginUser($user);
        $client->request('GET', '/admin/dashboard');

        $this->assertResponseStatusCodeSame(403);
    }

    // TEST 2 : Propriétaire ne peut pas accéder au dashboard admin
    public function testProprietaireCannotAccessAdminDashboard(): void
    {
        $client = static::createClient();
        $em = $this->getEntityManager();

        $proprio = $this->createTestUser('proprio@test.com', 'password123', ['ROLE_PROPRIETAIRE']);
        $proprio->setApprovalStatus(User::STATUS_APPROVED);
        $em->flush();

        $client->loginUser($proprio);
        $client->request('GET', '/admin/dashboard');

        $this->assertResponseStatusCodeSame(403);
    }

    // TEST 3 : Utilisateur non connecté redirigé vers login
    public function testUnauthenticatedUserRedirectedToLogin(): void
    {
        $client = static::createClient();

        $client->request('GET', '/proprietaire/logements');

        $this->assertResponseRedirects();
        $client->followRedirect();
        $this->assertRouteSame('app_login');
    }

    // TEST 4 : Admin peut accéder au dashboard
    public function testAdminCanAccessDashboard(): void
    {
        $client = static::createClient();
        $admin = $this->createTestUser('admin@test.com', 'password123', ['ROLE_ADMIN']);

        $client->loginUser($admin);
        $client->request('GET', '/admin/dashboard');

        $this->assertResponseIsSuccessful();
    }

    // TEST 5 : Propriétaire approuvé peut accéder à son profil
    public function testApprovedProprietaireCanAccessProfile(): void
    {
        $client = static::createClient();
        $em = $this->getEntityManager();

        $proprio = $this->createTestUser('proprio2@test.com', 'password123', ['ROLE_PROPRIETAIRE']);
        $proprio->setApprovalStatus(User::STATUS_APPROVED);
        $em->flush();

        $client->loginUser($proprio);
        $client->request('GET', '/proprietaire/profile');

        $this->assertResponseIsSuccessful();
    }

    // TEST 6 : Propriétaire PENDING redirigé (pas accès au profil)
    public function testPendingProprietaireCannotAccessProfile(): void
    {
        $client = static::createClient();
        $em = $this->getEntityManager();

        $proprio = $this->createTestUser('proprio3@test.com', 'password123', ['ROLE_PROPRIETAIRE']);
        $proprio->setApprovalStatus(User::STATUS_PENDING);
        $em->flush();

        $client->loginUser($proprio);
        $client->request('GET', '/proprietaire/profile');

        $this->assertResponseRedirects('/proprietaire/en-attente');
    }

    // TEST 7 : Propriétaire REJETÉ redirigé (pas accès au profil)
    public function testRejectedProprietaireCannotAccessProfile(): void
    {
        $client = static::createClient();
        $em = $this->getEntityManager();

        $proprio = $this->createTestUser('proprio4@test.com', 'password123', ['ROLE_PROPRIETAIRE']);
        $proprio->setApprovalStatus(User::STATUS_REJECTED);
        $em->flush();

        $client->loginUser($proprio);
        $client->request('GET', '/proprietaire/profile');

        $this->assertResponseRedirects('/proprietaire/rejete');
    }

    // TEST 8 : Route publique accessible sans connexion
    public function testPublicRouteAccessibleWithoutLogin(): void
    {
        $client = static::createClient();

        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
    }

    // ========== HELPER ==========

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

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}