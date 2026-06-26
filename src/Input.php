<?php

namespace Match3;

class Input
{
    private KeyBindings $bindings;

    public function __construct(KeyBindings $bindings)
    {
        $this->bindings = $bindings;
    }

    public static function enableRawMode(): void
    {
        shell_exec('stty -icanon -echo min 1 time 0 2>/dev/null');
    }

    public static function restoreTerminal(): void
    {
        echo "\e[?1000l\e[?1006l";
        shell_exec('stty sane 2>/dev/null');
    }

    public static function enableMouseTracking(): void
    {
        echo "\e[?1000h\e[?1006h";
        flush();
    }

    public function readRawKey(): string
    {
        return $this->readInput();
    }

    public function getAction(?int $timeoutUs = null): ?string
    {
        if ($timeoutUs !== null) {
            $read = [STDIN];
            $write = [];
            $except = [];
            $sec = intdiv($timeoutUs, 1_000_000);
            $usec = $timeoutUs % 1_000_000;

            if (stream_select($read, $write, $except, $sec, $usec) === 0) {
                return null;
            }
        }

        $bytes = $this->readInput();

        if ($bytes === '') {
            return null;
        }

        $mouse = $this->parseMouse($bytes);

        if ($mouse !== null) {
            return "click:{$mouse[0]}:{$mouse[1]}";
        }

        return $this->bindings->getAction($bytes);
    }

    protected function readInput(): string
    {
        $byte = fread(STDIN, 1);

        if ($byte === false || $byte === '') {
            return '';
        }

        if ($byte !== "\e") {
            return $byte;
        }

        $seq = "\e";
        stream_set_blocking(STDIN, false);
        usleep(10000);

        $rest = fread(STDIN, 64);

        if ($rest !== false && $rest !== '') {
            $seq .= $rest;
        }

        stream_set_blocking(STDIN, true);

        return $seq;
    }

    private function parseMouse(string $seq): ?array
    {
        if (!str_starts_with($seq, "\e[<")) {
            return null;
        }

        $last = substr($seq, -1);

        if ($last !== 'M' && $last !== 'm') {
            return null;
        }

        $inner = substr($seq, 3, -1);
        $parts = explode(';', $inner);

        if (count($parts) < 3) {
            return null;
        }

        $btn = (int) $parts[0];
        $x = (int) $parts[1];
        $y = (int) $parts[2];

        if ($btn > 3) {
            return null;
        }

        return [$x, $y];
    }
}
