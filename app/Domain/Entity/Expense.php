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

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getDate(): DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(DateTimeImmutable|string $date): void
    {
        if (is_string($date)) {
            $date = new DateTimeImmutable($date);
        }
        $this->date = $date;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): void
    {
        $this->category = $category;
    }

    public function getAmountCents(): int
    {
        return $this->amountCents;
    }

    public function setAmountCents(int $amountCents): void
    {
        $this->amountCents = $amountCents;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }
}