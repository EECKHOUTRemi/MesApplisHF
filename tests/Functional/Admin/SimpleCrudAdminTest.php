<?php

namespace App\Tests\Functional\Admin;

use App\Entity\MaCuisine\Category;
use App\Entity\MaCuisine\Ingredient;
use App\Entity\MaCuisine\Utensil;
use App\Tests\Functional\AppWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * CRUD admin des trois petites entités MaCuisine (catégorie, ustensile,
 * ingrédient) : même squelette de pages, testé via data provider.
 */
class SimpleCrudAdminTest extends AppWebTestCase
{
    /**
     * @return iterable<string, array{string, string, class-string}>
     */
    public static function crudProvider(): iterable
    {
        // [chemin de base, préfixe du formulaire, classe de l'entité]
        yield 'catégorie' => ['/admin/ma/cuisine/category', 'category', Category::class];
        yield 'ustensile' => ['/admin/ma/cuisine/utensil', 'utensil', Utensil::class];
        yield 'ingrédient' => ['/admin/macuisine/ingredient', 'ingredient', Ingredient::class];
    }

    /**
     * @param string $class
     * @param string $name
     * @return object
     */
    private function createEntity(string $class, string $name): object
    {
        $entity = new $class();
        $entity->setName($name);
        if (!$entity instanceof Ingredient) {
            $entity->setCreatedAt(new \DateTimeImmutable());
            $entity->setCreatedBy($this->createAdmin());
        }

        $em = $this->em();
        $em->persist($entity);
        $em->flush();

        return $entity;
    }

    /**
     * @param string $prefix
     * @return string
     */
    private function uniqueName(string $prefix): string
    {
        // les noms sont limités à 32 caractères
        return substr($prefix . bin2hex(random_bytes(6)), 0, 30);
    }

    /**
     * @param string $basePath
     * @param string $formPrefix
     * @param string $class
     * @return void
     */
    #[DataProvider('crudProvider')]
    public function testIndexListsEntities(string $basePath, string $formPrefix, string $class): void
    {
        $entity = $this->createEntity($class, $this->uniqueName('Liste-'));
        $this->login($this->createAdmin());

        $this->client->request('GET', $basePath);

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $entity->getName());
    }

    /**
     * @param string $basePath
     * @param string $formPrefix
     * @param string $class
     * @return void
     */
    #[DataProvider('crudProvider')]
    public function testCreatePersistsEntity(string $basePath, string $formPrefix, string $class): void
    {
        $this->login($this->createAdmin());
        $name = $this->uniqueName('Cree-');

        $crawler = $this->client->request('GET', $basePath . '/new');
        $this->assertResponseIsSuccessful();

        $field = sprintf('input[name="%s[name]"]', $formPrefix);
        $form = $crawler->filter($field)->closest('form')->form([
            $formPrefix . '[name]' => $name,
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects($basePath);
        $this->assertNotNull($this->em()->getRepository($class)->findOneBy(['name' => $name]));
    }

    /**
     * @param string $basePath
     * @param string $formPrefix
     * @param string $class
     * @return void
     */
    #[DataProvider('crudProvider')]
    public function testShowDisplaysEntity(string $basePath, string $formPrefix, string $class): void
    {
        $entity = $this->createEntity($class, $this->uniqueName('Detail-'));
        $this->login($this->createAdmin());

        $this->client->request('GET', $basePath . '/' . $entity->getId());

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', $entity->getName());
    }

    /**
     * @param string $basePath
     * @param string $formPrefix
     * @param string $class
     * @return void
     */
    #[DataProvider('crudProvider')]
    public function testEditUpdatesEntity(string $basePath, string $formPrefix, string $class): void
    {
        $entity = $this->createEntity($class, $this->uniqueName('Avant-'));
        $this->login($this->createAdmin());
        $newName = $this->uniqueName('Apres-');

        $crawler = $this->client->request('GET', $basePath . '/' . $entity->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $field = sprintf('input[name="%s[name]"]', $formPrefix);
        $form = $crawler->filter($field)->closest('form')->form([
            $formPrefix . '[name]' => $newName,
        ]);
        $this->client->submit($form);

        $this->assertResponseRedirects($basePath);
        $this->em()->clear();
        $this->assertSame($newName, $this->em()->find($class, $entity->getId())->getName());
    }

    /**
     * @param string $basePath
     * @param string $formPrefix
     * @param string $class
     * @return void
     */
    #[DataProvider('crudProvider')]
    public function testDeleteRemovesEntity(string $basePath, string $formPrefix, string $class): void
    {
        $entity = $this->createEntity($class, $this->uniqueName('Asuppr-'));
        $entityId = $entity->getId();
        $this->login($this->createAdmin());

        // le formulaire de suppression (token CSRF inclus) est sur la page de détail
        $crawler = $this->client->request('GET', $basePath . '/' . $entityId);
        $form = $crawler->filter(sprintf('form[action="%s/%d"]', $basePath, $entityId))->form();
        $this->client->submit($form);

        $this->assertResponseRedirects($basePath);
        $this->em()->clear();
        $this->assertNull($this->em()->find($class, $entityId));
    }
}
