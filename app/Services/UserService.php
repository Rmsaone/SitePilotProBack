<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    public function list(): LengthAwarePaginator
    {
        return User::query()
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(100);
    }
}
