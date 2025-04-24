<?php

namespace App\Dto;

class LoginResponseDto
{
    private int $clientId;
    private string $accessToken;
    private string $refreshToken;

    /**
     * @param int $clientId
     * @param string $accessToken
     * @param string $refreshToken
     */
    public function __construct(int $clientId, string $accessToken, string $refreshToken)
    {
        $this->clientId = $clientId;
        $this->accessToken = $accessToken;
        $this->refreshToken = $refreshToken;
    }

    public function getClientId(): int
    {
        return $this->clientId;
    }

    public function setClientId(int $clientId): self
    {
        $this->clientId = $clientId;
        return $this;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;
        return $this;
    }

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
