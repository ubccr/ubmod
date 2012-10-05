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
 * @copyright Center for Computational Research, University at Buffalo, 2012
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
   * Parse the API URL to extract the entity and action.
   *
   * @see Ubmod_BaseRequest
   */
  protected function parseUri()
  {
    $segments = $this->getPathSegments();

    $segmentCount = count($segments);

    if ($segmentCount === 0) {
      // Find first menu item that the user is authorized to access.
      $menu = Ubmod_Menu::factory();
      foreach ($menu as $item) {
        if ($this->isAllowed($item['resource'], 'menu')) {
          $this->entity = $item['resource'];
          break;
        }
      }

      if ($this->entity === null) {
        $msg = "Not authorized to view any pages";
        throw new Exception($msg);
      }
    } else {
      $this->entity = $segments[0];
    }

    $this->action = $segmentCount > 1 ? $segments[1] : 'index';
  }
}

