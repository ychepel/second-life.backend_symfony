<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;
use App\Validator as AppAssert;

class UserRegistrationRequestDto
{
    #[Assert\All([
        new Assert\NotBlank(),
//        new Assert\Uuid()
    ])]
    private array $baseNameOfImages = [];

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private string $firstName;

    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 100)]
    private string $lastName;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[AppAssert\UniqueUserEmail]
    private string $email;

    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    #[Assert\Regex(pattern: "/^(?=.*[A-Za-z])(?=.*\d)(?=.*[@#$%^&+=!])(?=\S+$).{8,}$/")]
    private string $password;

    public function __construct(
        array  $baseNameOfImages = [],
        string $firstName = '',
        string $lastName = '',
        string $email = '',
        string $password = ''
    )
    {
        $this->setBaseNameOfImages($baseNameOfImages);
        $this->setFirstName($firstName);
        $this->setLastName($lastName);
        $this->setEmail($email);
        $this->setPassword($password);
    }

    public function getBaseNameOfImages(): array
    {
        return $this->baseNameOfImages;
    }

    public function setBaseNameOfImages(array $baseNameOfImages): self
    {
        $this->baseNameOfImages = $baseNameOfImages;
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

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('UserRegistrationRequestDto { baseNameOfImages: %s, firstName: %s, lastName: %s, email: %s }',
            implode(', ', $this->baseNameOfImages),
            $this->firstName,
            $this->lastName,
            $this->email
        );
    }


}
