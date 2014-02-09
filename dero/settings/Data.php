<?php

/**
 * Engines
 */
define('DB_ENG_MYSQL', 'mysql');
define('DB_ENG_MSSQL', 'sqlsrv');
define('DB_ENG_SQLITE', 'sqlite');
define('DB_ENG_POSTGRE', 'pgsql');

/**
 * Parameter Data types
 */
define('DB_PARAM_INT', 1);
define('DB_PARAM_STR', 2);
define('DB_PARAM_BOOL', 3);
define('DB_PARAM_NULL', 4);
define('DB_PARAM_DEC', 5);

/**
 * Column Data Types
 */
define('COL_TYPE_INTEGER', 1);
define('COL_TYPE_DECIMAL', 2);
define('COL_TYPE_BOOLEAN', 3);
define('COL_TYPE_DATETIME', 4);
define('COL_TYPE_TEXT', 5);
define('COL_TYPE_STRING', 6);
define('COL_TYPE_FIXED_STRING', 7);

/**
 * Table Key Types
 */
define('KEY_TYPE_PRIMARY', 1);
define('KEY_TYPE_FOREIGN', 2);
