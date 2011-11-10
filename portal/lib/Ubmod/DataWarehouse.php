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
 * Data Warehouse implementation.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Data Warehouse class.
 *
 * The Ubmod_DataWarehouse implements a simple aggregate navigator to
 * optimize database queries.  The possible optimization is somewhat
 * limit and should only be used when certain criteria are met.  A json
 * file containing metadata regarding the facts, dimensions and
 * aggregates is used to perform the optimizations.  Futhermore, the
 * current aggregates are specifically designed to work with the queries
 * that are currently in use.
 *
 * Criteria:
 * - Only one fact table may be used
 * - All joins must be explicit
 * - Only dimensions are used in the WHERE and GROUP BY clauses
 * - No Subqueries may be used
 * - All aggregate functions on fact columns are used directly on that
 *   column. (No aggregates on functions)
 *
 * Algorithm:
 * - Identify all tables used by the query
 * - Identify all table columns used by the query
 * - Identify the fact table
 * - Identify the dimension tables
 * - Find the smallest roll-up of each dimension with the needed columns
 * - Find the aggregate that contains all these dimensions
 * - Substitute table and column names in the query
 *   - Replace the table fact name with the aggregate table name
 *   - Replace dimension table names with roll-up table names
 *   - Replace dimension primary key with roll-up primary key
 *   - Replace use of aggregate functions with aggregate columns
 * - Return the resulting query
 * - If any of these steps fail, return the original query
 *
 * @package Ubmod
 */
class Ubmod_DataWarehouse
{

  /**
   * Singleton instance.
   *
   * @var Ubmod_DataWarehouse
   */
  private static $_instance;

  /**
   * All tables known to the data warehouse.
   *
   * @var array
   */
  private $_tables = array();

  /**
   * All dimensions known to the data warehouse.
   *
   * @var array
   */
  private $_dimensions = array();

  /**
   * All facts known to the data warehouse.
   *
   * @var array
   */
  private $_facts = array();

  /**
   * All aggregates known to the data warehouse.
   *
   * @var array
   */
  private $_aggregates = array();

  /**
   * Factory method.
   *
   * @return Ubmod_DataWarehouse
   */
  public static function factory()
  {
    if (self::$_instance === NULL) {
      $config = new Zend_Config_Json(DW_CONFIG_FILE);

      self::$_instance = new Ubmod_DataWarehouse($config->toArray());
    }

    return self::$_instance;
  }

  /**
   * Optimize a SQL query.
   *
   * @param string $sql The SQL query that will be optimized.
   *
   * @return string
   */
  public static function optimize($sql)
  {
    $dw = self::factory();
    return $dw->navigate($sql);
  }

  /**
   * Private constructor for the factory method.
   *
   * @return Ubmod_DataWarehouse
   */
  private function __construct($config)
  {
    foreach ($config['dimensions'] as $dimension) {
      $this->addDimension($dimension);
    }

    foreach ($config['facts'] as $fact) {
      $this->addFact($fact);
    }

    foreach ($config['aggregates'] as $aggregate) {
      $this->addAggregate($aggregate);
    }
  }

  /**
   * Add a dimension to the data warehouse.
   *
   * @param array $def The dimension definition.
   *
   * @return Ubmod_DataWarehouse_Dimension
   */
  private function addDimension(array $def)
  {
    $name = $def['name'];
    $dimension = new Ubmod_DataWarehouse_Dimension($def);
    $this->_tables[$name]     = $dimension;
    $this->_dimensions[$name] = $dimension;

    if (isset($def['base'])) {
      $this->_dimensions[$def['base']]->addRollUp($dimension);
    }

    return $dimension;
  }

  /**
   * Add a fact to the data warehouse.
   *
   * @param array $def The fact definition.
   *
   * @return Ubmod_DataWarehouse_Fact
   */
  private function addFact(array $def)
  {
    $name = $def['name'];
    $fact = new Ubmod_DataWarehouse_Fact($def);
    $this->_tables[$name] = $fact;
    $this->_facts[$name]  = $fact;

    return $fact;
  }

  /**
   * Add an aggregate to the data warehouse.
   *
   * @param array $def The aggregate definition.
   *
   * @return Ubmod_DataWarehouse_Aggregate
   */
  private function addAggregate(array $def)
  {
    $name = $def['name'];
    $aggregate = new Ubmod_DataWarehouse_Aggregate($def);
    $this->_tables[$name]     = $aggregate;
    $this->_aggregates[$name] = $aggregate;

    return $aggregate;
  }

  /**
   * Returns all the columns for a given table.
   *
   * @param string $tableName The table name.
   *
   * @return array
   */
  private function getColumnsFor($tableName)
  {
    $table = $this->_tables[$tableName];
    return $table->getColumns();
  }

