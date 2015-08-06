<?php
namespace Todoist\Project;

class Projects extends \ArrayIterator {
  /**
   * @var \Todoist\Todoist
   */
  private $engine = NULL;

  /**
   * @param \Todoist\Todoist $engine
   * @return \Todoist\Task\Tasks
   */
  public function setEngine(&$engine) {
    $this->engine = &$engine;
    return $this;
  }

}