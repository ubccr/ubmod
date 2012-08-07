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
 * Bootstrap tests.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2011
 * @package Ubmod
 */

$dir = dirname(__FILE__);

define('TEST_DIR', $dir);

# bootstrap UBMoD code
require_once $dir . '/../config/bootstrap.php';

# Autoloader for test classes
spl_autoload_register(function($className) use($dir) {
  $classPath = $dir . '/lib/' . str_replace('_', '/', $className) . '.php';

  if (file_exists($classPath)) {
    return require_once $classPath;
  } else {
    return false;
  }
});

# Override UBMoD options
$configFile = $dir . '/config/settings.ini';
$GLOBALS['options'] = new Zend_Config_Ini($configFile);

