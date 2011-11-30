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
 * Data Warehouse Fact Table
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Data warehouse table representation.
 *
 * @package Ubmod
 */
class Ubmod_DataWarehouse_Fact extends Ubmod_DataWarehouse_Table
{

  /**
   * The fact's dimensions.
   *
   * @var array
   */
  protected $_dimensions = array();

  /**
   * Constructor.
   *
   * @param array $config
   *   - string name       The fact's name.
   *   - array  dimensions The fact's dimensions.
   *   - array  facts      The fact's attributes.
   *
   * @return Ubmod_DataWarehouse_Fact
   */
  public function __construct($config)
  {
    $columns = array();

    // Foreign Keys
    foreach ($config['dimensions'] as $dimension) {
      $columns[] = $dimension . '_id';
    }

    foreach ($config['facts'] as $fact) {
      $columns[] = $fact;
    }

    parent::__construct(array(
      'name'    => $config['name'],
      'columns' => $columns,
    ));
  }

  /**
   * Determine if this fact has a given dimension.
   *
   * @param Ubmod_DataWarehouse_Dimension $dimension The dimension to
   *   check for.
   *
   * @return bool
   */
  public function hasDimension(Ubmod_DataWarehouse_Dimension $dimension)
  {
    $key = $dimension->getPrimaryKey();

    foreach ($this->_columns as $column) {
      if ($column === $key) {
        return true;
      }
    }

    return false;
  }

  /**
   * Determine if this fact has all the given dimensions.
   *
   * @param array $dimensions The dimensions to check for.
   *
   * @return bool
   */
  public function hasDimensions(array $dimensions)
  {
    foreach ($dimensions as $dimension) {
      if (!$this->hasDimension($dimension)) {
        return false;
      }
    }

    return true;
  }
}
