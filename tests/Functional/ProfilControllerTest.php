<?php

namespace App\Tests\Functional;

use App\Entity\Friends\Relationship;
use App\Entity\MaCuisine\Recipe;
use App\Entity\User;

/**
 * Teste la page de profil : affichage, informations affichées selon le visiteur
 * et interactions avec les recettes / relations d'un autre membre.
 */
final class ProfilControllerTest extends AppWebTestCase
{
    private const INDEX_PATH = '/profil';

    /**
     * @param User $author
     * @param string $name
     * @return Recipe
     */
    private function createRecipe(User $author, string $name): Recipe
    {
        $recipe = (new Recipe())
            ->setAuthor($author)
            ->setName($name)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->em()->persist($recipe);
        $this->em()->flush();

        return $recipe;
    }

    /**
     * @param User $user1
     * @param User $user2
     * @return Relationship
     */
    private function createAcceptedRelationship(User $user1, User $user2): Relationship
    {
        $relationship = (new Relationship())
            ->setuser1($user1)
            ->setuser2($user2)
            ->setStatus(Relationship::STATUS_ACCEPTED)
            ->setCreatedAt(new \DateTimeImmutable());

        $this->em()->persist($relationship);
        $this->em()->flush();

        return $relationship;
    }

    public function testNavbarShowsProfileDropdown(): void
    {
        $this->login($this->createUser());

        $crawler = $this->client->request('GET', self::INDEX_PATH);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('nav #profileDropdown[data-bs-toggle="dropdown"]');

        $menu = $crawler->filter('nav ul[aria-labelledby="profileDropdown"]');
        $this->assertSame(1, $menu->count());
        $this->assertStringContainsString('Profil', $menu->text());
        $this->assertStringContainsString('Modifier le profil', $menu->text());
        $this->assertStringContainsString('Se déconnecter', $menu->text());

        // chaque entrée pointe au bon endroit et porte son icône bootstrap
        $this->assertSame(1, $menu->filter('a[href="' . self::INDEX_PATH . '"]')->count());
        $this->assertSame(1, $menu->filter('a[href="/settings"]')->count());
        $this->assertSame(1, $menu->filter('a[href="/logout"]')->count());
        $this->assertCount(3, $menu->filter('a.dropdown-item i.bi'));
    }

    public function testOtherUserProfileListsTheirRecipes(): void
    {
        $other = $this->createUser();
        $mine  = $this->createUser();
        $name  = 'Blanquette-' . bin2hex(random_bytes(4));

        $this->createRecipe($other, $name);
        // Recette du visiteur : elle ne doit pas apparaître sur le profil visité.
        $this->createRecipe($mine, 'Intruse-' . bin2hex(random_bytes(4)));

        $this->login($mine);
        $crawler = $this->client->request('GET', self::INDEX_PATH . '/' . $other->getId());

        $this->assertResponseIsSuccessful();

        $links = $crawler->filter('a[href^="/macuisine/"]');

        $this->assertCount(1, $links);
        $this->assertStringContainsString($name, $links->text());
    }

    public function testOwnProfileHasNoRecipeSection(): void
    {
        $user = $this->createUser();
        $this->createRecipe($user, 'Solo-' . bin2hex(random_bytes(4)));

        $this->login($user);
        $crawler = $this->client->request('GET', self::INDEX_PATH);

        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('Recettes publiées', $crawler->filter('body')->text());
    }

    public function testOtherUserProfileShowsChatButtonForAnAcceptedFriend(): void
    {
        $me     = $this->createUser();
        $friend = $this->createUser();
        $this->createAcceptedRelationship($me, $friend);

        $this->login($me);
        $crawler = $this->client->request('GET', self::INDEX_PATH . '/' . $friend->getId());

        $this->assertResponseIsSuccessful();
        $this->assertCount(
            1,
            $crawler->filter('button[formaction="/friends/chat/with/' . $friend->getId() . '"]'),
            "Le profil d'un ami doit proposer un bouton vers la conversation."
        );
    }

    public function testOtherUserProfileHasNoChatButtonWithoutAFriendship(): void
    {
        $me       = $this->createUser();
        $stranger = $this->createUser();

        $this->login($me);
        $crawler = $this->client->request('GET', self::INDEX_PATH . '/' . $stranger->getId());

        $this->assertResponseIsSuccessful();
        $this->assertCount(0, $crawler->filter('button[formaction="/friends/chat/with/' . $stranger->getId() . '"]'));
    }
}
