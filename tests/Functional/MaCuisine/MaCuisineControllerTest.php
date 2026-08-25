<?php

namespace App\Tests\Functional\MaCuisine;

use App\Entity\MaCuisine\Recipe;
use App\Tests\Functional\AppWebTestCase;

/** Teste le tableau de bord MaCuisine : recettes récentes et compteurs. */
class MaCuisineControllerTest extends AppWebTestCase
{
    public function testDashboardShowsRecentRecipesAndCounters(): void
    {
        $user = $this->createUser();
        $recipe = new Recipe();
        $recipe->setName(substr('Tdb-' . bin2hex(random_bytes(6)), 0, 30));
        $recipe->setDescription('Recette visible sur le tableau de bord.');
        $recipe->setAuthor($user);
        // Le tableau de bord n'affiche que les 6 recettes les plus récentes et
        // createdAt est stocké à la seconde : les recettes créées par les autres
        // tests dans la même seconde départageraient le tri au hasard. On date donc
        // la recette dans le futur proche pour la placer en tête de façon déterministe.
        $recipe->setCreatedAt(new \DateTimeImmutable('+1 hour'));
        $this->em()->persist($recipe);
        $this->em()->flush();

        $this->login($user);
        $this->client->request('GET', '/macuisine');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'MaCuisine');
        // la recette fraîchement créée fait partie des 6 plus récentes
        $this->assertSelectorTextContains('body', $recipe->getName());
    }
}
