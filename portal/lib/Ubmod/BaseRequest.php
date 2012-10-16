<?php
/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * The Original Code is UBMoD.
 *
 * The Initial Developer of the Original Code is Research Foundation of State
 * University of New York, on behalf of University at Buffalo.
 *
 * Portions created by the Initial Developer are Copyright (C) 2007 Research
 * Foundation of State University of New York, on behalf of University at
 * Buffalo.  All Rights Reserved.
 */

/**
 * Base class for HTTP requests.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Request class.
 *
 * @package Ubmod
 */
abstract class Ubmod_BaseRequest
{

  /**
   * Request URI (includes query string).
   *
   * @var string
   */
  protected $requestUri = null;

  /**
   * Request URL path.
   *
   * @var string
   */
  protected $path = null;

  /**
   * Request query string.
   *
   * @var string
   */
  protected $queryString = null;

  /**
   * Request GET data.
   *
   * @var array
   */
  protected $getData = null;

  /**
   * Request POST data.
   *
   * @var array
   */
  protected $postData = null;

  /**
   * Entity that we are querying.
   *
   * @var string
   */
  protected $entity = null;

  /**
   * The action to perform on the entity.
   *
   * @var string
   */
  protected $action = null;

  /**
   * Authenticated user.
   *
   * @var string
   */
  protected $user = null;

  /**
   * Authenticated user primary key.
   *
   * @var int
   */
  protected $userId = null;

  /**
   * Authenticated user's group.
   *
   * @var string
   */
  protected $group = null;

  /**
   * Authenticated user's group primary key.
   *
   * @var int
   */
  protected $groupId = null;

  /**
   * Authenticated user's role.
   *
   * @var string
   */
  protected $role = null;

  /**
   * Access control list.
   *
   * @var Zend_Acl
   */
  protected $acl = null;

  /**
   * Constructor.
   *
   * @param string $requestUri The request URL.
   * @param string $pathInfo The request path info.
   * @param string $queryString The request query string.
   * @param array $getData The request GET data.
   * @param array $postData The request POST data.
   *
   * @return void
   */
  protected function __construct(
    $requestUri,
    $pathInfo,
    $queryString,
    array $getData,
    array $postData
  ) {
    $this->requestUri  = $requestUri;
    $this->path        = $pathInfo;
    $this->queryString = urldecode($queryString);
    $this->getData     = $getData;
    $this->postData    = $postData;

    $this->parseUri();
  }

  /**
   * Factory method.
   *
   * @param string $requestUri The request URL.
   * @param string $pathInfo The request path info.
   * @param string $queryString The request query string.
   * @param array $getData The request GET data.
   * @param array $postData The request POST data.
   *
   * @return Ubmod_Request
   */
  public static function factory(
    $requestUri,
    $pathInfo,
    $queryString,
    $getData,
    $postData
  ) {
    $request = new static(
      $requestUri,
      $pathInfo,
      $queryString,
      $getData,
      $postData
    );

    $request->authenticate();
    $request->authorize();

    return $request;
  }

  /**
   * Returns POST data.
   *
   * @return array
   */
  public function getPostData()
  {
    return $this->postData;
  }

  /**
   * Returns GET data.
   *
   * @return array
   */
  public function getGetData()
  {
    return $this->getData;
  }

  /**
   * Returns the request path.
   *
   * @return string
   */
  public function getPath()
  {
    return $this->path;
  }

  /**
   * Returns an array containing the segments of the request path in
   * order.
   *
   * @return array
   */
  public function getPathSegments()
  {
    $path = trim($this->getPath(), '/');

    if ($path === '') {
      return array();
    }

    return explode('/', $path);
  }

  /**
   * Returns true if this is an AJAX request
   *
   * @return bool
   */
  public function isXmlHttpRequest()
  {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
      && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
  }

  /**
   * Authenticate the user session.
   */
  protected function authenticate()
  {

    // If the user is already authenticated, don't check again.
    if ($this->user !== null) {
      return;
    }

    $options = $GLOBALS['options'];

    // If there is no authentication section, don't require any
    // authentication.
    if (!isset($options->authentication)) {
      return;
    }

    $authOptions = $options->authentication;

    if (!isset($authOptions->enabled) || !$authOptions->enabled) {
      return;
    }

    // Check if debugging is enabled.
    $debug = isset($authOptions->debug) && $authOptions->debug;

    $key = isset($authOptions->key) ? $authOptions->key : 'REMOTE_USER';

    if (!isset($_SERVER[$key])) {
      if ($debug) {
        error_log("'$key' not found in \$_SERVER");
      }

      header('HTTP/1.0 401 Unauthorized');
      exit;
    }

    $this->user = $_SERVER[$key];

    if ($debug) {
      error_log("Authenticated as {$this->user}");
    }
  }

  /**
   * Check if user is authorized to access the requested resource.
   */
  protected function authorize()
  {
    $options = $GLOBALS['options'];

    // If there is no authorization section, don't require any
    // authorization.
    if (!isset($options->authorization)) {
      return;
    }

    $authOptions = $options->authorization;

    if (!isset($authOptions->enabled) || !$authOptions->enabled) {
      return;
    }

    // Check if debugging is enabled.
    $debug = isset($authOptions->debug) && $authOptions->debug;

    if ($this->getUser() === null) {
      if ($debug) {
        $msg = "Attempt to authorize a user that hasn't been authenticated.";
        error_log($msg);
      }

      return;
    }

    $entity = $this->getEntity();
    $action = $this->getAction();

    if ($debug) {
      $msg = "Checking authorization for '$entity/$action'";
      error_log($msg);
    }

    if (!$this->isAllowed($entity, $action)) {
      header('HTTP/1.0 401 Unauthorized');
      exit;
    }
  }

