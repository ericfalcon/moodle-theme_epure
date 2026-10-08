<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace theme_epure;

/**
 * The platform as an installable web app: a web app manifest (name, icon, colour), and a service worker that shows a
 * page « You are offline » without network.
 *
 * The service worker caches nothing else: the pages of Moodle depend on the user and change, they always come from the
 * network. It lives in the browsers, outside the pages: when the web app is turned off, or the user no longer sees
 * Épure, the browsers get from the same address a service worker that removes itself and its cache, at their next
 * check (on a visit, at least once an hour of use).
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class web_app {
    /** @var int[] Sizes of the icons, in pixels. */
    public const SIZES = [180, 192, 512];

    /**
     * Whether the web app is offered to the current user: turned on, and their pages are shown with Épure.
     *
     * @return bool
     */
    public static function enabled(): bool {
        return !during_initial_install() && get_config('theme_epure', 'webapp') && theme_use::current();
    }

    /**
     * Address of a file of the web app, with the revision of the theme so that the browsers fetch a new one after a
     * change.
     *
     * @param string $file manifest, icon, sw or offline.
     * @param array $params Parameters.
     * @return \moodle_url
     */
    public static function url(string $file, array $params = []): \moodle_url {
        return new \moodle_url('/theme/epure/webapp/' . $file . '.php', $params + ['rev' => theme_get_revision()]);
    }

    /**
     * Path of the site, the scope of the web app: the pages of the site only.
     *
     * @return string For example / or /moodle/.
     */
    public static function scope(): string {
        global $CFG;
        return rtrim((string) parse_url($CFG->wwwroot, PHP_URL_PATH), '/') . '/';
    }

    /**
     * The colours of the web app: the brand colour of the user's pages (with IOMAD, of their company).
     *
     * @return array{fill: string, on: string}
     */
    public static function colours(): array {
        $palette = palette::derive(company_style::page_brand());
        return ['fill' => $palette['fill'], 'on' => $palette['on']];
    }

    /**
     * The web app manifest.
     *
     * @return array
     */
    public static function manifest(): array {
        global $SITE;
        $name = format_string($SITE->fullname, true, ['context' => \context_system::instance(), 'escape' => false]);
        $shortname = trim((string) get_config('theme_epure', 'webappname'))
            ?: format_string($SITE->shortname, true, ['context' => \context_system::instance(), 'escape' => false]);
        $colours = self::colours();
        $icons = [];
        foreach ([192, 512] as $size) {
            $src = self::url('icon', ['size' => $size, 'colour' => ltrim($colours['fill'], '#')])->out(false);
            $icons[] = ['src' => $src, 'sizes' => "{$size}x{$size}", 'type' => 'image/png', 'purpose' => 'any'];
            $icons[] = ['src' => $src, 'sizes' => "{$size}x{$size}", 'type' => 'image/png', 'purpose' => 'maskable'];
        }
        return [
            'id' => self::scope(),
            'name' => $name,
            'short_name' => \core_text::substr($shortname, 0, 30),
            'lang' => current_language(),
            'start_url' => (new \moodle_url('/my/'))->out(false),
            'scope' => self::scope(),
            'display' => 'standalone',
            'background_color' => palette::SURFACE_LIGHT,
            'theme_color' => $colours['fill'],
            'icons' => $icons,
        ];
    }

    /**
     * Icon of the web app, as a square PNG: the icon set in the settings, else the initial of the site in the colour
     * of the text on the brand colour, on the brand colour.
     *
     * @param int $size Size, one of SIZES.
     * @param string|null $colour Brand colour, the one of the site by default.
     * @return string PNG data.
     */
    public static function icon(int $size, ?string $colour = null): string {
        global $CFG, $SITE;
        $size = in_array($size, self::SIZES, true) ? $size : 512;
        $image = imagecreatetruecolor($size, $size);
        $file = self::icon_file();
        if ($file && ($source = @imagecreatefromstring($file->get_content()))) {
            imagecopyresampled($image, $source, 0, 0, 0, 0, $size, $size, imagesx($source), imagesy($source));
            imagedestroy($source);
        } else {
            $palette = palette::derive(palette::normalise($colour) ?? palette::normalise(get_config('theme_epure', 'brandcolor')));
            [$r, $g, $b] = palette::to_rgb($palette['fill']);
            imagefill($image, 0, 0, imagecolorallocate($image, $r, $g, $b));
            [$r, $g, $b] = palette::to_rgb($palette['on']);
            $ink = imagecolorallocate($image, $r, $g, $b);
            $name = trim(format_string($SITE->shortname ?: $SITE->fullname, true, ['escape' => false]));
            $initial = \core_text::strtoupper(\core_text::substr($name !== '' ? $name : 'M', 0, 1));
            // The initial, in the safe zone of the maskable icons (the central 80 %).
            $font = $CFG->libdir . '/default.ttf';
            $points = $size * 0.42;
            $box = imagettfbbox($points, 0, $font, $initial);
            $x = (int) round(($size - ($box[2] - $box[0])) / 2 - $box[0]);
            $y = (int) round(($size - ($box[1] - $box[7])) / 2 - $box[7]);
            imagettftext($image, $points, 0, $x, $y, $ink, $font, $initial);
        }
        ob_start();
        imagepng($image);
        imagedestroy($image);
        return (string) ob_get_clean();
    }

    /**
     * The icon set in the settings.
     *
     * @return \stored_file|null
     */
    protected static function icon_file(): ?\stored_file {
        $path = (string) get_config('theme_epure', 'webappicon');
        if ($path === '') {
            return null;
        }
        $files = get_file_storage()->get_area_files(
            \context_system::instance()->id,
            'theme_epure',
            'webappicon',
            0,
            'itemid, filepath, filename',
            false
        );
        return $files ? reset($files) : null;
    }

    /**
     * Tags of the head of the pages: the manifest, the colour of the browser bar and the icon for iOS.
     *
     * @return string HTML.
     */
    public static function head_html(): string {
        $colours = self::colours();
        $icon = self::url('icon', ['size' => 180, 'colour' => ltrim($colours['fill'], '#')]);
        $manifest = ['rel' => 'manifest', 'href' => self::url('manifest')->out(false), 'crossorigin' => 'use-credentials'];
        return \html_writer::empty_tag('link', $manifest)
            . \html_writer::empty_tag('meta', ['name' => 'theme-color', 'content' => $colours['fill']])
            . \html_writer::empty_tag('link', ['rel' => 'apple-touch-icon', 'href' => $icon->out(false)]);
    }

    /**
     * The service worker.
     *
     * Turned on, it keeps the page « You are offline » and shows it for the pages that cannot be loaded; turned off,
     * or for a user who no longer sees Épure, it removes its cache and itself.
     *
     * @return string JavaScript.
     */
    public static function service_worker(): string {
        $cache = 'theme_epure-' . theme_get_revision();
        if (!self::enabled()) {
            return <<<JS
// Épure: the web app is turned off, or the theme is no longer in use. The service worker removes itself.
self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => {
    event.waitUntil(caches.keys()
        .then((keys) => Promise.all(keys.filter((key) => key.startsWith('theme_epure-')).map((key) => caches.delete(key))))
        .then(() => self.registration.unregister()));
});
JS;
        }
        $offline = json_encode(self::url('offline')->out(false));
        $cachename = json_encode($cache);
        return <<<JS
