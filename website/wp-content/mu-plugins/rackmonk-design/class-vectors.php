<?php
/**
 * Original vector illustrations for the site, drawn in the current theme palette.
 * Every generated file is recorded, so changing the Theme Color re-colours all of them
 * in place (same URLs, nothing to re-edit in Elementor).
 */
if (!defined('ABSPATH')) exit;

final class Rackmonk_Vectors {
    const REGISTRY = 'rackmonk_vectors';
    const FOLDER = 'rackmonk-vectors';

    /* ------------------------------------------------------------ files & registry */

    public static function dir() {
        $up = wp_get_upload_dir();
        return ['path' => trailingslashit($up['basedir']) . self::FOLDER, 'url' => trailingslashit($up['baseurl']) . self::FOLDER];
    }

    public static function registry() {
        $r = get_option(self::REGISTRY, []);
        return is_array($r) ? $r : [];
    }

    /**
     * Create (or update) one illustration and its Media Library entry.
     * @return array{url:string,id:int}
     */
    public static function ensure($name, $type, $w, $h, array $opts = []) {
        $name = sanitize_file_name($name);
        if (substr($name, -4) !== '.svg') $name .= '.svg';
        $dir = self::dir();
        wp_mkdir_p($dir['path']);
        $reg = self::registry();
        $spec = ['type' => $type, 'w' => (int) $w, 'h' => (int) $h, 'opts' => $opts];
        file_put_contents($dir['path'] . '/' . $name, self::render($type, (int) $w, (int) $h, $opts, Rackmonk_Design_System::roles()));
        $id = (int) ($reg[$name]['attachment'] ?? 0);
        if (!$id || get_post_type($id) !== 'attachment') {
            $id = wp_insert_attachment([
                'post_mime_type' => 'image/svg+xml',
                'post_title'     => ucwords(str_replace(['-', '_'], ' ', substr($name, 0, -4))),
                'post_status'    => 'inherit',
                'post_content'   => 'Vector illustration generated from the Theme Color.',
            ], $dir['path'] . '/' . $name);
        }
        update_post_meta($id, '_wp_attachment_metadata', ['width' => (int) $w, 'height' => (int) $h, 'file' => self::FOLDER . '/' . $name, 'sizes' => []]);
        update_post_meta($id, '_wp_attachment_image_alt', $opts['alt'] ?? '');
        $spec['attachment'] = $id;
        $reg[$name] = $spec;
        update_option(self::REGISTRY, $reg, false);
        return ['url' => $dir['url'] . '/' . $name, 'id' => $id];
    }

    public static function regenerate_all() {
        $dir = self::dir();
        if (!is_dir($dir['path'])) return 0;
        $roles = Rackmonk_Design_System::roles();
        $n = 0;
        foreach (self::registry() as $name => $spec) {
            file_put_contents($dir['path'] . '/' . $name, self::render($spec['type'], $spec['w'], $spec['h'], $spec['opts'] ?? [], $roles));
            $n++;
        }
        return $n;
    }

    /* ---------------------------------------------------------------- rendering */

    public static function render($type, $w, $h, array $o, array $c) {
        $method = 'draw_' . str_replace('-', '_', $type);
        if (!method_exists(__CLASS__, $method)) $method = 'draw_shape';
        [$vbw, $vbh, $body, $mode] = self::$method($w, $h, $o, $c);
        $aspect = $mode === 'slice' ? 'xMidYMid slice' : 'xMidYMid meet';
        $title = esc_html($o['alt'] ?? '');
        return '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $vbw . ' ' . $vbh . '" preserveAspectRatio="' . $aspect . '" role="img"' . ($title ? ' aria-label="' . esc_attr($title) . '"' : ' aria-hidden="true"') . '>'
            . ($title ? '<title>' . $title . '</title>' : '') . $body . '</svg>';
    }

    private static function t($s) { return htmlspecialchars((string) $s, ENT_XML1 | ENT_QUOTES, 'UTF-8'); }

    /** Soft rounded backdrop used behind spot illustrations. */
    private static function backdrop($c, $w = 480, $h = 360) {
        return '<rect x="0" y="0" width="' . $w . '" height="' . $h . '" rx="36" fill="' . $c['primary-container-low'] . '"/>'
            . '<circle cx="' . ($w * .82) . '" cy="' . ($h * .2) . '" r="' . ($h * .32) . '" fill="' . $c['primary-container'] . '" opacity=".55"/>'
            . '<circle cx="' . ($w * .12) . '" cy="' . ($h * .88) . '" r="' . ($h * .22) . '" fill="' . $c['secondary-container'] . '" opacity=".8"/>';
    }

    private static function sparkles($c, array $pts) {
        $s = '';
        foreach ($pts as [$x, $y, $r]) {
            $s .= '<path d="M' . $x . ' ' . ($y - $r) . ' L' . ($x + $r * .28) . ' ' . ($y - $r * .28) . ' L' . ($x + $r) . ' ' . $y . ' L' . ($x + $r * .28) . ' ' . ($y + $r * .28) . ' L' . $x . ' ' . ($y + $r) . ' L' . ($x - $r * .28) . ' ' . ($y + $r * .28) . ' L' . ($x - $r) . ' ' . $y . ' L' . ($x - $r * .28) . ' ' . ($y - $r * .28) . 'Z" fill="' . $c['primary-fixed-dim'] . '"/>';
        }
        return $s;
    }

    private static function server_unit($x, $y, $w, $c, $lit = 2) {
        $s = '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="46" rx="12" fill="#FFFFFF" stroke="' . $c['outline-variant'] . '" stroke-width="2"/>';
        $s .= '<rect x="' . ($x + 18) . '" y="' . ($y + 17) . '" width="' . ($w * .42) . '" height="12" rx="6" fill="' . $c['surface-container-high'] . '"/>';
        for ($i = 0; $i < 3; $i++) {
            $s .= '<circle cx="' . ($x + $w - 26 - $i * 18) . '" cy="' . ($y + 23) . '" r="5" fill="' . ($i < $lit ? $c['primary'] : $c['outline-variant']) . '"/>';
        }
        return $s;
    }

    private static function browser($x, $y, $w, $h, $c, $inner = '') {
        $s = '<rect x="' . ($x + 6) . '" y="' . ($y + 10) . '" width="' . $w . '" height="' . $h . '" rx="18" fill="' . $c['primary-tone-20'] . '" opacity=".08"/>';
        $s .= '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" rx="18" fill="#FFFFFF" stroke="' . $c['outline-variant'] . '" stroke-width="2"/>';
        $s .= '<path d="M' . $x . ' ' . ($y + 18) . 'a18 18 0 0 1 18 -18h' . ($w - 36) . 'a18 18 0 0 1 18 18v16h-' . $w . 'z" fill="' . $c['surface-container'] . '"/>';
        foreach ([0, 1, 2] as $i) $s .= '<circle cx="' . ($x + 20 + $i * 16) . '" cy="' . ($y + 17) . '" r="5" fill="' . ($i === 0 ? $c['primary'] : $c['outline-variant']) . '"/>';
        $s .= '<rect x="' . ($x + 76) . '" y="' . ($y + 10) . '" width="' . ($w - 100) . '" height="14" rx="7" fill="#FFFFFF"/>';
        return $s . $inner;
    }

    /* ---- spot illustrations (480 × 360 artboard) ---- */

    private static function draw_servers($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $b .= '<rect x="150" y="70" width="180" height="230" rx="26" fill="' . $c['primary-tone-20'] . '"/>';
        foreach ([0, 1, 2, 3] as $i) $b .= self::server_unit(166, 88 + $i * 52, 148, $c, $i === 1 ? 3 : 2);
        $b .= '<rect x="120" y="296" width="240" height="14" rx="7" fill="' . $c['outline-variant'] . '"/>';
        $b .= '<g transform="translate(318 66)"><rect width="104" height="44" rx="22" fill="#FFFFFF" stroke="' . $c['outline-variant'] . '" stroke-width="2"/><circle cx="22" cy="22" r="9" fill="' . $c['primary'] . '"/><text x="40" y="28" font-family="Inter,Arial,sans-serif" font-size="16" font-weight="700" fill="' . $c['on-surface'] . '">99.9%</text></g>';
        $b .= '<g transform="translate(64 196)"><rect width="88" height="40" rx="20" fill="' . $c['primary'] . '"/><text x="44" y="26" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-size="14" font-weight="700" fill="' . $c['on-primary'] . '">NVMe</text></g>';
        return [480, 360, $b . self::sparkles($c, [[96, 90, 10], [404, 250, 8]]), 'meet'];
    }

