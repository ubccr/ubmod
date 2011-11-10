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
      $alias = "'$expression'";
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

    // XXX QueryParams should include indiviudal attributes
    $this->_whereClauses[] = Ubmod_Model_Interval::getWhereClause($params);
    $this->addDimensionTable('dim_date');

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
      if (isset($this->_selectExpressions[$column])) {
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

    $sql .= ' FROM ' . $this->_factTable;

    foreach ($this->_dimensionTables as $dimension) {
      $sql .= " JOIN $dimension USING (${dimension}_id)";
    }

    $params = array();

    if (count($this->_whereClauses) > 0) {
      $whereClauses = array();
      foreach ($this->_whereClauses as $clause) {
        if (is_array($clause)) {
          list($column, $oper, $value) = $clause;
          $key = ":$column";
          $whereClauses[] = "$column $oper $key";

          if ($value !== null) {
            $params[$key] = $value;
          }
        } else {
          // XXX this is necessary due to the current date handling
          $whereClauses[] = $clause;
        }
      }
      $sql .= ' WHERE ' . implode(' AND ', $whereClauses);
    }

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
}
