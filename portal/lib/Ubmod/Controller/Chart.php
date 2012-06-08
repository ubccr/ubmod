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
 * Chart controller.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2012
 * @package Ubmod
 */

/**
 * Chart controller.
 *
 * @package Ubmod
 */
class Ubmod_Controller_Chart extends Ubmod_BaseController
{

  /**
   * Execute the "cached" chart action.
   *
   * @return void
   */
  public function executeCached()
  {
    $data = $this->getGetData();

    $chart = Ubmod_Model_Chart::cacheGet($data['id']);

    switch ($chart['type']) {
    case 'pie':
      Ubmod_Model_Chart::renderPieChart($chart['data']);
      break;
    case 'bar':
    case 'period':
      Ubmod_Model_Chart::renderBarChart($chart['data']);
      break;
    case 'stackedArea':
    case 'monthly':
      Ubmod_Model_Chart::renderStackedAreaChart($chart['data']);
      break;
    default:
      throw new Exception("Unknown chart type '{$chart['type']}'");
      break;
    }
  }
}