    private static function draw_cloud($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $b .= '<path d="M150 236h190a54 54 0 0 0 6-107.6A78 78 0 0 0 196 116a62 62 0 0 0-46 120z" fill="#FFFFFF" stroke="' . $c['outline-variant'] . '" stroke-width="3"/>';
        $b .= '<path d="M240 214v-62m0 0-24 24m24-24 24 24" stroke="' . $c['primary'] . '" stroke-width="12" stroke-linecap="round" stroke-linejoin="round" fill="none"/>';
        foreach ([[150, 296], [240, 306], [330, 296]] as [$x, $y]) {
            $b .= '<path d="M240 236 L' . $x . ' ' . ($y - 18) . '" stroke="' . $c['outline-variant'] . '" stroke-width="3" stroke-dasharray="6 8"/>';
            $b .= '<rect x="' . ($x - 26) . '" y="' . ($y - 22) . '" width="52" height="40" rx="12" fill="' . $c['primary-tone-20'] . '"/><circle cx="' . ($x + 12) . '" cy="' . ($y - 2) . '" r="4" fill="' . $c['primary'] . '"/><rect x="' . ($x - 16) . '" y="' . ($y - 6) . '" width="18" height="8" rx="4" fill="' . $c['inverse-surface-high'] . '"/>';
        }
        return [480, 360, $b . self::sparkles($c, [[90, 110, 11], [392, 80, 8], [410, 200, 6]]), 'meet'];
    }

    private static function draw_shield($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $b .= '<path d="M240 58l112 40v80c0 70-48 118-112 136-64-18-112-66-112-136V98z" fill="' . $c['primary'] . '"/>';
        $b .= '<path d="M240 82l88 31v65c0 55-37 93-88 108z" fill="' . $c['primary-strong'] . '" opacity=".35"/>';
        $b .= '<rect x="196" y="162" width="88" height="70" rx="16" fill="#FFFFFF"/><path d="M212 162v-16a28 28 0 0 1 56 0v16" stroke="#FFFFFF" stroke-width="12" fill="none"/>';
        $b .= '<path d="M222 197l13 13 25-27" stroke="' . $c['primary-strong'] . '" stroke-width="10" fill="none" stroke-linecap="round" stroke-linejoin="round"/>';
        $b .= '<g transform="translate(330 236)"><rect width="104" height="44" rx="22" fill="#FFFFFF" stroke="' . $c['outline-variant'] . '" stroke-width="2"/><text x="52" y="28" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-size="15" font-weight="700" fill="' . $c['on-surface'] . '">SSL ✓</text></g>';
        return [480, 360, $b . self::sparkles($c, [[104, 104, 12], [382, 92, 8]]), 'meet'];
    }

    private static function draw_globe($w, $h, $o, $c) {
        $top = !empty($o['top_space']);
        $vh = $top ? 600 : 360;
        $b = self::backdrop($c, 480, $vh);
        $cy = $top ? 400 : 180;
        $b .= '<circle cx="240" cy="' . $cy . '" r="' . ($top ? 92 : 112) . '" fill="#FFFFFF" stroke="' . $c['primary'] . '" stroke-width="4"/>';
        $r = $top ? 92 : 112;
        $b .= '<ellipse cx="240" cy="' . $cy . '" rx="' . ($r * .45) . '" ry="' . $r . '" fill="none" stroke="' . $c['primary-fixed-dim'] . '" stroke-width="3"/>';
        $b .= '<path d="M' . (240 - $r) . ' ' . $cy . 'h' . (2 * $r) . 'M' . (240 - $r * .9) . ' ' . ($cy - $r * .45) . 'h' . (1.8 * $r) . 'M' . (240 - $r * .9) . ' ' . ($cy + $r * .45) . 'h' . (1.8 * $r) . '" stroke="' . $c['primary-fixed-dim'] . '" stroke-width="3"/>';
        $tags = ['.com', '.in', '.net', '.org'];
        $pos = $top ? [[40, 330], [360, 340], [50, 460], [356, 470]] : [[56, 96], [344, 92], [60, 256], [352, 262]];
        foreach ($tags as $i => $tag) {
            [$x, $y] = $pos[$i];
            $fill = $i === 0 ? $c['primary'] : '#FFFFFF';
            $txt = $i === 0 ? $c['on-primary'] : $c['on-surface'];
            $b .= '<rect x="' . $x . '" y="' . $y . '" width="80" height="38" rx="19" fill="' . $fill . '" stroke="' . $c['outline-variant'] . '" stroke-width="' . ($i === 0 ? 0 : 2) . '"/><text x="' . ($x + 40) . '" y="' . ($y + 25) . '" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-size="16" font-weight="700" fill="' . $txt . '">' . $tag . '</text>';
        }
        if ($top) return [480, 600, $b, 'slice'];
        if (!$top) $b .= '<g transform="translate(170 300)"><rect width="140" height="40" rx="20" fill="' . $c['primary-tone-20'] . '"/><text x="70" y="26" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-size="15" font-weight="700" fill="#FFFFFF">www.</text></g>';
        return [480, 360, $b, 'meet'];
    }

    private static function draw_browser($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $inner = '<rect x="96" y="112" width="160" height="16" rx="8" fill="' . $c['on-surface'] . '" opacity=".85"/>'
            . '<rect x="96" y="138" width="120" height="10" rx="5" fill="' . $c['outline-variant'] . '"/>'
            . '<rect x="96" y="160" width="84" height="30" rx="15" fill="' . $c['primary'] . '"/>'
            . '<rect x="276" y="104" width="112" height="96" rx="16" fill="' . $c['primary-container'] . '"/>'
            . '<circle cx="332" cy="146" r="22" fill="' . $c['primary'] . '"/><path d="M322 146l7 7 14-15" stroke="' . $c['on-primary'] . '" stroke-width="5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>';
        foreach ([0, 1, 2] as $i) $inner .= '<rect x="' . (96 + $i * 100) . '" y="216" width="88" height="52" rx="12" fill="' . $c['surface-container'] . '"/>';
        $b .= self::browser(70, 60, 340, 236, $c, $inner);
        // speed gauge chip
        $b .= '<g transform="translate(330 248)"><rect width="120" height="72" rx="20" fill="#FFFFFF" stroke="' . $c['outline-variant'] . '" stroke-width="2"/><path d="M28 52a32 32 0 0 1 64 0" stroke="' . $c['surface-container-high'] . '" stroke-width="8" fill="none" stroke-linecap="round"/><path d="M28 52a32 32 0 0 1 56-21" stroke="' . $c['primary'] . '" stroke-width="8" fill="none" stroke-linecap="round"/><path d="M60 52l14-14" stroke="' . $c['on-surface'] . '" stroke-width="4" stroke-linecap="round"/></g>';
        return [480, 360, $b . self::sparkles($c, [[52, 70, 10], [440, 64, 8]]), 'meet'];
    }

    private static function draw_cms($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $inner = '<rect x="96" y="108" width="190" height="96" rx="14" fill="' . $c['primary-container'] . '"/>'
            . '<path d="M112 186l40-40 30 30 22-22 46 32z" fill="' . $c['primary'] . '" opacity=".75"/><circle cx="252" cy="134" r="12" fill="#FFFFFF"/>'
            . '<rect x="96" y="216" width="190" height="12" rx="6" fill="' . $c['on-surface'] . '" opacity=".8"/>'
            . '<rect x="96" y="236" width="150" height="10" rx="5" fill="' . $c['outline-variant'] . '"/>'
            . '<rect x="300" y="108" width="88" height="14" rx="7" fill="' . $c['outline-variant'] . '"/><rect x="300" y="132" width="70" height="14" rx="7" fill="' . $c['outline-variant'] . '"/><rect x="300" y="156" width="80" height="14" rx="7" fill="' . $c['outline-variant'] . '"/>';
        $b .= self::browser(70, 60, 340, 220, $c, $inner);
        // plug / extension tile
        $b .= '<g transform="translate(336 222)"><rect width="96" height="96" rx="28" fill="' . $c['primary'] . '"/><path d="M36 30v14m24-14v14M28 44h40v10a20 20 0 0 1-40 0zM48 74v10" stroke="' . $c['on-primary'] . '" stroke-width="7" stroke-linecap="round" fill="none"/></g>';
        return [480, 360, $b . self::sparkles($c, [[48, 92, 10]]), 'meet'];
    }

