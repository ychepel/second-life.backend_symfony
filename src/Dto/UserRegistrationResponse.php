<?php

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;

class UserRegistrationResponse
{
    #[Groups(['user:read'])]
    private int $id;

    #[Groups(['user:read'])]
    private string $firstName;

    #[Groups(['user:read'])]
    private string $lastName;

    #[Groups(['user:read'])]
    private string $email;

    #[Groups(['user:read'])]
    private \DateTime $createdAt;

    #[Groups(['user:read'])]
    private ?int $locationId;

    #[Groups(['user:read'])]
    private \DateTime $lastActive;

    #[Groups(['user:read'])]
    private array $images;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getLocationId(): ?int
    {
        return $this->locationId;
    }

    public function setLocationId(?int $locationId): self
    {
        $this->locationId = $locationId;
        return $this;
    }

    public function getLastActive(): \DateTime
    {
        return $this->lastActive;
    }

    public function setLastActive(\DateTime $lastActive): self
    {
        $this->lastActive = $lastActive;
        return $this;
    }

    public function getImages(): array
    {
        return $this->images;
    }

    public function setImages(array $images): self
    {
        $this->images = $images;
        return $this;
    }
}
