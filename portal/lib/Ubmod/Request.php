<?php
/*
 * The contents of this file are subject to the University at Buffalo Public
 * License Version 1.0 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.ccr.buffalo.edu/licenses/ubpl.txt
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for
 * the specific language governing rights and limitations under the License.
 *
 * The Original Code is UBMoD.
 *
 * The Initial Developer of the Original Code is Research Foundation of State
 * University of New York, on behalf of University at Buffalo.
 *
 * Portions created by the Initial Developer are Copyright (C) 2007 Research
 * Foundation of State University of New York, on behalf of University at
 * Buffalo.  All Rights Reserved.
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 (the "GPL"), or the GNU
 * Lesser General Public License Version 2.1 (the "LGPL"), in which case the
 * provisions of the GPL or the LGPL are applicable instead of those above. If
 * you wish to allow use of your version of this file only under the terms of
 * either the GPL or the LGPL, and not to allow others to use your version of
 * this file under the terms of the UBPL, indicate your decision by deleting
 * the provisions above and replace them with the notice and other provisions
 * required by the GPL or the LGPL. If you do not delete the provisions above,
 * a recipient may use your version of this file under the terms of any one of
 * the UBPL, the GPL or the LGPL.
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
  protected $_path;

  /**
   * Request query string.
   *
   * @var string
   */
  protected $_queryString;

  /**
   * Request GET data.
   *
   * @var array
   */
  protected $_getData;

  /**
   * Request POST data.
   *
   * @var array
   */
  protected $_postData;

  /**
   * Constructor.
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
