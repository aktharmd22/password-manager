<?php

namespace App\Services;

class PasswordGeneratorService
{
    private const UPPERCASE = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const LOWERCASE = 'abcdefghijklmnopqrstuvwxyz';
    private const NUMBERS = '0123456789';
    private const SYMBOLS = '!@#$%^&*()-_=+[]{};:,.<>?/~';
    private const SIMILAR = '0Oo1lI|`\'';

    /**
     * Generate a cryptographically-strong password using random_int (CSPRNG).
     *
     * @param  array{
     *     length?: int,
     *     uppercase?: bool,
     *     lowercase?: bool,
     *     numbers?: bool,
     *     symbols?: bool,
     *     exclude_similar?: bool
     * }  $options
     */
    public function generate(array $options = []): string
    {
        $defaults = config('vault.generator_defaults');
        $opts = array_merge($defaults, $options);

        $length = max(8, min(128, (int) $opts['length']));
        $excludeSimilar = (bool) $opts['exclude_similar'];

        $pools = [];
        if ($opts['uppercase']) {
            $pools[] = $this->filterSimilar(self::UPPERCASE, $excludeSimilar);
        }
        if ($opts['lowercase']) {
            $pools[] = $this->filterSimilar(self::LOWERCASE, $excludeSimilar);
        }
        if ($opts['numbers']) {
            $pools[] = $this->filterSimilar(self::NUMBERS, $excludeSimilar);
        }
        if ($opts['symbols']) {
            $pools[] = self::SYMBOLS;
        }

        if (empty($pools)) {
            // Caller passed all-false; fall back to lowercase so we never return ''.
            $pools[] = self::LOWERCASE;
        }

        // Guarantee at least one character from every enabled pool, then fill the
        // remainder from the combined pool. Finally shuffle so positions are random.
        $password = '';
        foreach ($pools as $pool) {
            $password .= $this->pickOne($pool);
        }

        $combined = implode('', $pools);
        for ($i = strlen($password); $i < $length; $i++) {
            $password .= $this->pickOne($combined);
        }

        return $this->shuffleSecure($password);
    }

    /**
     * Score a password using a heuristic similar to zxcvbn-lite. Returns 0–4.
     */
    public function strengthScore(string $password): int
    {
        $length = strlen($password);
        $score = 0;

        if ($length >= 8) $score++;
        if ($length >= 12) $score++;
        if ($length >= 16) $score++;

        $variety = 0;
        if (preg_match('/[a-z]/', $password)) $variety++;
        if (preg_match('/[A-Z]/', $password)) $variety++;
        if (preg_match('/[0-9]/', $password)) $variety++;
        if (preg_match('/[^A-Za-z0-9]/', $password)) $variety++;

        if ($variety >= 3) $score++;
        if ($variety === 4 && $length >= 16) $score++;

        // Common-pattern penalties
        if (preg_match('/^(.)\1+$/', $password)) $score = 0; // all same char
        if (preg_match('/(password|qwerty|admin|letmein|welcome|123456)/i', $password)) $score = max(0, $score - 2);

        return min(4, max(0, $score));
    }

    public function strengthLabel(int $score): string
    {
        return match (true) {
            $score <= 1 => 'Weak',
            $score === 2 => 'Fair',
            $score === 3 => 'Strong',
            default => 'Excellent',
        };
    }

    private function filterSimilar(string $chars, bool $exclude): string
    {
        if (! $exclude) {
            return $chars;
        }

        return str_replace(str_split(self::SIMILAR), '', $chars);
    }

    private function pickOne(string $pool): string
    {
        $max = strlen($pool) - 1;
        return $pool[random_int(0, $max)];
    }

    private function shuffleSecure(string $string): string
    {
        $chars = str_split($string);
        for ($i = count($chars) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$chars[$i], $chars[$j]] = [$chars[$j], $chars[$i]];
        }
        return implode('', $chars);
    }
}
