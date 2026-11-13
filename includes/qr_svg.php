<?php
declare(strict_types=1);

/**
 * ZEYNART DİJİTAL STÜDYO — YEREL VEKTÖREL QR KOD MOTORU (ISO/IEC 18004)
 * ----------------------------------------------------------------------------
 * Dış API veya üçüncü parti sunucu bağlantısı kullanmadan %100 yerel PHP ile
 * taranabilir SVG ve GD PNG QR kod üretir (Sıfır Çerez, Sıfır Veri Sızıntısı).
 */

/**
 * Encodes a UTF-8 string into a square boolean QR matrix (true = dark module).
 * Supports Byte mode across Versions 1..10 (Error Correction Level L, up to 271 bytes).
 *
 * @return array<int, array<int, bool>>
 */
function zeynart_qr_encode_matrix(string $text): array
{
    if ($text === '') {
        $text = 'https://zeynart.com';
    }
    // Version 1..10 specs for Error Correction Level L: [totalCodewords, ecCodewordsPerBlock, numBlocks]
    $versionSpecs = [
        1  => [26,  7,  1],
        2  => [44,  10, 1],
        3  => [70,  15, 1],
        4  => [100, 20, 1],
        5  => [134, 26, 1],
        6  => [172, 18, 2],
        7  => [196, 20, 2],
        8  => [242, 24, 2],
        9  => [292, 30, 2],
        10 => [346, 18, 4], // 2 blocks of 68 data + 2 blocks of 69 data -> handled via standard block split
    ];
    // Exact group splits for Level L: [ [count1, dataCols1], [count2, dataCols2], ecPerBlock ]
    $blockGroups = [
        1  => [[1, 19], [0, 0], 7],
        2  => [[1, 34], [0, 0], 10],
        3  => [[1, 55], [0, 0], 15],
        4  => [[1, 80], [0, 0], 20],
        5  => [[1, 108], [0, 0], 26],
        6  => [[2, 68], [0, 0], 18],
        7  => [[2, 78], [0, 0], 20],
        8  => [[2, 97], [0, 0], 24],
        9  => [[2, 116], [0, 0], 30],
        10 => [[2, 68], [2, 69], 18],
    ];
    $alignCenters = [
        1  => [],
        2  => [6, 18],
        3  => [6, 22],
        4  => [6, 26],
        5  => [6, 30],
        6  => [6, 34],
        7  => [6, 22, 38],
        8  => [6, 24, 42],
        9  => [6, 26, 46],
        10 => [6, 28, 50],
    ];

    $bytes = array_values(unpack('C*', $text) ?: []);
    $byteLen = count($bytes);
    if ($byteLen > 271) {
        $bytes = array_slice($bytes, 0, 271);
        $byteLen = 271;
    }

    $ver = 10;
    foreach ($blockGroups as $v => $bg) {
        $totalDataCw = ($bg[0][0] * $bg[0][1]) + ($bg[1][0] * $bg[1][1]);
        $lenBits = ($v <= 9) ? 8 : 16;
        $maxBytes = (int)floor(($totalDataCw * 8 - 4 - $lenBits) / 8);
        if ($byteLen <= $maxBytes) {
            $ver = $v;
            break;
        }
    }

    $bg = $blockGroups[$ver];
    $totalDataCw = ($bg[0][0] * $bg[0][1]) + ($bg[1][0] * $bg[1][1]);
    $ecCount = $bg[2];
    $lenBits = ($ver <= 9) ? 8 : 16;

    // 1. Build bit stream: Mode 0100 (Byte) + char count + payload + terminator + pad bytes
    $bits = [];
    $appendBits = static function (int $val, int $len) use (&$bits): void {
        for ($i = $len - 1; $i >= 0; $i--) {
            $bits[] = (($val >> $i) & 1);
        }
    };

    $appendBits(0b0100, 4);
    $appendBits($byteLen, $lenBits);
    foreach ($bytes as $b) {
        $appendBits($b, 8);
    }

    $capacityBits = $totalDataCw * 8;
    $termLen = min(4, $capacityBits - count($bits));
    if ($termLen > 0) {
        $appendBits(0, $termLen);
    }
    while (count($bits) % 8 !== 0) {
        $bits[] = 0;
    }

    $dataCodewords = [];
    for ($i = 0, $n = count($bits); $i < $n; $i += 8) {
        $byte = 0;
        for ($j = 0; $j < 8; $j++) {
            $byte = ($byte << 1) | $bits[$i + $j];
        }
        $dataCodewords[] = $byte;
    }

    $padSeq = [0xEC, 0x11];
    $pIdx = 0;
    while (count($dataCodewords) < $totalDataCw) {
        $dataCodewords[] = $padSeq[$pIdx % 2];
        $pIdx++;
    }

    // 2. Galois Field GF(256) tables (primitive polynomial 0x11D)
    $gfExp = array_fill(0, 512, 0);
    $gfLog = array_fill(0, 256, 0);
    $x = 1;
    for ($i = 0; $i < 255; $i++) {
        $gfExp[$i] = $x;
        $gfLog[$x] = $i;
        $x <<= 1;
        if (($x & 0x100) !== 0) {
            $x ^= 0x11D;
        }
    }
    for ($i = 255; $i < 512; $i++) {
        $gfExp[$i] = $gfExp[$i - 255];
    }

    $gfMul = static function (int $a, int $b) use ($gfExp, $gfLog): int {
        if ($a === 0 || $b === 0) {
            return 0;
        }
        return $gfExp[$gfLog[$a] + $gfLog[$b]];
    };

    // Generator polynomial of degree $ecCount
    $gen = [1];
    for ($i = 0; $i < $ecCount; $i++) {
        $next = array_fill(0, count($gen) + 1, 0);
        $root = $gfExp[$i];
        foreach ($gen as $j => $coef) {
            $next[$j] ^= $coef;
            $next[$j + 1] ^= $gfMul($coef, $root);
        }
        $gen = $next;
    }

    $computeEc = static function (array $blockData) use ($ecCount, $gen, $gfMul): array {
        $reg = array_fill(0, $ecCount, 0);
        foreach ($blockData as $b) {
            $factor = $b ^ array_shift($reg);
            $reg[] = 0;
            if ($factor !== 0) {
                for ($j = 0; $j < $ecCount; $j++) {
                    $reg[$j] ^= $gfMul($gen[$j + 1], $factor);
                }
            }
        }
        return $reg;
    };

    // 3. Split into blocks, compute Reed-Solomon EC, and interleave
    $dataBlocks = [];
    $ecBlocks = [];
    $offset = 0;
    for ($b = 0; $b < $bg[0][0]; $b++) {
        $len = $bg[0][1];
        $chunk = array_slice($dataCodewords, $offset, $len);
        $offset += $len;
        $dataBlocks[] = $chunk;
        $ecBlocks[] = $computeEc($chunk);
    }
    for ($b = 0; $b < $bg[1][0]; $b++) {
        $len = $bg[1][1];
        $chunk = array_slice($dataCodewords, $offset, $len);
        $offset += $len;
        $dataBlocks[] = $chunk;
        $ecBlocks[] = $computeEc($chunk);
    }

    $finalCodewords = [];
    $maxDataLen = max($bg[0][1], $bg[1][1]);
    for ($col = 0; $col < $maxDataLen; $col++) {
        foreach ($dataBlocks as $blk) {
            if (isset($blk[$col])) {
                $finalCodewords[] = $blk[$col];
            }
        }
    }
    for ($col = 0; $col < $ecCount; $col++) {
        foreach ($ecBlocks as $eblk) {
            $finalCodewords[] = $eblk[$col];
        }
    }

    $finalBits = [];
    foreach ($finalCodewords as $cw) {
        for ($i = 7; $i >= 0; $i--) {
            $finalBits[] = (($cw >> $i) & 1) === 1;
        }
    }
    // Remainder bits for versions 2..6 (7 bits)
    if ($ver >= 2 && $ver <= 6) {
        for ($r = 0; $r < 7; $r++) {
            $finalBits[] = false;
        }
    }

    // 4. Construct module grid and function patterns
    $size = $ver * 4 + 17;
    $modules = array_fill(0, $size, array_fill(0, $size, false));
    $isFunc = array_fill(0, $size, array_fill(0, $size, false));

    $setFunc = static function (int $x, int $y, bool $dark) use (&$modules, &$isFunc, $size): void {
        if ($x >= 0 && $x < $size && $y >= 0 && $y < $size) {
            $modules[$y][$x] = $dark;
            $isFunc[$y][$x] = true;
        }
    };

    $drawFinder = static function (int $ox, int $oy) use ($setFunc): void {
        for ($dy = -1; $dy <= 7; $dy++) {
            for ($dx = -1; $dx <= 7; $dx++) {
                $dist = max(abs($dx - 3), abs($dy - 3));
                $dark = ($dx >= 0 && $dx <= 6 && $dy >= 0 && $dy <= 6) && ($dist !== 2);
                $setFunc($ox + $dx, $oy + $dy, $dark);
            }
        }
    };

    $drawFinder(0, 0);
    $drawFinder($size - 7, 0);
    $drawFinder(0, $size - 7);

    // Timing patterns
    for ($i = 8; $i < $size - 8; $i++) {
        $setFunc($i, 6, ($i % 2) === 0);
        $setFunc(6, $i, ($i % 2) === 0);
    }

    // Alignment patterns
    $centers = $alignCenters[$ver];
    $cCount = count($centers);
    for ($iy = 0; $iy < $cCount; $iy++) {
        for ($ix = 0; $ix < $cCount; $ix++) {
            if (($ix === 0 && $iy === 0) || ($ix === 0 && $iy === $cCount - 1) || ($ix === $cCount - 1 && $iy === 0)) {
                continue;
            }
            $cx = $centers[$ix];
            $cy = $centers[$iy];
            for ($dy = -2; $dy <= 2; $dy++) {
                for ($dx = -2; $dx <= 2; $dx++) {
                    $setFunc($cx + $dx, $cy + $dy, max(abs($dx), abs($dy)) !== 1);
                }
            }
        }
    }

    // Reserve format information slots + dark module
    for ($i = 0; $i <= 8; $i++) {
        if ($i !== 6) {
            $setFunc(8, $i, false);
            $setFunc($i, 8, false);
        }
    }
    for ($i = 0; $i < 8; $i++) {
        $setFunc($size - 1 - $i, 8, false);
    }
    for ($i = 0; $i < 7; $i++) {
        $setFunc(8, $size - 1 - $i, false);
    }
    $setFunc(8, $size - 8, true); // Always dark module

    // Version info for versions >= 7
    if ($ver >= 7) {
        $rem = $ver;
        for ($i = 0; $i < 12; $i++) {
            $rem = ($rem << 1) ^ ((($rem >> 11) & 1) * 0x1F25);
        }
        $verBits = ($ver << 12) | $rem;
        for ($i = 0; $i < 18; $i++) {
            $bit = (($verBits >> $i) & 1) === 1;
            $a = $size - 11 + ($i % 3);
            $b = (int)floor($i / 3);
            $setFunc($a, $b, $bit);
            $setFunc($b, $a, $bit);
        }
    }

    // 5. Place zig-zag data bits
    $bitIdx = 0;
    $totalBits = count($finalBits);
    for ($right = $size - 1; $right >= 1; $right -= 2) {
        if ($right === 6) {
            $right = 5;
        }
        $upward = ((($right + 1) & 2) === 0);
        for ($vert = 0; $vert < $size; $vert++) {
            $y = $upward ? ($size - 1 - $vert) : $vert;
            for ($j = 0; $j < 2; $j++) {
                $x = $right - $j;
                if (!$isFunc[$y][$x]) {
                    $modules[$y][$x] = ($bitIdx < $totalBits) ? $finalBits[$bitIdx] : false;
                    $bitIdx++;
                }
            }
        }
    }

    // Helper to write Format Info bits (Level L = 01)
    $writeFormatBits = static function (array &$grid, int $mask) use ($size): void {
        $data = (0b01 << 3) | $mask; // Level L = 01
        $rem = $data;
        for ($i = 0; $i < 10; $i++) {
            $rem = ($rem << 1) ^ ((($rem >> 9) & 1) * 0x537);
        }
        $bits = (($data << 10) | $rem) ^ 0x5412;

        // First copy around top-left finder (x=col, y=row -> $grid[$y][$x])
        for ($i = 0; $i <= 5; $i++) {
            $grid[$i][8] = (($bits >> $i) & 1) === 1;
        }
        $grid[7][8] = (($bits >> 6) & 1) === 1;
        $grid[8][8] = (($bits >> 7) & 1) === 1;
        $grid[8][7] = (($bits >> 8) & 1) === 1;
        for ($i = 9; $i < 15; $i++) {
            $grid[8][14 - $i] = (($bits >> $i) & 1) === 1;
        }

        // Second copy along top-right (y=8, x=size-1..size-8) & bottom-left (x=8, y=size-7..size-1)
        for ($i = 0; $i < 8; $i++) {
            $grid[8][$size - 1 - $i] = (($bits >> $i) & 1) === 1;
        }
        for ($i = 8; $i < 15; $i++) {
            $grid[$size - 15 + $i][8] = (($bits >> $i) & 1) === 1;
        }
        $grid[$size - 8][8] = true;
    };

    // 6. Choose lowest-penalty mask among masks 0..7
    $bestMask = 0;
    $bestScore = PHP_INT_MAX;
    $bestGrid = $modules;

    for ($mask = 0; $mask < 8; $mask++) {
        $cand = $modules;
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ($isFunc[$y][$x]) {
                    continue;
                }
                $invert = match ($mask) {
                    0 => (($x + $y) % 2) === 0,
                    1 => ($y % 2) === 0,
                    2 => ($x % 3) === 0,
                    3 => (($x + $y) % 3) === 0,
                    4 => (((int)floor($y / 2) + (int)floor($x / 3)) % 2) === 0,
                    5 => ((($x * $y) % 2) + (($x * $y) % 3)) === 0,
                    6 => (((($x * $y) % 2) + (($x * $y) % 3)) % 2) === 0,
                    default => (((($x + $y) % 2) + (($x * $y) % 3)) % 2) === 0,
                };
                if ($invert) {
                    $cand[$y][$x] = !$cand[$y][$x];
                }
            }
        }
        $writeFormatBits($cand, $mask);

        // Quick penalty evaluation (runs + 2x2 blocks)
        $penalty = 0;
        for ($y = 0; $y < $size; $y++) {
            $runColor = $cand[$y][0];
            $runLen = 1;
            for ($x = 1; $x < $size; $x++) {
                if ($cand[$y][$x] === $runColor) {
                    $runLen++;
                } else {
                    if ($runLen >= 5) {
                        $penalty += 3 + ($runLen - 5);
                    }
                    $runColor = $cand[$y][$x];
                    $runLen = 1;
                }
            }
            if ($runLen >= 5) {
                $penalty += 3 + ($runLen - 5);
            }
        }
        for ($x = 0; $x < $size; $x++) {
            $runColor = $cand[0][$x];
            $runLen = 1;
            for ($y = 1; $y < $size; $y++) {
                if ($cand[$y][$x] === $runColor) {
                    $runLen++;
                } else {
                    if ($runLen >= 5) {
                        $penalty += 3 + ($runLen - 5);
                    }
                    $runColor = $cand[$y][$x];
                    $runLen = 1;
                }
            }
            if ($runLen >= 5) {
                $penalty += 3 + ($runLen - 5);
            }
        }
        for ($y = 0; $y < $size - 1; $y++) {
            for ($x = 0; $x < $size - 1; $x++) {
                $c = $cand[$y][$x];
                if ($c === $cand[$y][$x + 1] && $c === $cand[$y + 1][$x] && $c === $cand[$y + 1][$x + 1]) {
                    $penalty += 3;
                }
            }
        }
        if ($penalty < $bestScore) {
            $bestScore = $penalty;
            $bestMask = $mask;
            $bestGrid = $cand;
        }
    }

    return $bestGrid;
}

