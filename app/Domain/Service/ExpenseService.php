<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Exceptions\ValidationException;
use Cassandra\Exception\UnauthorizedException;
use DateTimeImmutable;
use DI\NotFoundException;
use Psr\Http\Message\UploadedFileInterface;

class ExpenseService
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenses,
    ) {}

    public function find(int $id): Expense{
        $expense=$this->expenses->find($id);
        if(!$expense)
            throw new NotFoundException('Expense not found');
        return $expense;
    }

    public function list(int $userId, int $year, int $month, int $pageNumber, int $pageSize): array
    {
        $critera=[
            'user_id'=>$userId,
            'date'=>new \DateTimeImmutable("$year-$month-01")
        ];
        return $this->expenses->findBy($critera, ($pageNumber - 1) * $pageSize, $pageSize);
    }

    public function countBy(int $userId, int $year, int $month): int{
        $critera=[
            'user_id'=>$userId,
            'date'=>new \DateTimeImmutable("$year-$month-01")
        ];
        return $this->expenses->countBy($critera);
    }

    public function create(
        int $userId,
        DateTimeImmutable $date,
        string $category,
        float $amount,
        string $description,
    ): void {

        $errors=[];
        if($date>new \DateTimeImmutable('today'))
            $errors['date']='Date cannot be in the future';
        if($amount<0)
            $errors['amount']='Amount cannot be negative';
        $categoriesString = $_ENV['EXPENSE_CATEGORIES'];
        $categories = json_decode($categoriesString, true);
        if(!in_array($category, $categories, true)){
            $errors['category']='Category is invalid. Choose one of:'.implode(',',$categories);
        }
        if(empty($description))
            $errors['description']='Description cannot be empty';
        if (!empty($errors)) {
            throw new ValidationException($errors, 'Add expense failed.');
        }
        $expense = new Expense(null, $userId, $date, $category, (int)$amount, $description);
        $this->expenses->save($expense);
    }

    public function update(
        Expense $expense,
        float $amount,
        string $description,
        DateTimeImmutable $date,
        string $category,
    ): void {
        // TODO: implement this to update expense entity, perform validation, and persist
    }

    public function delete(Expense $expense): void
    {
        $userId=$_SESSION['user_id'];
        if($userId!=$expense->getUserId())
            throw new \UnexpectedValueException('You cannot delete this expense');
        $this->expenses->delete($expense->getId());
    }


    public function importFromCsv(User $user, UploadedFileInterface $csvFile): int
    {
        // TODO: process rows in file stream, create and persist entities
        // TODO: for extra points wrap the whole import in a transaction and rollback only in case writing to DB fails

        return 0; // number of imported rows
    }
}
