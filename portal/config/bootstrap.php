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
 * Bootstrap the application.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

require_once 'constants.php';

ini_alter('include_path', ini_get('include_path') . ':' . LIB_DIR);

session_start();

/**
 * Generic autoload implementation.
 */
spl_autoload_register(function($className) {
  $file = LIB_DIR . '/' . str_replace('_', '/', $className) . '.php';
  if (file_exists($file)) {
    require_once $file;
  } else {
    return false;
  }
});

/**
 * pChart autoload implementation.
 */
spl_autoload_register(function($className) {
  $file = LIB_DIR . '/pChart/class/' . $className . '.class.php';
  if (file_exists($file)) {
    require_once $file;
  } else {
    return false;
  }
});

$GLOBALS['options'] = new Zend_Config_Ini(CONFIG_FILE);

if ( isset($GLOBALS['options']->portal)
  && isset($GLOBALS['options']->portal->base_url)
) {
  $GLOBALS['BASE_URL'] = rtrim($GLOBALS['options']->portal->base_url, '/');
} else {
  $GLOBALS['BASE_URL'] = '';
}