    private static function draw_vps($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $b .= '<rect x="64" y="60" width="300" height="210" rx="20" fill="' . $c['inverse-surface'] . '"/>';
        foreach ([0, 1, 2] as $i) $b .= '<circle cx="' . (86 + $i * 16) . '" cy="80" r="5" fill="' . ($i === 0 ? $c['primary'] : $c['inverse-surface-high']) . '"/>';
        $lines = [['$ ssh root@vps', 150], ['✓ Ubuntu 24.04 LTS', 180], ['✓ 4 vCPU · 8 GB RAM', 140], ['$ _', 40]];
        foreach ($lines as $i => [$txt, $len]) {
            $b .= '<text x="88" y="' . (124 + $i * 34) . '" font-family="Consolas,Menlo,monospace" font-size="17" fill="' . ($i % 3 === 0 ? $c['inverse-primary'] : $c['inverse-on-surface']) . '">' . self::t($txt) . '</text>';
        }
        // CPU chip
        $b .= '<g transform="translate(300 184)"><rect x="14" y="14" width="104" height="104" rx="22" fill="#FFFFFF" stroke="' . $c['outline-variant'] . '" stroke-width="2"/><rect x="40" y="40" width="52" height="52" rx="12" fill="' . $c['primary'] . '"/>';
        foreach ([34, 54, 74, 94] as $p) $b .= '<path d="M' . $p . ' 0v14M' . $p . ' 118v14M0 ' . $p . 'h14M118 ' . $p . 'h14" stroke="' . $c['outline'] . '" stroke-width="5" stroke-linecap="round"/>';
        $b .= '</g>';
        return [480, 360, $b . self::sparkles($c, [[420, 80, 10]]), 'meet'];
    }

    private static function draw_rocket($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $b .= '<g transform="rotate(35 240 180)">';
        $b .= '<path d="M240 52c40 30 56 82 50 150h-100c-6-68 10-120 50-150z" fill="#FFFFFF" stroke="' . $c['outline-variant'] . '" stroke-width="3"/>';
        $b .= '<circle cx="240" cy="132" r="22" fill="' . $c['primary-container'] . '" stroke="' . $c['primary'] . '" stroke-width="6"/>';
        $b .= '<path d="M190 168l-34 44h40zM290 168l34 44h-40z" fill="' . $c['primary'] . '"/>';
        $b .= '<path d="M214 204h52l-8 28h-36z" fill="' . $c['primary-strong'] . '"/>';
        $b .= '<path d="M222 236c6 30 12 44 18 56 6-12 12-26 18-56z" fill="' . $c['warning'] . '"/></g>';
        foreach ([[90, 250, 70], [110, 280, 50], [70, 220, 40]] as [$x, $y, $l]) $b .= '<path d="M' . $x . ' ' . $y . 'h' . $l . '" stroke="' . $c['primary-fixed-dim'] . '" stroke-width="8" stroke-linecap="round"/>';
        return [480, 360, $b . self::sparkles($c, [[380, 70, 12], [420, 150, 7], [86, 96, 9]]), 'meet'];
    }

    private static function draw_support($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $b .= '<circle cx="210" cy="170" r="88" fill="#FFFFFF" stroke="' . $c['outline-variant'] . '" stroke-width="3"/>';
        $b .= '<path d="M150 178v-16a60 60 0 0 1 120 0v16" stroke="' . $c['primary-tone-20'] . '" stroke-width="14" fill="none" stroke-linecap="round"/>';
        $b .= '<rect x="134" y="168" width="34" height="56" rx="14" fill="' . $c['primary'] . '"/><rect x="252" y="168" width="34" height="56" rx="14" fill="' . $c['primary'] . '"/>';
        $b .= '<path d="M270 222c0 26-22 36-46 36" stroke="' . $c['primary-tone-20'] . '" stroke-width="8" fill="none" stroke-linecap="round"/><rect x="200" y="248" width="34" height="20" rx="10" fill="' . $c['primary-tone-20'] . '"/>';
        $b .= '<g transform="translate(298 70)"><path d="M0 22a22 22 0 0 1 22-22h96a22 22 0 0 1 22 22v40a22 22 0 0 1-22 22H40l-22 20v-20A22 22 0 0 1 0 62z" fill="' . $c['primary'] . '"/><circle cx="46" cy="42" r="7" fill="' . $c['on-primary'] . '"/><circle cx="70" cy="42" r="7" fill="' . $c['on-primary'] . '"/><circle cx="94" cy="42" r="7" fill="' . $c['on-primary'] . '"/></g>';
        $b .= '<g transform="translate(316 210)"><rect width="120" height="44" rx="22" fill="#FFFFFF" stroke="' . $c['outline-variant'] . '" stroke-width="2"/><text x="60" y="28" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-size="16" font-weight="700" fill="' . $c['on-surface'] . '">24 × 7</text></g>';
        return [480, 360, $b . self::sparkles($c, [[76, 96, 10]]), 'meet'];
    }

    private static function draw_migrate($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $b .= '<rect x="54" y="110" width="130" height="170" rx="22" fill="' . $c['surface-container-highest'] . '"/>';
        foreach ([0, 1, 2] as $i) $b .= self::server_unit(66, 124 + $i * 50, 106, $c, 1);
        $b .= '<rect x="296" y="90" width="130" height="190" rx="22" fill="' . $c['primary-tone-20'] . '"/>';
        foreach ([0, 1, 2] as $i) $b .= self::server_unit(308, 106 + $i * 54, 106, $c, 3);
        $b .= '<path d="M196 160c40-34 68-34 92 0" stroke="' . $c['primary'] . '" stroke-width="8" fill="none" stroke-linecap="round" stroke-dasharray="2 16"/><path d="M276 146l14 16-20 8" stroke="' . $c['primary'] . '" stroke-width="8" fill="none" stroke-linecap="round" stroke-linejoin="round"/>';
        $b .= '<g transform="translate(196 214)"><rect width="92" height="40" rx="20" fill="' . $c['primary'] . '"/><text x="46" y="26" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-size="14" font-weight="700" fill="' . $c['on-primary'] . '">FREE</text></g>';
        return [480, 360, $b . self::sparkles($c, [[240, 76, 12]]), 'meet'];
    }

    private static function draw_chart($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $inner = '';
        $vals = [60, 92, 74, 120, 104, 146];
        foreach ($vals as $i => $v) $inner .= '<rect x="' . (104 + $i * 40) . '" y="' . (260 - $v) . '" width="26" height="' . $v . '" rx="8" fill="' . ($i === 5 ? $c['primary'] : $c['primary-container']) . '"/>';
        $inner .= '<path d="M110 190 L150 168 L190 178 L230 140 L270 150 L310 112" stroke="' . $c['primary-strong'] . '" stroke-width="5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>';
        $b .= self::browser(70, 60, 340, 220, $c, $inner);
        $b .= '<g transform="translate(316 230)"><rect width="132" height="76" rx="20" fill="#FFFFFF" stroke="' . $c['outline-variant'] . '" stroke-width="2"/><text x="20" y="34" font-family="Inter,Arial,sans-serif" font-size="13" fill="' . $c['on-surface-variant'] . '">Uptime</text><text x="20" y="60" font-family="Inter,Arial,sans-serif" font-size="22" font-weight="800" fill="' . $c['primary-strong'] . '">99.99%</text></g>';
        return [480, 360, $b, 'meet'];
    }

    private static function draw_team($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $people = [[150, 200, 0, 70], [330, 200, 2, 70], [240, 176, 1, 86]];
        foreach ($people as [$x, $y, $v, $r]) $b .= self::person($x, $y, $r, $v, $c);
        $b .= '<g transform="translate(178 292)"><rect width="124" height="40" rx="20" fill="' . $c['primary'] . '"/><text x="62" y="26" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-size="14" font-weight="700" fill="' . $c['on-primary'] . '">Team Rackmonk</text></g>';
        return [480, 360, $b . self::sparkles($c, [[80, 80, 10], [404, 84, 8]]), 'meet'];
    }

