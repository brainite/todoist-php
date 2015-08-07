# Todoist (unofficial PHP SDK)

## Basic Usage

```php
$todoist = new Todoist\Todoist($token);
$tasks = $todoist->getTasks();
$projects = $todoist->getProjects();
```

## Chaining

`Tasks` and `Projects` are intended to be iterable and chainable.

```php
foreach ($todoist->getTasks()->filterByProject('Project Name') as $task) {
  // $task is now an array derived from Todoist JSON.
}
```

## Task Methods

```php
// Filter tasks by project name or ID.
$tasks = $tasks->filterByProject('Project Name');
$tasks = $tasks->filterByProject($project_id);

// Filter tasks by regular expression.
$tasks = $tasks->filterByContentPreg('/regex for task content/');

// Manipulate task content by prepending or appending parent task content.
$tasks = $tasks->applyParentTasks(array(
  // 'prepend' or 'append' parent content
  // default: prepend
  'apply' => 'prepend',
  
  // Strip content 'pre', 'post' or other from parent task with a colon before adding it.
  // default: '' (do not strip colon content from parent)
  'strip_colon' => 'post',
  
  // Configure the delimiter to use between task and parent task content.
  // default: ': '
  'delimiter' => ': ',

  // Only apply the parent to short tasks (NULL = apply to all)
  // default: NULL
  'strlen_max' => 5,
));
```