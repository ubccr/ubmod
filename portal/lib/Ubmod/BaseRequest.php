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
    return new static(
      $requestUri,
      $pathInfo,
      $queryString,
      $getData,
      $postData
    );
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
    $options = $GLOBALS['options'];

    // If there is no authentication section, don't require any
    // authentication.
    if (!isset($options->authentication)) {
      return;
    }

    $authOptions = $options->authentication;

    // Check if debugging is enabled.
    $debug = isset($authOptions->debug) && $authOptions->debug;

    if (!isset($authOptions->key)) {
      $msg = '"key" missing from authentication configuration';
      throw new Exception($msg);
    }

    $key = $authOptions->key;

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
   * Return the entity name.
   *
   * @return string
   */
  public function getEntity()
  {
    if ($this->entity === null) {
      $this->parseUri();
    }

    return $this->entity;
  }

  /**
   * Return the action name.
   *
   * @return string
   */
  public function getAction()
  {
    if ($this->action === null) {
      $this->parseUri();
    }

    return $this->action;
  }

  /**
   * Parse the API URL to extract the entity and action.
   *
   * This function must set $this->entity and $this->action.
   */
  abstract protected function parseUri();
}

