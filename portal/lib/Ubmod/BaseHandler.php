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
 * Base Handler.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2012
 * @package Ubmod
 */

/**
 * Base class for handling REST requests. Other handlers should extend
 * this class.
 *
 * @package Ubmod
 */
class Ubmod_BaseHandler
{

  /**
   * Request object.
   *
   * @var Ubmod_RestRequest
   */
  private $_request;

  /**
   * Constructor.
   *
   * @param Ubmod_RestRequest $request The request this controller is
   *   handling.
   *
   * @return Ubmod_BaseHandler
   */
  protected function __construct(Ubmod_RestRequest $request)
  {
    $this->_request = $request;
  }

  /**
   * Factory method.
   *
   * @param Ubmod_RestRequest $request The request this controller is
   *   handling.
   *
   * @return Ubmod_BaseHandler
   */
  public static function factory(Ubmod_RestRequest $request)
  {
    return new static($request);
  }

  /**
   * Returns the request for this controller.
   *
   * @return Ubmod_RestRequest
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
}

