<?php

namespace App\Tests\Unit\Form;

use App\Entity\User;
use App\Form\UserType;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Component\Form\Test\TypeTestCase;

/**
 * Teste le mappage de UserType sur l'entité User : e-mail, rôles (toujours augmentés de
 * ROLE_USER), pseudonyme, vérification et taille (champ optionnel ajouté au formulaire admin).
 *
 * TypeTestCase::setUp() (Symfony) mocks EventDispatcherInterface without expectations ;
 * the attribute below opts out of PHPUnit's "unused mock" notice for that base-class mock.
 */
#[AllowMockObjectsWithoutExpectations]
final class UserTypeTest extends TypeTestCase
{
    public function testFormHasExpectedFields(): void
    {
        $form = $this->factory->create(UserType::class, new User());

        $this->assertTrue($form->has('email'));
        $this->assertTrue($form->has('roles'));
        $this->assertTrue($form->has('username'));
        $this->assertTrue($form->has('isVerified'));
        $this->assertTrue($form->has('height'));
    }

    public function testSubmitValidDataMapsToUser(): void
    {
        $formData = [
            'email' => 'admin.test@example.com',
            'roles' => ['ROLE_ADMIN'],
            'username' => 'admin_test',
            'isVerified' => true,
            'height' => '181.5',
        ];

        $user = new User();
        $form = $this->factory->create(UserType::class, $user);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame('admin.test@example.com', $user->getEmail());
        $this->assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
        $this->assertSame('admin_test', $user->getUsername());
        $this->assertTrue($user->isVerified());
        $this->assertSame(181.5, $user->getHeight());
    }

    public function testHeightIsOptionalAndStaysNullWhenOmitted(): void
    {
        $formData = [
            'email' => 'sans.taille@example.com',
            'roles' => ['ROLE_USER'],
            'username' => 'sans_taille',
            'isVerified' => false,
        ];

        $user = new User();
        $form = $this->factory->create(UserType::class, $user);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertNull($user->getHeight());
    }
}
