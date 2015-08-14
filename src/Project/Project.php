<?php
namespace Todoist\Project;

use Todoist\Resource\Resource;

class Project extends Resource {
  public function __construct($data) {
    $this->data = $this->source = $data;
    $this->update_method = 'project_update';
  }

}
