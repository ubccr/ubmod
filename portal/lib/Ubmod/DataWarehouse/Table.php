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
 * Abstract database table representation.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Abstract database table representation.
 *
 * @package Ubmod
 */
class Ubmod_DataWarehouse_Table
{

  /**
   * The table's name.
   *
   * @var string
   */
  private $_name;

  /**
   * Columns belonging to the table.
   *
   * @var array
   */
  protected $columns = array();

  /**
   * Constructor.
   *
   * @param array $def The table definition.
   *
   * @return Ubmod_DataWarehouse_Table
   */
  public function __construct(array $def)
  {
    $this->_name   = $def['name'];
    $this->columns = $def['columns'];
  }

  /**
   * Returns the table's name.
   *
   * @return string
   */
  public function getName()
  {
    return $this->_name;
  }

  /**
   * Returns the table's columns.
   *
   * @return array
   */
  public function getColumns()
  {
    return $this->columns;
  }

  /**
   * Check if the table has certain columns.
   *
   * @param columns array The columns to check for.
   *
   * @return bool
   */
  public function hasColumns($columns)
  {
    foreach ($columns as $column) {
      if (!in_array($column, $this->columns)) {
        return false;
      }
    }

    return true;
  }

  /**
   * Return the intersection of the table's columns with those provided.
   *
   * @param array $columns The columns to use in the intersection.
   *
   * @return array
   */
  public function intersectColumns($columns)
  {
    $intersection = array();

    foreach ($columns as $column) {
      if (in_array($column, $this->columns)) {
        $intersection[] = $column;
      }
    }

    return $intersection;
  }
}
