#!/usr/bin/env php
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
 * Import user name and tags from a JSON file.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2012
 * @package Ubmod_Examples
 */

require_once dirname(__FILE__) . '/../../portal/config/bootstrap.php';

if ($argc !== 2) {
  usage();
  exit(1);
}

$usersFile = $argv[1];

$users = json_decode(file_get_contents($usersFile), true);

foreach ($users as $name => $user) {
  $dbUser = getUser($name);

  if ($dbUser) {
    echo "Updating user: $name\n";
    $id = $dbUser['dim_user_id'];
  } else {
    echo "Adding new user: $name\n";
    $id = insertUser($name);
    echo "Successfully inserted new user with id: $id\n";
  }

  if (isset($user['display_name'])) {
    if ($dbUser && $dbUser['display_name'] !== $user['display_name']) {
      echo 'Found different display name: ' . $user['display_name'];
    }
    updateUser($name, $user['display_name']);
    echo "Successfully updated user's display name\n";
  }

  if (isset($user['tags'])) {
    Ubmod_Model_User::updateTags($id, $user['tags']);
    echo "Successfully updated tags\n";
  }
}

exit(0);

/**
 * Ouput usage message.
 */
function usage()
{
  global $argv;
  echo "usage: {$argv[0]} user_data.json\n";
}

/**
 * Get user data.
 *
 * @param string $name The user's username
 *
 * @return array
 */
function getUser($name)
{
  $sql = "SELECT * FROM dim_user WHERE name = :name";

  $dbh = Ubmod_DbService::dbh();
  $stmt = $dbh->prepare($sql);
  $r = $stmt->execute(array(':name' => $name));
  if (!$r) {
    $err = $stmt->errorInfo();
    throw new Exception($err[2]);
  }

  return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Create a new user in the database.
 *
 * @param string $name The user's username
 *
 * @return int The user's primary key
 */
function insertUser($name)
{
  $sql = "INSERT INTO dim_user SET name = :name";

  $dbh = Ubmod_DbService::dbh();
  $stmt = $dbh->prepare($sql);
  $r = $stmt->execute(array(':name' => $name));
  if (!$r) {
    $err = $stmt->errorInfo();
    throw new Exception($err[2]);
  }

  return $dbh->lastInsertId();
}

/**
 * Update a user's display name in the database.
 *
 * @param string $name The user's username
 * @param string $displayName The users's display name
 */
function updateUser($name, $displayName = '')
{
  $sql = "
    UPDATE dim_user SET
    display_name = :display_name
    WHERE name = :name
  ";

  $dbh = Ubmod_DbService::dbh();
  $stmt = $dbh->prepare($sql);
  $r = $stmt->execute(array(
    ':name'         => $name,
    ':display_name' => $displayName,
  ));
  if (!$r) {
    $err = $stmt->errorInfo();
    throw new Exception($err[2]);
  }
}

