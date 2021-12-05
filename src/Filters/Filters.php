<?php

namespace Habibi\Filters;

use Habibi\Interfaces\FiltersInterface;
use Habibi\Models\BaseModel;
use Habibi\Models\LocalizableModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

abstract class Filters implements FiltersInterface
{
    /**
     * @var Request
     */
    protected Request $request;

    /**
     * The Eloquent builder.
     *
     * @var Builder
     */
    protected Builder $builder;

    /**
     * Registered filters to operate upon.
     *
     * @var array
     */
    protected array $filters = [];

    /**
     * @var array
     */
    public array $attributes = [];

    /**
     * @var array
     */
    public array $orderByColumns = [];

    /**
     * Create a new ThreadFilters instance.
     *
     * @param Request $request BaseRequest.
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * Apply the filters.
     *
     * @param Builder $builder Builder.
     *
     * @return Builder
     */
    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->getFilters() as $filter => $value) {
            if (method_exists($this, $filter)) {
                $type = $this->attributes[$filter];
                if ($type == 'boolean') {
                    $value = $value === 'true';
                } elseif ($type == 'array' && is_array($value) && (empty($value) || empty(array_keys($value)))) {
                    continue;
                }
                settype($value, $type);
                $this->$filter($value);
            }
        }

        if ($this->request->filled('orderBy')) {
            $this->orderBy($this->request->orderBy);
        }

        if ($this->request->filled('search')) {
            $this->searchOnAllColumns($this->request->search, $builder->getModel());
        }

        return $this->builder;
    }

    /**
     * Fetch all relevant filters from the request.
     *
     * @return array
     */
    public function getFilters()
    {
        return array_filter($this->request->only($this->filters), function ($item) {
            return !is_null($item);
        });
    }

    /**
     * Order the query by givens orders
     *
     * @param array|string $orders Orders.
     *
     * @return Builder
     */
    protected function orderBy($orders)
    {
        if (!is_array($orders)) {
            $orders = json_decode($orders, true);
        }
        return $this->builder->when(!empty($orders), function ($query) use ($orders) {
            foreach ($orders as $key => $order) {
                if (!in_array($key, $this->orderByColumns)) {
                    continue;
                }

                if (Str::contains($key, '.')) {
                    $query = $this->makeOrderByRelation($query, $key, $order);
                } else {
                    $query->orderBy($key, $order);
                }
            }
        });
    }

    /**
     * @param Builder $query Query.
     * @param string  $key   Key.
     * @param string  $order Order.
     *
     * @return mixed
     */
    private function makeOrderByRelation(Builder $query, string $key, string $order): Builder
    {
        /** @var $model */
        $model = $this->builder->getModel();

        [$relation, $key] = explode('.', $key);

        /** Check if relation exists in Main Model */
        if (!method_exists($model, $relation)) {
            return $query;
        }

        /** @var $relationModel */
        $relationModel = $model->{$relation}();

        if ($relationModel instanceof HasMany) {
            $relationWhereQuery = "{$relationModel->getForeignKeyName()} = " . $model::TABLE . '.' . $model::ID;
        } elseif ($relationModel instanceof BelongsTo or $relationModel instanceof HasOne) {
            $relationWhereQuery = 'id = ' . $model::TABLE . '.' . $relation . '_id';
        } else {
            return $query;
        }

        $query->select('*')
            ->selectSub(
                $relationModel->getModel()::whereRaw($relationWhereQuery)
                    ->selectRaw("$key as {$relation}_{$key}")
                    ->when(($relation == 'translations'), function (Builder $builder) {
                        return $builder->whereLocale(app()->getLocale());
                    })
                    ->limit(1)
                    ->getQuery(),
                $relation . '_' . $key
            );

        return $query->orderBy($relation . '_' . $key, $order);
    }

    /**
     * search on all model fields
     * @param string    $search String to search.
     * @param BaseModel $model  Base Model.
     *
     * @return void
     */
    private function searchOnAllColumns(string $search, BaseModel $model): void
    {
        $this->builder->where(function ($query) use ($search, $model) {
            $allColumns = Schema::getColumnListing($model->getTable());
            $notAllowedFields = $model->getForeignKeys();
            $booleanFields = array_filter($model->getCasts(), fn($type) => $type == 'bool' || $type == 'boolean');
            $searchArray = explode(' ', $search);
            foreach ($searchArray as $item) {
                $query->where(function ($query) use ($item, $allColumns, $notAllowedFields, $booleanFields, $model) {
                    // start search on columns.
                    foreach ($allColumns as $column) {
                        // foreign keys are not allowed in global search
                        if (in_array($column, $notAllowedFields)) {
                            continue;
                        }
                        // set like query on all none boolean fields;
                        if (!isset($booleanFields[$column])) {
                            $query->orWhere($column, 'LIKE', "%$item%");
                            continue;
                        }
                        // query on boolean fields if search was equal one of these values.
                        if ($item == 'true' || $item == 'false' || $item == 1 || $item == 0) {
                            $query->orWhere($column, ($item == 'true' || $item == 1));
                        }
                    }
                    // query on translations fields.
                    $translations = $model->getTranslationsFields();
                    if (count($translations) > 0) {
                        $query->orWhereHas(
                            LocalizableModel::RELATION_NAME,
                            function ($query) use ($translations, $item) {
                                $query->where(function ($query) use ($translations, $item) {
                                    foreach ($translations as $key => $column) {
                                        $query->OrWhere($column, 'LIKE', "%$item%");
                                    }
                                });
                            }
                        );
                    }
                    // query on relation fields.
                    $relationsFields = $model->getAllowedSearchRelations();
                    if (count($relationsFields)) {
                        foreach ($relationsFields as $relationName => $fields) {
                            $query->orWhereHas($relationName, function ($query) use ($fields, $item) {
                                $query->where(function ($query) use ($fields, $item) {
                                    foreach ($fields as $column) {
                                        $query->orWhere($column, 'LIKE', "%$item%");
                                    }
                                });
                            });
                        }
                    }
                });
            }
        });
    }
}
