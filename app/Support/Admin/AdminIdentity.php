<?php

declare(strict_types=1);

namespace App\Support\Admin;

final readonly class AdminIdentity
{
    public function __construct(
        public int $id,
        public string $username,
        public ?AdminRole $role,
    ) {}
}
