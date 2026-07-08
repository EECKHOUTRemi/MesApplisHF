<?php

namespace App\Tests\Functional\MonPoids;

use App\Entity\MonPoids\Bmi;
use App\Entity\User;
use App\Tests\Functional\AppWebTestCase;

/**
 * Teste le CRUD IMC MonPoids : calcul de l'IMC, pré-remplissage de la taille depuis le profil,
 * isolation des données par utilisateur et protection contre l'accès aux entrées d'autrui.
 */
class BmiControllerTest extends AppWebTestCase
{
    private const INDEX_PATH = '/monpoids/bmi/';
    private const NEW_PATH = '/monpoids/bmi/new';

    /**
     * @param Bmi $bmi
     * @return string
     */
    private function showPath(Bmi $bmi): string
    {
        return self::INDEX_PATH . $bmi->getId();
    }

    /**
     * @param Bmi $bmi
     * @return string
     */
    private function editPath(Bmi $bmi): string
    {
        return $this->showPath($bmi) . '/edit';
    }

    /**
     * @param User $user
     * @param float $height
     * @param float $weight
     * @return Bmi
     */
    private function createBmi(User $user, float $height, float $weight): Bmi
    {
        $bmi = new Bmi();
        $bmi->setUser($user);
        $bmi->setHeight($height);
        $bmi->setWeight($weight);
        $bmi->setBmi($height, $weight);
        $bmi->setCreatedAt(new \DateTimeImmutable());

        $em = $this->em();
        $em->persist($bmi);
        $em->flush();

        return $bmi;
    }

    public function testIndexShowsEmptyStateForNewUser(): void
    {
        $this->login($this->createUser());

        $this->client->request('GET', self::INDEX_PATH);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('tbody', 'Aucun enregistrement');
    }

    public function testNewComputesAndPersistsBmi(): void
    {
        $user = $this->createUser();
        $this->login($user);

        $this->client->request('GET', self::NEW_PATH);
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Enregistrer', [
            'bmi[height]' => '180',
            'bmi[weight]' => '80',
            'bmi[createdAt]' => '2026-06-12',
        ]);

        $this->assertResponseRedirects(self::INDEX_PATH);

        $saved = $this->em()->getRepository(Bmi::class)->findOneBy(['user' => $user]);
        $this->assertNotNull($saved);
        $this->assertSame(24.69, $saved->getBmi());

        // l'IMC calculé apparaît dans l'historique
        $this->client->followRedirect();
        $this->assertSelectorTextContains('tbody', '24.69');
    }

    public function testNewStoresHeightOnUserWhenMissing(): void
    {
        $user = $this->createUser(); // sans taille renseignée
        $this->login($user);

        $this->client->request('GET', self::NEW_PATH);
        $this->client->submitForm('Enregistrer', [
            'bmi[height]' => '172',
            'bmi[weight]' => '65',
            'bmi[createdAt]' => '2026-06-12',
        ]);

        $this->em()->clear();
        $reloaded = $this->em()->find(User::class, $user->getId());
        $this->assertSame(172.0, $reloaded->getHeight());
    }

    public function testNewPrefillsHeightFromProfile(): void
    {
        $this->login($this->createUser(height: 168.0));

        $crawler = $this->client->request('GET', self::NEW_PATH);

        $this->assertSame('168', $crawler->filter('#bmi_height')->attr('value'));
    }

    public function testEditRecomputesBmi(): void
    {
        $user = $this->createUser();
        $bmi = $this->createBmi($user, 180.0, 80.0);
        $this->login($user);

        $this->client->request('GET', $this->editPath($bmi));
        $this->client->submitForm('Mettre à jour', [
            'bmi[height]' => '180',
            'bmi[weight]' => '90',
        ]);

        $this->assertResponseRedirects(self::INDEX_PATH);
        $this->em()->clear();
        $this->assertSame(27.78, $this->em()->find(Bmi::class, $bmi->getId())->getBmi());
    }

    public function testShowingSomeoneElsesEntryRedirectsToIndex(): void
    {
        $other = $this->createUser();
        $bmi = $this->createBmi($other, 180.0, 80.0);

        $this->login($this->createUser());
        $this->client->request('GET', $this->showPath($bmi));

        $this->assertResponseRedirects(self::INDEX_PATH);
    }

    public function testEditingSomeoneElsesEntryRedirectsToIndex(): void
    {
        $other = $this->createUser();
        $bmi = $this->createBmi($other, 180.0, 80.0);

        $this->login($this->createUser());
        $this->client->request('GET', $this->editPath($bmi));

        $this->assertResponseRedirects(self::INDEX_PATH);
        $this->em()->clear();
        $this->assertSame(
            24.69,
            $this->em()->find(
                Bmi::class,
                $bmi->getId()
            )->getBmi(),
            "L'IMC ne doit pas être modifié"
        );
    }

    public function testOwnerCanShowOwnEntry(): void
    {
        $user = $this->createUser();
        $bmi = $this->createBmi($user, 180.0, 80.0);
        $this->login($user);

        $this->client->request('GET', $this->showPath($bmi));

        $this->assertResponseIsSuccessful();
    }

    public function testOwnerCanDeleteOwnEntry(): void
    {
        $user = $this->createUser();
        $bmi = $this->createBmi($user, 180.0, 80.0);
        $bmiId = $bmi->getId();
        $this->login($user);

        // le formulaire de suppression (token CSRF inclus) est sur la page d'édition
        $crawler = $this->client->request('GET', '/monpoids/bmi/' . $bmiId . '/edit');
        // ^= : tolère une query string ajoutée à l'action (ex. ?from=...)
        $form = $crawler->filter('form[action^="/monpoids/bmi/' . $bmiId . '"]')->form();
        $this->client->submit($form);

        $this->assertResponseRedirects(self::INDEX_PATH);
        $this->em()->clear();
        $this->assertNull($this->em()->find(Bmi::class, $bmiId));
    }

    public function testDeletingSomeoneElsesEntryRedirectsWithoutDeleting(): void
    {
        $other = $this->createUser();
        $bmi = $this->createBmi($other, 180.0, 80.0);
        $bmiId = $bmi->getId();

        $this->login($this->createUser());
        $this->client->request('POST', $this->showPath($bmi));

        $this->assertResponseRedirects(self::INDEX_PATH);
        $this->em()->clear();
        $this->assertNotNull($this->em()->find(Bmi::class, $bmiId), "L'enregistrement ne doit pas être supprimé");
    }

    public function testIndexOnlyShowsOwnEntries(): void
    {
        $other = $this->createUser();
        $this->createBmi($other, 190.0, 111.0); // IMC 30.75, valeur reconnaissable

        $this->login($this->createUser());
        $this->client->request('GET', self::INDEX_PATH);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextNotContains('tbody', '30.75');
    }
}
