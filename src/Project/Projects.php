<?php
namespace Todoist\Project;
class Projects extends \ArrayIterator {
  /**
   * @var \Todoist\Todoist
   */
  private $engine = NULL;

  public function __construct($arr) {
    parent::__construct(array_map(function ($value) {
      return new Project($value);
    }, $arr));
  }

  public function getProject($id) {
    return $this->engine->getProject($id);
  }

  /**
   * @param \Todoist\Todoist $engine
   * @return \Todoist\Task\Tasks
   */
  public function setEngine(&$engine) {
    $this->engine = &$engine;
    foreach ($this as &$el) {
      $el->setEngine($engine);
    }
    return $this;
  }

  public function save() {
    foreach ($this as $project) {
      $project->save();
    }
    return $this;
  }

  /**
   * Sync project order and indent based on project NAME.
   *
   * WARNING: Each user has a different project id.
   * Thus, names must be distinct for this to work.
   *
   * @param \Todoist\Project\Projects $tpl
   * @param string $unknown_mode <top|bottom|ignore>
   * @return \Todoist\Project\Projects
   */
  public function &syncOrderIndent($tpl, $unknown_mode = 'ignore') {
    // Change the sort order.
    // Projects missing from template should float UP.
    // Warning: Error suppression is required
    // See https://bugs.php.net/bug.php?id=50688
    @$this->uasort(function ($a, $b) use ($tpl, $unknown_mode) {
      $a_tpl = $b_tpl = NULL;
      $unknown_order = ($unknown_mode === 'top') ? -1 : 10000;

      // If a is new, then leave alone.
      try {
        $a_tpl = $tpl->getProject($a['name']);
      } catch (\Exception $e) {
        if ($unknown_mode === 'ignore') {
          $a_tpl = $a;
        }
        else {
          $a_tpl = array(
            'item_order' => $unknown_order,
          );
        }
      }

      // If b is new, then leave alone
      try {
        $b_tpl = $tpl->getProject($b['name']);
      } catch (\Exception $e) {
        if ($unknown_mode === 'ignore') {
          $b_tpl = $b;
        }
        else {
          $b_tpl = array(
            'item_order' => $unknown_order,
          );
        }
      }

      if ($a_tpl['item_order'] < $b_tpl['item_order']) {
        return -1;
      }
      elseif ($a_tpl['item_order'] == $b_tpl['item_order']) {
        return 0;
      }
      return 1;
    });

    // Change the item_order codes based on the current order.
    $item_order = 0;
    foreach ($this as &$project) {
      $project['item_order'] = ++$item_order;
      try {
        $find = $tpl->getProject($project['name']);
        $project['indent'] = $find['indent'];
        $project['color'] = $find['color'];
        $project->save();
      } catch (\Exception $e) {
        // Not found - leave the indent alone.
        // echo "UNKNOWN: $project[name]\n";
      }
    }

    return $this;
  }

}