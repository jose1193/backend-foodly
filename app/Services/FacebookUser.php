<?php
namespace App\Services;
use App\Models\User;


class FacebookUser
{
    private $userData;

    public function __construct($userData)
    {
        $this->userData = $userData;
    }

    public function getId()
    {
        return $this->userData['id'] ?? null;
    }

    public function getName()
    {
        return $this->userData['name'] ?? null;
    }
    
    public function getFirstName()
    {
        return $this->userData['first_name'] ?? null;
    }

    public function getLastName()
    {
        return $this->userData['last_name'] ?? null;
    }
    public function getEmail()
    {
        return $this->userData['email'] ?? null;
    }

    public function getAvatar()
    {
        return $this->userData['picture']['data']['url'] ?? null;
    }

    public function getNickname()
{
    do {
        $nickname = $this->generateNickname();
    } while (User::where('username', $nickname)->exists());

    return $nickname;
}

private function generateNickname()
{
    // Obtén el nombre completo del usuario
    $fullName = strtolower(trim($this->userData['name'] ?? ''));

    // Divide el nombre completo en partes (suponiendo que el primer nombre es la primera parte)
    $nameParts = explode(' ', $fullName);

    // Usa el primer nombre como base para el nombre de usuario
    $firstName = $nameParts[0] ?? '';

    // Reemplaza caracteres especiales en el primer nombre por guiones bajos
    $firstName = preg_replace('/[^a-z0-9]+/', '_', $firstName);

    // Asegúrate de que el nombre de usuario no tenga guiones bajos al principio o al final
    $firstName = trim($firstName, '_');

    // Genera un número aleatorio de cuatro dígitos
    $randomNumber = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);

    return "{$firstName}_{$randomNumber}";
}




    public function getBirthday()
    {
        return $this->userData['birthday'] ?? null;
    }

    public function getGender()
    {
        return $this->userData['gender'] ?? null;
    }

    
}
