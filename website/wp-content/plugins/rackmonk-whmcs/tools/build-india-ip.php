<?php
/**
 * Builds data/india-ip.bin (IP ranges allocated to India) from APNIC's public delegation file.
 *
 * Refresh every few months:
 *   1. Download https://ftp.apnic.net/stats/apnic/delegated-apnic-latest
 *   2. php tools/build-india-ip.php path/to/delegated-apnic-latest
 *
 * File format: "RMIP1", uint32 IPv4 count, IPv4 [start,end] pairs (4+4 bytes),
 * uint32 IPv6 count, IPv6 [start,end] pairs (16+16 bytes). Ranges are sorted and merged.
 */
if (PHP_SAPI !== 'cli') exit;
if ($argc < 2 || !is_readable($argv[1])) {
    fwrite(STDERR, "Usage: php build-india-ip.php delegated-apnic-latest\n");
    exit(1);
}

$v4 = [];
$v6 = [];
$in = fopen($argv[1], 'rb');
while (($line = fgets($in)) !== false) {
    $f = explode('|', trim($line));
    if (count($f) < 7 || $f[1] !== 'IN' || !in_array($f[6], ['allocated', 'assigned'], true)) continue;
    if ($f[2] === 'ipv4' && filter_var($f[3], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && ctype_digit($f[4])) {
        $start = ip2long($f[3]);
        $v4[] = [$start, $start + (int) $f[4] - 1];
    } elseif ($f[2] === 'ipv6' && filter_var($f[3], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && ctype_digit($f[4]) && (int) $f[4] <= 128) {
        $start = inet_pton($f[3]);
        $bits = (int) $f[4];
        $end = '';
        for ($i = 0; $i < 16; $i++) {
            $keep = max(0, min(8, $bits - $i * 8));
            $mask = $keep === 8 ? 0xFF : ((0xFF << (8 - $keep)) & 0xFF);
            $end .= chr((ord($start[$i]) & $mask) | (~$mask & 0xFF));
        }
        $v6[] = [$start, $end];
    }
}
fclose($in);

$merge = static function (array $ranges, callable $next) {
    usort($ranges, static function ($a, $b) { return $a[0] <=> $b[0]; });
    $out = [];
    foreach ($ranges as $r) {
        $last = count($out) - 1;
        if ($last >= 0 && $r[0] <= $next($out[$last][1])) {
            if ($r[1] > $out[$last][1]) $out[$last][1] = $r[1];
        } else {
            $out[] = $r;
        }
    }
    return $out;
};
$v4 = $merge($v4, static function ($end) { return $end + 1; });
$v6 = $merge($v6, static function ($end) {
    for ($i = 15; $i >= 0; $i--) {
        if ($end[$i] !== "\xFF") return substr($end, 0, $i) . chr(ord($end[$i]) + 1) . str_repeat("\x00", 15 - $i);
    }
    return $end;
});

$bin = 'RMIP1' . pack('N', count($v4));
foreach ($v4 as [$a, $b]) $bin .= pack('NN', $a, $b);
$bin .= pack('N', count($v6));
foreach ($v6 as [$a, $b]) $bin .= $a . $b;

$target = dirname(__DIR__) . '/data/india-ip.bin';
if (!is_dir(dirname($target))) mkdir(dirname($target), 0755, true);
file_put_contents($target, $bin);
printf("IPv4 ranges: %d, IPv6 ranges: %d, %s bytes -> %s\n", count($v4), count($v6), number_format(strlen($bin)), $target);
