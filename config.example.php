<?php
/**
 * config.example.php
 * example config file
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.2
 */
namespace AIESEC\Identity;

// mysql configuration
define("MYSQL_HOST", "localhost");
define("MYSQL_USER", "");
define("MYSQL_PASS", "");
define("MYSQL_DB", "");

// plugins (just the names in the array)
$ACTIVE_PLUGINS = array('active');

// Curl SSL Verify Peer
define("VERIFY_PEER", true);

// define session path (both used for php and GIS Identity), don't use the standard path to avoid interferences with the garbage collector
define("SESSION_PATH", __DIR__ . '/sessions');

// define session lifetime in seconds
define("SESSION_LIFETIME", 604800);