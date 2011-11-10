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
 * Data Warehouse Aggregate Table.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Data Warehouse Aggregate.
 *
 * @package Ubmod
 */
class Ubmod_DataWarehouse_Aggregate extends Ubmod_DataWarehouse_Fact
{

  /**
   * Name of the base fact table.
   *
   * @var string
   */
  private $_base;

  /**
   * Fact aggregate mappings.
   *
   * Stores the mapping of how aggregates are represented in queries
   * against the base fact table to queries against the aggregate table.
   *
   * e.g., for the cput column of the fact table:
   * array(
   *   'SUM(cput)' => 'SUM(cput_sum)',
   *   'AVG(cput)' => 'SUM(cput_sum)/SUM(fact_job_count)',
   * );
   *
   * @var array
   */
  private $_aggregates = array();

  /**
   * Constructor.
   *
   * @param array $config Configuration data.
   *
   * @return Ubmod_DataWarehouse_Aggregate
   */
  public function __construct($config)
  {
    $facts = array();

    $this->_base = $config['base'];
    $this->_aggregates['COUNT(*)'] = 'SUM(' . $config['base'] . '_count)';

    foreach ($config['facts'] as $fact) {

      if (is_array($fact['type'])) {
        foreach ($fact['type'] as $type) {
          $this->addAggregate($fact['base'], $type);
        }
      } else {
        $this->addAggregate($fact['base'], $fact['type']);
      }
    }

    parent::__construct(array(
      'name'       => $config['name'],
      'facts'      => $facts,
      'dimensions' => $config['dimensions'],
    ));
  }

  /**
   * Return the name of the fact that this is an aggregate of.
   *
   * @return string The Name of the fact table.
   */
  public function getBaseName()
  {
    return $this->_base;
  }
  /**
   * Add an aggregate fact.
   *
   * Note: 'sum' implies 'avg'
   *
   * @param string $fact The name of the fact.
   * @param string $type The aggregate type.
   *
   * @return void
   */
  private function addAggregate($fact, $type)
  {
    $this->createAggregate($fact, $type);

    if ($type === 'sum') {
      $this->createAggregate($fact, 'avg');
    }
  }

  /**
   * Create an aggregate mapping and store it.
   *
   * @param string $fact The name of the fact.
   * @param string $type The aggregate type.
   *
   * @return void
   */
  private function createAggregate($fact, $type)
  {
    $prefix = '_' . $type;
    $func   = strtoupper($type);

    if ($func === 'AVG') {
      $agg  = $func . '(' . $fact . ')';
      $expr = 'SUM(' . $fact . '_sum' . ')/SUM(' . $this->_base . '_count)';
    } else {
      $agg  = $func . '(' . $fact . ')';
      $expr = $func . '(' . $fact . $prefix . ')';
    }

    $this->_aggregates[$agg] = $expr;
  }

  /**
   * Accessor for aggregates.
   *
   * @return array
   */
  public function getAggregates()
  {
    return $this->_aggregates;
  }
}
