<?php

declare(strict_types=1);

namespace App\Domain\Service;


class AlertGenerator
{

    public function __construct(
        private readonly MonthlySummaryService $monthlySummaryService,
    ) {}
    public function generate($userId, int $year, int $month): array
    {
        $categoriesString = $_ENV['EXPENSE_CATEGORIES'];
        $categoriesKeys = array_keys(json_decode($categoriesString, true));
        $categories = json_decode($categoriesString, true);
        $categoryTotals = $this->monthlySummaryService->computePerCategoryTotals($userId, $year, $month);
        $alerts = [];
        foreach ($categoriesKeys as $category) {
            if(!isset($categoryTotals[$category]))
                continue;
            if ($categories[$category] * 100 < $categoryTotals[$category]) {
                $alerts[$category] = [
                    'category' => $category,
                    'excess' => $categoryTotals[$category] - $categories[$category] * 100,
                ];
            }
        }
        return $alerts;
    }
}
