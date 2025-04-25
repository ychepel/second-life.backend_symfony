<?php

namespace App\Service;

use App\Dto\UserRegistrationRequestDto;
use App\Dto\UserResponseDto;
use App\Entity\User;
use App\Enum\UserRole;
use App\Mapper\UserMappingService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly ImageService $imageService,
        private readonly UserMappingService $userMappingService
    )
    {}

    public function createUser(UserRegistrationRequestDto $request): UserResponseDto
    {
        $user = $this->entityManager->wrapInTransaction(function (EntityManagerInterface $em) use ($request) {
            $newUser = new User();
            $newUser->setEmail($request->getEmail())
                ->setFirstName($request->getFirstName())
                ->setLastName($request->getLastName())
                ->setRole(UserRole::ROLE_USER)
                ->setIsActive(true);

            $hashedPassword = $this->passwordHasher->hashPassword($newUser, $request->getPassword());
            $newUser->setPassword($hashedPassword);

            $em->persist($newUser);

            if (!empty($request->getBaseNameOfImages())) {
                $em->flush();
                $images = $this->imageService->attachImages('user', $newUser->getId(), $request->getBaseNameOfImages());
                $newUser->setImages($images);
            }

            return $newUser;
        });

        return $this->userMappingService->toDto($user);
    }


}