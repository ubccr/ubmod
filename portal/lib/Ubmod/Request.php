<?php
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
