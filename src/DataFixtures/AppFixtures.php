<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $categories = [];

        for ($i = 0; $i < 5; $i++) {
            $category = new Category();
            $category->setNom($faker->word());

            $manager->persist($category);
            $categories[] = $category;
        }

        for ($i = 0; $i < 50; $i++) {
            $randomKey = array_rand($categories);

            $product = new Product();
            $product
                ->setNom($faker->word())
                ->setDescription($faker->sentence())
                ->setSpecification($faker->sentence())
                ->setPrix($faker->numberBetween(40, 600))
                ->setALaUne($faker->boolean())
                ->setCreatedAt(
                    \DateTimeImmutable::createFromMutable(
                        $faker->dateTimeBetween('-6 months', 'now')
                    )
                )
                ->setCategory($categories[$randomKey]);

            $manager->persist($product);
        }
            // creation d utilisateurs
            // ADMIN
            $admin = new User();
            $admin->setEmail('admin@innovshop.fr');
            $admin->setRoles(['ROLE_ADMIN']);
            $admin->setPassword(
                $this->passwordHasher->hashPassword($admin, '123456')
        );

        $manager->persist($admin);

            // USER 1
            $user1 = new User();
            $user1->setEmail('user1@innovshop.fr');
            $user1->setRoles(['ROLE_USER']);
            $user1->setPassword(
                $this->passwordHasher->hashPassword($user1, '123456')
        );

        $manager->persist($user1);

            // USER 2
            $user2 = new User();
            $user2->setEmail('user2@innovshop.fr');
            $user2->setRoles(['ROLE_USER']);
            $user2->setPassword(
                $this->passwordHasher->hashPassword($user2, '123456')
        );

        $manager->persist($user2);

        $manager->flush();
    }
}