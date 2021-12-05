<?php

namespace Habibi\App\Interfaces;

use Illuminate\Database\Eloquent\Builder;

interface FiltersInterface
{
    /**
     * Apply the filters.
     *
     * @param  Builder $builder Builder.
     *
     * @return Builder
     */
    public function apply(Builder $builder): Builder;

    /**
     * Fetch all relevant filters from the request.
     *
     * @return array
     */
    public function getFilters();
}
