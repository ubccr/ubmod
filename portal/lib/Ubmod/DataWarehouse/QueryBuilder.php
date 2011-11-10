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
 * Data warehouse SQL query builder.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id: User.php 3125 2011-09-14 19:33:14Z jtpalmer@K5.CCR.BUFFALO.EDU $
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Data warehouse SQL query builder.
 *
 * @package Ubmod
 */
class Ubmod_DataWarehouse_QueryBuilder
{

  /**
   * The database columns or expressions to SELECT.
   *
   * Each array key is the alias of the corresponding expression.
   *
   * @var array
   */
  protected $_selectExpressions = array();

  /**
   * The database fact table to SELECT FROM.
   *
   * @var string
   */
  protected $_factTable = null;

  /**
   * The database dimension tables to SELECT FROM.
   *
   * @var array
   */
  protected $_dimensionTables = array();

  /**
   * Data used to generate the WHERE clause.
   *
   * These will be combined using AND.
   *
   * @var array
   */
  protected $_whereClauses = array();

  /**
   * The database column to GROUP BY.
   *
   * @var string
   */
  protected $_groupBy = null;

  /**
   * The database column to ORDER BY.
   *
   * @var string
   */
  protected $_orderBy = null;

  /**
   * The database column to ORDER BY.
   *
   * @var bool
   */
  protected $_orderByDesc = false;

  /**
   * LIMIT offset.
   *
   * @var int
   */
  protected $_limitOffset = null;

  /**
   * LIMIT row count.
   *
   * @var int
   */
  protected $_limitRowCount = null;

  /**
   * Filter expression.
   *
   * If the query parameters contain a filter keyword, this expression
   * will be used in the WHERE clause using LIKE.
   *
   * @var string
   */
  protected $_filterExpression = null;

  /**
   * Add a SELECT expression to the generated query.
   *
   * @param string $expression A SQL expression. It should only contain
   *   columns from the fact table.
   * @param string $alias (optional) The expression alias.
   *
   * @return void
   */
  public function addSelectExpression($expression, $alias = null)
  {
    if ($alias === null) {
      $alias = "`$expression`";
    } else {
      $alias = "`$alias`";
    }

    $this->_selectExpressions[$alias] = $expression;
  }

  /**
   * Add multiple select expressions to the generated query.
   *
   * If the supplied array has string keys, they will be used as aliases
   * for the corresponding expression. Numeric keys are ignored.
   *
   * @param array $expression Expressions passed to addSelectExpression.
   *
   * @return void
   */
  public function addSelectExpressions($expressions)
  {
    foreach ($expressions as $alias => $expression) {
      if (is_numeric($alias)) {
        $this->addSelectExpression($expression);
      } else {
        $this->addSelectExpression($expression, $alias);
      }
    }
  }

  /**
   * Set the data warehouse fact table for the generated query.
   *
   * Only one fact table may be used in a query.
   *
   * @param string $table The name of the fact table.
   *
   * @return void
   */
  public function setFactTable($table)
  {
    $this->_factTable = $table;
  }

  /**
   * Add a data warehouse dimensino table to the generated query.
   *
   * A dimension table may only be joined once in the query, if the same
   * table is added multiple times, no errors are produced, but the
   * table is only used once.
   *
   * @param string $table The name of the dimension table.
   *
   * @return void
   */
  public function addDimensionTable($table)
  {
    if (!in_array($table, $this->_dimensionTables)) {
      $this->_dimensionTables[] = $table;
    }
  }

  /**
   * Set the filter expression to be used in the generated query.
   *
   * @param string $expr The filter expression. It should be a valid SQL
   *   expression using only column from the fact or dimension tables
   *   that have been added.
   *
   * @return void
   */
  public function setFilterExpression($expr)
  {
    $this->_filterExpression = $expr;
  }

  /**
   * Add a WHERE clause to the generated query.
   *
   * @param string $expr A valid SQL expression.
   * @param string $op A valid SQL (binary) operator (e.g. =, >, LIKE).
   * @param string $value (optional) The value to be used with the
   *   expression. This value will be paramterized and returned in an
   *   array when the query is built. If ommited, it is necessary to add
   *   the value to the parameter array before executing the query.
   *
   * @return void
   */
  public function addWhereClause($expr, $op, $value = null)
  {
    $this->_whereClauses[] = array($expr, $op, $value);
  }

  /**
   * Set the GROUP BY column or expression used in the generated query.
   *
   * @param string $expr The column or expression to GROUP BY.
   *
   * @return void
   */
  public function setGroupBy($expr)
  {
    $this->_groupBy = $expr;
  }

