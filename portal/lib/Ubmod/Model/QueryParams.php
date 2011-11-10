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
 * Query parameter model.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $_Id: User.php 3116 2011-09-13 15:10:23Z jtpalmer@K5.CCR.BUFFALO.EDU $_
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Convenience class to encapsulate parameters used by database queries.
 *
 * @package Ubmod
 */
class Ubmod_Model_QueryParams
{

  /**
   * Cluster dimension primary key.
   *
   * @var int
   */
  protected $_clusterId = null;

  /**
   * Time interval primary key.
   *
   * @var int
   */
  protected $_timeIntervalId = null;

  /**
   * Time interval start date.
   *
   * Used for custom date range queries. Stored in MM/DD/YYYY format.
   *
   * @var string
   */
  protected $_startDate = null;

  /**
   * Time interval end date.
   *
   * Used for custom date range queries. Stored in MM/DD/YYYY format.
   *
   * @var string
   */
  protected $_endDate = null;

  /**
   * Time interval year.
   *
   * Used for monthly queries.
   *
   * @var int
   */
  protected $_year = null;

  /**
   * Time interval month.
   *
   * Used for monthly queries.
   *
   * @var int
   */
  protected $_month = null;

  /**
   * Queue dimension primary key.
   *
   * @var int
   */
  protected $_queueId = null;

  /**
   * User dimension primary key.
   *
   * @var int
   */
  protected $_userId = null;

  /**
   * Group dimension primary key.
   *
   * @var int
   */
  protected $_groupId = null;

  /**
   * CPUs dimension primary key.
   *
   * @var int
   */
  protected $_cpusId = null;

  /**
   * Filter keyword to add to the WHERE clause.
   *
   * @var string
   */
  protected $_filter = null;

  /**
   * ORDER BY column.
   *
   * @var string
   */
  protected $_orderByColumn = null;

  /**
   * Indicates that the ORDER BY is descending.
   *
   * @var bool
   */
  protected $_isOrderByDescending = null;

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
   * Tag name.
   *
   * @var string
   */
  protected $_tag = null;

  /**
   * Private constructor.
   *
   * @return Ubmod_Model_QueryParams
   */
  private function __construct()
  {
  }

  /**
   * Factory method.
   *
   * Creates an instance and initializes it using an array. The keys of
   * the array are formatted using underscore (e.g. cluster_id) that
   * correspond to the properties of the object.
   *
   * @param array $params
   *
   * @return Ubmod_Model_QueryParams
   */
  public static function factory($params)
  {
    $query = new Ubmod_Model_QueryParams();

    if (isset($params['cluster_id'])) {
      $query->setClusterId(intval($params['cluster_id']));
    }

    if (isset($params['interval_id'])) {
      $query->setTimeIntervalId(intval($params['interval_id']));
    }

    if (isset($params['start_date'])) {
      $query->setStartDate($params['start_date']);
    }

    if (isset($params['end_date'])) {
      $query->setEndDate($params['end_date']);
    }

    if (isset($params['queue_id'])) {
      $query->setQueueId(intval($params['queue_id']));
    }

    if (isset($params['user_id'])) {
      $query->setUserId(intval($params['user_id']));
    }

    if (isset($params['group_id'])) {
      $query->setGroupId(intval($params['group_id']));
    }

    if (isset($params['cpus_id'])) {
      $query->setCpusId(intval($params['cpus_id']));
    }

    if (isset($params['filter'])) {
      $query->setFilter($params['filter']);
    }

    if (isset($params['sort'])) {
      $query->setOrderByColumn($params['sort']);
    }

    if (isset($params['dir'])) {
      $query->setOrderByDescending($params['dir'] === 'DESC');
    }

    if (isset($params['start'])) {
      $query->setLimitOffset(intval($params['start']));
    }

    if (isset($params['limit'])) {
      $query->setLimitRowCount(intval($params['limit']));
    }

    if (isset($params['tag'])) {
      $query->setTag($params['tag']);
    }

    return $query;
  }

  /**
   * Set the cluster ID.
   *
   * @param int $clusterId The cluster dimension primary key.
   *
   * @return void
   */
  public function setClusterId($clusterId)
  {
    $this->_clusterId = $clusterId;
  }

  /**
   * Get the cluster ID.
   *
   * @return int The cluster dimension primary key.
   */
  public function getClusterId()
  {
    return $this->_clusterId;
  }

