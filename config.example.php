<?php
/**
 * config.example.php
 * example config file
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.1
 */

// mysql configuration
define("MYSQL_HOST", "localhost");
define("MYSQL_USER", "");
define("MYSQL_PASS", "");
define("MYSQL_DB", "");

// plugins (just the names in the array)
$ACTIVE_PLUGINS = array();

// sites
$SITES = array(
    'CLIENT-ID' => array(
        'URLS' => array(
            'http://localhost/',
            'http://site.aiesec.org/subdir/'
        )
    )
);