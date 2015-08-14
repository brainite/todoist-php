<?php
namespace Todoist\Resource;
abstract class Resource implements \ArrayAccess {
  /**
   * @var \Todoist\Todoist
   */
  protected $engine = NULL;

  /**
   * Todoist method for updating this resource.
   * @var string
   */
  protected $update_method = NULL;

  /**
   * Immutable array of initial data.
   * @var array
   */
  protected $source = NULL;

  /**
   * Array of current data.
   * @var array
   */
  protected $data = NULL;

  public function offsetExists($offset) {
    return array_key_exists($offset, $this->data);
  }
  public function offsetGet($offset) {
    return $this->data[$offset];
  }
  public function offsetSet($offset, $value) {
    $this->data[$offset] = $value;
  }
  public function offsetUnset($offset) {
    unset($this->data[$offset]);
  }

  /**
   * Save the resource IFF it has changed.
   * @see \Todoist\Todoist::getApiSingleCommandResponse
   * @return \Todoist\Resource\Resource
   */
  public function &save() {
    $source = json_encode($this->source);
    $data = json_encode($this->data);
    if ($source === $data) {
      return $this;
    }

    // Attempt to perform the simple update.
    // This throws an exception if it would return anything other than TRUE.
    $this->engine->getApiSingleCommandResponse($this->update_method, $this->data);

    return $this;
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