<?php

declare(strict_types=1);

namespace HostByBelle;

use HttpAccept\AcceptEncodingParser;

class CompressionBuffer
{
    private static array $compressionPriority = [
        'zstd' => 1,
        'br' => 2,
        'gzip' => 3,
        'deflate' => 4,
        'identity' => 5,
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
                try {
                    $compressed = self::doCompression($encoding, $buffer);
                    header('Content-Encoding: ' . $encoding);
                    return $compressed;
                } catch (\Exception $e) {
                    if (self::$attemptMultiple) {
                        continue;
                    }
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
                    if ($encoding->score() < 1000 && $encoding->score() > 0) {
                        $noPreference = false;
                    }

                    if ($encoding->score() > 0) {
                        $compatible[] = $name;
                    }
                }
            }

            if ($noPreference || !self::$respectPreferred) {
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
        return match ($encoding) {
            'zstd' => function_exists('zstd_compress'),
            'br' => function_exists('brotli_compress'),
            'gzip', 'deflate' => function_exists('gzencode'),
            'identity' => true,
            default => false,
        };
    }

    private static function doCompression(string $encoding, string $buffer): string
    {
        $result = match ($encoding) {
            'zstd' => zstd_compress($buffer),
            'br' => brotli_compress($buffer),
            'gzip' => gzencode($buffer, -1, ZLIB_ENCODING_GZIP),
            'deflate' => gzencode($buffer, -1, ZLIB_ENCODING_DEFLATE),
            'identity' => $buffer,
            default => false,
        };

        if ($result === false) {
            throw new \Exception('Compression failed!');
        }

        return $result;
    }
}
