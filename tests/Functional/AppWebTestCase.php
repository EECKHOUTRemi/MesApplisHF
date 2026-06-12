<?php

namespace App\Tests\Functional;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Base commune des tests fonctionnels : client HTTP + création d'utilisateurs.
 *
 * Les données créées portent des identifiants uniques (uniqid) pour que les
 * tests restent indépendants sans vider la base entre chaque exécution.
 */
abstract class AppWebTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    protected function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * @param list<string> $roles
     */
    protected function createUser(
        array $roles = [],
        string $password = 'motdepasse',
        ?float $height = null,
    ): User {
        $user = new User();
        $user->setEmail(uniqid('user.', true) . '@test.local');
        $user->setUsername('testeur');
        $user->setRoles($roles);
        $user->setIsVerified(true);
        $user->setCreatedAt(new \DateTimeImmutable());
        if (null !== $height) {
            $user->setHeight($height);
        }

        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, $password));

        $em = $this->em();
        $em->persist($user);
        $em->flush();

        return $user;
    }

    protected function createAdmin(): User
    {
        return $this->createUser(['ROLE_ADMIN']);
    }

    protected function login(User $user): void
    {
        $this->client->loginUser($user);
    }
}
