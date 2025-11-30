<?php

/**
 * Copyright 2024 HostByBelle
 */

declare(strict_types=1);

namespace HostByBelle;

use HttpAccept\AcceptEncodingParser;

class CompressionBuffer
{
    /** @var array<string,int> */
    private static array $compressionPriority = [
        'zstd' => 1,
        'br' => 2,
        'gzip' => 3,
        'deflate' => 4,
        'identity' => 5,
    ];

    private static bool $attemptMultiple = false;
    private static bool $respectPreferred = true;
    private static bool $doCompression = true;

    /** @var null|callable */
    private static mixed $headerHandler = null;

    /** @var array<string> */
    private static array $tryOrder = [];

    /**
     * Enables output compression
     */
    public static function enable(): void
    {
        self::$doCompression = true;
    }

    /**
     * Disables output compression
     */
    public static function disable(): void
    {
        self::$doCompression = false;
    }

    /**
     * Used to check if output compression is enabled or disabled
     */
    public static function isEnabled(): bool
    {
        return self::$doCompression;
    }

    /**
     * Gets the full ordered list of compression methods detected as compatible with both the server and the client
     *
     * @return string[]
     */
    public static function getTryOrder(): array
    {
        return self::$tryOrder;
    }

    /**
     * Returns the first-choice compression method that will be used.
     */
    public static function getFirstMethodChoice(): string
    {
        return self::$tryOrder[0];
    }

    /**
     * Must be called so CompressionBuffer can perform the needed checks and setup.
     * Once called, it will automatically find compatible compression methods for the client & server before then sorting them in a logical try order.
     *
     * @param bool $respectPreferred (optional) If you want CompressionBuffer to use the client's defined order of preference for compression methods. Otherwise, they are tried based on effectiveness.
     * @param bool $attemptMultiple (optional) Indicates if CompressionBuffer should attempt other compression methods after an error. Otherwise, it'll default to no compression.
     * @param null|callable $headerHandler (optional) Optionally provide a callable function here which will be called to send headers. The first parameter must be the header name and the second must be the value.
     */
    public static function setUp(bool $respectPreferred = true, bool $attemptMultiple = false, ?callable $headerHandler = null): void
    {
        self::$respectPreferred = $respectPreferred;
        self::$attemptMultiple = $attemptMultiple;
        self::$headerHandler = $headerHandler;
        self::setTryOrder();
    }

    /**
     * The actual handler that should be handed to ob_start().
     * You may use this outside of ob_start by calling it directly and not specifying the phase.
     *
     * @return string the compressed output buffer, ready to be sent to the client.
     */
    public static function handler(string $buffer, int $phase = PHP_OUTPUT_HANDLER_FINAL): string
    {
        if ($phase & PHP_OUTPUT_HANDLER_FINAL || $phase & PHP_OUTPUT_HANDLER_END) {
            // Send needed headers for caching
            self::sendHeader('Vary', 'Accept-Encoding');

            if (!self::$doCompression) {
                return $buffer;
            }

            // Attempt the compatible compression method(s)
            foreach (self::$tryOrder as $encoding) {
                try {
                    $compressed = self::doCompression($encoding, $buffer);
                    self::sendHeader('Content-Encoding', $encoding);
                    self::sendHeader('Vary', 'Accept-Encoding');
                    return $compressed;
                } catch (\Exception $e) {
                    if (self::$attemptMultiple) {
                        continue;
                    } else {
                        break;
                    }
                }
            }
        }

        return $buffer;
    }

    /**
     * Parses the accept encoding header and builds an internal list of compression methods to attempt based on compatibility & priority.
     */
    private static function setTryOrder(): void
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

    /**
     * Checks if the current server can do a certain type of encoding (compression)
     */
    private static function canDo(string $encoding): bool
    {
        return match ($encoding) {
            'zstd' => function_exists('zstd_compress'),
            'br' => function_exists('brotli_compress'),
            'gzip', 'deflate' => function_exists('gzencode'),
            'identity' => true,
            default => false,
        };
    }

    /**
     * Does the actual compression.
     *
     * @throws \Exception if an error occurs during compression to allow for the system to try the next one.
     */
    private static function doCompression(string $encoding, string $buffer): string
    {
        $result = match ($encoding) {
            'zstd' => zstd_compress($buffer, 3),
            'br' => brotli_compress($buffer, 3),
            'gzip' => gzencode($buffer, 4, ZLIB_ENCODING_GZIP),
            'deflate' => gzencode($buffer, 4, ZLIB_ENCODING_DEFLATE),
            'identity' => $buffer,
            default => false,
        };

        if ($result === false) {
            throw new \Exception('Compression failed!');
        }

        return $result;
    }

    /**
     * Sends headers to the client. Will use the custom header handler if set, otherwise PHP's header function.
     */
    private static function sendHeader(string $name, string|int $value): void
    {
        // We actually don't want to send a `Content-Encoding` header if the content isn't encoded as this behavior risks confusing the client.
        if ($name === 'Content-Encoding' && $value === 'identity') {
            return;
        }

        if (is_callable(self::$headerHandler)) {
            call_user_func(self::$headerHandler, $name, $value);
        } else {
            header("$name: $value");
        }
    }
}
