<?php

namespace App\Tests\Functional;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * Teste UserRepository : recherche par pseudo (insensible à la casse, sans le
 * demandeur, plafonnée) et réencodage du mot de passe.
 *
 * Les pseudos portent un suffixe aléatoire : la base n'est pas vidée entre les
 * exécutions, la recherche doit donc pouvoir cibler ce seul jeu de données.
 */
final class UserRepositoryTest extends KernelTestCase
{
    private UserRepository $repository;
    private EntityManagerInterface $em;
    private string $token;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->repository = static::getContainer()->get(UserRepository::class);
        $this->em         = static::getContainer()->get(EntityManagerInterface::class);
        $this->token      = bin2hex(random_bytes(4));
    }

    public function testSearchByUsernameIsCaseInsensitive(): void
    {
        $searcher = $this->createUser('cherche');
        $target   = $this->createUser('Alice');

        $results = $this->repository->searchByUsername('ALICE' . $this->token, $searcher);

        $this->assertSame([$target->getId()], $this->ids($results));
    }

    public function testSearchByUsernameMatchesOnAFragment(): void
    {
        $searcher = $this->createUser('cherche');
        $target   = $this->createUser('Bertrand');

        // Le jeton est au milieu du pseudo : la recherche est encadrée de « % ».
        $results = $this->repository->searchByUsername($this->token, $searcher);

        $this->assertSame([$target->getId()], $this->ids($results));
    }

    public function testSearchByUsernameExcludesTheGivenUser(): void
    {
        // Le demandeur porte lui aussi le jeton : il doit rester hors des résultats.
        $searcher = $this->createUser('Moi');

        $this->assertSame([], $this->repository->searchByUsername($this->token, $searcher));
    }

    public function testSearchByUsernameSortsAlphabeticallyAndHonoursTheLimit(): void
    {
        $searcher = $this->createUser('cherche');
        $charlie  = $this->createUser('Charlie');
        $alice    = $this->createUser('Alice');
        $bob      = $this->createUser('Bob');

        $all = $this->repository->searchByUsername($this->token, $searcher);

        $this->assertSame($this->ids([$alice, $bob, $charlie]), $this->ids($all));

        $limited = $this->repository->searchByUsername($this->token, $searcher, 2);

        $this->assertSame($this->ids([$alice, $bob]), $this->ids($limited));
    }

    public function testUpgradePasswordStoresTheNewHash(): void
    {
        $user = $this->createUser('Rehash');

        $this->repository->upgradePassword($user, 'nouveau-hash');

        $this->em->clear();
        $reloaded = $this->em->find(User::class, $user->getId());

        $this->assertInstanceOf(User::class, $reloaded);
        $this->assertSame('nouveau-hash', $reloaded->getPassword());
    }

    public function testUpgradePasswordRejectsAnotherUserImplementation(): void
    {
        $foreign = new class implements PasswordAuthenticatedUserInterface {
            /** @return string|null */
            public function getPassword(): ?string
            {
                return null;
            }
        };

        $this->expectException(UnsupportedUserException::class);

        $this->repository->upgradePassword($foreign, 'peu-importe');
    }

    /**
     * @param string $prefix
     * @return User
     */
    private function createUser(string $prefix): User
    {
        $user = (new User())
            ->setEmail(uniqid('user.repository.', true) . '@test.local')
            // Suffixe commun au jeu de données du test, préfixe pour l'ordre alphabétique.
            ->setUsername($prefix . $this->token)
            ->setPassword('hash-initial')
            ->setCreatedAt(new \DateTimeImmutable());

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * @param User[] $users
     * @return list<int|null>
     */
    private function ids(array $users): array
    {
        return array_map(static fn (User $user): ?int => $user->getId(), array_values($users));
    }
}
