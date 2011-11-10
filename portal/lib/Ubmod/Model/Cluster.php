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
 * Cluster model.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Cluster Model
 *
 * @package Ubmod
 */
class Ubmod_Model_Cluster
{

  /**
   * Return cluster data given a cluster ID.
   *
   * @param int id The cluster ID.
   *
   * @return array
   */
  public static function getById($id)
  {
    $dbh = Ubmod_DbService::dbh();
    $sql = '
      SELECT
        dim_cluster_id               AS cluster_id,
        name                         AS host,
        COALESCE(display_name, name) AS display_name
      FROM dim_cluster
      WHERE dim_cluster_id = ?
    ';
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute(array($id));
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * Returns an array of all the clusters.
   *
   * @return array
   */
  public static function getAll()
  {
    $sql = '
      SELECT
        dim_cluster_id               AS cluster_id,
        name                         AS host,
        COALESCE(display_name, name) AS display_name
      FROM dim_cluster
      ORDER BY display_name
    ';
    $dbh = Ubmod_DbService::dbh();
    $stmt = $dbh->prepare($sql);
    $r = $stmt->execute();
    if (!$r) {
      $err = $stmt->errorInfo();
      throw new Exception($err[2]);
    }

    $clusters = $stmt->fetchAll(PDO::FETCH_ASSOC);

    array_unshift($clusters, array('display_name' => 'All'));

    return $clusters;
  }
}
