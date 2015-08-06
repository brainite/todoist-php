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
   * All projects
   */

  /**
   * All tasks
   * @var \Todoist\Task\Tasks
   */
  private $tasks = NULL;

  /**
   * @see Todoist::setToken
   * @param string $token
   */
  public function __construct($token = NULL) {
    $this->client = new \GuzzleHttp\Client(array(
      'base_uri' => 'https://todoist.com/API/v6/',
      'timeout' => 2,
      'verify' => TRUE,
    ));
    $this->token = $token;
  }

  public function getApiResponse($method, $params) {
    // Make the request.
    $params['token'] = $this->token;
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

    // Look for an error message in JSON.
    if (is_array($data) && isset($data['error'])) {
      throw new \ErrorException($data['error'], $data['error_code']);
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
    $this->projects = new Projects($data['Projects']);
    $this->projects->setEngine($projects);
    $this->tasks = new Tasks($data['Items']);
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

}