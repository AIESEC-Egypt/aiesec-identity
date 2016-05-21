<?php
/**
 * clear.php
 * deletes gis identity session files which are unused since more than a week
 *
 * @author Karl Johann Schubert <karljohann@familieschubi.de>
 * @version 0.1
 */
if(file_exists('./sessions')) {
    $deadline = time() - (7 * 24 * 60 * 60);
    $dir = new DirectoryIterator('./sessions');
    foreach ($dir as $file) {
        if (!$file->isDot() && substr($file->getFilename(), -4, 4) == '.txt') {
            if(fileatime($file->getPathname()) < $deadline && filectime($file->getPathname()) < $deadline) {
                unlink($file->getPathname());
            }
        }
    }
}