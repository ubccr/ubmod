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
 * Handle a REST request.
 *
 * @author Steve Gallo
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2012
 * @package Ubmod
 */

/**
 * Handle a REST
 * (http://en.wikipedia.org/wiki/Representational_State_Transfer)
 * request to the REDfly API.
 *
 * The API is set up using Apache redirects so that the url represents
 * the API call.  The actual controller url called is
 * /api/rest/index.php (the controller) with path info following but the
 * apache redirect removes the index.php portion.  URLs have the format:
 *
 * /api/rest/{format}/{entity}/{action}[?tag=value&tag=value&species=X]
 *
 * Where
 *
 * {format} is the return format: json, xml
 * {entity} is the entity we will operate on: job, user, group
 * {action} is an action to perform: list, search, delete, add, edit
 * [?tag=value] are optional arguments to the action
 *
 * In order for an entity to be valid, a class must be defined to serve
 * as a handler for that entity (e.g., "Ubmod_Handler_User" will handle
 * a "user" entity).  The controller will instantiate Ubmod_RestRequest
 * to process an API request.  Ubmod_RestRequest will perform validation
 * on the request URL, parse it, and attempt to load a handler for the
 * entity.  If a handler is not found, an "invalid url" message is
 * returned.  This allows the API to be extended simply by the addition
 * of a handler class.
 *
 * Handlers are named for the entity (with the first letter capitalized)
 * prepended with "Ubmod_Handler_" (e.g., chromosome is handled by
 * Ubmod_Handler_Chromosome).
 *
 * Handlers contain two types of methods: "help" and "action".  If the
 * handler class contains an action method (e.g., "listAction" or
 * "searchAction") matching the requested action then it will be called
 * with any arguments and post data sent with the API request.  If an
 * action method is not found, an "invalid action" response is returned.
 * If "help=y" is specified as an option and a help method is present
 * for an action (e.g., "listHelp" or "searchHelp") then an
 * action-specific help message is returned.
 *
 * For example:
 *
 * /api/rest/json/user/list
 * /api/rest/json/user/search?name=2R
 * /api/rest/json/user/search?help=y
 * /api/rest/json/group/search?name=%25wow%25&gene=eve
 * /api/rest/json/cluster/list
 *
 * @package Ubmod
 */
class Ubmod_RestRequest extends Ubmod_BaseRequest
{

  /**
   * Return format parsed from API url
   *
   * @var string
   */
  private $_returnFormat = null;

  /**
   * API query handler object
   *
   * @var mixed
   */
  private $_handler = null;

  /**
   * Query options parsed from the query string in the API call
   *
   * @var array
   */
  private $_options = array();

  /**
   * Set to true if the request included a help request (e.g., help=y)
   *
   * @var bool
   */
  private $_displayHelp = false;

  /**
   * Response message
   *
   * @var Ubmod_RestResponse
   */
  private $_response = null;

  /**
   * Construct a new instance of a Ubmod_RestRequest object.
   *
   * The constructor is private and is meant to be called by the
   * factory() method.
   *
   * @param string $requestUri The full url of the request, including
   *   any path information preceeding the api URL as well as any query
   *   string information.  For example,
   *   /api/rest/json/entities/bindingsite/citation?pmid=9834431
   * @param string $pathInfo The API path portion of the URL following
   *   the actual location of the API script and not including the query
   *   string.  This is typically provided by the $_SERVER['PATH_INFO']
   *   variable.  For example, json/entities/bindingsite/citation
   * @param array $queryString The http query string, if any.
   * @param array $getData The contents of the form GET, if any, as
   *   parsed by PHP. This differs from the query string in the handling
   *   of arrays specified using the "[]" construct (e.g. redfly_id[]).
   * @param array $postData The contents of the form POST, if any.
   *
   * @return Ubmod_RestRequest
   */
  protected function __construct(
    $requestUri,
    $pathInfo = null,
    $queryString = null,
    array $getData = null,
    array $postData = null
  ) {
    parent::__construct(
      $requestUri,
      $pathInfo,
      $queryString,
      $getData,
      $postData
    );

    // Parse the path info to determine our return type, entity, action,
    // and any options.
    $this->parseUri();

    // Verify that the return format is valid.
    if (!$this->verifyReturnFormat()) {
      $msg = "Invalid return data format requested '{$this->_returnFormat}'";
      throw new Exception($msg);
    }

    // Authenticate the user.
    $this->authenticate();

    // Load the API handler.
    $this->loadHandler();
  }

