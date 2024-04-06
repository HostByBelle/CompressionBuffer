<?php
function compressWithGzip($data)
{
    return gzencode($data, 9, ZLIB_ENCODING_GZIP);
}

function compressionTest()
{
    $file = 'war_and_peace.html';

    // Read file content
    $originalData = file_get_contents($file);
    $originalLength = strlen($originalData);

    echo "Original data size: " . strlen($originalData) . "\n";

    // Compress using gzip
    $gzipSize = strlen(compressWithGzip($originalData));
    $ratio = 100 - round(($gzipSize / $originalLength) * 100, 3);

    echo "GZIP size reduction: " . $ratio . "%\n";
}

compressionTest();

