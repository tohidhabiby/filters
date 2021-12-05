# Filters
This package helps you to create custom search on index method of controllers easily.

## Installation
Use the package manager [composer](https://getcomposer.org) to install this package.
```bash
composer require tohidhabiby/filters
```

## Usage
Each model should extend from BaseModel. Then create a filter class extended from Filters, the class should contain these parameters like this :
```bash
    /**
     * Registered filters to operate upon.
     *
     * @var array
     */
    protected array $filters = [
        'ids',
        ...
    ];
    
    /**
     * Define type of variables.
     *
     * @var array
     */
    public array $attributes = [
        'ids' => 'array',
        ...
    ];
    
    /**
     * @param string $email Email.
     *
     * @return Builder
     */
    protected function ids(array $ids) {
        return $this->builder->whereIn('id', $ids);
    }
```
Then in index method of controller you can do like this :
```bash
    /**
     * Display a listing of the resource.
     *
     * @param UserFilter $filters UserFilter.
     * @param Request    $request Request.
     *
     * @return AnonymousResourceCollection
     */
    public function index(UserFilter $filters, Request $request): AnonymousResourceCollection
    {
        return UserResource::collection(User::filter($filters));
    }
```

## License
[MIT](https://choosealicense.com/licenses/mit/)