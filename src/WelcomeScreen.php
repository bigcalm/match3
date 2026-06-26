<?php

namespace Match3;

class WelcomeScreen
{
    private Input $input;
    private int $cursor = 0;
    private string $mode = 'moves';
    private string $preset = 'arrows';

    private const int ITEM_COUNT = 5;

    private const int MODE_ROW = 0;
    private const int PRESET_ROW = 1;
    private const int START_ROW = 2;
    private const int LEADERBOARD_ROW = 3;
    private const int QUIT_ROW = 4;

    public function __construct(Input $input)
    {
        $this->input = $input;
    }

    public function run(): array
    {
        while (true) {
            $this->render();
            $key = $this->input->readRawKey();

            if ($key === '' || $key === 'q' || $key === "\e") {
                return ['action' => 'quit'];
            }

            if ($key === "\e[A" || $key === 'k') {
                $this->cursor = max(0, $this->cursor - 1);
                continue;
            }

            if ($key === "\e[B" || $key === 'j') {
                $this->cursor = min(self::ITEM_COUNT - 1, $this->cursor + 1);
                continue;
            }

            if ($key === "\e[D" || $key === 'h') {
                $this->change(-1);
                continue;
            }

            if ($key === "\e[C" || $key === 'l') {
                $this->change(1);
                continue;
            }

            if ($key === "\n" || $key === ' ') {
                $result = $this->activate();
                if ($result !== null) {
                    return $result;
                }
            }
        }
    }

    private function render(): void
    {
        $out = "\e[2J\e[H";

        $out .= "\e[1m╔══════════════════════════════════╗\e[0m\n";
        $out .= "\e[1m║         ★  MATCH-3  ★            ║\e[0m\n";
        $out .= "\e[1m║     A terminal puzzle game       ║\e[0m\n";
        $out .= "\e[1m╚══════════════════════════════════╝\e[0m\n\n";

        $out .= $this->renderModeSelector() . "\n";
        $out .= $this->renderPresetSelector() . "\n";
        $out .= $this->renderAction('  Start Game', self::START_ROW) . "\n";
        $out .= $this->renderAction('  Leaderboard', self::LEADERBOARD_ROW) . "\n";
        $out .= $this->renderAction('  Quit', self::QUIT_ROW) . "\n";

        $out .= "\n\e[2m↑↓ navigate  ←→ change  ↵ select  q quit\e[0m\n";

        echo $out;
    }

    private function renderModeSelector(): string
    {
        $label = '  Mode: ';
        $opts = ['moves', 'timer'];
        $parts = [];

        foreach ($opts as $m) {
            if ($m === $this->mode) {
                $parts[] = "\e[7m {$m} \e[0m";
            } else {
                $parts[] = "  {$m}  ";
            }
        }

        $line = $label . implode(' ', $parts);

        if ($this->cursor === self::MODE_ROW) {
            return "\e[33m→\e[0m {$line}";
        }

        return "   {$line}";
    }

    private function renderPresetSelector(): string
    {
        $label = '  Keys: ';
        $opts = ['arrows', 'wasd', 'hjkl'];
        $parts = [];

        foreach ($opts as $p) {
            if ($p === $this->preset) {
                $parts[] = "\e[7m {$p} \e[0m";
            } else {
                $parts[] = "  {$p}  ";
            }
        }

        $line = $label . implode(' ', $parts);

        if ($this->cursor === self::PRESET_ROW) {
            return "\e[33m→\e[0m {$line}";
        }

        return "   {$line}";
    }

    private function renderAction(string $label, int $row): string
    {
        if ($this->cursor === $row) {
            return "\e[33m→\e[0m \e[7m{$label}\e[0m";
        }

        return "   {$label}";
    }

    private function change(int $dir): void
    {
        if ($this->cursor === self::MODE_ROW) {
            $modes = ['moves', 'timer'];
            $i = array_search($this->mode, $modes, true);
            $this->mode = $modes[($i + $dir + 2) % 2];
        } elseif ($this->cursor === self::PRESET_ROW) {
            $presets = ['arrows', 'wasd', 'hjkl'];
            $i = array_search($this->preset, $presets, true);
            $this->preset = $presets[($i + $dir + 3) % 3];
        }
    }

    private function activate(): ?array
    {
        return match ($this->cursor) {
            self::START_ROW => ['action' => 'start', 'mode' => $this->mode, 'preset' => $this->preset],
            self::LEADERBOARD_ROW => ['action' => 'leaderboard'],
            self::QUIT_ROW => ['action' => 'quit'],
            default => null,
        };
    }
}
