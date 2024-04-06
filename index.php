<?php

use HostByBelle\CompressionBuffer;

require 'vendor/autoload.php';

CompressionBuffer::setUp();
ob_start(CompressionBuffer::handler(...));
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";
echo "test";

//ob_get_flush();
//ob_get_clean();
ob_end_flush();
//ob_end_clean();

//
//print_r($encodings);