  /**
   * Set the ORDER BY column or expression used in the generated query.
   *
   * @param string $expr The column or expression to ORDER BY.
   *
   * @return void
   */
  public function setOrderBy($expr)
  {
    $this->_orderBy = $expr;
  }

  /**
   * Clear any data that would be used to LIMIT the generated query.
   *
   * @return void
   */
  public function clearLimit()
  {
    $this->_limitRowCount = null;
    $this->_limitOffset   = null;
  }

  /**
   * Set the query parameters using an object.
   *
   * This will set all the relevant properties and add any clauses that
   * can be determined from the given object.
   *
   * @param Ubmod_Model_QueryParams $params The query parameters.
   *
   * @return void
   */
  public function setQueryParams(Ubmod_Model_QueryParams $params)
  {
    if ($params->hasClusterId()) {
      $this->addWhereClause('dim_cluster_id', '=', $params->getClusterId());
    }

    if ($params->hasQueueId()) {
      $this->addWhereClause('dim_queue_id', '=', $params->getQueueId());
    }

    if ($params->hasUserId()) {
      $this->addWhereClause('dim_user_id', '=', $params->getUserId());
    }

    if ($params->hasGroupId()) {
      $this->addWhereClause('dim_group_id', '=', $params->getGroupId());
    }

    if ($params->hasCpusId()) {
      $this->addWhereClause('dim_cpus_id', '=', $params->getCpusId());
    }

    if ($params->hasDateData()) {
      $this->addDimensionTable('dim_date');

      if ($params->hasStartDate()) {
        $this->addWhereClause('date', '>=', $params->getStartDate());
      }

      if ($params->hasEndDate()) {
        $this->addWhereClause('date', '<=', $params->getEndDate());
      }

      if ($params->hasMonth()) {
        $this->addWhereClause('month', '=', $params->getMonth());
      }

      if ($params->hasYear()) {
        $this->addWhereClause('year', '=', $params->getYear());
      }

      if ($params->isLast365Days()) {
        $this->addWhereClause('last_365_days', '=', 1);
      }

      if ($params->isLast90Days()) {
        $this->addWhereClause('last_90_days', '=', 1);
      }

      if ($params->isLast30Days()) {
        $this->addWhereClause('last_30_days', '=', 1);
      }

      if ($params->isLast7Days()) {
        $this->addWhereClause('last_7_days', '=', 1);
      }
    }

    // If the parameters include a model add a predetermined set of
    // parameters that result in each row of the query results to
    // represent a instance of that model.
    if ($params->hasModel()) {
      $model = $params->getModel();
      if ($model === 'user') {
        $this->addDimensionTable('dim_user');
        $this->setGroupBy('dim_user_id');
        $this->setFilterExpression('dim_user.name');
        $this->addSelectExpressions(array(
          'user_id'      => 'dim_user_id',
          'user'         => 'dim_user.name',
          'display_name' => 'COALESCE(dim_user.display_name, dim_user.name)',
        ));
      } elseif ($model === 'group') {
        $this->addDimensionTable('dim_group');
        $this->setGroupBy('dim_group_id');
        $this->setFilterExpression('dim_group.name');
        $this->addSelectExpressions(array(
          'group_id'     => 'dim_group_id',
          'group_name'   => 'dim_group.name',
          'display_name' => 'COALESCE(dim_group.display_name,'
                          . ' dim_group.name)',
          'user_count'   => 'COUNT(DISTINCT dim_user_id)',
        ));
      } elseif ($model === 'queue') {
        $this->addDimensionTable('dim_queue');
        $this->setGroupBy('dim_queue_id');
        $this->setFilterExpression('dim_queue.name');
        $this->addSelectExpressions(array(
          'queue_id'     => 'dim_queue_id',
          'queue'        => 'dim_queue.name',
          'display_name' => 'COALESCE(dim_queue.display_name,'
                          . ' dim_queue.name)',
          'user_count'   => 'COUNT(DISTINCT dim_user_id)',
          'group_count'  => 'COUNT(DISTINCT dim_group_id)',
        ));
      } elseif ($model === 'cluster') {
        $this->addDimensionTable('dim_cluster');
        $this->setGroupBy('dim_cluster_id');
        $this->setFilterExpression('dim_cluster.name');
        $this->addSelectExpressions(array(
          'cluster_id'   => 'dim_cluster_id',
          'cluster'      => 'dim_cluster.name',
          'display_name' => 'COALESCE(dim_cluster.display_name,'
                          . ' dim_cluster.name)',
          'user_count'   => 'COUNT(DISTINCT dim_user_id)',
          'group_count'  => 'COUNT(DISTINCT dim_group_id)',
          'queue_count'  => 'COUNT(DISTINCT dim_queue_id)',
        ));
      }
    }

    if ($params->hasGroupByColumn()) {
      $this->_groupBy = $params->getGroupByColumn();
    }

    if ($params->hasFilter() && $this->_filterExpression !== null) {
      $this->addWhereClause($this->_filterExpression, 'LIKE',
        '%' . $params->getFilter() . '%');
    }

    if ($params->hasTag()) {
      $this->addDimensionTable('dim_user');
      $this->addWhereClause('tags', 'LIKE',
        '%' . json_encode($params->getTag()) . '%');
    }

    if ($params->hasOrderByColumn()) {
      $column = $params->getOrderByColumn();

      # FIXME shouldn't check _selectExpressions directly
      if (isset($this->_selectExpressions["`$column`"])) {
        $this->_orderBy = $column;
        $this->_orderByDesc = $params->isOrderByDescending();
      }
    }

    if ($params->hasLimitRowCount()) {
      $this->_limitRowCount = $params->getLimitRowCount();

      if ($params->hasLimitOffset()) {
        $this->_limitOffset = $params->getLimitOffset();
      }
    }
  }

