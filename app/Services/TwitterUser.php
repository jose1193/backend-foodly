<?php

namespace App\Services;

class TwitterUser
{
    protected $userData;

    public function __construct(array $userData)
    {
        $this->userData = $userData;
    }

    public function getId()
    {
        return $this->userData['data']['id'] ?? null;
    }

    public function getEmail()
    {
        return $this->userData['data']['email'] ?? 'no-email@twitter.com';
    }

    public function getName()
    {
        return $this->userData['data']['name'] ?? 'Twitter User';
    }

    public function getAvatar()
    {
        return $this->userData['data']['profile_image_url'] ?? null;
    }

    public function getNickname()
    {
        return $this->userData['data']['username'] ?? null;
    }
}