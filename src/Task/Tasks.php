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

  private function factorySub($tasks) {
    $ret = new Tasks($tasks);
    $ret->setEngine($this->engine);
    return $ret;
  }

  /**
   * Filter by content.
   *
   * @param string $match PREG
   * @return \Todoist\Task\Tasks
   */
  public function filterByContentPreg($match) {
    // Build a new array of tasks.
    $tasks = array();
    foreach ($this as $task) {
      if (preg_match($match, $task['content'])) {
        $tasks[] = $task;
      }
    }
    return $this->factorySub($tasks);
  }

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
    return $this->factorySub($tasks);
  }

  public function applyParentTasks($conf) {
    $conf = array_merge(array(
      'apply' => 'prepend',
      'strip_colon' => '',
      'delimiter' => ': ',
      'strlen_max' => NULL,
    ), (array) $conf);

    // Get the unfiltered tasks.
    $all = $this->engine->getTasks();
    $project_tasks = array();

    // Build a new array of tasks.
    $tasks = array();
    foreach ($this->getArrayCopy() as $task) {
      if (isset($conf['strlen_max']) && $conf['strlen_max'] < strlen($task['content'])) {
        // Do nothing if the task is already long enough.
      }
      elseif ($task['indent'] > 1) {
        // Look for the previous item
        $prev_indent = $task['indent'] - 1;
        $prev_order = $task['item_order'] - 1;
        $prev_project = $task['project_id'];
        if (!isset($project_tasks[$prev_project])) {
          $project_tasks[$prev_project] = $all->filterByProject($prev_project);
        }
        while ($prev_order > 0) {
          foreach ($project_tasks[$prev_project] as $item) {
            if ($item['item_order'] == $prev_order) {
              if ($prev_indent == $item['indent']) {
                $parent = $item['content'];
                switch (strtolower($conf['strip_colon'])) {
                  case 'pre':
                    $parent = preg_replace('@^.*:@s', '', $parent);
                    break;
                  case 'post':
                    $parent = preg_replace('@:.*$@s', '', $parent);
                    break;
                }

                $parent = trim($parent);
                switch (strtolower($conf['apply'])) {
                  case 'append':
                    $task['content'] .= $conf['delimiter'] . $parent;
                    break;
                  case 'prepend':
                    $task['content'] = $parent . $conf['delimiter']
                      . $task['content'];
                    break;
                }
                break (2);
              }
              else {
                break;
              }
            }
          }
          $prev_order--;
        }
      }
      $tasks[] = $task;
    }
    return $this->factorySub($tasks);
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