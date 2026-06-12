<?php

namespace App\Tests\Functional\MonPoids;

use App\Entity\MonPoids\Measurement;
use App\Entity\User;
use App\Tests\Functional\AppWebTestCase;

class MeasurementControllerTest extends AppWebTestCase
{
    private const INDEX_PATH = '/monpoids/measurement/';

    private function createMeasurement(User $user): Measurement
    {
        $measurement = new Measurement();
        $measurement->setUser($user);
        $measurement->setChest(95.0);
        $measurement->setHips(100.0);
        $measurement->setThigh(55.0);
        $measurement->setWaist(80.0);
        $measurement->setCreatedAt(new \DateTimeImmutable());

        $em = $this->em();
        $em->persist($measurement);
        $em->flush();

        return $measurement;
    }

    public function testIndexLoadsForLoggedUser(): void
    {
        $this->login($this->createUser());

        $this->client->request('GET', self::INDEX_PATH);

        $this->assertResponseIsSuccessful();
    }

    public function testNewPersistsMeasurement(): void
    {
        $user = $this->createUser();
        $this->login($user);

        $this->client->request('GET', self::INDEX_PATH . 'new');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Enregistrer', [
            'measurement[chest]' => '95',
            'measurement[hips]' => '100',
            'measurement[thigh]' => '55',
            'measurement[waist]' => '80',
            'measurement[createdAt]' => '2026-06-12',
        ]);

        $this->assertResponseRedirects(self::INDEX_PATH);

        $saved = $this->em()->getRepository(Measurement::class)->findOneBy(['user' => $user]);
        $this->assertNotNull($saved);
        $this->assertSame(95.0, $saved->getChest());
        $this->assertSame(100.0, $saved->getHips());
        $this->assertSame(55.0, $saved->getThigh());
        $this->assertSame(80.0, $saved->getWaist());
    }

    public function testShowDisplaysMeasurement(): void
    {
        $user = $this->createUser();
        $measurement = $this->createMeasurement($user);
        $this->login($user);

        $this->client->request('GET', self::INDEX_PATH . $measurement->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Détail mesure');
    }

    public function testEditUpdatesMeasurement(): void
    {
        $user = $this->createUser();
        $measurement = $this->createMeasurement($user);
        $this->login($user);

        $this->client->request('GET', self::INDEX_PATH . $measurement->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Mettre à jour', [
            'measurement[chest]' => '97',
        ]);

        $this->assertResponseRedirects(self::INDEX_PATH);
        $this->em()->clear();
        $this->assertSame(97.0, $this->em()->find(Measurement::class, $measurement->getId())->getChest());
    }

    public function testDeleteRemovesMeasurement(): void
    {
        $user = $this->createUser();
        $measurement = $this->createMeasurement($user);
        $measurementId = $measurement->getId();
        $this->login($user);

        // le formulaire de suppression (token CSRF inclus) est sur la page d'édition
        $crawler = $this->client->request('GET', self::INDEX_PATH . $measurementId . '/edit');
        // ^= : tolère une query string ajoutée à l'action (ex. ?from=...)
        $form = $crawler->filter('form[action^="' . self::INDEX_PATH . $measurementId . '"]')->form();
        $this->client->submit($form);

        $this->assertResponseRedirects(self::INDEX_PATH);
        $this->em()->clear();
        $this->assertNull($this->em()->find(Measurement::class, $measurementId));
    }
}
