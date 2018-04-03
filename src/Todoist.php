<?php
namespace Todoist;

use Todoist\Task\Tasks;
use Todoist\Project\Projects;
class Todoist {
  /**
   * Factory method to create a Todoist API object.
   * @param string $token
   * @return \Todoist\Todoist
   */
  static public function factory($token) {
    $ret = new Todoist();
    return $ret->setToken($token);
  }

  /**
   * Maintain a Guzzle client.
   * @var \GuzzleHttp\Client
   */
  private $client = NULL;

  /**
   * The projects.
   * @var \Todoist\Project\Projects
   */
  private $projects = NULL;

  /**
   * The authentication token.
   * @var string
   */
  private $token = NULL;

  /**
   * All tasks
   * @var \Todoist\Task\Tasks
   */
  private $tasks = NULL;

  /**
   * Use the command queue.
   * @var TRUE
   */
  private $use_command_queue = FALSE;

  /**
   * Track the command queue for this engine.
   * @var array
   */
  private $command_queue = array();

  /**
   * @see Todoist::setToken
   * @param string $token
   */
  public function __construct($token = NULL) {
    $this->client = new \GuzzleHttp\Client(array(
      'base_uri' => 'https://todoist.com/api/v7/',
      'timeout' => 10,
      'verify' => TRUE,
    ));
    $this->token = $token;
  }

  /**
   * Run all commands in the queue.
   * @return \Todoist\Todoist
   */
  public function &flushCommandQueue() {
    $commands_per_batch = 50;
    while (!empty($this->command_queue)) {
      // Get the first batch of commands.
      $commands = array_slice($this->command_queue, 0, $commands_per_batch);
      $this->command_queue = array_slice($this->command_queue, $commands_per_batch);

      // Run the commands
      $response = $this->getApiResponse('sync', array(
        'commands' => $commands,
      ));
      foreach ($response['SyncStatus'] as $data) {
        if ($data !== 'ok' && is_array($data['error'])) {
          throw new \ErrorException($data['error'], $data['error_code']);
        }
      }
    }
    return $this;
  }

  /**
   * Get a structured array of commands that should be sent.
   * @return array
   */
  public function getCommandQueue() {
    return $this->command_queue;
  }

  public function getApiResponse($method, $params) {
    // Make the request.
    $params['token'] = $this->token;
    foreach ($params as $k => $v) {
      if (is_array($v)) {
        $params[$k] = json_encode($v);
      }
    }
    $response = $this->client->post($method, array(
      'form_params' => $params,
    ));

    // Parse the body.
    $body = $response->getBody();
    $data = json_decode($body, TRUE);

    // Return when everything is OK.
    if ($response->getStatusCode() == 200 && is_array($data)) {
      return $data;
    }

    /** @link https://developer.todoist.com/#errors */
    switch ($response->getStatusCode()) {
      case 400:
      case 404:
        throw new \InvalidArgumentException("Incorrect request to Todoist.");

      case 401:
      case 403:
        throw new \ErrorException("Unauthorized request to Todoist.");

      case 429:
        throw new \ErrorException("You have exceeded your API limit with Todoist.");

      case 500:
      case 503:
        throw new \ErrorException("Todoist is unreachable.");

      default:
        throw new \ErrorException("Error making API request to Todoist.");
    }

    // Look for an error message in JSON.
    if (is_array($data) && isset($data['error'])) {
      throw new \ErrorException($data['error'], $data['error_code']);
    }
  }

  public function getApiSingleCommandResponse($type, $args) {
    // Build the command.
    $command = array(
      'type' => $type,
      'uuid' => uuid_create(),
      'args' => (array) $args,
    );

    // Stop quickly if the command should be queued.
    if ($this->useCommandQueue()) {
      $this->command_queue[] = $command;
      return $this;
    }

    // Build the API call
    $method = 'sync';
    $commands = array();
    $commands[] = $command;
    $response = $this->getApiResponse($method, array(
      'commands' => $commands,
    ));

    // Validate the response.
    if (isset($response['SyncStatus'][$command['uuid']])) {
      $data = $response['SyncStatus'][$command['uuid']];

      // Handle the simple response.
      if ($data === 'ok') {
        return TRUE;
      }
      // Handle the error response.
      if (isset($data['error'])) {
        throw new \ErrorException($data['error'], $data['error_code']);
      }
      // Handle the complex response (multiple return values).
      foreach ($data as $d) {
        if (isset($d['error'])) {
          throw new \ErrorException($d['error'], $d['error_code']);
        }
      }
      return TRUE;
    }
  }

  /**
   * Get a project_id or validate it.
   * @param mixed $ref <string Project name|int Project id>
   */
  public function getProject($ref) {
    $this->loadAll();
    foreach ($this->projects as $project) {
      if ($project['id'] == $ref || $project['name'] == $ref) {
        return $project;
      }
    }
    throw new \InvalidArgumentException("Invalid Todoist project reference.");
  }

  /**
   * Get an iterable listing of all projects.
   * @return \Todoist\Project\Projects
   */
  public function &getProjects() {
    $this->loadAll();
    return $this->projects;
  }

  /**
   * Get an iterable listing of all tasks.
   * @return \Todoist\Task\Tasks
   */
  public function getTasks() {
    $this->loadAll();
    return $this->tasks;
  }

  /**
   * Load all data
   * @return \Todoist\Todoist
   */
  public function loadAll() {
    if (isset($this->projects)) {
      return $this;
    }
    $data = $this->getApiResponse('sync', array(
      'seq_no' => 0,
      'seq_no_global' => 0,
      'resource_types' => json_encode(array(
        'items',
        'projects',
      )),
    ));
    $this->projects = new Projects($data['projects']);
    $this->projects->setEngine($this);
    $this->tasks = new Tasks($data['items']);
    $this->tasks->setEngine($this);

    return $this;
  }

  /**
   * Set the authentication token.
   *
   * @link https://developer.todoist.com/#authorization
   * @param string $token
   * @return \Todoist\Todoist
   */
  public function setToken($token) {
    $this->token = $token;
    return $this;
  }

  /**
   * Set (or get) whether to use the command queue.
   * @param bool $set
   * @return \Todoist\TRUE
   */
  public function useCommandQueue($set = NULL) {
    if ($set) {
      $old = $this->use_command_queue;
      $this->use_command_queue = (bool) $set;
      return $old;
    }
    else {
      return $this->use_command_queue;
    }
  }

}