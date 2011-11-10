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
 * Job model.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id: User.php 3125 2011-09-14 19:33:14Z jtpalmer@K5.CCR.BUFFALO.EDU $
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Job Model.
 *
 * @package Ubmod
 */
class Ubmod_Model_Job
{

  /**
   * Return the number of users with activity for the given parameters.
   *
   * @param Ubmod_Model_QueryParams $params The parameters for the query.
   *
   * @return int
   */
  public static function getActivityCount(Ubmod_Model_QueryParams $params)
  {
    $qb = new Ubmod_DataWarehouse_QueryBuilder();
    $qb->setFactTable('fact_job');
    $qb->setQueryParams($params);


    list($sql, $dbParams) = $qb->buildCountQuery();

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute($dbParams);
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result['count'];
  }

  /**
   * Returns job activity.
   *
   * @param Ubmod_Model_QueryParams $params The parameters for the query.
   *
   * @return array
   */
  public static function getActivity(Ubmod_Model_QueryParams $params)
  {
    $qb = new Ubmod_DataWarehouse_QueryBuilder();

    $qb->setFactTable('fact_job');

    // Common fields
    $qb->addSelectExpressions(array(
      'jobs'      => 'COUNT(*)',
      'wallt'     => 'ROUND(SUM(wallt) / 86400, 1)',
      'avg_wallt' => 'ROUND(AVG(wallt) / 86400, 1)',
      'max_wallt' => 'ROUND(MAX(wallt) / 86400, 1)',
      'cput'      => 'ROUND(SUM(cput)  / 86400, 1)',
      'avg_cput'  => 'ROUND(AVG(cput)  / 86400, 1)',
      'max_cput'  => 'ROUND(MAX(cput)  / 86400, 1)',
      'avg_mem'   => 'ROUND(AVG(mem)   / 1024,  1)',
      'max_mem'   => 'ROUND(MAX(mem)   / 1024,  1)',
      'avg_vmem'  => 'ROUND(AVG(vmem)  / 1024,  1)',
      'max_vmem'  => 'ROUND(MAX(vmem)  / 1024,  1)',
      'avg_wait'  => 'ROUND(AVG(wait)  / 3600,  1)',
      'avg_exect' => 'ROUND(AVG(exect) / 3600,  1)',
      'max_nodes' => 'ROUND(MAX(nodes),         1)',
      'avg_nodes' => 'ROUND(AVG(nodes),         1)',
      'max_cpus'  => 'ROUND(MAX(cpus),          1)',
      'avg_cpus'  => 'ROUND(AVG(cpus),          1)',
    ));

    $qb->setQueryParams($params);

    list($sql, $dbParams) = $qb->buildQuery();

    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute($dbParams);
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
