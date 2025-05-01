<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class CategoryRequestDto implements \Stringable
{
    public function __construct(#[Assert\All([
        new Assert\NotBlank(),
        new Assert\Uuid(),
    ])]
    private array $baseNameOfImages, #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 64)]
    private string $name, #[Assert\Length(min: 0, max: 1000)]
    private string $description)
    {
    }

    public function getBaseNameOfImages(): array
    {
        return $this->baseNameOfImages;
    }

    public function setBaseNameOfImages(array $baseNameOfImages): void
    {
        $this->baseNameOfImages = $baseNameOfImages;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function __toString(): string
    {
        return sprintf('CategoryRequestDto { baseNameOfImages: %s, name: %s, description: %s }',
            implode(', ', $this->baseNameOfImages),
            $this->name,
            $this->description
        );
    }
}
