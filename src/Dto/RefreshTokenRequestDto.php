<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class RefreshTokenRequestDto
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 1)]
    private string $refreshToken;

    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }
}
