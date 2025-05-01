<?php

namespace App\DataFixtures;

use App\Entity\RejectionReason;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RejectionReasonFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $reasons = [
            'Incorrect title',
            'Incorrect description',
            'Incorrect images',
            'Incorrect category',
        ];

        foreach ($reasons as $reason) {
            $rejectionReason = new RejectionReason();
            $rejectionReason->setName($reason);
            $manager->persist($rejectionReason);
        }

        $manager->flush();
    }
}