    /** Friendly abstract person bust inside a circle. */
    private static function person($cx, $cy, $r, $v, $c) {
        $skins = ['#F2C7A5', '#D9A07A', '#B67A55', '#8D5A3B', '#F7D6BF'];
        $hairs = ['#2B211C', '#4A3426', '#1C1C1C', '#6B4A2E', '#3A2A20'];
        $shirts = [$c['primary'], $c['primary-strong'], $c['inverse-surface-high'], $c['primary-fixed-dim'], $c['on-secondary-container']];
        $skin = $skins[$v % 5]; $hair = $hairs[($v + 2) % 5]; $shirt = $shirts[$v % 5];
        $id = 'p' . $v . '_' . (int) $cx . (int) $cy;
        $s = '<clipPath id="' . $id . '"><circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '"/></clipPath>';
        $s .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="' . ($v % 2 ? $c['secondary-container'] : $c['primary-container']) . '"/>';
        $s .= '<g clip-path="url(#' . $id . ')">';
        $s .= '<ellipse cx="' . $cx . '" cy="' . ($cy + $r * .98) . '" rx="' . ($r * .78) . '" ry="' . ($r * .62) . '" fill="' . $shirt . '"/>';
        $s .= '<rect x="' . ($cx - $r * .13) . '" y="' . ($cy + $r * .1) . '" width="' . ($r * .26) . '" height="' . ($r * .3) . '" fill="' . $skin . '"/>';
        $s .= '<circle cx="' . $cx . '" cy="' . ($cy - $r * .12) . '" r="' . ($r * .34) . '" fill="' . $skin . '"/>';
        switch ($v % 4) {
            case 0: $s .= '<path d="M' . ($cx - $r * .36) . ' ' . ($cy - $r * .12) . 'a' . ($r * .36) . ' ' . ($r * .38) . ' 0 0 1 ' . ($r * .72) . ' 0c-' . ($r * .2) . '-' . ($r * .16) . '-' . ($r * .5) . '-' . ($r * .1) . '-' . ($r * .72) . ' 0z" fill="' . $hair . '"/>'; break;
            case 1: $s .= '<path d="M' . ($cx - $r * .38) . ' ' . ($cy + $r * .2) . 'v-' . ($r * .34) . 'a' . ($r * .38) . ' ' . ($r * .4) . ' 0 0 1 ' . ($r * .76) . ' 0v' . ($r * .34) . 'c-' . ($r * .06) . '-' . ($r * .2) . '-' . ($r * .1) . '-' . ($r * .34) . '-' . ($r * .1) . '-' . ($r * .44) . 'c-' . ($r * .2) . ' ' . ($r * .06) . '-' . ($r * .4) . ' ' . ($r * .06) . '-' . ($r * .56) . ' 0c0 ' . ($r * .1) . '-' . ($r * .04) . ' ' . ($r * .24) . '-' . ($r * .1) . ' ' . ($r * .44) . 'z" fill="' . $hair . '"/>'; break;
            case 2: $s .= '<circle cx="' . $cx . '" cy="' . ($cy - $r * .42) . '" r="' . ($r * .2) . '" fill="' . $hair . '"/><path d="M' . ($cx - $r * .35) . ' ' . ($cy - $r * .16) . 'a' . ($r * .35) . ' ' . ($r * .3) . ' 0 0 1 ' . ($r * .7) . ' 0z" fill="' . $hair . '"/>'; break;
            default: $s .= '<path d="M' . ($cx - $r * .34) . ' ' . ($cy - $r * .2) . 'q' . ($r * .34) . '-' . ($r * .36) . ' ' . ($r * .68) . ' 0q-' . ($r * .34) . '-' . ($r * .12) . '-' . ($r * .68) . ' 0z" fill="' . $hair . '"/>';
        }
        $s .= '<circle cx="' . ($cx - $r * .12) . '" cy="' . ($cy - $r * .1) . '" r="' . ($r * .035) . '" fill="#2B211C"/><circle cx="' . ($cx + $r * .12) . '" cy="' . ($cy - $r * .1) . '" r="' . ($r * .035) . '" fill="#2B211C"/>';
        $s .= '<path d="M' . ($cx - $r * .09) . ' ' . ($cy + $r * .02) . 'q' . ($r * .09) . ' ' . ($r * .08) . ' ' . ($r * .18) . ' 0" stroke="#2B211C" stroke-width="' . max(1.2, $r * .03) . '" fill="none" stroke-linecap="round"/>';
        return $s . '</g>';
    }

    private static function draw_avatar($w, $h, $o, $c) {
        $v = (int) ($o['variant'] ?? 0);
        return [200, 200, self::person(100, 100, 100, $v, $c), 'slice'];
    }

    /** Large testimonial / portrait card: person in a tonal scene. */
    private static function draw_portrait($w, $h, $o, $c) {
        $v = (int) ($o['variant'] ?? 1);
        $b = '<rect width="400" height="460" rx="36" fill="' . $c['primary-container-low'] . '"/>';
        $b .= '<circle cx="320" cy="90" r="120" fill="' . $c['primary-container'] . '" opacity=".6"/>';
        $b .= self::person(200, 250, 170, $v, $c);
        $b .= self::sparkles($c, [[62, 70, 14], [350, 380, 10]]);
        return [400, 460, $b, 'slice'];
    }

    /* ---- AI & GPU illustrations (480 × 360 artboard) ---- */

    /** Four-point sparkle in any colour. */
    private static function star4($x, $y, $r, $fill) {
        $k = round($r * .28, 2);
        return '<path d="M' . $x . ' ' . ($y - $r) . 'L' . ($x + $k) . ' ' . ($y - $k) . 'L' . ($x + $r) . ' ' . $y . 'L' . ($x + $k) . ' ' . ($y + $k) . 'L' . $x . ' ' . ($y + $r) . 'L' . ($x - $k) . ' ' . ($y + $k) . 'L' . ($x - $r) . ' ' . $y . 'L' . ($x - $k) . ' ' . ($y - $k) . 'Z" fill="' . $fill . '"/>';
    }

    /** Round AI assistant avatar (sparkle on the theme colour). */
    private static function ai_avatar($cx, $cy, $r, $c) {
        return '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="' . $c['primary'] . '"/>' . self::star4($cx, $cy, round($r * .58, 1), $c['on-primary']);
    }

    /** Processor chip with pins on all sides; $s = outer size. */
    private static function chip($x, $y, $s, $c, $label = 'AI') {
        $pin = round($s * .12, 1); $in = round($s * .26, 1); $sw = max(2, round($s * .035, 1));
        $d = '';
        for ($i = 0; $i < 4; $i++) {
            $p = round($in + ($s - 2 * $in) * ($i + .5) / 4, 1);
            $d .= 'M' . $p . ' 0v' . $pin . 'M' . $p . ' ' . ($s - $pin) . 'v' . $pin . 'M0 ' . $p . 'h' . $pin . 'M' . ($s - $pin) . ' ' . $p . 'h' . $pin;
        }
        $b = '<g transform="translate(' . $x . ' ' . $y . ')"><path d="' . $d . '" stroke="' . $c['outline'] . '" stroke-width="' . $sw . '" stroke-linecap="round"/>';
        $b .= '<rect x="' . $pin . '" y="' . $pin . '" width="' . ($s - 2 * $pin) . '" height="' . ($s - 2 * $pin) . '" rx="' . round($s * .16, 1) . '" fill="#FFFFFF" stroke="' . $c['outline-variant'] . '" stroke-width="2"/>';
        $b .= '<rect x="' . $in . '" y="' . $in . '" width="' . ($s - 2 * $in) . '" height="' . ($s - 2 * $in) . '" rx="' . round($s * .1, 1) . '" fill="' . $c['primary'] . '"/>';
        if ($label !== '') $b .= '<text x="' . ($s / 2) . '" y="' . round($s / 2 + $s * .085, 1) . '" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-size="' . round($s * .22) . '" font-weight="800" fill="' . $c['on-primary'] . '">' . self::t($label) . '</text>';
        return $b . '</g>';
    }

    /** Cooling fan seen from the front. */
    private static function fan($cx, $cy, $r, $c) {
        $b = '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . $r . '" fill="' . $c['inverse-surface-high'] . '"/>';
        $b .= '<circle cx="' . $cx . '" cy="' . $cy . '" r="' . round($r - 3, 1) . '" fill="none" stroke="' . $c['inverse-on-surface'] . '" stroke-opacity=".25" stroke-width="2"/>';
        $blade = 'M' . $cx . ' ' . $cy . 'q' . round($r * .55, 1) . ' ' . round(-$r * .25, 1) . ' ' . round($r * .78, 1) . ' ' . round($r * .2, 1);
        $b .= '<g stroke="' . $c['inverse-on-surface'] . '" stroke-opacity=".55" stroke-width="' . max(2, round($r * .14, 1)) . '" stroke-linecap="round" fill="none">';
        for ($k = 0; $k < 6; $k++) $b .= '<path d="' . $blade . '" transform="rotate(' . ($k * 60) . ' ' . $cx . ' ' . $cy . ')"/>';
        return $b . '</g><circle cx="' . $cx . '" cy="' . $cy . '" r="' . round($r * .3, 1) . '" fill="' . $c['primary'] . '"/>';
    }

    /** Graphics card (side view): dark shroud, fans, bracket and gold contacts. */
    private static function gpu_card($x, $y, $w, $h, $c, $fans = 2) {
        $b = '<rect x="' . ($x + 8) . '" y="' . ($y + 12) . '" width="' . $w . '" height="' . $h . '" rx="18" fill="' . $c['primary-tone-20'] . '" opacity=".1"/>';
        $b .= '<rect x="' . ($x - 12) . '" y="' . ($y - 8) . '" width="14" height="' . ($h + 34) . '" rx="4" fill="' . $c['outline-variant'] . '"/>';
        $fx = round($x + $w * .16, 1); $fw = round($w * .46, 1);
        $b .= '<rect x="' . $fx . '" y="' . ($y + $h - 6) . '" width="' . $fw . '" height="20" rx="3" fill="' . $c['outline-variant'] . '"/>';
        $b .= '<path d="M' . ($fx + 6) . ' ' . ($y + $h + 7) . 'h' . ($fw - 12) . '" stroke="' . $c['warning'] . '" stroke-width="9" stroke-dasharray="5 3"/>';
        $b .= '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="' . $h . '" rx="18" fill="' . $c['inverse-surface'] . '"/>';
        $b .= '<rect x="' . ($x + 18) . '" y="' . ($y + 12) . '" width="' . ($w - 36) . '" height="6" rx="3" fill="' . $c['primary'] . '"/>';
        $r = round($h * .32, 1); $cy = round($y + $h * .58, 1);
        for ($i = 0; $i < $fans; $i++) $b .= self::fan(round($x + $w * ($i + .5) / $fans, 1), $cy, $r, $c);
        return $b;
    }

