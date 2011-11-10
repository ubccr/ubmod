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
 * Time interval model.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

/**
 * Time interval Model
 *
 * @package Ubmod
 **/
class Ubmod_Model_Interval
{

  /**
   * Return time interval data given a interval id.
   *
   * @param int id The interval id
   * @return array
   */
  public static function getById($id)
  {
    $dbh = Ubmod_DbService::dbh();
    $sql = 'SELECT
        interval_id,
        time_interval,
        DATE_FORMAT(start, "%m/%d/%Y") as start,
        DATE_FORMAT(end, "%m/%d/%Y") as end
      FROM time_interval
      WHERE interval_id = ?';
    $stmt = $dbh->prepare($sql);
    $stmt->execute(array($id));
    return $stmt->fetch(PDO::FETCH_ASSOC);
  }

  /**
   * Returns an array of all time intervals.
   *
   * @return array
   */
  public static function getAll()
  {
    $dbh = Ubmod_DbService::dbh();
    $sql = 'SELECT
        interval_id,
        time_interval,
        DATE_FORMAT(start, "%m/%d/%Y") AS start,
        DATE_FORMAT(end, "%m/%d/%Y") AS end
      FROM time_interval';
    $stmt = $dbh->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  }
}
