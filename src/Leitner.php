<?php
declare(strict_types=1);

/** Logique métier de la boîte de Leitner (indépendante de la base). */
final class Leitner
{
    public const LEVELS = ['M1', 'M2', 'M3', 'M4', 'M5'];
    public const RESULTS = ['correct', 'incorrect', 'incomplet'];
    private const DELAYS = ['M1' => 1, 'M2' => 3, 'M3' => 7, 'M4' => 14, 'M5' => 30];

    public static function nextLevel(string $level, string $result): string
    {
        $idx = array_search($level, self::LEVELS, true);
        if ($idx === false) {
            throw new InvalidArgumentException('Niveau invalide');
        }
        if ($result === 'correct') {
            $idx = min($idx + 1, 4);
        } elseif ($result === 'incorrect') {
            $idx = max($idx - 1, 0);
        } elseif ($result !== 'incomplet') {
            throw new InvalidArgumentException('Résultat invalide');
        }
        return self::LEVELS[$idx];
    }

    public static function nextDate(string $level, ?DateTimeImmutable $from = null): string
    {
        $from ??= new DateTimeImmutable('today');
        return $from->modify('+' . self::DELAYS[$level] . ' days')->format('Y-m-d');
    }

    public static function isValidDate(string $d): bool
    {
        $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $d);
        return $dt !== false && $dt->format('Y-m-d') === $d;
    }
}
