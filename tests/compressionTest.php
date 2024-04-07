<?php

function compressWithGzip(string $data, int $level)
{
    return gzencode($data, $level, ZLIB_ENCODING_GZIP);
}

function compressWithZstd(string $data, int $level)
{
    return zstd_compress($data, $level);
}

function compressWithBrotli(string $data, int $level)
{
    return brotli_compress($data, $level);
}

function testGzip($file)
{
    $level = 1;
    $max = 9;
    $originalData = file_get_contents($file);
    $originalLength = strlen($originalData);

    echo '----GZIP----' . PHP_EOL;
    echo 'Level, Reduction, Time, Percent Per Millisecond' . PHP_EOL;
    for ($level; $level <= $max; $level++) {
        $start = hrtime(true);
        $newSize = strlen(compressWithGzip($originalData, $level));
        $ratio = 100 - round(($newSize / $originalLength) * 100, 3);
        $time = (hrtime(true) - $start) / 1e+6;
        $percentPerMs = $ratio / $time;
        echo "$level, $ratio%, $time ms, $percentPerMs% per milisecond" . PHP_EOL;
    }
}

function testZstd($file)
{
    $level = 1;
    $max = 19;
    $originalData = file_get_contents($file);
    $originalLength = strlen($originalData);

    echo '----zstd----' . PHP_EOL;
    echo 'Level, Reduction, Time, Percent Per Millisecond' . PHP_EOL;
    for ($level; $level <= $max; $level++) {
        $start = hrtime(true);
        $newSize = strlen(compressWithZstd($originalData, $level));
        $ratio = 100 - round(($newSize / $originalLength) * 100, 3);
        $time = (hrtime(true) - $start) / 1e+6;
        $percentPerMs = $ratio / $time;
        echo "$level, $ratio%, $time ms, $percentPerMs% per milisecond" . PHP_EOL;
    }
}

function testBrotli($file)
{
    $level = 1;
    $max = 11;
    $originalData = file_get_contents($file);
    $originalLength = strlen($originalData);

    echo '----Brotli----' . PHP_EOL;
    echo 'Level, Reduction, Time, Percent Per Millisecond' . PHP_EOL;
    for ($level; $level <= $max; $level++) {
        $start = hrtime(true);
        $newSize = strlen(compressWithBrotli($originalData, $level));
        $ratio = 100 - round(($newSize / $originalLength) * 100, 3);
        $time = (hrtime(true) - $start) / 1e+6;
        $percentPerMs = $ratio / $time;
        echo "$level, $ratio%, $time ms, $percentPerMs% per milisecond" . PHP_EOL;
    }
}

$testFile = 'war_and_peace.html'; 

testGzip($testFile);
testZstd($testFile);
testBrotli($testFile);