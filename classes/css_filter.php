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
 * The custom CSS of an entity or of an IOMAD company, made safe to put in the page: it can change how the page looks,
 * not send what is in it elsewhere. A CSS can read the page (an attribute selector on the hidden key of the session,
 * input[value^="a"]) and load a picture from another site for each guess: the value leaves the site, one letter at a
 * time. The filter takes out the @import rules and every reference to another site (url(), src(), image-set(),
 * image(), cross-fade()), written plainly or hidden by the escapes of CSS; the pictures of the site, the relative
 * addresses and the data: addresses stay. A closing style tag, which would end the style element, is neutralised.
 *
 * @package    theme_epure
 * @copyright  2026 Eric Falcon
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class css_filter {
    /** @var string[] Functions of CSS that load a resource, by their name in lower case. */
    protected const LOADERS = ['url', 'src', 'image-set', '-webkit-image-set', 'image', 'cross-fade', '-webkit-cross-fade'];

    /**
     * The CSS made safe.
     *
     * @param string $css CSS written by a manager.
     * @param string|null $wwwroot Address of the site, whose resources stay allowed; that of the site by default.
     * @return string
     */
    public static function clean(string $css, ?string $wwwroot = null): string {
        global $CFG;
        $wwwroot = rtrim($wwwroot ?? $CFG->wwwroot, '/');
        // In UTF-8 only, as the decoding of the escapes reads it; the comments, which could hide a rule from the
        // reading below, are taken out.
        $css = fix_utf8($css);
        $css = preg_replace('~/\*.*?(\*/|$)~s', '', $css);
        $out = '';
        $length = strlen($css);
        for ($i = 0; $i < $length;) {
            $char = $css[$i];
            if ($char === '"' || $char === "'") {
                $end = self::string_end($css, $i);
                $out .= substr($css, $i, $end - $i);
                $i = $end;
                continue;
            }
            if ($char === '@') {
                [$name, $end] = self::ident($css, $i + 1);
                if (strtolower(self::decode($name)) === 'import') {
                    // The whole rule, up to its semicolon.
                    $i = self::statement_end($css, $end);
                    continue;
                }
                $out .= '@' . $name;
                $i = $end;
                continue;
            }
            if (self::ident_start($css, $i)) {
                [$name, $end] = self::ident($css, $i);
                if ($end < $length && $css[$end] === '(' && in_array(strtolower(self::decode($name)), self::LOADERS, true)) {
                    $close = self::call_end($css, $end);
                    $call = substr($css, $i, $close - $i);
                    $out .= self::call_allowed($call, $wwwroot) ? $call : 'none';
                    $i = $close;
                    continue;
                }
                $out .= $name;
                $i = $end;
                continue;
            }
            $out .= $char;
            $i++;
        }
        return str_ireplace('</style', '<\\/style', $out);
    }

    /**
     * Whether every address a call loads (url(), image-set() and the others) is of the site, relative or data:.
     *
     * @param string $call The call, from its name to its closing bracket.
     * @param string $wwwroot Address of the site.
     * @return bool
     */
    protected static function call_allowed(string $call, string $wwwroot): bool {
        $open = strpos($call, '(');
        $name = strtolower(self::decode(substr($call, 0, $open)));
        $inside = substr($call, $open + 1, -1);
        if ($name === 'url' || $name === 'src') {
            $value = trim($inside);
            if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                $value = substr($value, 1, self::string_end($value, 0) - 2);
            }
            return self::address_allowed($value, $wwwroot);
        }
        // Inside image-set() and the others: each string, and each call that loads something.
        $length = strlen($inside);
        for ($i = 0; $i < $length;) {
            $char = $inside[$i];
            if ($char === '"' || $char === "'") {
                $end = self::string_end($inside, $i);
                if (!self::address_allowed(substr($inside, $i + 1, $end - $i - 2), $wwwroot)) {
                    return false;
                }
                $i = $end;
                continue;
            }
            if (self::ident_start($inside, $i)) {
                [$inner, $end] = self::ident($inside, $i);
                if ($end < $length && $inside[$end] === '(') {
                    $close = self::call_end($inside, $end);
                    if (
                        in_array(strtolower(self::decode($inner)), self::LOADERS, true)
                        && !self::call_allowed(substr($inside, $i, $close - $i), $wwwroot)
                    ) {
                        return false;
                    }
                    $i = $close;
                    continue;
                }
                $i = $end;
                continue;
            }
            $i++;
        }
        return true;
    }

    /**
     * Whether an address stays in the site: relative, data:, or under the address of the site. Read as the browser
     * reads it: the escapes of CSS decoded, the tabs and new lines taken out, the backslashes as slashes.
     *
     * @param string $address Address, as written in the CSS.
     * @param string $wwwroot Address of the site.
     * @return bool
     */
    protected static function address_allowed(string $address, string $wwwroot): bool {
        $address = self::decode($address);
        $address = str_replace(["\t", "\n", "\r", '\\'], ['', '', '', '/'], $address);
        $address = trim($address, " \x00..\x1F");
        $lower = strtolower($address);
        if ($lower === '' || str_starts_with($lower, 'data:')) {
            return true;
        }
        // An address with a scheme (https:, http:, ftp:…) or without (//host): only the site itself.
        if (preg_match('~^[a-z][a-z0-9+.\-]*:~', $lower) || str_starts_with($lower, '//')) {
            $site = strtolower($wwwroot);
            $nosite = preg_replace('~^[a-z][a-z0-9+.\-]*:~', '', $site);
            foreach ([$site, $nosite] as $prefix) {
                if ($lower === $prefix || str_starts_with($lower, $prefix . '/')) {
                    return true;
                }
            }
            return false;
        }
        // Relative to the page, so in the site.
        return true;
    }

    /**
     * The value of a text of CSS with its escapes decoded (\41 or \A to the character, \x to x).
     *
     * @param string $text Text.
     * @return string
     */
    protected static function decode(string $text): string {
        return (string) preg_replace_callback('~\\\\(?:([0-9a-fA-F]{1,6})[ \t\n\r\f]?|(\r\n|[\n\r\f])|(.))~su', function ($match) {
            if (($match[1] ?? '') !== '') {
                $code = hexdec($match[1]);
                return ($code === 0 || $code > 0x10FFFF || ($code >= 0xD800 && $code <= 0xDFFF))
                    ? "\u{FFFD}" : \core_text::code2utf8($code);
            }
            if (($match[2] ?? '') !== '') {
                return '';
            }
            return $match[3] ?? '';
        }, $text);
    }

    /**
     * Whether an identifier of CSS starts here: a letter, _, a character beyond ASCII, a hyphen or an escape.
     *
     * @param string $css CSS.
     * @param int $i Position.
     * @return bool
     */
    protected static function ident_start(string $css, int $i): bool {
        $char = $css[$i];
        return ctype_alpha($char) || $char === '_' || $char === '-' || ord($char) >= 0x80
            || ($char === '\\' && isset($css[$i + 1]) && $css[$i + 1] !== "\n");
    }

    /**
     * The identifier that starts here, with its escapes as written.
     *
     * @param string $css CSS.
     * @param int $i Position.
     * @return array The identifier and the position after it.
     */
    protected static function ident(string $css, int $i): array {
        $start = $i;
        $length = strlen($css);
        while ($i < $length) {
            $char = $css[$i];
            if ($char === '\\' && $i + 1 < $length && $css[$i + 1] !== "\n") {
                if (preg_match('~\G\\\\[0-9a-fA-F]{1,6}[ \t\n\r\f]?~', $css, $match, 0, $i)) {
                    $i += strlen($match[0]);
                } else {
                    $i += 2;
                }
                continue;
            }
            if (ctype_alnum($char) || $char === '_' || $char === '-' || ord($char) >= 0x80) {
                $i++;
                continue;
            }
            break;
        }
        return [substr($css, $start, $i - $start), $i];
    }

    /**
     * The position after the string that starts here (its closing quote, or the end of the CSS).
     *
     * @param string $css CSS.
     * @param int $i Position of the opening quote.
     * @return int
     */
    protected static function string_end(string $css, int $i): int {
        $quote = $css[$i];
        $length = strlen($css);
        for ($i++; $i < $length; $i++) {
            if ($css[$i] === '\\') {
                $i++;
            } else if ($css[$i] === $quote) {
                return $i + 1;
            }
        }
        return $length;
    }

    /**
     * The position after the closing bracket of the call whose opening bracket is here, the strings and the nested
     * brackets skipped.
     *
     * @param string $css CSS.
     * @param int $i Position of the opening bracket.
     * @return int
     */
    protected static function call_end(string $css, int $i): int {
        $depth = 0;
        $length = strlen($css);
        while ($i < $length) {
            $char = $css[$i];
            if ($char === '"' || $char === "'") {
                $i = self::string_end($css, $i);
                continue;
            }
            if ($char === '\\') {
                $i += 2;
                continue;
            }
            if ($char === '(') {
                $depth++;
            } else if ($char === ')') {
                $depth--;
                if ($depth === 0) {
                    return $i + 1;
                }
            }
            $i++;
        }
        return $length;
    }

    /**
     * The position after the semicolon that ends the rule here (or after the end of the CSS), the strings and the
     * brackets skipped.
     *
     * @param string $css CSS.
     * @param int $i Position.
     * @return int
     */
    protected static function statement_end(string $css, int $i): int {
        $length = strlen($css);
        while ($i < $length) {
            $char = $css[$i];
            if ($char === '"' || $char === "'") {
                $i = self::string_end($css, $i);
                continue;
            }
            if ($char === '(') {
                $i = self::call_end($css, $i);
                continue;
            }
            if ($char === '\\') {
                $i += 2;
                continue;
            }
            $i++;
            if ($char === ';') {
                return $i;
            }
        }
        return $length;
    }
}
