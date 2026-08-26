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

    /** @return EntityManagerInterface */
    protected function em(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * @param list<string> $roles
     * @param string $password
     * @param float|null $height
     * @param bool $isVerified
     * @return User
     */
    protected function createUser(
        array $roles = [],
        string $password = 'motdepasse',
        ?float $height = null,
        bool $isVerified = true,
    ): User {
        $user = new User();
        $user->setEmail(uniqid('user.', true) . '@test.local');
        // Le nom d'utilisateur est unique en base (voir User) : la base n'étant pas
        // vidée entre les tests, un nom fixe ferait échouer tous les tests suivants.
        $user->setUsername(uniqid('test.', true));
        $user->setRoles($roles);
        $user->setIsVerified($isVerified);
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

    /** @return User */
    protected function createAdmin(): User
    {
        return $this->createUser(['ROLE_ADMIN']);
    }

    /**
     * @param User $user
     * @return void
     */
    protected function login(User $user): void
    {
        $this->client->loginUser($user);
    }
}
