<?php /** ce test unitaire permet d’éviter une régression sur une partie importante de la sécurité de l’application */

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserRolesTest extends TestCase
{
    /**
     * Vérifie qu'un utilisateur possède toujours le rôle ROLE_USER.
     *
     * Dans Symfony, ROLE_USER est le rôle de base d'un utilisateur connecté.
     * Même si aucun rôle spécial n'est défini, l'utilisateur doit avoir ce rôle.
     */
    public function testUserAlwaysHasRoleUser(): void
    {
        $user = new User();

        $roles = $user->getRoles();

        $this->assertContains('ROLE_USER', $roles);
    }

    /**
     * Vérifie qu'un administrateur garde bien son rôle ROLE_ADMIN.
     *
     * On vérifie aussi qu'il possède toujours ROLE_USER,
     * car un administrateur reste avant tout un utilisateur connecté.
     */
    public function testAdminHasRoleAdminAndRoleUser(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN']);

        $roles = $user->getRoles();

        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    /**
     * Vérifie qu'un vendeur garde bien son rôle ROLE_SELLER.
     *
     * Ce test est utile pour InnovShop si tu as une partie marketplace
     * ou un espace vendeur.
     */
    public function testSellerHasRoleSellerAndRoleUser(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_SELLER']);

        $roles = $user->getRoles();

        $this->assertContains('ROLE_SELLER', $roles);
        $this->assertContains('ROLE_USER', $roles);
    }

    /**
     * Vérifie qu'il n'y a pas de doublons dans les rôles.
     *
     * Exemple :
     * Si ROLE_ADMIN est ajouté deux fois, getRoles() doit retourner
     * une liste propre sans doublon.
     */
    public function testRolesAreUnique(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_ADMIN', 'ROLE_ADMIN', 'ROLE_USER']);

        $roles = $user->getRoles();

        $this->assertSame(count($roles), count(array_unique($roles)));
    }
}