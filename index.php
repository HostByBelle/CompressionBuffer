<?php

use HostByBelle\CompressionBuffer;

require 'vendor/autoload.php';

CompressionBuffer::setUp();
ob_start(CompressionBuffer::handler(...));

echo "If you can read this, it works!";

ob_end_flush();