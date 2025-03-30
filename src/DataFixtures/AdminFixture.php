<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\UserRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AdminFixture extends Fixture
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {}

    public function load(ObjectManager $manager): void
    {
        $user = new User();
        $user->setEmail('admin@email.com');
        $user->setFirstName('AdminName');
        $user->setLastName('AdminLastName');
        $user->setPassword($this->passwordHasher->hashPassword($user, 'Security!234'));
        $user->setRole(UserRole::ADMIN);
        $user->setCreatedAt(new \DateTime());
        $user->setUpdatedAt(new \DateTime());
        $user->setIsActive(true);

        $manager->persist($user);
        $manager->flush();
    }
}