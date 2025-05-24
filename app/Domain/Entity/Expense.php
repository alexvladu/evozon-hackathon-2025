<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use DateTimeImmutable;

final class Expense
{
    public function __construct(
        private ?int $id,
        private int $userId,
        private DateTimeImmutable $date,
        private string $category,
        private int $amountCents,
        private string $description,
    ) {}

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getAmountCents(): int
    {
        return $this->amountCents;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}