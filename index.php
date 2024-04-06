<?php

declare(strict_types=1);

namespace HostByBelle;

require 'vendor/autoload.php';

use HttpAccept\AcceptEncodingParser;

class CompressionBuffer
{
    public static array $compressionPriority = [
        'br' => 1,
        'zstd' => 2,
        'gzip' => 3,
        'deflate' => 4,
        'compress' => 5,
        'identity' => 6,
    ];

    private static bool $attemptMultiple = true;
    private static bool $respectPreferred = true;
    private static array $tryOrder = [];

    public static function setUp(bool $respectPreferred = true, bool $attemptMultiple = true)
    {
        self::$respectPreferred = $respectPreferred;
        self::$attemptMultiple = $attemptMultiple;
        self::setTryOrder();
    }

    public static function handler(string $buffer, int $phase): string
    {
        if ($phase & PHP_OUTPUT_HANDLER_FINAL || $phase & PHP_OUTPUT_HANDLER_END) {
            foreach (self::$tryOrder as $encoding) {
                $compressed = self::doCompression($encoding, $buffer);
                if ($compressed !== false) {
                    header('Content-Encoding: ' . $encoding);
                    return $compressed;
                } elseif (!self::$attemptMultiple) {
                    break;
                }
            }

            header('Content-Encoding: identity');
            return $buffer;
        }

        return $buffer;
    }

    private static function setTryOrder()
    {
        if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && is_string($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            $encodings = (new AcceptEncodingParser())->parse($_SERVER['HTTP_ACCEPT_ENCODING']);
            $compatible = [];
            $noPreference = true;

            foreach ($encodings as $encoding) {
                if (strcasecmp($encoding->name(), 'x-compress') === 0) {
                    $name = 'compress';
                } elseif (strcasecmp($encoding->name(), 'x-gzip') === 0) {
                    $name = 'gzip';
                } else {
                    $name = strtolower($encoding->name());
                }

                if (self::canDo($name)) {
                    if ($encoding->score() < 1000) {
                        $noPreference = false;
                    }
                    $compatible[] = $name;
                }
            }

            // There was no overall preference, so we can sort it by our actual preference. Otherwise, it's already sorted.
            if ($noPreference) {
                usort($compatible, function ($a, $b) {
                    if (self::$compressionPriority[$a] == self::$compressionPriority[$b]) {
                        return 0;
                    }
                    return (self::$compressionPriority[$a] < self::$compressionPriority[$b]) ? -1 : 1;
                });
            }

            self::$tryOrder = $compatible;
        } else {
            self::$tryOrder = [];
        }
    }

    private static function canDo(string $encoding)
    {
        switch ($encoding) {
            case 'gzip':
            case 'deflate':
                return extension_loaded('zlib');
            case 'identity':
                return true;
            default:
                return false;
        }
    }

    private static function doCompression(string $encoding, string $buffer): string
    {
        switch ($encoding) {
            case 'gzip':
                return gzcompress($buffer, -1, ZLIB_ENCODING_GZIP);
            case 'deflate':
                return gzcompress($buffer, -1, ZLIB_ENCODING_DEFLATE);
            default:
                return $buffer;
        }
    }
}

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