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

function testGzip()
{
    $level = 1;
    $max = 9;
    $originalData = file_get_contents('war_and_peace.html');
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

function testZstd()
{
    $level = 1;
    $max = 19;
    $originalData = file_get_contents('war_and_peace.html');
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

function testBrotli()
{
    $level = 1;
    $max = 11;
    $originalData = file_get_contents('war_and_peace.html');
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

testGzip();
testZstd();
testBrotli();