    /** One GPU server in a rack: two fans, drive slot and status lights. */
    private static function gpu_unit($x, $y, $w, $c, $lit = 2) {
        $s = '<rect x="' . $x . '" y="' . $y . '" width="' . $w . '" height="46" rx="12" fill="#FFFFFF" stroke="' . $c['outline-variant'] . '" stroke-width="2"/>';
        foreach ([0, 1] as $i) {
            $fx = $x + 22 + $i * 24;
            $s .= '<circle cx="' . $fx . '" cy="' . ($y + 23) . '" r="10" fill="' . $c['surface-container-high'] . '" stroke="' . $c['outline-variant'] . '" stroke-width="1.5"/><path d="M' . ($fx - 6) . ' ' . ($y + 23) . 'h12M' . $fx . ' ' . ($y + 17) . 'v12" stroke="' . $c['outline'] . '" stroke-width="2" stroke-linecap="round" opacity=".6"/><circle cx="' . $fx . '" cy="' . ($y + 23) . '" r="3.5" fill="' . $c['primary'] . '"/>';
        }
        if ($w >= 130) $s .= '<rect x="' . ($x + 64) . '" y="' . ($y + 18) . '" width="' . ($w - 120) . '" height="10" rx="5" fill="' . $c['surface-container-high'] . '"/>';
        for ($i = 0; $i < 2; $i++) $s .= '<circle cx="' . ($x + $w - 22 - $i * 18) . '" cy="' . ($y + 23) . '" r="5" fill="' . ($i < $lit ? $c['primary'] : $c['outline-variant']) . '"/>';
        return $s;
    }

    private static function pill($x, $y, $text, $c, $filled = false, $dot = true) {
        $w = ($dot ? 52 : 32) + mb_strlen($text) * 9.2;
        $b = '<g transform="translate(' . $x . ' ' . $y . ')"><rect width="' . round($w) . '" height="44" rx="22" fill="' . ($filled ? $c['primary'] : '#FFFFFF') . '"' . ($filled ? '' : ' stroke="' . $c['outline-variant'] . '" stroke-width="2"') . '/>';
        if ($dot) $b .= '<circle cx="22" cy="22" r="9" fill="' . ($filled ? $c['on-primary'] : $c['primary']) . '"/>';
        return $b . '<text x="' . ($dot ? 40 : 16) . '" y="28" font-family="Inter,Arial,sans-serif" font-size="15" font-weight="700" fill="' . ($filled ? $c['on-primary'] : $c['on-surface']) . '">' . self::t($text) . '</text></g>';
    }

    /** Graphics card with speed lines: fast, GPU-ready hosting. */
    private static function draw_gpu($w, $h, $o, $c) {
        $b = self::backdrop($c);
        foreach ([[30, 138, 52], [18, 168, 64], [36, 198, 44]] as [$x, $y, $l]) $b .= '<path d="M' . $x . ' ' . $y . 'h' . $l . '" stroke="' . $c['primary-fixed-dim'] . '" stroke-width="7" stroke-linecap="round"/>';
        $b .= self::gpu_card(110, 124, 300, 116, $c, 2);
        $b .= self::pill(296, 50, 'AI Ready', $c);
        $b .= self::pill(64, 274, 'GPU', $c, true, false);
        $b .= self::chip(352, 248, 76, $c, 'AI');
        return [480, 360, $b . self::sparkles($c, [[96, 80, 10], [446, 150, 7]]), 'meet'];
    }

    /** Rack of GPU servers. */
    private static function draw_gpu_rack($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $b .= '<rect x="150" y="70" width="180" height="230" rx="26" fill="' . $c['primary-tone-20'] . '"/>';
        foreach ([0, 1, 2, 3] as $i) $b .= self::gpu_unit(166, 88 + $i * 52, 148, $c, $i === 1 ? 1 : 2);
        $b .= '<rect x="120" y="296" width="240" height="14" rx="7" fill="' . $c['outline-variant'] . '"/>';
        $b .= self::pill(318, 66, '99.9%', $c);
        $b .= '<path d="M134 220h18" stroke="' . $c['primary'] . '" stroke-width="4" stroke-linecap="round"/>' . self::chip(48, 176, 88, $c, 'AI');
        return [480, 360, $b . self::sparkles($c, [[96, 90, 10], [404, 250, 8]]), 'meet'];
    }

    /** AI processor with circuit traces. */
    private static function draw_ai_chip($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $traces = ['M165 153H132V122H104', 'M165 189H72', 'M165 207H132V238H104', 'M315 153H348V122H376', 'M315 189H408', 'M315 207H348V238H376', 'M213 105V64', 'M267 105V64', 'M249 255V300'];
        $nodes = [[104, 122], [72, 189], [104, 238], [376, 122], [408, 189], [376, 238], [213, 64], [267, 64], [249, 300]];
        foreach ($traces as $i => $d) $b .= '<path d="' . $d . '" stroke="' . ($i % 3 === 1 ? $c['primary'] : $c['primary-fixed-dim']) . '" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>';
        foreach ($nodes as $i => [$x, $y]) $b .= '<circle cx="' . $x . '" cy="' . $y . '" r="9" fill="' . ($i % 3 === 1 ? $c['primary'] : '#FFFFFF') . '" stroke="' . $c['primary'] . '" stroke-width="4"/>';
        $b .= self::chip(165, 105, 150, $c, 'AI');
        return [480, 360, $b . self::sparkles($c, [[60, 80, 10], [424, 300, 8]]), 'meet'];
    }

    /** Neural network diagram. */
    private static function draw_neural($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $b .= '<rect x="96" y="56" width="288" height="248" rx="28" fill="#FFFFFF" stroke="' . $c['outline-variant'] . '" stroke-width="2"/>';
        $layers = [[150, [104, 180, 256]], [240, [92, 148, 212, 268]], [330, [140, 220]]];
        $hot = [[0, 0], [1, 1], [2, 0], [0, 2], [1, 2], [2, 1]];
        for ($l = 0; $l < 2; $l++) foreach ($layers[$l][1] as $y1) foreach ($layers[$l + 1][1] as $y2) $b .= '<path d="M' . $layers[$l][0] . ' ' . $y1 . 'L' . $layers[$l + 1][0] . ' ' . $y2 . '" stroke="' . $c['outline-variant'] . '" stroke-width="2"/>';
        foreach ([[0, 1, 2], [3, 4, 5]] as $path) {
            $d = '';
            foreach ($path as $k => $p) { [$l, $n] = $hot[$p]; $d .= ($k ? 'L' : 'M') . $layers[$l][0] . ' ' . $layers[$l][1][$n]; }
            $b .= '<path d="' . $d . '" stroke="' . $c['primary'] . '" stroke-width="4" fill="none" stroke-linejoin="round"/>';
        }
        foreach ($layers as $l => [$x, $ys]) foreach ($ys as $n => $y) {
            $on = in_array([$l, $n], $hot, true);
            $b .= '<circle cx="' . $x . '" cy="' . $y . '" r="15" fill="' . ($on ? $c['primary'] : '#FFFFFF') . '" stroke="' . ($on ? $c['primary'] : $c['primary-fixed-dim']) . '" stroke-width="3"/>';
        }
        $b .= self::pill(318, 268, 'AI Model', $c);
        return [480, 360, $b . self::sparkles($c, [[64, 84, 10], [424, 72, 8]]), 'meet'];
    }

