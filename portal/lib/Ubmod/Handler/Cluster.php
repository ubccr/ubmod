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
 * Cluster REST handler.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2012
 * @package Ubmod
 */

/**
 * Cluster REST Handler.
 *
 * @package Ubmod
 */
class Ubmod_Handler_Cluster
{

  /**
   * Factory method.
   *
   * @return Ubmod_Handler_Cluster
   */
  public static function factory()
  {
    return new Ubmod_Handler_Cluster();
  }

  /**
   * Return help for the "list" action.
   *
   * @return Ubmod_RestResponse
   */
  public function listHelp()
  {
    $desc = 'List all clusters.  Results will be an array where individual'
      . ' records will consist of (cluster_id, name, display_name).';
    return Ubmod_RestResponse::factory(array('message' => $desc));
  }

  /**
   * List clusters.
   *
   * @param array $arguments Request GET data
   * @param array $postData  Request POST data
   *
   * @return Ubmod_RestResponse
   */
  public function listAction(array $arguments, array $postData = null)
  {
    return Ubmod_RestResponse::factory(array(
      'results' => Ubmod_Model_Cluster::getAll(),
    ));
  }
}
