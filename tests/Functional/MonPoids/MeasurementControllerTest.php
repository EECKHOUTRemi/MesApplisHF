<?php

namespace App\Tests\Functional\MonPoids;

use App\Entity\MonPoids\Measurement;
use App\Tests\Functional\AppWebTestCase;

class MeasurementControllerTest extends AppWebTestCase
{
    public function testIndexLoadsForLoggedUser(): void
    {
        $this->login($this->createUser());

        $this->client->request('GET', '/monpoids/measurement/');

        $this->assertResponseIsSuccessful();
    }

    public function testNewPersistsMeasurement(): void
    {
        $user = $this->createUser();
        $this->login($user);

        $this->client->request('GET', '/monpoids/measurement/new');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Enregistrer', [
            'measurement[chest]' => '95',
            'measurement[hips]' => '100',
            'measurement[thigh]' => '55',
            'measurement[waist]' => '80',
            'measurement[createdAt]' => '2026-06-12',
        ]);

        $this->assertResponseRedirects('/monpoids/measurement/');

        $saved = $this->em()->getRepository(Measurement::class)->findOneBy(['user' => $user]);
        $this->assertNotNull($saved);
        $this->assertSame(95.0, $saved->getChest());
        $this->assertSame(100.0, $saved->getHips());
        $this->assertSame(55.0, $saved->getThigh());
        $this->assertSame(80.0, $saved->getWaist());
    }
}
