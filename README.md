# CompressionBuffer

CompressionBuffer provides easy access to zstd, brotli, and gzip output buffering with PHP on **any** webserver. You can even get zstd [output compression with the PHP development server](https://www.daniel.priv.no/tools/zstd-browser-test/).

## Requirements

- `PHP 8.0` or greater.
- `ext-brotli` if you want brotli compression.
- `ext-zstd`if you want zstd compression.
- `ext-zlib` if you want gzip / deflate compression.

CompressionBuffer will automatically pick the best possible compression method based on the client's `Accept-Encoding` header and installed extensions with no extra configuration required.

## Installation & Usage

Install via composer:

```bash
composer install hostbybelle/compressionbuffer
```

Enabling in your application:

```PHP
<?php
use HostByBelle\CompressionBuffer;

require 'vendor/autoload.php';

CompressionBuffer::setUp(); // Have compressionBuffer detect & sort the compression methods
ob_start(CompressionBuffer::handler(...)); // Register it

// Send some content to the output buffer
echo "Lorem ipsum dolor sit amet, consectetur adipiscing elit,";

// Finally send it to the browser & let CompressionBuffer do it's magic.
ob_end_flush();
```

## Compression Method Tests & Results

### Compression Methods, Ranked

1. `zstd` is the fastest, most efficient compression method. On it's lowest level it will be 4.5x faster than `gzip` while producing a 5% smaller output. When matching `gzip` on it's fastest setting, `zstd` will produce a 10% smaller output.
2. `brotli` is a good middle-ground between the two alternatives. It's lowest setting is still about 3x faster than `gzip` while producing a 5% smaller output. It will produce a similar overall compression level to `zstd`, but will do so at a slower pace. Still an overall great choice.
3. `gzip` is the longstanding, tried & true compression method, but is quite slow compared to the new alternatives and will produce larger outputs while doing so.

These stats are based off some simple testing we've performed on a [4mb HTML document containing the book war and peace](https://www.gutenberg.org/files/2600/2600-h/2600-h.htm). This choice was inspired by [jab4/zstdtest](https://github.com/jab4/zstdtest) & represents a large document a server might serve. Our testing with small documents shows all 3 compression methods have very similar compression levels and overall processing time.

The results of our testing can be found [here on Google Sheets](https://docs.google.com/spreadsheets/d/1rXWHH5sT03jKMFe1ATGRrzK3UAQK2TsKQaqWK0t3wks/edit#gid=1362319378).

### Compression Levels Used

- `zstd`: Level 3. Level 4 would also be a good option as it will still be faster than the rest, however at level 3 `zstd` far exceeds the other options for speed while matching them in overall compression level and level 4 is only 38% the speed of level 3 for for only .4% more compression.
- `brotli`: Level 3. Level 4 does come with a noticeable increase to compression level, but does so in 3x the time. At level 3 brotli provides a similar compression level to `gzip`, but at around 4.5x the speed.
- `gzip`: Level 6 as this is the point at which it's speed to compression ratio plateaus.