    /** App window with an AI assistant conversation. */
    private static function draw_ai_chat($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $inner = self::ai_avatar(104, 130, 16, $c)
            . '<rect x="128" y="112" width="176" height="44" rx="16" fill="' . $c['surface-container'] . '"/><rect x="144" y="124" width="132" height="8" rx="4" fill="' . $c['on-surface'] . '" opacity=".75"/><rect x="144" y="138" width="96" height="8" rx="4" fill="' . $c['outline-variant'] . '"/>'
            . '<rect x="196" y="170" width="190" height="36" rx="16" fill="' . $c['primary-container'] . '"/><rect x="212" y="184" width="150" height="8" rx="4" fill="' . $c['primary-strong'] . '" opacity=".7"/>'
            . self::ai_avatar(104, 234, 16, $c)
            . '<rect x="128" y="218" width="200" height="34" rx="16" fill="' . $c['surface-container'] . '"/><rect x="144" y="231" width="150" height="8" rx="4" fill="' . $c['on-surface'] . '" opacity=".6"/>'
            . '<rect x="90" y="264" width="262" height="24" rx="12" fill="#FFFFFF" stroke="' . $c['outline-variant'] . '" stroke-width="2"/><rect x="104" y="273" width="110" height="6" rx="3" fill="' . $c['outline-variant'] . '"/><circle cx="338" cy="276" r="8" fill="' . $c['primary'] . '"/>';
        $b .= self::browser(70, 60, 340, 236, $c, $inner);
        $b .= self::chip(370, 212, 92, $c, 'AI');
        return [480, 360, $b . self::sparkles($c, [[52, 70, 10], [440, 64, 8]]), 'meet'];
    }

    /** Cloud with an AI chip, connected to GPU nodes. */
    private static function draw_ai_cloud($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $b .= '<path d="M150 236h190a54 54 0 0 0 6-107.6A78 78 0 0 0 196 116a62 62 0 0 0-46 120z" fill="#FFFFFF" stroke="' . $c['outline-variant'] . '" stroke-width="3"/>';
        $b .= self::chip(206, 140, 68, $c, 'AI');
        foreach ([[150, 296], [240, 306], [330, 296]] as [$x, $y]) {
            $b .= '<path d="M240 236 L' . $x . ' ' . ($y - 18) . '" stroke="' . $c['outline-variant'] . '" stroke-width="3" stroke-dasharray="6 8"/>';
            $b .= '<rect x="' . ($x - 30) . '" y="' . ($y - 22) . '" width="60" height="40" rx="12" fill="' . $c['inverse-surface'] . '"/>';
            foreach ([-13, 13] as $dx) $b .= '<circle cx="' . ($x + $dx) . '" cy="' . ($y - 2) . '" r="10" fill="' . $c['inverse-surface-high'] . '"/><circle cx="' . ($x + $dx) . '" cy="' . ($y - 2) . '" r="3.5" fill="' . $c['primary'] . '"/>';
        }
        return [480, 360, $b . self::sparkles($c, [[90, 110, 11], [392, 80, 8], [410, 200, 6]]), 'meet'];
    }

    /** Monitoring dashboard for AI apps: usage bars and a falling training curve. */
    private static function draw_ai_dashboard($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $inner = '';
        foreach ([54, 88, 70, 116, 98, 140] as $i => $v) $inner .= '<rect x="' . (104 + $i * 40) . '" y="' . (260 - $v) . '" width="26" height="' . $v . '" rx="8" fill="' . ($i === 5 ? $c['primary'] : $c['primary-container']) . '"/>';
        $inner .= '<path d="M110 118C150 150 180 182 228 192S300 208 330 212" stroke="' . $c['primary-strong'] . '" stroke-width="5" fill="none" stroke-linecap="round"/>';
        $inner .= self::chip(346, 100, 46, $c, '');
        $b .= self::browser(70, 60, 340, 220, $c, $inner);
        $b .= '<g transform="translate(316 230)"><rect width="132" height="76" rx="20" fill="#FFFFFF" stroke="' . $c['outline-variant'] . '" stroke-width="2"/><text x="20" y="34" font-family="Inter,Arial,sans-serif" font-size="13" fill="' . $c['on-surface-variant'] . '">AI app</text><circle cx="26" cy="54" r="6" fill="' . $c['primary'] . '"/><text x="40" y="60" font-family="Inter,Arial,sans-serif" font-size="20" font-weight="800" fill="' . $c['primary-strong'] . '">Online</text></g>';
        return [480, 360, $b, 'meet'];
    }

    /** Security shield wired into a circuit. */
    private static function draw_ai_shield($w, $h, $o, $c) {
        $b = self::backdrop($c);
        foreach (['M130 125H92V100H66', 'M130 170H70', 'M350 125H388V100H414', 'M350 170H410'] as $d) $b .= '<path d="' . $d . '" stroke="' . $c['primary-fixed-dim'] . '" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>';
        foreach ([[66, 100], [70, 170], [414, 100], [410, 170]] as [$x, $y]) $b .= '<circle cx="' . $x . '" cy="' . $y . '" r="8" fill="#FFFFFF" stroke="' . $c['primary'] . '" stroke-width="4"/>';
        $b .= '<path d="M240 58l112 40v80c0 70-48 118-112 136-64-18-112-66-112-136V98z" fill="' . $c['primary'] . '"/>';
        $b .= '<path d="M240 82l88 31v65c0 55-37 93-88 108z" fill="' . $c['primary-strong'] . '" opacity=".35"/>';
        $b .= '<rect x="196" y="162" width="88" height="70" rx="16" fill="#FFFFFF"/><path d="M212 162v-16a28 28 0 0 1 56 0v16" stroke="#FFFFFF" stroke-width="12" fill="none"/>';
        $b .= '<path d="M222 197l13 13 25-27" stroke="' . $c['primary-strong'] . '" stroke-width="10" fill="none" stroke-linecap="round" stroke-linejoin="round"/>';
        $b .= self::pill(330, 236, 'SSL ✓', $c, false, false);
        return [480, 360, $b . self::sparkles($c, [[100, 262, 10], [382, 52, 8]]), 'meet'];
    }

    /** Support expert with a headset and an AI helper. */
    private static function draw_ai_support($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $b .= '<circle cx="210" cy="170" r="90" fill="#FFFFFF" stroke="' . $c['outline-variant'] . '" stroke-width="3"/>';
        $b .= self::person(210, 172, 84, 1, $c);
        $b .= '<path d="M172 166v-10a38 38 0 0 1 76 0v10" stroke="' . $c['primary-tone-20'] . '" stroke-width="9" fill="none" stroke-linecap="round"/>';
        $b .= '<rect x="160" y="152" width="20" height="34" rx="9" fill="' . $c['primary'] . '"/><rect x="240" y="152" width="20" height="34" rx="9" fill="' . $c['primary'] . '"/>';
        $b .= '<path d="M250 184c0 18-12 26-28 26" stroke="' . $c['primary-tone-20'] . '" stroke-width="5" fill="none" stroke-linecap="round"/><circle cx="220" cy="210" r="6" fill="' . $c['primary-tone-20'] . '"/>';
        $b .= '<g transform="translate(298 70)"><path d="M0 22a22 22 0 0 1 22-22h96a22 22 0 0 1 22 22v40a22 22 0 0 1-22 22H40l-22 20v-20A22 22 0 0 1 0 62z" fill="' . $c['primary'] . '"/>' . self::star4(44, 42, 16, $c['on-primary']) . '<text x="68" y="50" font-family="Inter,Arial,sans-serif" font-size="22" font-weight="800" fill="' . $c['on-primary'] . '">AI</text></g>';
        $b .= self::pill(316, 210, '24 × 7', $c, false, false);
        return [480, 360, $b . self::sparkles($c, [[76, 96, 10]]), 'meet'];
    }

    /** Moving a site between GPU-ready servers. */
    private static function draw_ai_migrate($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $b .= '<rect x="54" y="110" width="130" height="170" rx="22" fill="' . $c['surface-container-highest'] . '"/>';
        foreach ([0, 1, 2] as $i) $b .= self::gpu_unit(66, 124 + $i * 50, 106, $c, 1);
        $b .= '<rect x="296" y="90" width="130" height="190" rx="22" fill="' . $c['primary-tone-20'] . '"/>';
        foreach ([0, 1, 2] as $i) $b .= self::gpu_unit(308, 106 + $i * 54, 106, $c, 2);
        $b .= '<path d="M196 160c40-34 68-34 92 0" stroke="' . $c['primary'] . '" stroke-width="8" fill="none" stroke-linecap="round" stroke-dasharray="2 16"/><path d="M276 146l14 16-20 8" stroke="' . $c['primary'] . '" stroke-width="8" fill="none" stroke-linecap="round" stroke-linejoin="round"/>';
        $b .= self::pill(196, 214, 'FREE', $c, true, false);
        return [480, 360, $b . self::sparkles($c, [[240, 76, 12]]), 'meet'];
    }

