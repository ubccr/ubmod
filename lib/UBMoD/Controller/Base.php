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
  private $_data;

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
  protected function __constructor()
  {

  }

  /**
   * Factory method.
   *
   * @return BaseController
   */
  public static function factory()
  {
    return new static();
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
