<?php

namespace Match3;

class KeyBindings
{
    private const array PRESETS = [
        'arrows' => [
            "\e[A" => 'up',
            "\e[B" => 'down',
            "\e[D" => 'left',
            "\e[C" => 'right',
            ' ' => 'select',
            "\n" => 'confirm',
            'q' => 'quit',
            "\e" => 'cancel',
            'h' => 'hint',
            '?' => 'hint',
            'b' => 'leaderboard',
        ],
        'wasd' => [
            'w' => 'up',
            'a' => 'left',
            's' => 'down',
            'd' => 'right',
            ' ' => 'select',
            'f' => 'swap',
            "\n" => 'confirm',
            'q' => 'quit',
            "\e" => 'cancel',
            'h' => 'hint',
            '?' => 'hint',
            'b' => 'leaderboard',
        ],
        'hjkl' => [
            'k' => 'up',
            'h' => 'left',
            'j' => 'down',
            'l' => 'right',
            ' ' => 'select',
            'f' => 'swap',
            "\n" => 'confirm',
            'q' => 'quit',
            "\e" => 'cancel',
            '?' => 'hint',
            'b' => 'leaderboard',
        ],
    ];

    private array $bindings = [];

    public function __construct(string $preset = 'arrows')
    {
        $this->loadPreset($preset);
    }

    public function loadPreset(string $name): void
    {
        $name = strtolower($name);

        if (!isset(self::PRESETS[$name])) {
            $name = 'arrows';
        }

        $this->bindings = self::PRESETS[$name];
    }

    public function loadCustom(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $contents = file_get_contents($path);

        $json = json_decode($contents, true);

        if (!is_array($json)) {
            return;
        }

        $this->bindings = [];

        foreach ($json as $key => $action) {
            $this->bindings[$this->parseKeyName($key)] = $action;
        }
    }

    public function getAction(string $bytes): ?string
    {
        return $this->bindings[$bytes] ?? null;
    }

    private function parseKeyName(string $name): string
    {
        return match ($name) {
            'up' => "\e[A",
            'down' => "\e[B",
            'left' => "\e[D",
            'right' => "\e[C",
            'space' => ' ',
            'enter' => "\n",
            'escape' => "\e",
            'tab' => "\t",
            default => $name,
        };
    }
}
