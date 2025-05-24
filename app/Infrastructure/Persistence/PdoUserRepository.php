<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Exception;
use PDO;

class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}


    /**
     * Find the user with specific id.
     * @param mixed $id
     * @return User|null
     * @throws Exception
     */
    public function find(mixed $id): ?User
    {
        $query = 'SELECT * FROM users WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $id]);
        $data = $statement->fetch();
        if (false === $data) {
            return null;
        }

        return new User(
            $data['id'],
            $data['username'],
            $data['password_hash'],
            new DateTimeImmutable($data['created_at']),
        );
    }

    /**
     * Return user with specific username
     * @param string $username
     * @return User|null
     * @throws Exception
     */
    public function findByUsername(string $username): ?User
    {
        $query= 'SELECT * FROM users WHERE username = :username';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute(['username' => $username]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($data === false) {
            return null;
        }

        return new User(
            $data['id'],
            $data['username'],
            $data['password_hash'],
            new \DateTimeImmutable($data['created_at'])
        );
    }


    public function save(User $user): void
    {
        $query = 'INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)';
        $stmt = $this->pdo->prepare($query);
        $stmt->execute([
            'username' => $user->getUsername(),
            'password_hash' => $user->getPasswordHash()
        ]);
    }
}
