<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\Expense;
use App\Domain\Entity\User;
use App\Domain\Repository\ExpenseRepositoryInterface;
use DateTimeImmutable;
use Exception;
use PDO;

class PdoExpenseRepository implements ExpenseRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * @throws Exception
     */
    public function find(int $id): ?Expense
    {
        $query = 'SELECT * FROM expenses WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $id]);
        $data = $statement->fetch();
        if (false === $data) {
            return null;
        }

        return $this->createExpenseFromData($data);
    }

    public function save(Expense $expense): void
    {
        $query = 'INSERT INTO expenses (user_id, date, category, amount_cents, description) VALUES (:user_id, :date, :category, :amount_cents, :description)';
        $statement = $this->pdo->prepare($query);
        $statement->execute([
            'user_id' => $expense->getUserId(),
            'date' => $expense->getDate()->format('Y-m-d'),
            'category' => $expense->getCategory(),
            'amount_cents' => $expense->getAmountCents(),
            'description' => $expense->getDescription(),
        ]);
    }

    public function update(Expense $expense): void
    {
        $query = 'UPDATE expenses SET user_id = :user_id, date = :date, category = :category, amount_cents = :amount_cents, description = :description WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute([
            'id' => $expense->getId(),
            'user_id' => $expense->getUserId(),
            'date' => $expense->getDate()->format('Y-m-d'),
            'category' => $expense->getCategory(),
            'amount_cents' => $expense->getAmountCents(),
            'description' => $expense->getDescription()
        ]);
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM expenses WHERE id=?');
        $statement->execute([$id]);
    }

    private function executeByCritera($criteria, bool $paginate=false, int $from=0, int $limit=100000): array
    {
        $query = 'SELECT * FROM expenses';
        $params = [];
        $conditions = [];

        foreach ($criteria as $key => $value) {
            if ($key === 'date') {
                $date = $value instanceof DateTimeImmutable ? $value : new DateTimeImmutable($value);

                $startOfMonth = $date->modify('first day of this month')->setTime(0, 0, 0);
                $endOfMonth = $date->modify('last day of this month')->setTime(23, 59, 59);

                $conditions[] = 'date BETWEEN :start_date AND :end_date';
                $params['start_date'] = $startOfMonth->format('Y-m-d');
                $params['end_date'] = $endOfMonth->format('Y-m-d');
            }else{
                $conditions[] = "$key = :$key";
                $params[$key] = $value;
            }
        }
        $query .= ' WHERE ' . implode(' AND ', $conditions);

        $query .= ' ORDER BY date DESC LIMIT :limit OFFSET :from';;
        $params['from'] = $from;
        $params['limit'] = $limit;

        $statement = $this->pdo->prepare($query);

        // Bind parameters
        foreach ($params as $key => $value) {
            if ($key === 'from' || $key === 'limit') {
                $statement->bindValue(":$key", $value, PDO::PARAM_INT);
            } elseif ($key === 'date' && $value instanceof DateTimeImmutable) {
                $statement->bindValue(":$key", $value->format('Y-m-d'), PDO::PARAM_STR);
            } else {
                $statement->bindValue(":$key", $value);
            }
        }

        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_ASSOC);


        $expenses = [];
        foreach ($results as $row) {
            $expenses[] = $this->createExpenseFromData($row);
        }
        return $expenses;

    }

    public function findBy(array $criteria, int $from, int $limit): array
    {
        return $this->executeByCritera($criteria, true, $from, $limit);
    }

    public function countBy(array $criteria): int
    {
        return count($this->executeByCritera($criteria));
    }

    public function listExpenditureYears($userId): array
    {
        $query = 'SELECT DISTINCT strftime(\'%Y\', date) as year FROM expenses WHERE user_id = :user_id ORDER BY year DESC';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['user_id' => $userId]);
        return $statement->fetchAll(PDO::FETCH_COLUMN);
    }

    public function sumAmountsByCategory(array $criteria): array
    {
        $userId = $criteria['user_id'];
        $month = $criteria['month'];
        $year = $criteria['year'];

        $startDate = sprintf('%d-%02d-01', $year, $month);
        $endDate = sprintf('%d-%02d-31', $year, $month);

        $query = 'SELECT category, SUM(amount_cents) as total FROM expenses 
                 WHERE user_id = :user_id 
                 AND date BETWEEN :start_date AND :end_date
                 GROUP BY category
                 ORDER BY total DESC';

        $statement = $this->pdo->prepare($query);
        $statement->execute([
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        return $statement->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function averageAmountsByCategory(array $criteria): array
    {
        $userId = $criteria['user_id'];
        $month = $criteria['month'];
        $year = $criteria['year'];

        $startDate = sprintf('%d-%02d-01', $year, $month);
        $endDate = sprintf('%d-%02d-31', $year, $month);

        $query = 'SELECT category, AVG(amount_cents) as average FROM expenses 
                WHERE user_id = :user_id
                AND date BETWEEN :start_date AND :end_date
                GROUP BY category
                ORDER BY average DESC';

        $statement = $this->pdo->prepare($query);
        $statement->execute([
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        return $statement->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    public function sumAmounts(array $criteria): float
    {
        $userId = $criteria['user_id'];
        $month = $criteria['month'];
        $year = $criteria['year'];

        $startDate = sprintf('%d-%02d-01', $year, $month);
        $endDate = sprintf('%d-%02d-31', $year, $month);

        $query = 'SELECT SUM(amount_cents) as total FROM expenses WHERE user_id = :user_id AND date BETWEEN :start_date AND :end_date';
        $statement = $this->pdo->prepare($query);
        $statement->execute([
            'user_id' => $userId,
            'start_date' => $startDate,
            'end_date' => $endDate
        ]);

        $result = $statement->fetch(PDO::FETCH_ASSOC);
        return (float)($result['total'] ?? 0);
    }

    /**
     * @throws Exception
     */
    private function createExpenseFromData(mixed $data): Expense
    {
        return new Expense(
            $data['id'],
            $data['user_id'],
            new DateTimeImmutable($data['date']),
            $data['category'],
            $data['amount_cents'],
            $data['description'],
        );
    }
}
