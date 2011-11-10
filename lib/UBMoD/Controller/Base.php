<?php
/**
 * Base Controller.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package UBMoD
 */

/**
 * Base Controller.
 *
 * @package UBMoD
 */
class UBMoD_Controller_Base
{

  /**
   * Data store for overloaded properties.
   *
   * @var array
   */
  private $_data = array();

  /**
   * Request object.
   *
   * @var UBMoD_Request
   */
  protected $_request;

  /**
   * Response Object.
   *
   * @var UBMoD_Response
   */
  protected $_response;

  /**
   * Constructor.
   *
   * @return void
   */
  protected function __construct($request)
  {
    $this->_request = $request;
  }

  /**
   * Factory method.
   *
   * @return BaseController
   */
  public static function factory($request)
  {
    return new static($request);
  }

  /**
   * Returns the request for this controller.
   *
   * @return UBMoD_Request
   */
  public function getRequest()
  {
    return $this->_request;
  }

  /**
   * Returns the POST data for this controller.
   *
   * @return array
   */
  public function getPostData()
  {
    return $this->_request->getPostData();
  }

  /**
   * Returns the GET data for this controller.
   *
   * @return array
   */
  public function getGetData()
  {
    return $this->_request->getGetData();
  }

  /**
   * Returns the property data for this controller.
   *
   * @return array
   */
  public function getData()
  {
    return $this->_data;
   }

  /**
   * Overload reading properties.
   *
   * @param string The property name
   * @return mixed
   */
  public function __get($name)
  {
    return $this->_data[$name];
  }

  /**
   * Overload writing properties.
   *
   * @param string The property name
   * @return void
   */
  public function __set($name, $value)
  {
    $this->_data[$name] = $value;
  }

  /**
   * Overload isset on properties.
   *
   * @param string The property name
   * @return bool
   */
  public function __isset($name)
  {
    return isset($this->_data[$name]);
  }

  /**
   * Overload unset on properties.
   *
   * @param string The property name
   * @return void
   */
  public function __unset($name)
  {
    unset($this->_data[$name]);
  }
}
