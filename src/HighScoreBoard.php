<?php

namespace Match3;

class HighScoreBoard
{
    private string $filePath;
    private string $mode;
    private array $entries;

    public function __construct(?string $filePath = null, string $mode = 'moves')
    {
        $this->filePath = $filePath ?? __DIR__ . '/../data/high_scores.json';
        $this->mode = $mode;
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
            'mode' => $this->mode,
        ];

        $this->sort();
        $this->save();
    }

    public function getTop(int $n = 10): array
    {
        return array_slice($this->filtered(), 0, $n);
    }

    public function isHighScore(int $score): bool
    {
        $filtered = $this->filtered();

        if (count($filtered) < 10) {
            return true;
        }

        return $score > $filtered[count($filtered) - 1]['score'];
    }

    public function render(?string $mode = null): string
    {
        $m = $mode ?? $this->mode;
        $filtered = $this->filtered($m);

        if (empty($filtered)) {
            return "No high scores for {$m} mode yet.\n";
        }

        $out = '';
        $heading = $m === 'timer' ? 'HIGH SCORES — TIMER' : 'HIGH SCORES — MOVES';
        $out .= Renderer::ANSI_BOLD . "═══════════════════════════════════════" . Renderer::ANSI_RESET . "\n";
        $out .= Renderer::ANSI_BOLD . "          {$heading}" . Renderer::ANSI_RESET . "\n";
        $out .= Renderer::ANSI_BOLD . "═══════════════════════════════════════" . Renderer::ANSI_RESET . "\n";

        $top = array_slice($filtered, 0, 10);

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

    public function renderAll(): string
    {
        $this->load();

        return $this->render('moves') . "\n" . $this->render('timer');
    }

    private function filtered(?string $mode = null): array
    {
        $m = $mode ?? $this->mode;
        return array_values(array_filter($this->entries, fn(array $e): bool => ($e['mode'] ?? 'moves') === $m));
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