  /**
   * Parse the API URL to extract the data return format, type of query,
   * entity to query, optional entity component, and optional query
   * options.
   *
   * @see Ubmod_BaseRequest
   */
  protected function parseUri()
  {
    $splitPath = $this->getPathSegments();

    if (count($splitPath) !== 3) {
      $msg = "Invalid Url '{$this->path}'";
      throw new Exception($msg);
    }

    list($returnFormat, $entity, $action) = $splitPath;

    $this->_returnFormat = $returnFormat;
    $this->entity = $entity;
    $this->action = $action;

    if ($this->getData !== null) {
      $this->_displayHelp = array_key_exists('help', $this->getData)
        && strtolower(substr($this->getData['help'], 0, 1)) === 'y';

      foreach ($this->getData as $key => $value) {
        $this->_options[$key] = $value;
      }
    }
  }

  /**
   * Verify that the return format is supported.
   */
  private function verifyReturnFormat()
  {
    $formatMethod = $this->_returnFormat . 'Format';
    return method_exists('Ubmod_RestResponse', $formatMethod);
  }

  /**
   * Load the request handler and create an instance via it's factory()
   * method.
   *
   * @throws Exception If the request handler was not found
   */
  private function loadHandler()
  {
    $handlerClassName = 'Ubmod_Handler_' . ucfirst($this->entity);
    $handlerClassFile = $handlerClassName . '.php';

    if (!class_exists($handlerClassName)) {
      $msg = "Unknown handler '$handlerClassName' for entity '$entity'";
      throw new Exception($msg);
    }

    $this->_handler = $handlerClassName::factory();
  }

  /**
   * Process the request.
   *
   * @return Ubmod_RestResponse
   */
  public function process()
  {
    if ($this->_handler === null) {
      $this->loadHandler();
    }

    if ($this->_displayHelp) {
      $helpMethod = $this->action . 'Help';
      if (method_exists($this->_handler, $helpMethod)) {
        $this->_response = $this->_handler->$helpMethod();
      } else {
        $this->_response = Ubmod_RestResponse::factory(array(
          'message' => "No help available for action '{$this->action}'",
        ));
      }
      return $this->_response;
    }

    $actionMethod = $this->action . 'Action';
    if (!method_exists($this->_handler, $actionMethod)) {
      $msg = "Undefined action '{$this->action}'";
      throw new Exception($msg);
    }

    try {
      $this->_response
        = $this->_handler->$actionMethod($this->_options, $this->postData);
    } catch (Exception $e) {
      $this->_response = Ubmod_RestResponse::factory(array(
        'success' => false,
        'message' => $e->getMessage(),
      ));
    }

    return $this->_response;
  }

  /**
   * Return the response in the requested format.
   *
   * @return mixed A response formatted according to the API request.
   */
  public function formatResponse()
  {
    // Process the request if it hasn't already been done

    if ($this->_response === null) {
      $this->process();
    }

    // Call the appropriate format method.  Existance of the formatted
    // should be checked in the constructor.

    $formatMethod = $this->_returnFormat . 'Format';
    $retval = $this->_response->$formatMethod();

    return $retval;
  }

  /**
   * Return an array of response headers.
   *
   * @return array
   */
  public function responseHeader()
  {
    // Process the request if it hasn't already been done

    if ($this->_response === null) {
      $this->process();
    }

    // Call the appropriate header method.  Existance of the formatted
    // should be checked in the constructor.

    $headerMethod = $this->_returnFormat . 'Header';
    $retval = $this->_response->$headerMethod();

    return $retval;
  }

  /**
   * Return the response as a string.
   *
   * @return string
   */
  public function __toString()
  {
    return $this->formatResponse();
  }
}

