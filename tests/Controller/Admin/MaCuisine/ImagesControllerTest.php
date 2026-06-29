<?php

namespace App\Tests\Controller\Admin\MaCuisine;

use App\Tests\Functional\AppWebTestCase;

final class ImagesControllerTest extends AppWebTestCase
{
    public function testIndexRequiresAdmin(): void
    {
        $this->client->request('GET', '/admin/macuisine/images');

        self::assertResponseStatusCodeSame(302);
    }

    public function testIndexDisplaysGallery(): void
    {
        $this->login($this->createAdmin());
        $this->client->request('GET', '/admin/macuisine/images');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Images des recettes');
    }
}