    /** Server terminal with a graphics card. */
    private static function draw_gpu_vps($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $b .= '<rect x="64" y="60" width="300" height="210" rx="20" fill="' . $c['inverse-surface'] . '"/>';
        foreach ([0, 1, 2] as $i) $b .= '<circle cx="' . (86 + $i * 16) . '" cy="80" r="5" fill="' . ($i === 0 ? $c['primary'] : $c['inverse-surface-high']) . '"/>';
        $lines = ['$ ssh root@ai-vps', '✓ Ubuntu 24.04 LTS', '✓ Python + Docker ready', '$ _'];
        foreach ($lines as $i => $txt) $b .= '<text x="88" y="' . (124 + $i * 34) . '" font-family="Consolas,Menlo,monospace" font-size="17" fill="' . ($i % 3 === 0 ? $c['inverse-primary'] : $c['inverse-on-surface']) . '">' . self::t($txt) . '</text>';
        $b .= self::gpu_card(300, 236, 156, 64, $c, 2);
        return [480, 360, $b . self::sparkles($c, [[420, 80, 10]]), 'meet'];
    }

    /** Website builder with an AI content helper. */
    private static function draw_ai_cms($w, $h, $o, $c) {
        $b = self::backdrop($c);
        $inner = '<rect x="96" y="108" width="190" height="96" rx="14" fill="' . $c['primary-container'] . '"/>'
            . '<path d="M112 186l40-40 30 30 22-22 46 32z" fill="' . $c['primary'] . '" opacity=".75"/><circle cx="252" cy="134" r="12" fill="#FFFFFF"/>'
            . '<rect x="96" y="216" width="190" height="12" rx="6" fill="' . $c['on-surface'] . '" opacity=".8"/>'
            . '<rect x="96" y="236" width="150" height="10" rx="5" fill="' . $c['outline-variant'] . '"/>'
            . '<rect x="300" y="108" width="88" height="96" rx="14" fill="' . $c['primary-container-low'] . '" stroke="' . $c['primary-fixed-dim'] . '" stroke-width="2"/>' . self::star4(344, 138, 16, $c['primary'])
            . '<rect x="314" y="166" width="60" height="8" rx="4" fill="' . $c['outline-variant'] . '"/><rect x="314" y="182" width="44" height="8" rx="4" fill="' . $c['outline-variant'] . '"/>';
        $b .= self::browser(70, 60, 340, 220, $c, $inner);
        $b .= '<g transform="translate(336 222)"><rect width="96" height="96" rx="28" fill="' . $c['primary'] . '"/>' . self::star4(46, 50, 26, $c['on-primary']) . self::star4(72, 24, 9, $c['on-primary']) . '</g>';
        return [480, 360, $b . self::sparkles($c, [[48, 92, 10]]), 'meet'];
    }

    /** Globe with a connected AI network (domains stay visible). */
    private static function draw_ai_globe($w, $h, $o, $c) {
        [$vw, $vh, $b, $mode] = self::draw_globe($w, $h, $o, $c);
        $top = !empty($o['top_space']);
        $cy = $top ? 400 : 180; $r = $top ? 92 : 112;
        $pts = [[-.5, -.35], [.15, -.62], [.6, -.05], [.05, .5], [-.62, .3], [-.05, -.02]];
        $xy = array_map(static function ($p) use ($cy, $r) { return [round(240 + $r * $p[0], 1), round($cy + $r * $p[1], 1)]; }, $pts);
        $net = '';
        foreach ([[0, 1], [1, 2], [2, 3], [3, 4], [4, 0], [5, 0], [5, 2], [5, 3]] as [$a, $z]) $net .= '<path d="M' . $xy[$a][0] . ' ' . $xy[$a][1] . 'L' . $xy[$z][0] . ' ' . $xy[$z][1] . '" stroke="' . $c['primary'] . '" stroke-width="2.5" opacity=".8"/>';
        foreach ($xy as $i => [$x, $y]) $net .= '<circle cx="' . $x . '" cy="' . $y . '" r="' . ($i === 5 ? 10 : 7) . '" fill="' . $c['primary'] . '" stroke="#FFFFFF" stroke-width="3"/>';
        return [$vw, $vh, $b . $net, $mode];
    }

    /* ---- decorative ---- */

    private static function draw_shape($w, $h, $o, $c) {
        $seed = crc32($o['seed'] ?? 'x');
        $vw = 300; $vh = max(40, round(300 * $h / max(1, $w)));
        $b = '';
        $kind = $o['shape'] ?? ['blob', 'rings', 'dots'][$seed % 3];
        if ($kind === 'blob') {
            $b .= '<path d="M' . ($vw * .5) . ' ' . ($vh * .06) . 'C' . ($vw * .82) . ' ' . ($vh * .04) . ' ' . ($vw * .98) . ' ' . ($vh * .34) . ' ' . ($vw * .92) . ' ' . ($vh * .6) . 'S' . ($vw * .62) . ' ' . ($vh * .98) . ' ' . ($vw * .38) . ' ' . ($vh * .92) . 'S' . ($vw * .02) . ' ' . ($vh * .62) . ' ' . ($vw * .08) . ' ' . ($vh * .36) . 'S' . ($vw * .22) . ' ' . ($vh * .08) . ' ' . ($vw * .5) . ' ' . ($vh * .06) . 'z" fill="' . $c['primary-container'] . '" opacity=".7"/>';
        } elseif ($kind === 'rings') {
            foreach ([.46, .34, .22] as $i => $r) $b .= '<circle cx="' . ($vw / 2) . '" cy="' . ($vh / 2) . '" r="' . (min($vw, $vh) * $r) . '" fill="none" stroke="' . ($i === 1 ? $c['primary'] : $c['primary-fixed-dim']) . '" stroke-width="' . ($i === 1 ? 3 : 2) . '" opacity="' . ($i === 1 ? .5 : .7) . '"/>';
        } elseif ($kind === 'swoosh') {
            $vh = max(20, round(300 * $h / max(1, $w)));
            $b .= '<path d="M6 ' . ($vh * .7) . 'C' . ($vw * .3) . ' ' . ($vh * .15) . ' ' . ($vw * .6) . ' ' . ($vh * .15) . ' ' . ($vw - 6) . ' ' . ($vh * .45) . '" stroke="' . $c['primary'] . '" stroke-width="' . max(3, $vh * .14) . '" fill="none" stroke-linecap="round"/>';
        } elseif ($kind === 'line') {
            $b .= '<path d="M4 ' . ($vh / 2) . 'H' . ($vw - 4) . '" stroke="' . $c['primary-fixed-dim'] . '" stroke-width="2" stroke-dasharray="4 8" stroke-linecap="round" opacity=".7"/>';
        } elseif ($kind === 'pill') {
            $b .= '<rect x="2" y="2" width="' . ($vw - 4) . '" height="' . ($vh - 4) . '" rx="' . (($vh - 4) / 2) . '" fill="' . $c['primary-container-low'] . '" stroke="' . $c['primary-fixed-dim'] . '" stroke-width="2"/>';
        } else {
            for ($y = 12; $y < $vh; $y += 24) for ($x = 12; $x < $vw; $x += 24) $b .= '<circle cx="' . $x . '" cy="' . $y . '" r="2.6" fill="' . $c['primary-fixed-dim'] . '" opacity=".7"/>';
        }
        return [$vw, $vh, $b, 'meet'];
    }

    /** Soft light glow for dark sections (replaces coloured blur images). */
    private static function draw_glow($w, $h, $o, $c) {
        $vw = 800; $vh = max(100, round(800 * $h / max(1, $w)));
        $id = 'gl' . substr(md5(($o['seed'] ?? '') . $w . $h), 0, 6);
        $b = '<defs><radialGradient id="' . $id . '" cx=".5" cy=".5" r=".5"><stop offset="0" stop-color="' . $c['primary'] . '" stop-opacity=".28"/><stop offset=".6" stop-color="' . $c['primary'] . '" stop-opacity=".08"/><stop offset="1" stop-color="' . $c['primary'] . '" stop-opacity="0"/></radialGradient></defs>';
        $b .= '<ellipse cx="' . ($vw / 2) . '" cy="' . ($vh / 2) . '" rx="' . ($vw / 2) . '" ry="' . ($vh / 2) . '" fill="url(#' . $id . ')"/>';
        return [$vw, $vh, $b, 'meet'];
    }

    private static function draw_stars($w, $h, $o, $c) {
        $b = '';
        for ($i = 0; $i < 5; $i++) {
            $cx = 12 + $i * 24.5; $cy = 13.5; $pts = [];
            for ($k = 0; $k < 10; $k++) {
                $r = $k % 2 ? 5 : 11.5;
                $a = deg2rad(-90 + $k * 36);
                $pts[] = round($cx + $r * cos($a), 2) . ',' . round($cy + $r * sin($a), 2);
            }
            $b .= '<polygon points="' . implode(' ', $pts) . '" fill="' . $c['warning'] . '"/>';
        }
        return [122, 26, $b, 'meet'];
    }