// Épure: page « You are offline » of the installable web app. Nothing else is cached.
const CACHE = {$cachename};
const OFFLINE = {$offline};
let checked = Date.now();
self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.add(new Request(OFFLINE, {credentials: 'same-origin'})))
        .then(() => self.skipWaiting()));
});
self.addEventListener('activate', (event) => {
    event.waitUntil(caches.keys()
        .then((keys) => Promise.all(keys.filter((key) => key.startsWith('theme_epure-') && key !== CACHE)
            .map((key) => caches.delete(key))))
        .then(() => self.clients.claim()));
});
self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate') {
        return;
    }
    // At most once an hour, the browser checks whether the service worker changed (turned off, theme changed).
    if (Date.now() - checked > 3600000) {
        checked = Date.now();
        self.registration.update();
    }
    event.respondWith(fetch(event.request).catch(() => caches.match(OFFLINE)));
});
JS;
    }

    /**
     * Records that the web app was turned on once: its service worker is then removed from the browsers of the users
     * of Épure once turned off.
     */
    public static function setting_updated(): void {
        if (get_config('theme_epure', 'webapp')) {
            set_config('webappused', 1, 'theme_epure');
        }
    }

    /**
     * Script of the pages that registers the service worker, or removes it when the web app is turned off.
     *
     * @return string JavaScript.
     */
    public static function register_js(): string {
        $sw = json_encode(self::url('sw')->out(false));
        $scope = json_encode(self::scope());
        if (get_config('theme_epure', 'webapp')) {
            return "if ('serviceWorker' in navigator) { navigator.serviceWorker.register({$sw}, {scope: {$scope}}); }";
        }
        // Never turned on: nothing to remove.
        if (!get_config('theme_epure', 'webappused')) {
            return '';
        }
        return "if ('serviceWorker' in navigator) { navigator.serviceWorker.getRegistrations().then((registrations) => "
            . "registrations.forEach((registration) => { const worker = registration.active || registration.waiting; "
            . "if (worker && worker.scriptURL.includes('/theme/epure/webapp/sw.php')) { registration.unregister(); } })); }";
    }
}
