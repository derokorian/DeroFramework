<?php

/**
 * Engines
 */
define('DB_ENG_MYSQL', 1);
define('DB_ENG_MSSQL', 2);
define('DB_ENG_SQLITE', 3);
define('DB_ENG_POSTGRE', 4);

/**
 * Parameter Data types
 */
define('DB_PARAM_INT', 1);
define('DB_PARAM_STR', 2);
define('DB_PARAM_BOOL', 3);
define('DB_PARAM_NULL', 4);

/**
 * Column Data Types
 */
define('COL_TYPE_INTEGER', 1);
define('COL_TYPE_DECIMAL', 2);
define('COL_TYPE_BOOLEAN', 3);
define('COL_TYPE_DATETIME', 4);
define('COL_TYPE_TEXT', 5);


/**
 * Table Key Types
 */
define('KEY_TYPE_PRIMARY', 1);
define('KEY_TYPE_FOREIGN', 2);