    private static function draw_badge($w, $h, $o, $c) {
        $text = self::t($o['text'] ?? 'Rackmonk');
        $icon = $o['icon'] ?? 'check';
        $paths = [
            'check'  => '<path d="M17 25l6 6 12-13" stroke="' . $c['on-primary'] . '" stroke-width="4" fill="none" stroke-linecap="round" stroke-linejoin="round"/>',
            'bolt'   => '<path d="M28 13l-10 14h8l-2 11 10-14h-8z" fill="' . $c['on-primary'] . '"/>',
            'lock'   => '<rect x="17" y="23" width="18" height="14" rx="3" fill="' . $c['on-primary'] . '"/><path d="M20 23v-4a6 6 0 0 1 12 0v4" stroke="' . $c['on-primary'] . '" stroke-width="3" fill="none"/>',
            'clock'  => '<circle cx="26" cy="26" r="10" stroke="' . $c['on-primary'] . '" stroke-width="3" fill="none"/><path d="M26 20v7l5 3" stroke="' . $c['on-primary'] . '" stroke-width="3" fill="none" stroke-linecap="round"/>',
            'disk'   => '<rect x="16" y="17" width="20" height="18" rx="4" fill="none" stroke="' . $c['on-primary'] . '" stroke-width="3"/><path d="M20 23h12M20 29h8" stroke="' . $c['on-primary'] . '" stroke-width="3" stroke-linecap="round"/>',
            'cloud'  => '<path d="M19 34h15a6 6 0 0 0 1-12 9 9 0 0 0-17 2 5 5 0 0 0 1 10z" fill="' . $c['on-primary'] . '"/>',
        ];
        $b = '<rect x=".75" y=".75" width="278.5" height="78.5" rx="16" fill="' . $c['surface-container-lowest'] . '" stroke="' . $c['outline-variant'] . '" stroke-width="1.5"/>';
        $b .= '<g transform="translate(16 14)"><rect x="2" y="2" width="48" height="48" rx="14" fill="' . $c['primary'] . '"/>' . ($paths[$icon] ?? $paths['check']) . '</g>';
        $b .= '<text x="82" y="46" font-family="Inter,Arial,sans-serif" font-size="18" font-weight="600" fill="' . $c['on-surface'] . '">' . $text . '</text>';
        return [280, 80, $b, 'meet'];
    }

    private static function draw_payments($w, $h, $o, $c) {
        $items = ['UPI', 'Cards', 'Net Banking', 'Wallets'];
        $b = ''; $x = 0;
        foreach ($items as $item) {
            $iw = 24 + strlen($item) * 8.6;
            $b .= '<rect x="' . ($x + 1) . '" y="1" width="' . $iw . '" height="30" rx="8" fill="#FFFFFF" stroke="' . $c['outline-variant'] . '" stroke-width="1.5"/><text x="' . ($x + 1 + $iw / 2) . '" y="21" text-anchor="middle" font-family="Inter,Arial,sans-serif" font-size="13" font-weight="700" fill="' . $c['on-surface'] . '">' . $item . '</text>';
            $x += $iw + 8;
        }
        return [(int) ceil($x), 32, $b, 'meet'];
    }

    /* ---- backgrounds ---- */

    /** Hero / section background. dark = white text sits on it; light = dark text. */
    private static function draw_hero($w, $h, $o, $c) {
        $dark = !empty($o['dark']);
        $vw = 1600; $vh = max(300, round(1600 * $h / max(1, $w)));
        $id = 'g' . substr(md5(($o['seed'] ?? '') . $w . $h), 0, 6);
        $from = $dark ? $c['inverse-surface'] : $c['primary-container-low'];
        $to = $dark ? $c['primary-tone-20'] : $c['surface-bright'];
        $b = '<defs><linearGradient id="' . $id . '" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="' . $from . '"/><stop offset="1" stop-color="' . $to . '"/></linearGradient>'
            . '<radialGradient id="' . $id . 'r" cx=".8" cy=".3" r=".6"><stop offset="0" stop-color="' . $c['primary'] . '" stop-opacity="' . ($dark ? .45 : .22) . '"/><stop offset="1" stop-color="' . $c['primary'] . '" stop-opacity="0"/></radialGradient></defs>';
        $b .= '<rect width="' . $vw . '" height="' . $vh . '" fill="url(#' . $id . ')"/><rect width="' . $vw . '" height="' . $vh . '" fill="url(#' . $id . 'r)"/>';
        // subtle grid
        $grid = $dark ? $c['inverse-on-surface'] : $c['primary-fixed-dim'];
        for ($x = 0; $x <= $vw; $x += 64) $b .= '<path d="M' . $x . ' 0V' . $vh . '" stroke="' . $grid . '" stroke-opacity="' . ($dark ? .05 : .18) . '"/>';
        for ($y = 0; $y <= $vh; $y += 64) $b .= '<path d="M0 ' . $y . 'H' . $vw . '" stroke="' . $grid . '" stroke-opacity="' . ($dark ? .05 : .18) . '"/>';
        // illustration on the right
        if (!empty($o['art'])) {
            $method = 'draw_' . $o['art'];
            if (method_exists(__CLASS__, $method)) {
                [, , $art] = self::$method(480, 360, [], $c);
                $scale = min(1.55, ($vh * .8) / 360);
                $art = preg_replace('/^<rect[^>]*\/><circle[^>]*\/><circle[^>]*\/>/', '', $art);
                $b .= '<circle cx="' . (($o['art_side'] ?? 'right') === 'left' ? $vw * .22 : $vw * .78) . '" cy="' . ($vh * .5) . '" r="' . ($vh * .42) . '" fill="' . $c['primary'] . '" fill-opacity="' . ($dark ? .12 : .10) . '"/>';
                $tx = ($o['art_side'] ?? 'right') === 'left' ? $vw * .06 : $vw - 480 * $scale - $vw * .06;
                $b .= '<g transform="translate(' . $tx . ' ' . (($vh - 360 * $scale) / 2) . ') scale(' . $scale . ')">' . $art . '</g>';
            }
        } else {
            $b .= '<circle cx="' . ($vw * .86) . '" cy="' . ($vh * .25) . '" r="' . ($vh * .3) . '" fill="none" stroke="' . $c['primary'] . '" stroke-opacity=".25" stroke-width="2"/>';
            $b .= '<circle cx="' . ($vw * .86) . '" cy="' . ($vh * .25) . '" r="' . ($vh * .18) . '" fill="' . $c['primary'] . '" fill-opacity=".08"/>';
            // faint AI network inside the ring
            $cx = $vw * .86; $cy = $vh * .25; $rr = $vh * .26;
            $pts = [[-.8, -.3], [-.35, -.75], [.3, -.6], [.75, .1], [.25, .65], [-.45, .5], [0, 0]];
            $xy = array_map(static function ($p) use ($cx, $cy, $rr) { return [round($cx + $rr * $p[0], 1), round($cy + $rr * $p[1], 1)]; }, $pts);
            $net = '';
            foreach ([[0, 1], [1, 2], [2, 3], [3, 4], [4, 5], [5, 0], [6, 0], [6, 2], [6, 4]] as [$a, $z]) $net .= 'M' . $xy[$a][0] . ' ' . $xy[$a][1] . 'L' . $xy[$z][0] . ' ' . $xy[$z][1];
            $b .= '<path d="' . $net . '" stroke="' . $c['primary'] . '" stroke-opacity=".28" stroke-width="2" fill="none"/>';
            foreach ($xy as $i => [$x, $y]) $b .= '<circle cx="' . $x . '" cy="' . $y . '" r="' . round(max(4, $vh * ($i === 6 ? .016 : .01)), 1) . '" fill="' . $c['primary'] . '" fill-opacity=".4"/>';
        }
        return [$vw, $vh, $b, 'slice'];
    }

    /** Blog cover: tonal background, big topic icon art, title-free so any post can use it. */
    private static function draw_cover($w, $h, $o, $c) {
        $art = $o['art'] ?? 'browser';
        $b = '<rect width="800" height="450" fill="' . $c['primary-container-low'] . '"/>';
        $b .= '<circle cx="680" cy="80" r="180" fill="' . $c['primary-container'] . '" opacity=".55"/><circle cx="90" cy="420" r="140" fill="' . $c['secondary-container'] . '"/>';
        for ($y = 24; $y < 450; $y += 32) for ($x = 24; $x < 260; $x += 32) $b .= '<circle cx="' . $x . '" cy="' . $y . '" r="2" fill="' . $c['primary-fixed-dim'] . '" opacity=".5"/>';
        $method = 'draw_' . $art;
        if (method_exists(__CLASS__, $method)) {
            [, , $inner] = self::$method(480, 360, [], $c);
            // drop the inner backdrop (first three elements) by overlaying on a transparent group
            $b .= '<g transform="translate(160 45) scale(1)">' . preg_replace('/^<rect[^>]*\/><circle[^>]*\/><circle[^>]*\/>/', '', $inner) . '</g>';
        }
        return [800, 450, $b, 'slice'];
    }
}
