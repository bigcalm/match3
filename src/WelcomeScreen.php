<?php

namespace Match3;

class WelcomeScreen
{
    private Input $input;
    private int $cursor = 0;
    private string $mode = 'moves';
    private string $preset = 'arrows';
    private string $mouseMode = 'drag';

    private const int ITEM_COUNT = 6;

    private const int MODE_ROW = 0;
    private const int PRESET_ROW = 1;
    private const int MOUSE_ROW = 2;
    private const int START_ROW = 3;
    private const int LEADERBOARD_ROW = 4;
    private const int QUIT_ROW = 5;

    public function __construct(Input $input)
    {
        $this->input = $input;
    }

    public function run(): array
    {
        while (true) {
            $this->render();
            $action = $this->input->getAction();

            if ($action === null) {
                continue;
            }

            if ($action === 'quit') {
                return ['action' => 'quit'];
            }

            if ($action === 'up') {
                $this->cursor = max(0, $this->cursor - 1);
                continue;
            }

            if ($action === 'down') {
                $this->cursor = min(self::ITEM_COUNT - 1, $this->cursor + 1);
                continue;
            }

            if ($action === 'left') {
                $this->change(-1);
                continue;
            }

            if ($action === 'right') {
                $this->change(1);
                continue;
            }

            if ($action === 'select' || $action === 'confirm') {
                $result = $this->activate();
                if ($result !== null) {
                    return $result;
                }
            }
        }
    }

    private function render(): void
    {
        $out = Renderer::ANSI_CLEAR_ALL_HOME;

        $out .= Renderer::ANSI_BOLD . "╔══════════════════════════════════╗" . Renderer::ANSI_RESET . "\n";
        $out .= Renderer::ANSI_BOLD . "║         ★  MATCH-3  ★            ║" . Renderer::ANSI_RESET . "\n";
        $out .= Renderer::ANSI_BOLD . "║     A terminal puzzle game       ║" . Renderer::ANSI_RESET . "\n";
        $out .= Renderer::ANSI_BOLD . "╚══════════════════════════════════╝" . Renderer::ANSI_RESET . "\n\n";

        $out .= $this->renderModeSelector() . "\n";
        $out .= $this->renderPresetSelector() . "\n";
        $out .= $this->renderMouseSelector() . "\n";
        $out .= $this->renderAction('  Start Game', self::START_ROW) . "\n";
        $out .= $this->renderAction('  Leaderboard', self::LEADERBOARD_ROW) . "\n";
        $out .= $this->renderAction('  Quit', self::QUIT_ROW) . "\n";

        $out .= "\n" . Renderer::ANSI_DIM . "Arrow/WASD/HJKL navigate  ←→ change  Space/Enter select  Q quit" . Renderer::ANSI_RESET . "\n";

        echo $out;
    }

    private function renderModeSelector(): string
    {
        $label = '  Mode: ';
        $opts = ['moves', 'timer'];
        $parts = [];

        foreach ($opts as $m) {
            if ($m === $this->mode) {
                $parts[] = Renderer::ANSI_REVERSE . " {$m} " . Renderer::ANSI_RESET;
            } else {
                $parts[] = "  {$m}  ";
            }
        }

        $line = $label . implode(' ', $parts);

        if ($this->cursor === self::MODE_ROW) {
            return Renderer::ANSI_YELLOW . "→" . Renderer::ANSI_RESET . " {$line}";
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
                $parts[] = Renderer::ANSI_REVERSE . " {$p} " . Renderer::ANSI_RESET;
            } else {
                $parts[] = "  {$p}  ";
            }
        }

        $line = $label . implode(' ', $parts);

        if ($this->cursor === self::PRESET_ROW) {
            return Renderer::ANSI_YELLOW . "→" . Renderer::ANSI_RESET . " {$line}";
        }

        return "   {$line}";
    }

    private function renderMouseSelector(): string
    {
        $label = ' Mouse: ';
        $opts = ['drag', 'click'];
        $parts = [];

        foreach ($opts as $m) {
            if ($m === $this->mouseMode) {
                $parts[] = Renderer::ANSI_REVERSE . " {$m} " . Renderer::ANSI_RESET;
            } else {
                $parts[] = "  {$m}  ";
            }
        }

        $line = $label . implode(' ', $parts);

        if ($this->cursor === self::MOUSE_ROW) {
            return Renderer::ANSI_YELLOW . "→" . Renderer::ANSI_RESET . " {$line}";
        }

        return "   {$line}";
    }

    private function renderAction(string $label, int $row): string
    {
        if ($this->cursor === $row) {
            return Renderer::ANSI_YELLOW . "→" . Renderer::ANSI_RESET . " " . Renderer::ANSI_REVERSE . "{$label}" . Renderer::ANSI_RESET;
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
            $this->input->loadBindingsPreset($this->preset);
        } elseif ($this->cursor === self::MOUSE_ROW) {
            $modes = ['drag', 'click'];
            $i = array_search($this->mouseMode, $modes, true);
            $this->mouseMode = $modes[($i + $dir + 2) % 2];
        }
    }

    private function activate(): ?array
    {
        return match ($this->cursor) {
            self::START_ROW => ['action' => 'start', 'mode' => $this->mode, 'preset' => $this->preset, 'mouseMode' => $this->mouseMode],
            self::LEADERBOARD_ROW => ['action' => 'leaderboard'],
            self::QUIT_ROW => ['action' => 'quit'],
            default => null,
        };
    }
}
