<?php
/**
 * Encapsulate a request from a client.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package UBMoD
 */

/**
 * Request class.
 *
 * @package default
 */
class UBMoD_Request
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
   * @return UBMoD_Request
   */
  public static function factory($requestUrl, $pathInfo, $queryString, $getData,
    $postData)
  {
    return new UBMoD_Request($requestUrl, $pathInfo, $queryString, $getData,
      $postData);
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
    return explode('/', substr($this->getPath(), 1));
  }
}
