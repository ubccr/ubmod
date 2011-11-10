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
   * Remove a SELECT expression from the query.
   *
   * @param string $alias The alias of the expression that should be
   *   removed. This may be the expression if no alias was specified.
   *
   * @return void
   */
  public function removeSelectExpression($alias)
  {
    unset($this->_selectExpressions["`$alias`"]);
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
   * @param string $expr A valid SQL expression. It may contain named
   *   paramater markers.
   * @param array $params (optional) An array of named parameters that
   *   should be used with the expression. These will be returned in an
   *   array when the query is built. If ommited, and the expression
   *   contains parameter markers, it is necessary to add the value to
   *   the parameter array before executing the query.
   *
   * @return void
   */
  public function addWhereClause($expr, array $params = array())
  {
    $this->_whereClauses[] = array($expr, $params);
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
      $this->addWhereClause('dim_cluster_id = :dim_cluster_id', array(
        ':dim_cluster_id' => $params->getClusterId()
      ));
    }

    if ($params->hasQueueId()) {
      $this->addWhereClause('dim_queue_id = :dim_queue_id', array(
        ':dim_queue_id' => $params->getQueueId()
      ));
    }

    if ($params->hasUserId()) {
      $this->addWhereClause('dim_user_id = :dim_user_id', array(
        ':dim_user_id' => $params->getUserId()
      ));
    }

    if ($params->hasGroupId()) {
      $this->addWhereClause('dim_group_id = :dim_group_id', array(
        ':dim_group_id' => $params->getGroupId()
      ));
    }

    if ($params->hasCpusId()) {
      $this->addWhereClause('dim_cpus_id = :dim_cpus_id', array(
        ':dim_cpus_id' => $params->getCpusId()
      ));
    }

    if ($params->hasDateData()) {
      $this->addDimensionTable('dim_date');

      if ($params->hasStartDate()) {
        $this->addWhereClause('date >= :start_date', array(
          ':start_date' => $params->getStartDate()
        ));
      }

      if ($params->hasEndDate()) {
        $this->addWhereClause('date <= :end_date', array(
          ':end_date' => $params->getEndDate()
        ));
      }

      if ($params->hasMonth()) {
        $this->addWhereClause('month = :month', array(
          ':month' => $params->getMonth()
        ));
      }

      if ($params->hasYear()) {
        $this->addWhereClause('year = :year', array(
          ':year' => $params->getYear()
        ));
      }

      if ($params->isLast365Days()) {
        $this->addWhereClause('last_365_days = 1');
      }

      if ($params->isLast90Days()) {
        $this->addWhereClause('last_90_days = 1');
      }

      if ($params->isLast30Days()) {
        $this->addWhereClause('last_30_days = 1');
      }

      if ($params->isLast7Days()) {
        $this->addWhereClause('last_7_days = 1');
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
        $this->removeSelectExpression('user_count');
        $this->removeSelectExpression('group_count');
        $this->removeSelectExpression('queue_count');
      } elseif ($model === 'group') {
        $this->addDimensionTable('dim_group');
        $this->setGroupBy('dim_group_id');
        $this->setFilterExpression('dim_group.name');
        $this->addSelectExpressions(array(
          'group_id'     => 'dim_group_id',
          'group_name'   => 'dim_group.name',
          'display_name' => 'COALESCE(dim_group.display_name,'
                          . ' dim_group.name)',
        ));
        $this->removeSelectExpression('group_count');
        $this->removeSelectExpression('queue_count');
      } elseif ($model === 'queue') {
        $this->addDimensionTable('dim_queue');
        $this->setGroupBy('dim_queue_id');
        $this->setFilterExpression('dim_queue.name');
        $this->addSelectExpressions(array(
          'queue_id'     => 'dim_queue_id',
          'queue'        => 'dim_queue.name',
          'display_name' => 'COALESCE(dim_queue.display_name,'
                          . ' dim_queue.name)',
        ));
        $this->removeSelectExpression('queue_count');
      } elseif ($model === 'cluster') {
        $this->addDimensionTable('dim_cluster');
        $this->setGroupBy('dim_cluster_id');
        $this->setFilterExpression('dim_cluster.name');
        $this->addSelectExpressions(array(
          'cluster_id'   => 'dim_cluster_id',
          'cluster'      => 'dim_cluster.name',
          'display_name' => 'COALESCE(dim_cluster.display_name,'
                          . ' dim_cluster.name)',
        ));
      }
    }

    if ($params->hasGroupByColumn()) {
      $this->_groupBy = $params->getGroupByColumn();
    }

    if ($params->hasFilter() && $this->_filterExpression !== null) {
      $this->addWhereClause($this->_filterExpression . ' LIKE :filter', array(
        ':filter' => '%' . $params->getFilter() . '%'
      ));
    }

    if ($params->hasTag()) {
      $tag = json_encode($params->getTag());
      $this->addDimensionTable('dim_user');
      $this->addDimensionTable('dim_tags');

      $this->addWhereClause(
        '(tags LIKE :tags OR event_tags LIKE :event_tags)',
        array(
          ':tags'       => '%' . $tag . '%',
          ':event_tags' => '%' . $tag . '%',
        )
      );
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

    $params  = array();
    $clauses = array();

    foreach ($this->_whereClauses as $clause) {
      list($expr, $clauseParams) = $clause;

      $clauses[] = $expr;
      $params    = array_merge($params, $clauseParams);
    }

    $sql = ' WHERE ' . implode(' AND ', $clauses);

    return array($sql, $params);
  }
}
