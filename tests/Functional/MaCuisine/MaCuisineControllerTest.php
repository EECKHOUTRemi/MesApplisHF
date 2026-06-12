<?php

namespace App\Tests\Functional\MaCuisine;

use App\Entity\MaCuisine\Recipe;
use App\Tests\Functional\AppWebTestCase;

class MaCuisineControllerTest extends AppWebTestCase
{
    public function testDashboardShowsRecentRecipesAndCounters(): void
    {
        $user = $this->createUser();
        $recipe = new Recipe();
        $recipe->setName(substr('Tdb-' . bin2hex(random_bytes(6)), 0, 30));
        $recipe->setDescription('Recette visible sur le tableau de bord.');
        $recipe->setAuthor($user);
        $recipe->setCreatedAt(new \DateTimeImmutable());
        $this->em()->persist($recipe);
        $this->em()->flush();

        $this->login($user);
        $this->client->request('GET', '/macuisine/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'MaCuisine');
        // la recette fraîchement créée fait partie des 6 plus récentes
        $this->assertSelectorTextContains('body', $recipe->getName());
    }
}
