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
 * Add a tag to every event in the database that has an account.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

// Update this to the location of the bootstrap file, if it is in a
// different location on your system.
require_once '/etc/ubmod/bootstrap.php';

$selectSql = '
  SELECT event_id, account, tags
  FROM event
  WHERE account IS NOT NULL
';

$updateSql = '
  UPDATE event
  SET tags = :tags
  WHERE event_id = :event_id
';

$dbh = Ubmod_DbService::dbh();

$selectStmt = $dbh->prepare($selectSql);
$updateStmt = $dbh->prepare($updateSql);

$r = $selectStmt->execute();
if (!$r) {
  $err = $selectStmt->errorInfo();
  die($err[2]);
}

$eventCount = 0;

while ($event = $selectStmt->fetch(PDO::FETCH_ASSOC)) {
  $tags = json_decode($event['tags'], true);

  // Add account tag.
  $tags[] = 'Account=' . $event['account'];

  // Remove any duplicates and sort tags.
  $tags = array_unique($tags);
  sort($tags);

  $r = $updateStmt->execute(array(
    ':tags'     => json_encode($tags),
    ':event_id' => $event['event_id'],
  ));
  if (!$r) {
    $err = $updateStmt->errorInfo();
    die($err[2]);
  }
  $eventCount++;
}

echo "Updated $eventCount events\n";
