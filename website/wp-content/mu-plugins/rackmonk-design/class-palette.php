<?php
/**
 * Material 3 style tonal palette from one seed colour.
 * Tones are perceptual (CIE L*), computed through OKLCH with sRGB gamut mapping.
 */
if (!defined('ABSPATH')) exit;

final class Rackmonk_Palette {
    const DEFAULT_SEED = '#17BD54';

    /** All colour roles used by the site, as #RRGGBB. */
    public static function roles($seed) {
        $seed = self::normalize($seed);
        [$L, $C, $h] = self::hex_to_oklch($seed);
        $tone = static function ($t, $chroma) use ($h) { return self::tone($t, $chroma, $h); };
        $pc = max($C, 0.04);           // primary chroma
        $nc = min(0.010, $C * 0.08);    // neutral chroma (slightly tinted)
        $vc = min(0.022, $C * 0.18);    // neutral-variant chroma

        $dark = $tone(10, $pc);
        $on_primary = self::contrast($seed, '#FFFFFF') >= 4.5 || self::contrast($seed, '#FFFFFF') >= self::contrast($seed, $dark) ? '#FFFFFF' : $dark;

        return [
            'primary'                 => $seed,
            'on-primary'              => $on_primary,
            'primary-strong'          => $tone(40, $pc),   // accessible text/links on light surfaces
            'primary-strong-hover'    => $tone(30, $pc),
            'primary-container'       => $tone(90, min($pc, 0.075)),
            'on-primary-container'    => $tone(10, $pc),
            'primary-container-low'   => $tone(96, min($pc, 0.035)),
            'primary-fixed-dim'       => $tone(80, min($pc, 0.12)),
            'primary-tone-60'         => $tone(60, $pc),
            'primary-tone-20'         => $tone(20, $pc),
            'secondary-container'     => $tone(92, $vc * 2.2),
            'on-secondary-container'  => $tone(15, $vc * 2.2),
            'surface'                 => $tone(98, $nc),
            'surface-bright'          => $tone(99, $nc),
            'surface-container-lowest'=> '#FFFFFF',
            'surface-container-low'   => $tone(96, $nc),
            'surface-container'       => $tone(94, $nc),
            'surface-container-high'  => $tone(92, $nc),
            'surface-container-highest'=> $tone(90, $nc),
            'on-surface'              => $tone(10, $nc),
            'on-surface-variant'      => $tone(30, $vc),
            'outline'                 => $tone(50, $vc),
            'outline-variant'         => $tone(85, $vc),
            'inverse-surface'         => $tone(12, $vc * 1.4),
            'inverse-surface-high'    => $tone(20, $vc * 1.4),
            'inverse-on-surface'      => $tone(95, $nc),
            'inverse-primary'         => $tone(80, min($pc, 0.14)),
            'scrim'                   => '#000000',
            'error'                   => '#BA1A1A',
            'warning'                 => '#F2B705',
        ];
    }

    public static function normalize($hex) {
        $hex = sanitize_hex_color((string) $hex);
        if (!$hex) return self::DEFAULT_SEED;
        if (strlen($hex) === 4) $hex = '#' . $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3];
        return strtoupper($hex);
    }

    /** Tone = CIE L* (0–100) at the given OKLCH chroma/hue, chroma reduced until it fits sRGB. */
    public static function tone($t, $chroma, $h) {
        if ($t >= 100) return '#FFFFFF';
        if ($t <= 0) return '#000000';
        $Y = $t > 8 ? pow(($t + 16) / 116, 3) : $t / 903.3;
        $L = self::oklab_l_for_y($Y, $chroma, $h);
        $lo = 0.0; $hi = $chroma;
        if (self::in_gamut($L, $hi, $h)) return self::oklch_to_hex($L, $hi, $h);
        for ($i = 0; $i < 22; $i++) {
            $mid = ($lo + $hi) / 2;
            if (self::in_gamut($L, $mid, $h)) $lo = $mid; else $hi = $mid;
        }
        return self::oklch_to_hex($L, $lo, $h);
    }

    /** Find OKLab L whose luminance Y matches (luminance depends slightly on chroma). */
    private static function oklab_l_for_y($Y, $C, $h) {
        $lo = 0.0; $hi = 1.0;
        for ($i = 0; $i < 30; $i++) {
            $mid = ($lo + $hi) / 2;
            [$r, $g, $b] = self::oklch_to_linear($mid, min($C, 0.05), $h);
            $y = 0.2126 * max(0, $r) + 0.7152 * max(0, $g) + 0.0722 * max(0, $b);
            if ($y < $Y) $lo = $mid; else $hi = $mid;
        }
        return ($lo + $hi) / 2;
    }

    private static function in_gamut($L, $C, $h) {
        foreach (self::oklch_to_linear($L, $C, $h) as $v) if ($v < -0.0001 || $v > 1.0001) return false;
        return true;
    }

    private static function oklch_to_linear($L, $C, $h) {
        $a = $C * cos(deg2rad($h));
        $b = $C * sin(deg2rad($h));
        $l = pow($L + 0.3963377774 * $a + 0.2158037573 * $b, 3);
        $m = pow($L - 0.1055613458 * $a - 0.0638541728 * $b, 3);
        $s = pow($L - 0.0894841775 * $a - 1.2914855480 * $b, 3);
        return [
            4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s,
            -1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s,
            -0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s,
        ];
    }

    private static function oklch_to_hex($L, $C, $h) {
        $out = '#';
        foreach (self::oklch_to_linear($L, $C, $h) as $v) {
            $v = max(0, min(1, $v));
            $v = $v <= 0.0031308 ? 12.92 * $v : 1.055 * pow($v, 1 / 2.4) - 0.055;
            $out .= sprintf('%02X', (int) round($v * 255));
        }
        return $out;
    }

    public static function hex_to_oklch($hex) {
        [$r, $g, $b] = array_map(static function ($c) {
            $c = $c / 255;
            return $c <= 0.04045 ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4);
        }, self::rgb($hex));
        $l = pow(0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b, 1 / 3);
        $m = pow(0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b, 1 / 3);
        $s = pow(0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b, 1 / 3);
        $L = 0.2104542553 * $l + 0.7936177850 * $m - 0.0040720468 * $s;
        $a = 1.9779984951 * $l - 2.4285922050 * $m + 0.4505937099 * $s;
        $bb = 0.0259040371 * $l + 0.7827717662 * $m - 0.8086757660 * $s;
        $h = rad2deg(atan2($bb, $a));
        return [$L, sqrt($a * $a + $bb * $bb), $h < 0 ? $h + 360 : $h];
    }

    public static function rgb($hex) {
        $hex = ltrim(self::normalize($hex), '#');
        return [hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2))];
    }

    public static function luminance($hex) {
        [$r, $g, $b] = array_map(static function ($c) {
            $c = $c / 255;
            return $c <= 0.03928 ? $c / 12.92 : pow(($c + 0.055) / 1.055, 2.4);
        }, self::rgb($hex));
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    public static function contrast($a, $b) {
        $x = self::luminance($a) + 0.05;
        $y = self::luminance($b) + 0.05;
        return max($x, $y) / min($x, $y);
    }
}
