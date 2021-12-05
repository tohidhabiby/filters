<?php

namespace Habibi\Models;

use Habibi\Interfaces\BaseModelInterface;
use Habibi\Interfaces\FiltersInterface;
use Illuminate\Support\Facades\DB;
use Habibi\Traits\HasIdTrait;
use Habibi\Traits\MagicMethodsTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

abstract class BaseModel extends Model implements BaseModelInterface
{
    use HasFactory;
    use HasIdTrait;
    use MagicMethodsTrait;

    /**
     * Filter scope.
     *
     * @param Builder          $builder Builder.
     * @param FiltersInterface $filters Filters.
     *
     * @return Builder
     */
    public function scopeFilter(Builder $builder, FiltersInterface $filters): Builder
    {
        return $filters->apply($builder);
    }

    /**
     * get relation fields.
     *
     * @return array
     */
    public function getAllowedSearchRelations(): array
    {
        return empty($this->allowedSearchRelations) ? [] : $this->allowedSearchRelations;
    }

    /**
     * get translation fields.
     * @return array
     */
    public function getTranslationsFields(): array
    {
        return empty($this->localizable) ? [] : $this->localizable;
    }

    /**
     * get foreignKeys columns.
     *
     * @return array
     */
    public function getForeignKeys(): array
    {
        $allColumns = DB::select(DB::raw('DESCRIBE ' . $this->getTable() . ';'));

        return collect($allColumns)
            ->filter(fn($item) => $item->Key == 'MUL')
            ->pluck('Field')
            ->toArray();
    }
}
