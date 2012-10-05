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
 * Encapsulate a non-REST request from a client.
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
class Ubmod_Request extends Ubmod_BaseRequest
{

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
}

