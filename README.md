# Todoist (unofficial PHP SDK)

## Basic Usage

```php
$todoist = new Todoist\Todoist($token);
$tasks = $todoist->getTasks()->filterByProject('Project Name');
```