  /**
   * Optimize a SQL query.
   *
   * @param string $sql The SQL query that will be optimized.
   *
   * @return string
   */
  public function navigate($sql)
  {
    error_log("Original SQL: $sql");

    try {
      $data = $this->parseSql($sql);

      $fact       = $this->findFact($data['tables']);
      $dimensions = $this->findDimensions($data['tables']);

      $nonKeyColumns = array();
      foreach ($data['columns'] as $column) {
        if (substr($column, -3) !== '_id') {
          $nonKeyColumns[] = $column;
        }
      }

      // Pairs of corresponding original and roll-up dimensions
      $dimensionMaps = array();

      // Optimal set of dimensions for the query
      $optimalDimensions = array();

      error_log('Query has columns: ' . print_r($nonKeyColumns, 1));

      foreach ($dimensions as $dimension) {
        $columns = $dimension->intersectColumns($nonKeyColumns);

        error_log($dimension->getName() . ' intersection has columns: '
          . print_r($columns, 1));

        if ($rollUp = $dimension->findRollUpWith($columns)) {
          $dimensionMaps[]     = array($dimension, $rollUp);
          $optimalDimensions[] = $rollUp;
          error_log("Found roll-up: " . $rollUp->getName());
        } else {
          $optimalDimensions[] = $dimension;
        }
      }

      if ($aggregate = $this->findAggregateWith($optimalDimensions)) {

        // Substitute fact table
        $factName = $fact->getName();
        $aggName  = $aggregate->getName();
        $sql = preg_replace("/ \\b $factName \\b /x", $aggName, $sql);

        // Substitute dimension tables
        foreach ($dimensionMaps as $map) {
          list($orig, $rollUp) = $map;

          $origName   = $orig->GetName();
          $rollUpName = $rollUp->GetName();
          $sql = preg_replace("/ \\b $origName \\b /x", $rollUpName, $sql);

          $origPK   = $orig->GetPrimaryKey();
          $rollUpPK = $rollUp->GetPrimaryKey();
          $sql = preg_replace("/ \\b $origPK \\b /x", $rollUpPK, $sql);
        }

        // Replace fact aggregations with aggregate facts
        #foreach ($aggregate->getAggregates($columns) as $key => $value) {
        foreach ($aggregate->getAggregates() as $key => $value) {
          $sql = str_replace($key, $value, $sql);
        }

        error_log("Optimized SQL: $sql");

      } else {
        error_log("No appropriate aggregate found");
        return $sql;
      }

    } catch (Exception $e) {
      return $sql;
    }

    return $sql;
  }

  /**
   * Parse a SQL SELECT query.
   *
   * @param string $sql The SQL query that will be parsed.
   *
   * @return array
   */
  private function parseSql($sql)
  {
    // All tables used in the query
    $tables = array();

    if (preg_match_all('/ FROM \s+ `? (\w+) `? /xsi', $sql, $matches)) {
      $tables = array_merge($tables, $matches[1]);
    }

    if (preg_match_all('/ JOIN \s+ `? (\w+) `? /xsi', $sql, $matches)) {
      $tables = array_merge($tables, $matches[1]);
    }

    // All columns in identified tables
    $columns = array();

    foreach ($tables as $table) {
      $columns = array_merge($columns, $this->getColumnsFor($table));
    }

    $columns = array_unique($columns);

    // Columns used in the query
    $usedColumns = array();

    foreach ($columns as $column) {
      if (preg_match("/ \\b `? $column `? \\b /xsi", $sql, $matches)) {
        $usedColumns[] = $column;
      }
    }

    return array(
      'tables'  => $tables,
      'columns' => $usedColumns,
    );
  }

  /**
   * Given an array of table names return the first fact table.
   *
   * @param array $tables The tables to check.
   *
   * @return Ubmod_DataWarehouse_Fact
   */
  private function findFact($tables)
  {
    foreach ($tables as $table) {
      if (isset($this->_facts[$table])) {
        return $this->_facts[$table];
      }
    }

    return null;
  }

  /**
   * Given an array of table names return an array of dimension tables.
   *
   * @param array $tables The tables to check.
   *
   * @return array
   */
  private function findDimensions($tables)
  {
    $dimensions = array();

    foreach ($tables as $table) {
      if (isset($this->_dimensions[$table])) {
        $dimensions[] = $this->_dimensions[$table];
      }
    }

    return $dimensions;
  }

  /**
   * Find an appropriate aggregate table.
   *
   * @param array $dimensions The dimensions required in the aggregate.
   *
   * @return Ubmod_DataWarehouse_Aggregate
   */
  private function findAggregateWith($dimensions)
  {
    foreach ($this->_aggregates as $aggregate) {
      if ($aggregate->hasDimensions($dimensions)) {
        return $aggregate;
      }
    }

    return null;
  }
}
