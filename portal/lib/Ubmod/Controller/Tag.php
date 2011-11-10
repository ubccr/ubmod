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
 * Tag controller.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Tag controller.
 *
 * @package Ubmod
 */
class Ubmod_Controller_Tag extends Ubmod_BaseController
{

  /**
   * Execute the "index" action.
   *
   * @return void
   */
  public function executeIndex()
  {

  }

  /**
   * Execute the "keys" action.
   *
   * @return void
   */
  public function executeKeys()
  {

  }

  /**
   * Execute the "details" action.
   *
   * @return void
   */
  public function executeDetails()
  {
    $params = Ubmod_Model_QueryParams::factory($this->getPostData());

    $this->tagName  = $params->getTag();
    $this->interval = Ubmod_Model_TimeInterval::getByParams($params);
    $this->activity = Ubmod_Model_Job::getActivity($params);
    $queryString    = Ubmod_Model_Chart::getQueryString($params);

    $this->pieChart  = '/chart/user-pie?'  . $queryString;
    $this->barChart  = '/chart/user-bar?'  . $queryString;
    $this->areaChart = '/chart/user-area?' . $queryString;
  }

  /**
   * Execute the "keyDetails" action.
   *
   * @return void
   */
  public function executeKeyDetails()
  {
    $params = Ubmod_Model_QueryParams::factory($this->getPostData());

    $this->tagKey   = $params->getTagKey();
    $this->interval = Ubmod_Model_TimeInterval::getByParams($params);
    $queryString    = Ubmod_Model_Chart::getQueryString($params);

    $this->pieChart  = '/chart/tag-pie?'  . $queryString;
    $this->barChart  = '/chart/tag-bar?'  . $queryString;
    $this->areaChart = '/chart/tag-area?' . $queryString;
  }
}
