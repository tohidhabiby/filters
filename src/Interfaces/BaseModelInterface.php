<?php

namespace Habibi\Interfaces;

use Illuminate\Database\Eloquent\Builder;

interface BaseModelInterface extends HasIdInterface
{
    /**
     * Filter scope.
     *
     * @param Builder          $builder Builder.
     * @param FiltersInterface $filters Filters.
     *
     * @return Builder
     */
    public function scopeFilter(Builder $builder, FiltersInterface $filters): Builder;

    /**
     * get relation fields.
     *
     * @return array
     */
    public function getAllowedSearchRelations(): array;

    /**
     * get translation fields.
     * @return array
     */
    public function getTranslationsFields(): array;

    /**
     * get foreignKeys columns.
     *
     * @return array
     */
    public function getForeignKeys(): array;
}
