<?php
namespace Todoist\Task;

/**
 * Interact with Todoist tasks.
 *
 * @author Greg Payne
 * @link https://developer.todoist.com/#items
 */
class Tasks extends \ArrayIterator {
  /**
   * @var \Todoist\Todoist
   */
  private $engine = NULL;

  /**
   * Filter by project name or id.
   *
   * @param mixed $project_ref
   * @return \Todoist\Task\Tasks
   */
  public function filterByProject($project_ref) {
    // Get the project_id.
    $project = $this->engine->getProject($project_ref);
    $project_id = $project['id'];

    // Build a new array of tasks.
    $tasks = array();
    foreach ($this as $task) {
      if ($task['project_id'] == $project_id) {
        $tasks[] = $task;
      }
    }
    return new Tasks($tasks);
  }

  /**
   * @param \Todoist\Todoist $engine
   * @return \Todoist\Task\Tasks
   */
  public function setEngine(&$engine) {
    $this->engine = &$engine;
    return $this;
  }

}