  /**
   * Check if the cluster ID is set.
   *
   * @return int True if the cluster ID is set.
   */
  public function hasClusterId()
  {
    return $this->_clusterId !== null;
  }

  /**
   * Set the time interval ID.
   *
   * @param int $intervalId The time interval primary key.
   *
   * @return void
   */
  public function setTimeIntervalId($intervalId)
  {
    $this->_timeIntervalId = $intervalId;
  }

  /**
   * Get the time interval ID.
   *
   * @return int The time interval primary key.
   */
  public function getTimeIntervalId()
  {
    return $this->_timeIntervalId;
  }

  /**
   * Check if the time interval ID is set.
   *
   * @return bool True if the time interval ID is set.
   */
  public function hasTimeIntervalId()
  {
    return $this->_timeIntervalId !== null;
  }

  /**
   * Clear the time interval ID.
   *
   * @return void
   */
  public function clearTimeIntervalId()
  {
    $this->_timeIntervalId = null;
  }

  /**
   * Set the time interval start date.
   *
   * @param string $startDate The time interval start date.
   *
   * @return void
   */
  public function setStartDate($startDate)
  {
    if (!$this->_isDateValid($startDate)) {
      throw new Exception("Invalid date format: '$startDate'");
    }

    $this->_startDate = $startDate;
  }

  /**
   * Get the time interval start date.
   *
   * @return string The time interval start date.
   */
  public function getStartDate()
  {
    return $this->_startDate;
  }

  /**
   * Set the time interval end date.
   *
   * @param string $endDate The time interval end date.
   *
   * @return void
   */
  public function setEndDate($endDate)
  {
    if (!$this->_isDateValid($endDate)) {
      throw new Exception("Invalid date format: '$endDate'");
    }

    $this->_endDate = $endDate;
  }

  /**
   * Get the time interval end date.
   *
   * @return string The time interval end date.
   */
  public function getEndDate()
  {
    return $this->_endDate;
  }

  /**
   * Set the time interval month.
   *
   * @param int $month The time interval month.
   *
   * @return void
   */
  public function setMonth($month)
  {
    $this->_month = $month;
  }

  /**
   * Get the time interval month.
   *
   * @return int The time interval month.
   */
  public function getMonth()
  {
    return $this->_month;
  }

  /**
   * Set the time interval year.
   *
   * @param int $year The time interval year.
   *
   * @return void
   */
  public function setYear($year)
  {
    $this->_year = $year;
  }

  /**
   * Get the time interval year.
   *
   * @return int The time interval year.
   */
  public function getYear()
  {
    return $this->_year;
  }

  /**
   * Set the queue ID.
   *
   * @param int $queueId The queue dimension primary key.
   *
   * @return void
   */
  public function setQueueId($queueId)
  {
    $this->_queueId = $queueId;
  }

  /**
   * Get the queue ID.
   *
   * @return int The queue dimension primary key.
   */
  public function getQueueId()
  {
    return $this->_queueId;
  }

  /**
   * Check if the queue ID is set.
   *
   * @return int True if the queue ID is set.
   */
  public function hasQueueId()
  {
    return $this->_queueId !== null;
  }

  /**
   * Set the user ID.
   *
   * @param int $userId The user dimension primary key.
   *
   * @return void
   */
  public function setUserId($userId)
  {
    $this->_userId = $userId;
  }

  /**
   * Get the user ID.
   *
   * @return int The user dimension primary key.
   */
  public function getUserId()
  {
    return $this->_userId;
  }

  /**
   * Check if the user ID is set.
   *
   * @return int True if the user ID is set.
   */
  public function hasUserId()
  {
    return $this->_userId !== null;
  }

  /**
   * Set the group ID.
   *
   * @param int $groupId The group dimension primary key.
   *
   * @return void
   */
  public function setGroupId($groupId)
  {
    $this->_groupId = $groupId;
  }

  /**
   * Get the group ID.
   *
   * @return int The group dimension primary key.
   */
  public function getGroupId()
  {
    return $this->_groupId;
  }

  /**
   * Check if the group ID is set.
   *
   * @return int True if the group ID is set.
   */
  public function hasGroupId()
  {
    return $this->_groupId !== null;
  }

