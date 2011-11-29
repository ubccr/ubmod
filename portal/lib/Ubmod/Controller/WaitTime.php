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
 * Wait time controller.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Wait time controller.
 *
 * @package Ubmod
 */
class Ubmod_Controller_WaitTime extends Ubmod_BaseController
{

  /**
   * Execute the index action.
   *
   * @return void
   */
  public function executeIndex()
  {

  }

  /**
   * Execute the chart action.
   *
   * @return void
   */
  public function executeChart()
  {
    $params = Ubmod_Model_QueryParams::factory($this->getPostData());

    $this->interval = Ubmod_Model_TimeInterval::getByParams($params);

    $queryString = Ubmod_Model_Chart::getQueryString($params);

    $this->periodChart  = '/chart/wait-time-period?'  . $queryString;
    $this->monthlyChart = '/chart/wait-time-monthly?' . $queryString;
  }
}
