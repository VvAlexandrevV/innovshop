<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
       $faker = Factory::create('fr_FR');

       $categories = [];
        for ($i = 0; $i < 5; $i++) {
            $category = new Category();
            $category
                ->setNom($faker->word());

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
            ->setCreatedAt(\DateTimeImmutable::createFromMutable(
                $faker->dateTimeBetween('-6 months', 'now')
            ))
            ->setCategory($categories[$randomKey]);

        $manager->persist($product);
}
             

        $manager->flush();
    }
}