  /**
   * Set the cpus ID.
   *
   * @param int $cpusId The cpus dimension primary key.
   *
   * @return void
   */
  public function setCpusId($cpusId)
  {
    $this->_cpusId = $cpusId;
  }

  /**
   * Get the cpus ID.
   *
   * @return int The cpus dimension primary key.
   */
  public function getCpusId()
  {
    return $this->_cpusId;
  }

  /**
   * Check if the cpus ID is set.
   *
   * @return int True if the cpus ID is set.
   */
  public function hasCpusId()
  {
    return $this->_cpusId !== null;
  }

  /**
   * Set the filter keyword.
   *
   * @param string $filter The filter keyword.
   *
   * @return void
   */
  public function setFilter($filter)
  {
    $this->_filter = $filter;
  }

  /**
   * Get the filter keyword
   *
   * @return string The filter keyword.
   */
  public function getFilter()
  {
    return $this->_filter;
  }

  /**
   * Check if the filter keyword is set.
   *
   * @return bool True if the the filter keyword is set.
   */
  public function hasFilter()
  {
    return $this->_filter !== null && $this->_filter !== '';
  }

  /**
   * Set the ORDER BY column.
   *
   * @param string $orderByColumn The ORDER BY column.
   *
   * @return void
   */
  public function setOrderByColumn($orderByColumn)
  {
    $this->_orderByColumn = $orderByColumn;
  }

  /**
   * Get the ORDER BY column.
   *
   * @return string The ORDER BY column.
   */
  public function getOrderByColumn()
  {
    return $this->_orderByColumn;
  }

  /**
   * Check if the ORDER BY column is set.
   *
   * @return bool True if the the ORDER BY column is set.
   */
  public function hasOrderByColumn()
  {
    return $this->_orderByColumn !== null;
  }

  /**
   * Set the ORDER BY to be descending.
   *
   * @param bool $orderByDescending The ORDER BY column.
   *
   * @return void
   */
  public function setOrderByDescending($orderByDescending)
  {
    $this->_orderByDescending = $orderByDescending;
  }

  /**
   * Returns true if the ORDER BY is descending.
   *
   * @return bool True if descending.
   */
  public function isOrderByDescending()
  {
    return $this->_orderByDescending;
  }

  /**
   * Set the LIMIT offset.
   *
   * @param int $limitOffset The LIMIT offset.
   *
   * @return void
   */
  public function setLimitOffset($limitOffset)
  {
    $this->_limitOffset = $limitOffset;
  }

  /**
   * Get the LIMIT offset.
   *
   * @return int The LIMIT offset.
   */
  public function getLimitOffset()
  {
    return $this->_limitOffset;
  }

  /**
   * Check if the LIMIT offset is set.
   *
   * @return bool True if the LIMIT offset is set.
   */
  public function hasLimitOffset()
  {
    return $this->_limitOffset !== null;
  }

  /**
   * Set the LIMIT row count.
   *
   * @param int $limitRowCount The LIMIT row count.
   *
   * @return void
   */
  public function setLimitRowCount($limitRowCount)
  {
    $this->_limitRowCount = $limitRowCount;
  }

  /**
   * Get the LIMIT row count.
   *
   * @return int The LIMIT row count.
   */
  public function getLimitRowCount()
  {
    return $this->_limitRowCount;
  }

  /**
   * Check if the LIMIT row count is set.
   *
   * @return bool True if the LIMIT row count is set.
   */
  public function hasLimitRowCount()
  {
    return $this->_limitRowCount !== null;
  }

  /**
   * Set the tag.
   *
   * @param string $tag The tag.
   *
   * @return void
   */
  public function setTag($tag)
  {
    $this->_tag = $tag;
  }

  /**
   * Get the tag.
   *
   * @return string The tag.
   */
  public function getTag()
  {
    return $this->_tag;
  }

  /**
   * Check if the tag is set.
   *
   * @return bool True if the tag is set.
   */
  public function hasTag()
  {
    return $this->_tag !== null;
  }

  /**
   * Check if a string is a valid date.
   *
   * The only valid date format is MM/DD/YYYY
   *
   * @param string $date The date string to check.
   *
   * @return bool True if that date is valid.
   */
  private function _isDateValid($date)
  {
     return preg_match('# ^ (\d\d) / (\d\d) / (\d{4}) $ #x', $date);
  }
}