  /**
   * Check if the user has a privilege for a resource.
   *
   * @param string $resource The resource name.
   * @param string $privilege The privilege name.
   *
   * @return bool
   */
  public function isAllowed($resource, $privilege = null)
  {

    // If no user is set, assume that authentication is not being used
    // and allow everything.
    if ($this->getUser() === null) {
      return true;
    }

    $acl  = $this->getAcl();
    $role = $this->getRole();

    return $acl->isAllowed($role, $resource, $privilege);
  }

  /**
   * Return the access control list.
   *
   * @return Zend_Acl
   */
  protected function getAcl()
  {
    if ($this->acl === null) {
      $acl = new Zend_Acl();

      $resources = $this->decodeJsonFile(ACL_RESOURCES_FILE);

      foreach ($resources as $resource) {
        $privileges
          = array_key_exists('privileges', $resource)
          ? $resource['privileges']
          : null;

        $acl->add(new Zend_Acl_Resource($resource['name'], $privileges));
      }

      $roles = $this->decodeJsonFile(ACL_ROLES_FILE);
      $this->addRolesToAcl($acl, $roles);

      $roles = $this->decodeJsonFile(ROLES_CONFIG_FILE);
      $this->addRolesToAcl($acl, $roles);

      $this->acl = $acl;
    }

    return $this->acl;
  }

  /**
   * Helper function for adding a role to an ACL.
   *
   * @param Zend_Acl $acl
   * @param array $roles
   */
  private function addRolesToAcl(Zend_Acl $acl, array $roles)
  {
    foreach ($roles as $role) {
      $name = $role['name'];

      $parents = array_key_exists('parents', $role) ? $role['parents'] : null;

      $acl->addRole($name, $parents);

      if (array_key_exists('allow', $role)) {
        if (is_array($role['allow'])) {
          foreach ($role['allow'] as $resource => $privileges) {
            $acl->allow($name, $resource, $privileges);
          }
        } else {
          $acl->allow($name, $role['allow']);
        }
      }

      if (array_key_exists('deny', $role)) {
        if (is_array($role['deny'])) {
          foreach ($role['deny'] as $resource => $privileges) {
            $acl->deny($name, $resource, $privileges);
          }
        } else {
          $acl->deny($name, $role['deny']);
        }
      }
    }
  }

  /**
   * Return the user name.
   *
   * @return string
   */
  public function getUser()
  {
    return $this->user;
  }

  /**
   * Return the user primary key.
   *
   * @return int
   */
  public function getUserId()
  {
    if ($this->userId === null) {
      $user = $this->getUser();
      if ($user === null) {
        return null;
      }

      $this->userId = Ubmod_Model_User::getUserId($user);
    }

    return $this->userId;
  }

  /**
   * Return the user's group name.
   *
   * @return string
   */
  public function getGroup()
  {
    if ($this->group === null) {
      $userId = $this->getUserId();
      if ($userId === null) {
        return null;
      }

      $this->group = Ubmod_Model_User::getCurrentGroup($userId);
    }

    return $this->group;
  }

  /**
   * Return the user's group primary key.
   *
   * @return int
   */
  public function getGroupId()
  {
    if ($this->groupId === null) {
      $group = $this->getGroup();
      if ($group === null) {
        return null;
      }

      $this->groupId = Ubmod_Model_Group::getGroupId($group);
    }

    return $this->groupId;
  }

  /**
   * Return the name of the role associated with the authenticated user.
   *
   * @return string
   */
  protected function getRole()
  {
    if ($this->role === null) {
      $user = $this->getUser();
      if ($user === null) {
        return null;
      }

      $this->role = '__default__';

      $userRoles = $this->decodeJsonFile(USER_ROLES_FILE);

      foreach ($userRoles as $role => $users) {
        if (in_array($user, $users)) {
          $this->role = $role;
          break;
        }
      }
    }

    return $this->role;
  }

  /**
   * Return the entity name.
   *
   * @return string
   */
  public function getEntity()
  {
    return $this->entity;
  }

  /**
   * Return the action name.
   *
   * @return string
   */
  public function getAction()
  {
    return $this->action;
  }

  /**
   * Decode JSON contained in a file.
   *
   * @param string $file Path to a file containing JSON data.
   * @param bool $assoc When true, convert objects to arrays.
   *
   * @return mixed
   */
  private function decodeJsonFile($file, $assoc = true)
  {
    $json = file_get_contents($file);
    if ($json === false) {
      $msg = "Failed to read data from '$file'";
      throw new Exception($msg);
    }

    $data = json_decode($json, $assoc);
    if ($data === null) {
      $msg = "Failed to decode data from '$file'";
      throw new Exception($msg);
    }

    return $data;
  }

  /**
   * Parse the API URL to extract the entity and action.
   *
   * This function must set the "entity" and "action" properties.
   */
  abstract protected function parseUri();
}