/**
 * Renders a crisp vector SVG QR code with configurable quiet zone and colors.
 */
function zeynart_qr_render_svg(
    string $text,
    string $fgColor = '#061318',
    string $bgColor = '#ffffff',
    int $marginModules = 3,
    int $pixelSize = 512
): string {
    $matrix = zeynart_qr_encode_matrix($text);
    $n = count($matrix);
    $margin = max(1, min(8, $marginModules));
    $total = $n + ($margin * 2);
    $fg = preg_match('/^#[0-9a-fA-F]{3,8}$/', $fgColor) ? $fgColor : '#061318';
    $bg = preg_match('/^#[0-9a-fA-F]{3,8}$/', $bgColor) ? $bgColor : '#ffffff';
    $px = max(120, min(2048, $pixelSize));

    $paths = [];
    for ($y = 0; $y < $n; $y++) {
        $x = 0;
        while ($x < $n) {
            if ($matrix[$y][$x]) {
                $run = 1;
                while (($x + $run) < $n && $matrix[$y][$x + $run]) {
                    $run++;
                }
                $mx = $x + $margin;
                $my = $y + $margin;
                $paths[] = "M{$mx} {$my}h{$run}v1h-{$run}z";
                $x += $run;
            } else {
                $x++;
            }
        }
    }
    $d = implode('', $paths);
    return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $px . '" height="' . $px . '" viewBox="0 0 ' . $total . ' ' . $total . '" shape-rendering="crispEdges" role="img" aria-label="QR Kod">'
        . '<rect width="' . $total . '" height="' . $total . '" rx="1.5" fill="' . htmlspecialchars($bg, ENT_QUOTES, 'UTF-8') . '"/>'
        . '<path d="' . $d . '" fill="' . htmlspecialchars($fg, ENT_QUOTES, 'UTF-8') . '"/>'
        . '</svg>';
}

