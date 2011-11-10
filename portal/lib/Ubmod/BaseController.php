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
 * Base Controller.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Base class for handling requests. Other controllers should extend
 * this class.
 *
 * @package Ubmod
 */
class Ubmod_BaseController
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
   * @var Ubmod_Request
   */
  protected $_request;

  /**
   * Constructor.
   *
   * @param Ubmod_Request $request The request this controller is
   *   handling.
   *
   * @return Ubmod_BaseController
   */
  protected function __construct(Ubmod_Request $request)
  {
    $this->_request = $request;
  }

  /**
   * Factory method.
   *
   * @param Ubmod_Request $request The request this controller is
   *   handling.
   *
   * @return Ubmod_BaseController
   */
  public static function factory(Ubmod_Request $request)
  {
    return new static($request);
  }

  /**
   * Returns the request for this controller.
   *
   * @return Ubmod_Request
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
   * @param string $name The property name.
   *
   * @return mixed
   */
  public function __get($name)
  {
    return $this->_data[$name];
  }

  /**
   * Overload writing properties.
   *
   * @param string $name The property name.
   * @param mixed $value The value to associate with this property.
   *
   * @return void
   */
  public function __set($name, $value)
  {
    $this->_data[$name] = $value;
  }

  /**
   * Overload isset on properties.
   *
   * @param string $name The property name.
   *
   * @return bool
   */
  public function __isset($name)
  {
    return isset($this->_data[$name]);
  }

  /**
   * Overload unset on properties.
   *
   * @param string $name The property name.
   *
   * @return void
   */
  public function __unset($name)
  {
    unset($this->_data[$name]);
  }
}
