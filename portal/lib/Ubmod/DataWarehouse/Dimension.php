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
 * Data Warehouse Dimension.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Dimension table representation.
 *
 * @package Ubmod
 */
class Ubmod_DataWarehouse_Dimension extends Ubmod_DataWarehouse_Table
{

  /**
   * The roll-up dimensions of this dimension.
   *
   * @var array
   */
  private $_rollUps = array();

  /**
   * Constructor
   *
   * @param array $config The configuration arguments.
   *
   * @return Ubmod_DataWarehouse_Dimension
   */
  public function __construct($config)
  {
    $columns = array();

    // Primary Key
    $columns[] = $config['name'] . '_id';

    foreach ($config['attributes'] as $attr) {
      $columns[] = $attr;
    }

    parent::__construct(array(
      'name'    => $config['name'],
      'columns' => $columns,
    ));
  }

  /**
   * Returns the primary key.
   *
   * @return string
   */
  public function getPrimaryKey()
  {
    return $this->_columns[0];
  }

  /**
   * Add a roll-up to this dimension.
   *
   * @param Ubmod_DataWarehouse_Dimension $dimension The roll-up.
   *
   * @return void
   */
  public function addRollUp($dimension)
  {
    $this->_rollUps[] = $dimension;
  }

  /**
   * Find the smallest roll-up of this dimension with the given columns.
   *
   * Returns null if a suitable roll-up cannot be found.
   *
   * @param array $columns The columns that must be in the dimension.
   *
   * @return Ubmod_DataWarehouse_Dimension
   */
  public function findRollUpWith($columns)
  {

    // XXX this is not optimal when dimensions the roll-ups don't have
    // mutually exclusive attributes

    foreach ($this->_rollUps as $rollUp) {
      if ($rollUp->hasColumns($columns)) {

        // XXX this should probably be recursive
        return $rollUp;
      }
    }

    return null;
  }
}
