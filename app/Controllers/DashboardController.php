<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\AlertGenerator;
use App\Domain\Service\ExpenseService;
use App\Domain\Service\MonthlySummaryService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class DashboardController extends BaseController
{
    public function __construct(
        Twig $view,
        private readonly AlertGenerator $alertGenerator,
        private readonly MonthlySummaryService $monthlySummaryService,
        private readonly ExpenseService $expenseService
    )
    {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        $userId = (int) $_SESSION['user_id'];
        $year = (int)($request->getQueryParams()['year'] ?? date('Y'));
        $month = (int)($request->getQueryParams()['month'] ?? date('m'));

        $categoryTotals = $this->monthlySummaryService->computePerCategoryTotals($userId, $year, $month);
        $averages = $this->monthlySummaryService->computePerCategoryAverages($userId, $year, $month);
        $categoryAverages=[];
        foreach ($categoryTotals as $category=>$total){
            $categoryAverages[$category]=[
                'total'=>$total,
                'average'=>$averages[$category] ?? 0,
            ];
        }
        return $this->render($response, 'dashboard.twig', [
            'years' => $this->expenseService->listExpenditureYears($userId),
            'selectedYear'  => $year,
            'month' => $month,
            'alerts'                => [],
            'totalForMonth'         => $this->monthlySummaryService->computeTotal($userId, $year, $month),
            'totalsForCategories'   => $categoryTotals,
            'averagesForCategories' => $categoryAverages
        ]);
    }
}