  /**
   * Create a SQL query.
   *
   * @return array The first element is a SQL string, the second element
   *   is an array of bind parameters.
   */
  public function buildQuery()
  {
    $selectExpressions = array();
    foreach ($this->_selectExpressions as $alias => $expression) {
      $selectExpressions[] = "$expression AS $alias";
    }

    $sql = 'SELECT ' . implode(', ', $selectExpressions);

    $sql .= ' FROM ' . $this->_getTableReferences();

    list($where, $params) = $this->_getWhereClause();

    $sql .= $where;

    if ($this->_groupBy !== null) {
      $sql .= ' GROUP BY ' . $this->_groupBy;
    }

    if ($this->_orderBy !== null) {
      $sql .= ' ORDER BY ' . $this->_orderBy;
      if ($this->_orderByDesc) {
        $sql .= ' DESC';
      }
    }

    if ($this->_limitRowCount !== null) {
      $sql .= ' LIMIT ' . $this->_limitRowCount;
      if ($this->_limitOffset !== null) {
        $sql .= ' OFFSET ' . $this->_limitOffset;
      }
    }

    $sql = Ubmod_DataWarehouse::optimize($sql);

    return array($sql, $params);
  }

  /**
   * Create a SQL query to count the number of rows that would be
   * returned by the corresponding query, if no LIMIT is applied.
   *
   * The query SELECTs a single expression aliased to "count".
   *
   * @return array The first element is a SQL string, the second element
   *   is an array of bind parameters.
   */
  public function buildCountQuery()
  {
    $sql = 'SELECT ';

    if ($this->_groupBy !== null) {
      $sql .= 'COUNT(DISTINCT ' . $this->_groupBy . ')';
    } else {
      $sql .= 'COUNT(*)';
    }
    $sql .= ' AS count ';

    $sql .= ' FROM ' . $this->_getTableReferences();

    list($where, $params) = $this->_getWhereClause();

    $sql .= $where;

    $sql = Ubmod_DataWarehouse::optimize($sql);

    return array($sql, $params);
  }

  /**
   * Returns a SQL fragment containing the table references to be
   * included in the query.
   *
   * @return string
   */
  private function _getTableReferences()
  {
    $sql = $this->_factTable;

    foreach ($this->_dimensionTables as $dimension) {
      $sql .= " JOIN $dimension USING (${dimension}_id)";
    }

    return $sql;
  }

  /**
   * Returns the WHERE clause to be used in the query. Also returns an
   * array of bind parameters.
   *
   * @return array The first element is a SQL string, the second element
   *   is an array of bind parameters.
   */
  private function _getWhereClause()
  {
    if (count($this->_whereClauses) === 0) {
      return array('', array());
    }

    $params       = array();
    $whereClauses = array();

    foreach ($this->_whereClauses as $clause) {
      list($column, $oper, $value) = $clause;

      $key = str_replace('.', '_', ":$column");

      if ($value !== null) {

        // Prevent duplicate parameter keys
        $origKey = $key;
        $count = 0;
        do {
          $count++;
          $key = $origKey . $count;
        } while (isset($params[$key]));

        $params[$key] = $value;
      }

      $whereClauses[] = "$column $oper $key";
    }

    $sql = ' WHERE ' . implode(' AND ', $whereClauses);

    return array($sql, $params);
  }
}
