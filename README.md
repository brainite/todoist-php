# Todoist (unofficial PHP SDK)

## Project Status

Currently, this project provides read-only access to Todoist Tasks and Projects.
It adds some basic filtering and manipulation capabilities that are not provided
by the API directly.

## Basic Usage

```php
$todoist = new Todoist\Todoist($token);
$tasks = $todoist->getTasks();
$projects = $todoist->getProjects();
```

## Design Decision

`Tasks` and `Projects` are intended to be iterable, chainable and immutable.
Each custom method added to the classes return a new object with copies of the
items rather than references.

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

## Sync Project Hierarchy

WARNING: Each user receives a custom project_id.
Therefore, the project hierarchy sync relies on distinct project names regardless of indentation.

WARNING: It is also important to use the Command Queue to avoid the rate limit error from Todoist.

The following example syncs projects and moves unknown projects to the top.

```php
$source_todoist = Todoist::factory($source_token);
$source_projects = $source_todoist->getProjects();

try {
  $todoist = Todoist::factory($token);
  $todoist->useCommandQueue(TRUE);
  $projects = &$todoist->getProjects();
  $projects->syncOrderIndent($source_projects, 'top');
  $todoist->flushCommandQueue();
} catch (\Exception $e) {
  echo "Error: " . $e->getMessage() . "\n";
}

```
