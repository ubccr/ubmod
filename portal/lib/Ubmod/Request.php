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
 * Encapsulate a request from a client.
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
class Ubmod_Request
{

  /**
   * Request URL path.
   *
   * @var string
   */
  private $_path;

  /**
   * Request query string.
   *
   * @var string
   */
  private $_queryString;

  /**
   * Request GET data.
   *
   * @var array
   */
  private $_getData;

  /**
   * Request POST data.
   *
   * @var array
   */
  private $_postData;

  /**
   * Constructor.
   *
   * @param string $requestUrl The request URL.
   * @param string $pathInfo The request path info.
   * @param string $queryString The request query string.
   * @param array $getData The request GET data.
   * @param array $postData The request POST data.
   *
   * @return void
   */
  private function __construct($requestUrl, $pathInfo, $queryString, $getData,
    $postData)
  {
    $this->_path     = $pathInfo;
    $this->_getData  = $getData;
    $this->_postData = $postData;
  }

  /**
   * Factory method.
   *
   * @param string $requestUrl The request URL.
   * @param string $pathInfo The request path info.
   * @param string $queryString The request query string.
   * @param array $getData The request GET data.
   * @param array $postData The request POST data.
   *
   * @return Ubmod_Request
   */
  public static function factory($requestUrl, $pathInfo, $queryString, $getData,
    $postData)
  {
    return new Ubmod_Request($requestUrl, $pathInfo, $queryString, $getData,
      $postData);
  }

  /**
   * Returns POST data.
   *
   * @return array
   */
  public function getPostData()
  {
    return $this->_postData;
  }

  /**
   * Returns GET data.
   *
   * @return array
   */
  public function getGetData()
  {
    return $this->_getData;
  }

  /**
   * Returns the request path.
   *
   * @return string
   */
  public function getPath()
  {
    return $this->_path;
  }

  /**
   * Returns an array containing the segments of the request path in order.
   *
   * @return array
   */
  public function getPathSegments()
  {
    error_log($this->getPath());
    $path = trim($this->getPath(), '/');
    if ($path === '') {
      return array();
    }
    return explode('/', $path);
  }


  /**
   * Returns the portion of the path segment that is used to determine
   * which controller should process this request
   *
   * @return string
   */
  public function getControllerSegment()
  {
    $segments = $this->getPathSegments();
    if (count($segments) > 0) {
      return $segments[0];
    } else {
      return 'dashboard';
    }
  }

  /**
   * Returns the portion of the path segment that is used to determine
   * which action should process this request
   *
   * @return string
   */
  public function getActionSegment()
  {
    $segments = $this->getPathSegments();
    if (count($segments) > 1) {
      return $segments[1];
    } else {
      return 'index';
    }
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
}
