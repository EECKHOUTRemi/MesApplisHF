<?php

namespace App\Tests\Functional\MonPoids;

use App\Tests\Functional\AppWebTestCase;

/** Teste la navigation de base dans MonPoids : redirection de l'accueil vers la vue globale. */
class MonPoidsPagesTest extends AppWebTestCase
{
    public function testIndexRedirectsToGlobalView(): void
    {
        $this->login($this->createUser());

        // /monpoids/ (index) redirige vers la vue globale, désormais servie à /monpoids.
        $this->client->request('GET', '/monpoids/');

        $this->assertResponseRedirects('/monpoids');
    }

    public function testGlobalPageLoadsForLoggedUser(): void
    {
        $this->login($this->createUser());

        $this->client->request('GET', '/monpoids');

        $this->assertResponseIsSuccessful();
    }
}
