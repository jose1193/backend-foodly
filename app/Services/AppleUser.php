<?php

namespace App\Services;

class AppleUser
{
    protected $userData;

    public function __construct(array $userData)
    {
        $this->userData = $userData;
    }

    public function getId()
    {
        return $this->userData['sub'] ?? null;
    }

    public function getEmail()
    {
        return $this->userData['email'] ?? null;
    }

    public function getName()
    {
        return $this->userData['name'] ?? $this->getEmail() ?? 'Apple User';
    }

    public function getAvatar()
    {
        return null; // Apple no provee avatar
    }

    public function getNickname()
    {
        return $this->getEmail();
    }
}