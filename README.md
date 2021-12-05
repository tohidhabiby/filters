# Filters
This package help you to create custom search on index method of controllers easily.

## Installation
Use the package manager [composer](https://getcomposer.org) to install this package.
```bash
composer require tohidhabiby/filters
```

## Usage
Each model should extend from BaseModel. Then create a filter class extended from Filters, the class should contain these parameters like this :
```bash
protected array $filters = [
    'ids',
];

public array $attributes = [
    'ids' => 'array',
];

protected function ids(array $ids) {
    return $this->builder->whereIn('id', $ids);
}
```

## License
[MIT](https://choosealicense.com/licenses/mit/)