<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use App\Exceptions\ValidationException;
use DateTimeImmutable;
use DI\NotFoundException;
use Psr\Http\Message\UploadedFileInterface;
use PDO;

class ExpenseService
{
    public function __construct(
        private readonly PDO $pdo,
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

    public function validate(DateTimeImmutable $date, string $category, float $amount, string $description): void{
        $errors=[];
        if($date>new \DateTimeImmutable('today'))
            $errors['date']='Date cannot be in the future';
        if($amount<0)
            $errors['amount']='Amount cannot be negative';
        $categoriesString = $_ENV['EXPENSE_CATEGORIES'];
        $categories = array_keys(json_decode($categoriesString, true));
        if(!in_array($category, $categories, true)){
            $errors['category']='Category is invalid. Choose one of:'.implode(',',$categories);
        }
        if(empty($description))
            $errors['description']='Description cannot be empty';
        if (!empty($errors)) {
            throw new ValidationException($errors, 'Add expense failed.');
        }
    }

    public function create(
        int $userId,
        DateTimeImmutable $date,
        string $category,
        float $amount,
        string $description,
    ): void {
        $this->validate($date, $category, $amount, $description);;
        $expense = new Expense(null, $userId, $date, $category, (int)$amount, $description);
        $this->expenses->save($expense);
    }

    public function update(
        Expense $expense,
        DateTimeImmutable $date,
        string $category,
        float $amount,
        string $description
    ): void {
        $this->validate($date, $category, $amount, $description);
        $expense->setAmountCents((int)$amount);
        $expense->setDescription($description);
        $expense->setDate($date);
        $expense->setCategory($category);
        $this->expenses->update($expense);
    }

    public function delete(Expense $expense): void
    {
        $userId=$_SESSION['user_id'];
        if($userId!=$expense->getUserId())
            throw new \UnexpectedValueException('You cannot delete this expense');
        $this->expenses->delete($expense->getId());
    }


    public function importFromCsv(int $userId, UploadedFileInterface $csvFile): int
    {
        $csvContent = $csvFile->getStream()->getContents();
        $csvLines = explode("\n", $csvContent);
        $successCount = 0;

        $this->pdo->beginTransaction();
        foreach ($csvLines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }
            $lineParts = explode(',', $line);
            if (count($lineParts) !== 4) {
                continue;
            }
            try {
                try {
                    $lineParts[0] = str_replace('"', '', $lineParts[0]);
                    $lineParts[2] = str_replace('"', '', $lineParts[2]);
                    $date = new \DateTimeImmutable($lineParts[0]);
                    $amount = (int)trim($lineParts[1]);
                    $description = trim($lineParts[2]);
                    $category = trim($lineParts[3]);
                    $this->validate($date, $category, $amount, $description);
                    $expense = new Expense(null, $userId, $date, $category, $amount, $description);
                }
                catch (ValidationException $e) {
                    continue;
                }
                $this->expenses->save($expense);
                $successCount++;
            } catch (\Exception $e) {
                $this->pdo->rollBack();
                return 0;
            }
        }
        $this->pdo->commit();
        return $successCount;
    }
}
