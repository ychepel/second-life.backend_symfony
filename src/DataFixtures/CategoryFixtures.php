<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CategoryFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            [
                'name' => 'Electronics and Gadgets',
                'description' => 'Smartphones,Laptops,Televisions,Peripherals'
            ],
            [
                'name' => 'Furniture and Home Decor',
                'description' => 'Sofas,Tables and Chairs,Cabinets and Shelves,Decor and Accessories'
            ],
            [
                'name' => 'Clothing and Accessories',
                'description' => 'Men\'s Clothing,Women\'s Clothing,Footwear,Bags and Backpacks'
            ],
            [
                'name' => 'Vehicles',
                'description' => 'Cars,Motorcycles and Scooters,Bicycles,Auto Parts'
            ],
            [
                'name' => 'Kids\' Items',
                'description' => 'Toys,Children\'s Clothing,Strollers,Educational Materials'
            ],
            [
                'name' => 'Sports and Leisure',
                'description' => 'Sports Equipment,Camping Gear,Bicycles,Fishing Gear'
            ],
            [
                'name' => 'Home Appliances',
                'description' => 'Kitchen Appliances,Washing Machines,Vacuum Cleaners,Air Conditioners'
            ],
            [
                'name' => 'Hobbies and Interests',
                'description' => 'Musical Instruments,Books and Magazines,Collectibles,Board Games'
            ],
            [
                'name' => 'Garden and Outdoor',
                'description' => 'Garden Tools,Plants and Seeds,Garden Furniture,Watering Equipment'
            ],
            [
                'name' => 'Pets and Supplies',
                'description' => 'Pets,Pet Food and Accessories,Cages and Aquariums,Pet Services'
            ]
        ];

        foreach ($categories as $categoryData) {
            $category = new Category();
            $category->setName($categoryData['name']);
            $category->setDescription($categoryData['description']);
            $manager->persist($category);
        }

        $manager->flush();
    }
}
