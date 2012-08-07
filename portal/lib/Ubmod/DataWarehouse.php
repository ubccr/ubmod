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
   * Debugging flag.
   *
   * If this variable is true debugging output is logged using
   * error_log().
   *
   * @var bool
   */
  private static $_debug = false;

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
    if (self::$_instance === null) {
      $config = new Zend_Config_Json(DW_CONFIG_FILE);

      $options = $GLOBALS['options'];
      if (isset($options->datawarehouse)) {
        if (isset($options->datawarehouse->debug)) {
          self::$_debug = $options->datawarehouse->debug;
        }
      }

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
   * @param array $config
   *   - array dimensions
   *   - array facts
   *   - array aggregates
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
    if (!isset($this->_tables[$tableName])) {
      throw new Exception("Unkown table '$tableName'");
    }
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
    if (self::$_debug) {
      error_log("Original SQL: $sql");
    }

    try {
      $data = $this->parseSql($sql);

      if (self::$_debug) {
        error_log('Query has tables: ' . print_r($data['tables'], 1));
      }

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

      if (self::$_debug) {
        error_log('Query has columns: ' . print_r($nonKeyColumns, 1));
      }

      foreach ($dimensions as $dimension) {
        $columns = $dimension->intersectColumns($nonKeyColumns);

        if (self::$_debug) {
          error_log($dimension->getName() . ' intersection has columns: '
            . print_r($columns, 1));
        }

        if ($rollUp = $dimension->findRollUpWith($columns)) {
          $dimensionMaps[]     = array($dimension, $rollUp);
          $optimalDimensions[] = $rollUp;
          if (self::$_debug) {
            error_log("Found roll-up: " . $rollUp->getName());
          }
        } else {
          $optimalDimensions[] = $dimension;
        }
      }

      $haveRollUps = true;

      // Check for aggregates using the optimal dimensions. If there is
      // no aggregate with the optimal dimensions, remove one roll-up
      // at a time and check for an aggregate with those dimensions. If
      // there are no roll-up dimensions, it is possible that there is
      // an aggregate, so check at least once for that.
      do {
        $aggregate = $this->findAggregateWith($optimalDimensions);

        if ($aggregate) {
          if (self::$_debug) {
            error_log('Using aggregate: ' . $aggregate->getName());
          }

          $sql = $this->substituteAggregate($sql, $aggregate, $dimensionMaps);

          if (self::$_debug) {
            error_log("Optimized SQL: $sql");
          }

          return $sql;

        } else {

          // Remove a roll-up dimension to check for weaker aggregates
          if (count($dimensionMaps) > 0) {
            $map = array_pop($dimensionMaps);
            list($orig, $rollUp) = $map;

            if (self::$_debug) {
              error_log("Couldn't find optimal dimensions, trying without "
                . $rollUp->getName());
            }

            // Replace roll-up with original
            $optimalDimensions = array_map(
              function ($dim) use($orig, $rollUp) {
                if ($dim->getName() === $rollUp->getName()) {
                  return $orig;
                } else {
                  return $dim;
                }
              }, $optimalDimensions);

          } else {
            $haveRollUps = false;
          }
        }

      } while ($haveRollUps);

      if (self::$_debug) {
        error_log("No appropriate aggregate found");
      }

    } catch (Exception $e) {
      if (self::$_debug) {
        error_log("Optimization failed: " . $e->getMessage());
      }

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

    if (preg_match_all('/ \s FROM \s+ `? (\w+) `? /xsi', $sql, $matches)) {
      $tables = array_merge($tables, $matches[1]);
    }

    if (preg_match_all('/ \s JOIN \s+ `? (\w+) `? /xsi', $sql, $matches)) {
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

  /**
   * Substitute a fact table with an aggregate table in a SQL query.
   *
   * @param string $sql The SQL query.
   * @param Ubmod_DataWarehouse_Aggregate $aggregate The aggregate that
   *   will be substituted.
   * @param array $dimensionMaps An array of arrays containing fact
   *   dimensions and roll-up dimensions used by the aggregate. e.g.:
   *     array(
   *       array( dimension, roll-up dimension ),
   *       ...
   *     )
   *
   * @return void
   */
  private function substituteAggregate($sql, $aggregate, $dimensionMaps)
  {
    // Substitute fact table
    $factName = $aggregate->getBaseName();
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
    foreach ($aggregate->getAggregates() as $key => $value) {
      $sql = str_replace($key, $value, $sql);
    }

    return $sql;
  }
}
