<?php

namespace Match3;

class Input
{
    private const int ESCAPE_TIMEOUT_US = 10000;

    private KeyBindings $bindings;
    /** @var resource */
    private $stdin;
    private string $mouseMode = 'drag';

    public function __construct(KeyBindings $bindings)
    {
        $this->bindings = $bindings;
        $this->stdin = STDIN;
    }

    /** @param resource $stdin */
    public function setStdin($stdin): void
    {
        $this->stdin = $stdin;
    }

    public function loadBindingsPreset(string $name): void
    {
        $this->bindings->loadPreset($name);
    }

    public function setMouseMode(string $mode): void
    {
        $this->mouseMode = $mode;
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
            $read = [$this->stdin];
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
        $byte = fread($this->stdin, 1);

        if ($byte === false || $byte === '') {
            return '';
        }

        if ($byte !== "\e") {
            return $byte;
        }

        $seq = "\e";
        $meta = stream_get_meta_data($this->stdin);
        $wasBlocking = $meta['blocked'];
        stream_set_blocking($this->stdin, false);
        usleep(self::ESCAPE_TIMEOUT_US);

        for ($i = 0; $i < 32; $i++) {
            $b = fread($this->stdin, 1);

            if ($b === false || $b === '') {
                break;
            }

            $seq .= $b;

            $ord = ord($b);

            if ($ord >= 0x40 && $ord <= 0x7E && $ord !== 0x5B) {
                break;
            }
        }

        stream_set_blocking($this->stdin, $wasBlocking);

        return $seq;
    }

    private function parseMouse(string $seq): ?array
    {
        if (!str_starts_with($seq, "\e[<")) {
            return null;
        }

        $last = substr($seq, -1);

        if ($last !== 'M' && ($this->mouseMode !== 'drag' || $last !== 'm')) {
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