/**
 * Renders a high-resolution PNG binary QR code via GD (for downloading printable 1000x1000 PNGs).
 */
function zeynart_qr_render_png_bytes(
    string $text,
    int $pixelSize = 1000,
    array $fgRgb = [6, 19, 24],
    array $bgRgb = [255, 255, 255]
): string {
    if (!function_exists('imagecreatetruecolor')) {
        return '';
    }
    $matrix = zeynart_qr_encode_matrix($text);
    $n = count($matrix);
    $margin = 3;
    $total = $n + ($margin * 2);
    $scale = max(4, (int)floor($pixelSize / $total));
    $imgSize = max($pixelSize, $total * $scale);
    $pad = (int)floor(($imgSize - ($total * $scale)) / 2);

    $im = imagecreatetruecolor($imgSize, $imgSize);
    $bg = imagecolorallocate($im, (int)$bgRgb[0], (int)$bgRgb[1], (int)$bgRgb[2]);
    $fg = imagecolorallocate($im, (int)$fgRgb[0], (int)$fgRgb[1], (int)$fgRgb[2]);
    imagefilledrectangle($im, 0, 0, $imgSize - 1, $imgSize - 1, $bg);

    for ($y = 0; $y < $n; $y++) {
        for ($x = 0; $x < $n; $x++) {
            if ($matrix[$y][$x]) {
                $x1 = $pad + (($x + $margin) * $scale);
                $y1 = $pad + (($y + $margin) * $scale);
                imagefilledrectangle($im, $x1, $y1, $x1 + $scale - 1, $y1 + $scale - 1, $fg);
            }
        }
    }

    ob_start();
    imagepng($im, null, 6);
    $bytes = (string)ob_get_clean();
    unset($im);
    return $bytes;
}
