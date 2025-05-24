<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use App\Exceptions\ValidationException;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}
    public function register(string $username, string $password): User
    {
        $errors = [];

        if (empty($username)) {
            $errors['username'] = 'Username cannot be empty';
        } elseif (strlen($username) < 4) {
            $errors['username'] = 'Username must be at least 4 characters long';
        }

        if (empty($password)) {
            $errors['password'] = 'Password cannot be empty';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters long';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors, 'User registration failed.');
        }
        if ($this->users->findByUsername($username)) {
            $errors['username'] = 'Username already taken';
            throw new ValidationException($errors, 'User registration failed.');
        }


        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $user = new User(null, $username, $hashedPassword, new \DateTimeImmutable());
        $this->users->save($user);

        return $user;
    }

    public function attempt(string $username, string $password): User
    {
        $user=$this->users->findByUsername($username);
        if(!$user)
            throw new ValidationException([], 'User not found');
        if(!password_verify($password,$user->getPasswordHash()))
            throw new ValidationException([],'Invalid password');
        return $user;
    }
}
