<?php
declare(strict_types=1);

/**
 * Rendu Markdown léger (titres, listes, gras/italique, code, liens, images, tableaux).
 * Tout le HTML brut est échappé avant traitement ; les URL sont limitées à http(s)/relatif.
 */
final class Markdown
{
    public static function render(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = explode("\n", $text);
        $out = [];
        $n = count($lines);
        $i = 0;
        while ($i < $n) {
            $line = $lines[$i];
            if (trim($line) === '') {
                $i++;
                continue;
            }
            if (preg_match('/^```/', $line)) {
                $code = [];
                $i++;
                while ($i < $n && !preg_match('/^```/', $lines[$i])) {
                    $code[] = $lines[$i++];
                }
                $i++;
                $out[] = '<pre><code>' . self::esc(implode("\n", $code)) . '</code></pre>';
                continue;
            }
            if (preg_match('/^(#{1,6})\s+(.*)$/', $line, $m)) {
                $l = strlen($m[1]);
                $out[] = "<h$l>" . self::inline($m[2]) . "</h$l>";
                $i++;
                continue;
            }
            if (self::isTableRow($line) && $i + 1 < $n && preg_match('/^\s*\|?\s*:?-{2,}:?\s*(\|\s*:?-{2,}:?\s*)*\|?\s*$/', $lines[$i + 1])) {
                $head = self::cells($line);
                $i += 2;
                $html = '<table><thead><tr>';
                foreach ($head as $c) {
                    $html .= '<th>' . self::inline($c) . '</th>';
                }
                $html .= '</tr></thead><tbody>';
                while ($i < $n && self::isTableRow($lines[$i])) {
                    $html .= '<tr>';
                    foreach (self::cells($lines[$i]) as $c) {
                        $html .= '<td>' . self::inline($c) . '</td>';
                    }
                    $html .= '</tr>';
                    $i++;
                }
                $out[] = $html . '</tbody></table>';
                continue;
            }
            if (preg_match('/^\s*([-*+]|\d+\.)\s+/', $line)) {
                $ordered = (bool) preg_match('/^\s*\d+\./', $line);
                $items = [];
                while ($i < $n && preg_match('/^\s*(?:[-*+]|\d+\.)\s+(.*)$/', $lines[$i], $m)) {
                    $items[] = '<li>' . self::inline($m[1]) . '</li>';
                    $i++;
                }
                $tag = $ordered ? 'ol' : 'ul';
                $out[] = "<$tag>" . implode('', $items) . "</$tag>";
                continue;
            }
            if (preg_match('/^>\s?/', $line)) {
                $q = [];
                while ($i < $n && preg_match('/^>\s?(.*)$/', $lines[$i], $m)) {
                    $q[] = $m[1];
                    $i++;
                }
                $out[] = '<blockquote>' . self::inline(implode(' ', $q)) . '</blockquote>';
                continue;
            }
            $p = [];
            while ($i < $n && trim($lines[$i]) !== '' && !preg_match('/^(```|#{1,6}\s|>|\s*([-*+]|\d+\.)\s)/', $lines[$i])) {
                $p[] = $lines[$i++];
            }
            if (!$p) {
                $p[] = $lines[$i++];
            }
            $out[] = '<p>' . implode('<br>', array_map([self::class, 'inline'], $p)) . '</p>';
        }
        return implode("\n", $out);
    }

    private static function isTableRow(string $l): bool
    {
        return str_contains($l, '|') && trim($l) !== '';
    }

    private static function cells(string $l): array
    {
        $l = trim($l);
        $l = preg_replace('/^\||\|$/', '', $l);
        return array_map('trim', explode('|', $l));
    }

    private static function esc(string $s): string
    {
        return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function safeUrl(string $url): ?string
    {
        $url = html_entity_decode($url, ENT_QUOTES, 'UTF-8');
        if (preg_match('/^(https?:\/\/|\/|\.\/|\.\.\/)/i', $url) || !preg_match('/^[a-z][a-z0-9+.\-]*:/i', $url)) {
            return self::esc($url);
        }
        return null;
    }

    public static function inline(string $s): string
    {
        $codes = [];
        $s = preg_replace_callback('/`([^`]+)`/', function ($m) use (&$codes) {
            $codes[] = '<code>' . self::esc($m[1]) . '</code>';
            return "\x00" . (count($codes) - 1) . "\x00";
        }, $s);
        $s = self::esc($s);
        $s = preg_replace_callback('/!\[([^\]]*)\]\(([^)\s]+)\)/', function ($m) {
            $u = self::safeUrl($m[2]);
            return $u === null ? $m[0] : '<img src="' . $u . '" alt="' . $m[1] . '" loading="lazy">';
        }, $s);
        $s = preg_replace_callback('/\[([^\]]+)\]\(([^)\s]+)\)/', function ($m) {
            $u = self::safeUrl($m[2]);
            return $u === null ? $m[0] : '<a href="' . $u . '" rel="noopener noreferrer" target="_blank">' . $m[1] . '</a>';
        }, $s);
        $s = preg_replace('/\*\*(.+?)\*\*|__(.+?)__/s', '<strong>$1$2</strong>', $s);
        $s = preg_replace('/(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?!\*)/s', '<em>$1</em>', $s);
        return preg_replace_callback('/\x00(\d+)\x00/', fn ($m) => $codes[(int) $m[1]], $s);
    }
}
