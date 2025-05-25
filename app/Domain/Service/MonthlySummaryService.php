<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;

class MonthlySummaryService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function computeTotal(int $userId, int $year, int $month):float
    {
        $criteria['user_id']=$userId;
        $criteria['year']=$year;
        $criteria['month']=$month;
        return $this->expenses->sumAmounts($criteria);
    }
    public function computePerCategoryTotals(int $userId, int $year, int $month): array
    {
        $criteria['user_id']=$userId;
        $criteria['year']=$year;
        $criteria['month']=$month;
        return $this->expenses->sumAmountsByCategory($criteria);
    }

    public function computePerCategoryAverages($userId, int $year, int $month): array
    {
        $criteria['user_id']=$userId;
        $criteria['year']=$year;
        $criteria['month']=$month;
        return $this->expenses->averageAmountsByCategory($criteria);
    }
}
