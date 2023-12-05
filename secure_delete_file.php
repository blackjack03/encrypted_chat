<?php

/* ### WARNING ### */

function securelyDeleteFile_DoD($filePath) {
    if(!file_exists($filePath)) {
        return false;
    }

    $fileSize = filesize($filePath);

    $file = fopen($filePath, 'wb'); // Write Binary

    for ($i = 0; $i < $fileSize; $i++) {
        fwrite($file, "\0");
    }
    fflush($file);

    fseek($file, 0);
    for ($i = 0; $i < $fileSize; $i++) {
        fwrite($file, "\xFF");
    }
    fflush($file);

    fseek($file, 0);
    for ($i = 0; $i < $fileSize; $i++) {
        $randomByte = random_int(0, 255);
        fwrite($file, chr($randomByte));
    }
    fflush($file);

    fclose($file);

    unlink($filePath);
    return true;
}

function securelyDeleteFile_gutmann($filePath) {
    if(!file_exists($filePath)) {
        return false;
    }

    $fileSize = filesize($filePath);
    $file = fopen($filePath, 'wb');

    $patterns = [
        pack('H*', '55'),
        pack('H*', 'AA'),
        pack('H*', '924924'),
        pack('H*', '492492'),
        pack('H*', '249249'),
        pack('H*', '00'),
        pack('H*', '11'),
        pack('H*', '22'),
        pack('H*', '33'),
        pack('H*', '44'),
        pack('H*', '55'),
        pack('H*', '66'),
        pack('H*', '77'),
        pack('H*', '88'),
        pack('H*', '99'),
        pack('H*', 'AA'),
        pack('H*', 'BB'),
        pack('H*', 'CC'),
        pack('H*', 'DD'),
        pack('H*', 'EE'),
        pack('H*', 'FF'),
        pack('H*', '924924'),
        pack('H*', '492492'),
        pack('H*', '249249'),
        pack('H*', '6DB6DB'),
        pack('H*', 'B6DB6D'),
        pack('H*', 'DB6DB6')
    ];

    // Add 8 random pattern(s) to patterns array
    for ($i = 0; $i < 8; $i++) {
        array_push($patterns, pack('H*', bin2hex(random_bytes(1))));
    }

    /* File Erase */

    foreach ($patterns as $pattern) {
        fseek($file, 0);
        for ($i = 0; $i < $fileSize; $i += strlen($pattern)) {
            fwrite($file, $pattern);
        }
        fflush($file);
    }

    for ($j = 0; $j < 4; $j++) {
        fseek($file, 0);
        for ($i = 0; $i < $fileSize; $i++) {
            $randomByte = random_int(0, 255);
            fwrite($file, chr($randomByte));
        }
        fflush($file);
    }

    fclose($file);

    unlink($filePath);
    return true;
}

?>
