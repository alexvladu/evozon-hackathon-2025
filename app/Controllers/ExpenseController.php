<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\Service\ExpenseService;
use App\Exceptions\ValidationException;
use DI\NotFoundException;
use PHPUnit\Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ExpenseController extends BaseController
{
    private const PAGE_SIZE = 5;

    public function __construct(
        Twig $view,
        private readonly ExpenseService $expenseService,
    ) {
        parent::__construct($view);
    }

    public function index(Request $request, Response $response): Response
    {
        $userId = (int) $_SESSION['user_id'];
        $year = (int)($request->getQueryParams()['year'] ?? date('Y'));
        $month = (int)($request->getQueryParams()['month'] ?? date('m'));
        $page = (int)($request->getQueryParams()['page'] ?? 1);
        $pageSize = (int)($request->getQueryParams()['pageSize'] ?? self::PAGE_SIZE);
        $expenses = $this->expenseService->list($userId, $year, $month, $page, $pageSize);
        $total = $this->expenseService->countBy($userId, $year, $month);
        return $this->render($response, 'expenses/index.twig', [
            'total'    => $total,
            'expenses' => $expenses,
            'page'     => $page,
            'pageSize' => $pageSize,
        ]);
    }

    public function create(Request $request, Response $response): Response
    {
        $categoriesString = $_ENV['EXPENSE_CATEGORIES'];
        $categories = json_decode($categoriesString, true);
        return $this->render($response, 'expenses/create.twig', [
            'categories' => $categories,
            'defaultDate' => (new \DateTimeImmutable('today'))->format('Y-m-d'),
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $categoriesString = $_ENV['EXPENSE_CATEGORIES'];
        $categories = json_decode($categoriesString, true);
        try{
            $userId=$_SESSION['user_id'];
            $date = new \DateTimeImmutable($request->getParsedBody()['date']);
            $category = $request->getParsedBody()['category'];
            $amount = (float) $request->getParsedBody()['amount']*100;
            $description = $request->getParsedBody()['description'];
            $this->expenseService->create($userId, $date, $category, $amount, $description);
        }
        catch (ValidationException $e)
        {
            $errors=$e->getErrors();
            return $this->render($response, 'expenses/create.twig', [
                'errors' => $errors,
                'categories' => $categories,
                'defaultDate' => (new \DateTimeImmutable('today'))->format('Y-m-d'),
            ]);
        }
        catch (\Exception $e){
            $errors['general']=$e->getMessage();
            return $this->render($response, 'expenses/create.twig', [
                'errors' => $errors,
                'categories' => $categories,
                'defaultDate' => (new \DateTimeImmutable('today'))->format('Y-m-d'),
            ]);
        }
        return $response->withHeader('Location', '/expenses')->withStatus(302);
    }

    public function edit(Request $request, Response $response, array $routeParams): Response
    {
        $categoriesString = $_ENV['EXPENSE_CATEGORIES'];
        $categories = json_decode($categoriesString, true);
        try {
            $expenseId = (int)$routeParams['id'];
            $expense = $this->expenseService->find($expenseId);
            return $this->render($response, 'expenses/edit.twig', ['expense' => [
                'id' => $expense->getId(),
                'userId' => $expense->getUserId(),
                'date' => $expense->getDate()->format('Y-m-d'),
                'category' => $expense->getCategory(),
                'amountCents' => $expense->getAmountCents(),
                'description' => $expense->getDescription(),
            ],
            'categories' => $categories]);
        }
        catch (NotFoundException $e){
            return $response->withHeader('Location', '/expenses')->withStatus(404);
        }
        catch (\UnexpectedValueException $e){
            return $response->withHeader('Location', '/expenses')->withStatus(403);
        }
    }

    public function update(Request $request, Response $response, array $routeParams): Response
    {
        try{
            $expenseId = (int) $routeParams['id'];
            $expense = $this->expenseService->find($expenseId);
            $date = new \DateTimeImmutable($request->getParsedBody()['date']);
            $category = $request->getParsedBody()['category'];
            $amount = (float) $request->getParsedBody()['amount']*100;
            $description = $request->getParsedBody()['description'];
            $this->expenseService->update($expense, $date, $category, $amount, $description);
            return $this->index($request, $response);
        }
        catch (NotFoundException $e){

        }
        // Hints:
        // - load the expense to be edited by its ID (use route params to get it)
        // - check that the logged-in user is the owner of the edited expense, and fail with 403 if not
        // - get the new values from the request and prepare for update
        // - update the expense entity with the new values
        // - rerender the "expenses.edit" page with included errors in case of failure
        // - redirect to the "expenses.index" page in case of success

        return $response;
    }

    public function destroy(Request $request, Response $response, array $routeParams): Response
    {
        try{
            $expenseId = (int) $routeParams['id'];
            $expense = $this->expenseService->find($expenseId);
            $this->expenseService->delete($expense);
            return $this->index($request, $response);
        }
        catch (NotFoundException $e){
            return $response->withStatus(404);
        }
        catch (\UnexpectedValueException $e){
            return $response->withStatus(403);
        }
    }
}
