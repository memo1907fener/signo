<?php

namespace App\Support;

/** Marken-Paket: prägende Farben aus einem Logo lesen. */
class Brand
{
    /**
     * Liefert bis zu 5 kräftige Farben des Bildes als Hex-Werte, die auffälligste zuerst.
     * Weiß, Schwarz, Grau und durchsichtige Flächen zählen nicht – sie sind Hintergrund, keine Markenfarbe.
     */
    public static function colors(string $file): array
    {
        if (! function_exists('imagecreatefromstring')) return [];
        $src = @imagecreatefromstring((string) @file_get_contents($file));
        if (! $src) return [];

        $w = imagesx($src); $h = imagesy($src);
        $size = 72;
        $img = imagecreatetruecolor($size, $size);
        imagealphablending($img, false);
        imagesavealpha($img, true);
        imagefill($img, 0, 0, imagecolorallocatealpha($img, 255, 255, 255, 127));
        imagecopyresampled($img, $src, 0, 0, 0, 0, $size, $size, $w, $h);

        $buckets = [];
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $c = imagecolorat($img, $x, $y);
                $a = ($c >> 24) & 0x7F;
                if ($a > 60) continue;
                $r = ($c >> 16) & 0xFF; $g = ($c >> 8) & 0xFF; $b = $c & 0xFF;
                $max = max($r, $g, $b); $min = min($r, $g, $b);
                $sat = $max === 0 ? 0 : ($max - $min) / $max;
                if ($sat < 0.28 || $max < 50) continue;            // grau oder fast schwarz
                if ($min > 215) continue;                          // fast weiß
                $key = ($r >> 4) << 8 | ($g >> 4) << 4 | ($b >> 4);
                $buckets[$key] ??= ['n' => 0, 'r' => 0, 'g' => 0, 'b' => 0, 'w' => 0];
                $buckets[$key]['n']++;
                $buckets[$key]['r'] += $r; $buckets[$key]['g'] += $g; $buckets[$key]['b'] += $b;
                $buckets[$key]['w'] += 0.4 + $sat;                // kräftige Farben zählen mehr
            }
        }
        if (! $buckets) return [];
        uasort($buckets, fn ($a, $b) => $b['w'] <=> $a['w']);

        $out = [];
        foreach ($buckets as $bk) {
            $rgb = [intdiv($bk['r'], $bk['n']), intdiv($bk['g'], $bk['n']), intdiv($bk['b'], $bk['n'])];
            // Ähnliche Töne nur einmal
            foreach ($out as $o) {
                if (abs($o[0] - $rgb[0]) + abs($o[1] - $rgb[1]) + abs($o[2] - $rgb[2]) < 90) continue 2;
            }
            $out[] = $rgb;
            if (count($out) >= 5) break;
        }
        return array_map(fn ($c) => sprintf('#%02x%02x%02x', ...$c), $out);
    }

    /** Sehr helle Farben etwas abdunkeln, damit weiße Schrift darauf lesbar bleibt. */
    public static function readable(string $hex): string
    {
        [$r, $g, $b] = sscanf($hex, '#%02x%02x%02x');
        $lum = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;
        if ($lum <= 0.62) return strtolower($hex);
        $k = 0.62 / $lum;
        return sprintf('#%02x%02x%02x', (int) ($r * $k), (int) ($g * $k), (int) ($b * $k));
    }
}
