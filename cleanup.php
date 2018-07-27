<?php

function deleteDir($dir) {
    if (!file_exists($dir)) {
        return;
    }
    foreach (scandir($dir) as $entry) {
        if (in_array($entry, ['.', '..'])) {
            continue;
        }
        if (is_dir($dir . DIRECTORY_SEPARATOR . $entry)) {
            deleteDir($dir . DIRECTORY_SEPARATOR . $entry);
        } else {
            unlink($dir . DIRECTORY_SEPARATOR . $entry);
        }
    }
}

deleteDir(__DIR__ . DIRECTORY_SEPARATOR . 'tmp');
echo 'Clenup complete<br/>';