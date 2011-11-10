<?php
/*
 * The contents of this file are subject to the University at Buffalo Public
 * License Version 1.0 (the "License"); you may not use this file except in
 * compliance with the License. You may obtain a copy of the License at
 * http://www.ccr.buffalo.edu/licenses/ubpl.txt
 *
 * Software distributed under the License is distributed on an "AS IS" basis,
 * WITHOUT WARRANTY OF ANY KIND, either express or implied. See the License for
 * the specific language governing rights and limitations under the License.
 *
 * The Original Code is UBMoD.
 *
 * The Initial Developer of the Original Code is Research Foundation of State
 * University of New York, on behalf of University at Buffalo.
 *
 * Portions created by the Initial Developer are Copyright (C) 2007 Research
 * Foundation of State University of New York, on behalf of University at
 * Buffalo.  All Rights Reserved.
 *
 * Alternatively, the contents of this file may be used under the terms of
 * either the GNU General Public License Version 2 (the "GPL"), or the GNU
 * Lesser General Public License Version 2.1 (the "LGPL"), in which case the
 * provisions of the GPL or the LGPL are applicable instead of those above. If
 * you wish to allow use of your version of this file only under the terms of
 * either the GPL or the LGPL, and not to allow others to use your version of
 * this file under the terms of the UBPL, indicate your decision by deleting
 * the provisions above and replace them with the notice and other provisions
 * required by the GPL or the LGPL. If you do not delete the provisions above,
 * a recipient may use your version of this file under the terms of any one of
 * the UBPL, the GPL or the LGPL.
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
