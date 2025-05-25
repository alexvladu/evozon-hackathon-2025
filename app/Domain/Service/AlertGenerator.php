<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;

class AlertGenerator
{
    public function generate(User $user, int $year, int $month): array
    {
        // TODO: implement this to generate alerts for overspending by category

        return [];
    }
}
