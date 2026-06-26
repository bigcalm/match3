<?php

namespace Match3;

class HighScoreBoard
{
    private string $filePath;
    private array $entries;

    public function __construct(?string $filePath = null)
    {
        $this->filePath = $filePath ?? __DIR__ . '/../data/high_scores.json';
        $this->entries = [];
        $this->load();
    }

    public function add(string $name, int $score, int $level, int $validMoves, int $invalidMoves): void
    {
        $this->entries[] = [
            'name' => $name,
            'score' => $score,
            'level' => $level,
            'valid_moves' => $validMoves,
            'invalid_moves' => $invalidMoves,
        ];

        $this->sort();
        $this->save();
    }

    public function getTop(int $n = 10): array
    {
        return array_slice($this->entries, 0, $n);
    }

    public function isHighScore(int $score): bool
    {
        if (count($this->entries) < 10) {
            return true;
        }

        return $score > $this->entries[count($this->entries) - 1]['score'];
    }

    public function render(): string
    {
        if (empty($this->entries)) {
            return "No high scores yet.\n";
        }

        $out = '';
        $out .= "\e[1m═══════════════════════════════════════\e[0m\n";
        $out .= "\e[1m          HIGH SCORES\e[0m\n";
        $out .= "\e[1m═══════════════════════════════════════\e[0m\n";

        $top = $this->getTop(10);

        foreach ($top as $i => $entry) {
            $rank = $i + 1;
            $out .= sprintf(
                " %d. %-12s %6d  Level %-2d (%d invalid)\n",
                $rank,
                $entry['name'],
                $entry['score'],
                $entry['level'],
                $entry['invalid_moves'],
            );
        }

        $out .= "───────────────────────────────────────\n";

        return $out;
    }

    private function load(): void
    {
        if (!is_file($this->filePath)) {
            return;
        }

        $data = file_get_contents($this->filePath);

        if ($data === false) {
            return;
        }

        $decoded = json_decode($data, true);

        if (!is_array($decoded)) {
            return;
        }

        $this->entries = $decoded;
        $this->sort();
    }

    private function save(): void
    {
        $dir = dirname($this->filePath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->filePath, json_encode($this->entries, JSON_PRETTY_PRINT));
    }

    private function sort(): void
    {
        usort($this->entries, function (array $a, array $b): int {
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }

            return $a['invalid_moves'] <=> $b['invalid_moves'];
        });
    }
}
