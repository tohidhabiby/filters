<?php

namespace Habibi\App\Interfaces;

use Illuminate\Database\Eloquent\Builder;

interface HasIdInterface
{
    const ID = 'id';

    /**
     * @param Builder $builder Builder.
     * @param array   $ids     IDs.
     *
     * @return Builder
     */
    public function scopeWhereIdIn(Builder $builder, array $ids): Builder;
}
