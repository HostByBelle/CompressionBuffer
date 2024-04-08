<?php

use HostByBelle\CompressionBuffer;

require dirname(__DIR__) . '/vendor/autoload.php';

CompressionBuffer::setUp();
ob_start([CompressionBuffer::class, 'handler']);

echo "Hello, world!";

ob_end_flush();
