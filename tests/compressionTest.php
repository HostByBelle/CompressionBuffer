<?php

function testGzip($data)
{
    echo PHP_EOL . PHP_EOL;
    echo '----GZIP----' . PHP_EOL;
    echo 'Level, Reduction, Time, Percent Per Millisecond' . PHP_EOL;

    if (!function_exists('gzencode')) {
        echo "The zlib extension is not installed, cannot test gzip." . PHP_EOL;
        return;
    }

    $level = 1;
    $max = 9;
    $originalLength = strlen($data);

    for ($level; $level <= $max; $level++) {
        $start = hrtime(true);
        $newSize = strlen(gzencode($data, $level, ZLIB_ENCODING_GZIP));
        $ratio = 100 - round(($newSize / $originalLength) * 100, 3);
        $time = (hrtime(true) - $start) / 1e+6;
        $percentPerMs = $ratio / $time;
        echo "$level, $ratio%, $time ms, $percentPerMs% per milisecond" . PHP_EOL;
    }
}

function testZstd($data)
{
    echo PHP_EOL . PHP_EOL;
    echo '----zstd----' . PHP_EOL;
    echo 'Level, Reduction, Time, Percent Per Millisecond' . PHP_EOL;

    if (!function_exists('zstd_compress')) {
        echo "The zstd extension is not installed, cannot test zstd." . PHP_EOL;
        return;
    }

    $level = 1;
    $max = 19;
    $originalLength = strlen($data);

    for ($level; $level <= $max; $level++) {
        $start = hrtime(true);
        $newSize = strlen(zstd_compress($data, $level));
        $ratio = 100 - round(($newSize / $originalLength) * 100, 3);
        $time = (hrtime(true) - $start) / 1e+6;
        $percentPerMs = $ratio / $time;
        echo "$level, $ratio%, $time ms, $percentPerMs% per milisecond" . PHP_EOL;
    }
}

function testBrotli($data, bool $useTextMode = false)
{
    echo PHP_EOL . PHP_EOL;
    if ($useTextMode) {
        echo '----Brotli (Text Mode)----' . PHP_EOL;
    } else {
        echo '----Brotli (Generic Mode)----' . PHP_EOL;
    }
    echo 'Level, Reduction, Time, Percent Per Millisecond' . PHP_EOL;

    if (!function_exists('brotli_compress')) {
        echo "The brotli extension is not installed, cannot test brotli." . PHP_EOL;
        return;
    }

    $level = 1;
    $max = 11;
    $originalLength = strlen($data);

    for ($level; $level <= $max; $level++) {
        $start = hrtime(true);
        $newSize = strlen(brotli_compress($data, $level, $useTextMode ? BROTLI_TEXT : BROTLI_GENERIC));
        $ratio = 100 - round(($newSize / $originalLength) * 100, 3);
        $time = (hrtime(true) - $start) / 1e+6;
        $percentPerMs = $ratio / $time;
        echo "$level, $ratio%, $time ms, $percentPerMs% per milisecond" . PHP_EOL;
    }
}


echo "Retrieving the large test data (War and Peace e-book by Project Gutenberg)" . PHP_EOL;

$data = file_get_contents('https://www.gutenberg.org/files/2600/2600-h/2600-h.htm');

echo "Total size: " . strlen($data) / 1_048_576 . "mb" . PHP_EOL;

testGzip($data);
testZstd($data);
testBrotli($data);
testBrotli($data, true);

echo PHP_EOL . PHP_EOL;

echo "Retrieving the medium test data (Wikipedia page on Project Gutenberg)" . PHP_EOL;

$data = file_get_contents('https://en.wikipedia.org/wiki/Project_Gutenberg');

echo "Total size: " . strlen($data) / 1_048_576 . "mb" . PHP_EOL;

testGzip($data);
testZstd($data);
testBrotli($data);
testBrotli($data, true);
