<?php
/**
 * Define constants used by the application.
 *
 * @author Jeffrey T. Palmer <jtpalmer@ccr.buffalo.edu>
 * @version $Id$
 * @copyright Center for Computational Research, University at Buffalo, 2012
 * @package Ubmod
 */

/**
 * Ubmod version string.
 */
define('UBMOD_VERSION', '0.2.3');

/**
 * Ubmod application base directory.
 */
define('BASE_DIR', dirname(dirname(__FILE__)));

/**
 * Class file directory.
 */
define('LIB_DIR', BASE_DIR . '/lib');

/**
 * Template directory.
 */
define('TEMPLATE_DIR', BASE_DIR . '/templates');

/**
 * Configuration file directory.
 */
define('CONFIG_DIR', BASE_DIR . '/config');

/**
 * Configuration file path.
 */
define('CONFIG_FILE', CONFIG_DIR . '/settings.ini');

/**
 * Menu configuration file path.
 */
define('MENU_CONFIG_FILE', CONFIG_DIR . '/menu.json');

/**
 * Access control list resource definition file path.
 */
define('ACL_RESOURCES_FILE', CONFIG_DIR . '/acl-resources.json');

/**
 * Access control list roles definition file path.
 */
define('ACL_ROLES_FILE', CONFIG_DIR . '/acl-roles.json');

/**
 * Custom user role configuration file path.
 */
define('ROLES_CONFIG_FILE', CONFIG_DIR . '/roles.json');

/**
 * User role mapping file path.
 */
define('USER_ROLES_FILE', CONFIG_DIR . '/user-roles.json');

/**
 * Data warehouse configuration file path.
 */
define('DW_CONFIG_FILE', CONFIG_DIR . '/datawarehouse.json');

/**
 * Directory containing fonts.
 */
define('FONT_DIR', LIB_DIR . '/pChart/fonts');

/**
 * Color palette file.
 */
define('PALETTE_FILE', CONFIG_DIR . '/palette.csv');

