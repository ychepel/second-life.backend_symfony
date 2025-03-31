<?php

namespace App\DataFixtures;

use App\Entity\Location;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class LocationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $locations = [
            ['id' => 1, 'name' => 'Baden-Württemberg'],
            ['id' => 2, 'name' => 'Bayern'],
            ['id' => 3, 'name' => 'Berlin'],
            ['id' => 4, 'name' => 'Brandenburg'],
            ['id' => 5, 'name' => 'Hamburg'],
            ['id' => 6, 'name' => 'Hessen'],
            ['id' => 7, 'name' => 'Mecklenburg-Vorpommern'],
            ['id' => 8, 'name' => 'Niedersachsen'],
            ['id' => 9, 'name' => 'Nordrhein-Westfalen'],
            ['id' => 10, 'name' => 'Rheinland-Pfalz'],
            ['id' => 11, 'name' => 'Sachsen'],
            ['id' => 12, 'name' => 'Sachsen-Anhalt'],
            ['id' => 13, 'name' => 'Schleswig-Holstein'],
            ['id' => 14, 'name' => 'Thüringen']
        ];

        foreach ($locations as $locationData) {
            $location = new Location();
            $location->setId($locationData['id']);
            $location->setName($locationData['name']);
            $manager->persist($location);
        }

        $manager->flush();
    }
}
