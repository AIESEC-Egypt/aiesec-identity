<?php
/**
 * clear.php
 * clear the session path
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @package AIESEC\Identity
 * @version 0.2
 */
namespace AIESEC\Identity;

// require composer autoload
require_once __DIR__ . '/vendor/autoload.php';

// check that config file exists
if(!file_exists(__DIR__ . '/config.php')) {
    die("System not configured");
}

// load config
require_once __DIR__ . '/config.php';

// check that session path exists
if(!is_dir(SESSION_PATH) || !is_writeable(SESSION_PATH)) {
    trigger_error("Session folder does not exists or is not writeable", E_USER_ERROR);
    die("configuration error");
}

$deadline = time() - SESSION_LIFETIME;
$dir = new \DirectoryIterator(SESSION_PATH);
foreach($dir as $file) {
    if (!$file->isDot() && substr($file->getFilename(), -4, 4) == '.txt') {
        if(fileatime($file->getPathname()) < $deadline && filectime($file->getPathname()) < $deadline) {
            $res = DBController::query("SELECT `id` FROM `persons` WHERE `session_file`='" . $file->getPathname() . "'");
            if($res && $res->num_rows < 1) {
                unlink($file->getPathname());
            }
        }
    }
}