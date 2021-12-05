<?php

namespace Habibi\Filters;

use Illuminate\Database\Eloquent\Builder;

class UserFilter extends Filters
{
    /**
     * Registered filters to operate upon.
     *
     * @var array
     */
    protected array $filters = [
        'email',
    ];

    /**
     * Registered filters to operate upon.
     *
     * @var array
     */
    public array $attributes = [
        'email' => 'string',
    ];

    /**
     * @param string $email Email.
     *
     * @return Builder
     */
    protected function email(string $email): Builder
    {
        return $this->builder->whereEmail($email);
    }
}
