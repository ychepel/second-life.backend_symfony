<?php

namespace App\Controller;

use App\Dto\UserRegistrationRequest;
use App\Dto\UserRegistrationResponse;
use App\Entity\Image;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsController]
class UserRegistrationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly TranslatorInterface $translator,
        private readonly SerializerInterface $serializer,
        private readonly LoggerInterface $logger
    ) {}

    #[Route(path: 'api/v1/users/register', name: 'register', methods: ['POST'])]
    public function register(#[MapRequestPayload] UserRegistrationRequest $request): Response
    {
        $this->logger->debug("Email: {$request->getEmail()}");
        // Check if email already exists
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $request->getEmail()]);

        if ($existingUser) {
            return new Response(
                $this->translator->trans('user.email_already_exists'),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        // Create new user
        $user = new User();
        $user->setEmail($request->getEmail())
            ->setFirstName($request->getFirstName())
            ->setLastName($request->getLastName())
            ->setCreatedAt(new \DateTime())
            ->setUpdatedAt(new \DateTime())
            ->setIsActive(true);

        // Hash and set password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $request->getPassword());
        $user->setPassword($hashedPassword);

        // Save user
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Create images
        $images = $this->createImages($request->getBaseNameOfImages(), $user->getId());

        // Prepare response
        $response = new UserRegistrationResponse();
        $response->setId($user->getId())
            ->setFirstName($user->getFirstName())
            ->setLastName($user->getLastName())
            ->setEmail($user->getEmail())
            ->setCreatedAt($user->getCreatedAt())
            ->setLocationId($user->getLocation()?->getId())
            ->setLastActive($user->getCreatedAt())
            ->setImages($images);

        return new JsonResponse(
            $this->serializer->serialize($response, 'json', ['groups' => 'user:read']),
            Response::HTTP_CREATED,
            [],
            true
        );
    }

    private function createImages(array $baseNames, int $userId): array
    {
        foreach ($baseNames as $baseName) {
            $image = new Image();
            $image->setEntityId($userId)
                ->setEntityType('user')
                ->setBaseName($baseName)
                ->setCreatedAt(new \DateTime());

            // Set different sizes
            $sizes = ['1024x1024', '320x320', '64x64'];
            foreach ($sizes as $size) {
                $image->setSize($size);
                $image->setFullPath(sprintf(
                    'https://domain.com/prod/user/%d/%s_%s.jpg',
                    $userId,
                    $size,
                    $baseName
                ));

                $this->entityManager->persist($image);
            }
        }

        $this->entityManager->flush();

        // Prepare response format
        $responseImages = [];
        foreach ($baseNames as $baseName) {
            $responseImages[$baseName] = [
                '1024x1024' => sprintf('https://domain.com/prod/user/%d/1024x1024_%s.jpg', $userId, $baseName),
                '320x320' => sprintf('https://domain.com/prod/user/%d/320x320_%s.jpg', $userId, $baseName),
                '64x64' => sprintf('https://domain.com/prod/user/%d/64x64_%s.jpg', $userId, $baseName)
            ];
        }

        return ['values' => $responseImages];
    }
}
