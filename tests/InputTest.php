<?php

namespace Match3\Tests;

use Match3\Input;
use Match3\KeyBindings;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class InputTest extends TestCase
{
    public static function provideValidMousePresses(): array
    {
        return [
            'left button'   => ["\e[<0;15;18M", [15, 18]],
            'middle button' => ["\e[<1;5;10M",  [5, 10]],
            'right button'  => ["\e[<2;1;1M",   [1, 1]],
            'no button'     => ["\e[<3;80;24M", [80, 24]],
            'm terminator'  => ["\e[<0;15;18m", [15, 18]],
            'zero coords'   => ["\e[<0;0;0M",   [0, 0]],
            'large coords'  => ["\e[<0;999;999M", [999, 999]],

        ];
    }

    public static function provideInvalidMouseSequences(): array
    {
        return [
            'release event'           => ["\e[<64;15;18m"],
            'release via M'           => ["\e[<64;15;18M"],
            'drag event'              => ["\e[<32;15;18M"],
            'button 4'                => ["\e[<4;15;18M"],
            'button 5'                => ["\e[<5;15;18M"],
            'button 35'               => ["\e[<35;15;18M"],
            'no angle bracket'        => ["\e[0;15;18M"],
            'wrong terminator'        => ["\e[<0;15;18X"],
            'too few parts'           => ["\e[<0;15M"],
            'empty inner'             => ["\e[<M"],
            'not a mouse sequence'    => ["\e[A"],
            'plain text'              => ["abc"],
            'empty string'            => [""],
        ];
    }

    #[DataProvider('provideValidMousePresses')]
    public function testParseMouseReturnsCoordinates(string $seq, array $expected): void
    {
        $input = new Input(new KeyBindings('arrows'));
        $m = new \ReflectionMethod(Input::class, 'parseMouse');
        $m->setAccessible(true);
        $this->assertSame($expected, $m->invoke($input, $seq));
    }

    #[DataProvider('provideInvalidMouseSequences')]
    public function testParseMouseRejectsInvalid(string $seq): void
    {
        $input = new Input(new KeyBindings('arrows'));
        $m = new \ReflectionMethod(Input::class, 'parseMouse');
        $m->setAccessible(true);
        $this->assertNull($m->invoke($input, $seq));
    }

    public function testGetActionWithZeroTimeoutReturnsNull(): void
    {
        $input = new Input(new KeyBindings('arrows'));
        $this->assertNull($input->getAction(0));
    }

    public function testGetActionReturnsClickForMouseSequence(): void
    {
        $input = new TestableInput(new KeyBindings('arrows'));
        $input->feedInput("\e[<0;15;18M");
        $this->assertSame('click:15:18', $input->getAction());
    }

    public function testGetActionReturnsNullForEmptyInput(): void
    {
        $input = new TestableInput(new KeyBindings('arrows'));
        $input->feedInput('');
        $this->assertNull($input->getAction());
    }

    public function testGetActionDelegatesToBindings(): void
    {
        $input = new TestableInput(new KeyBindings('arrows'));
        $input->feedInput("\e[A");
        $this->assertSame('up', $input->getAction());
    }

    public function testGetActionWithTimeoutReturnsActionIfInputAvailable(): void
    {
        $input = new TestableInput(new KeyBindings('arrows'));
        $input->feedInput('q');
        $this->assertSame('quit', $input->getAction(1_000_000));
    }

    public function testGetActionNonMouseEscapeSequence(): void
    {
        $input = new TestableInput(new KeyBindings('arrows'));
        $input->feedInput("\e");
        $this->assertSame('quit', $input->getAction());
    }

    public function testGetActionWithWasdBindings(): void
    {
        $input = new TestableInput(new KeyBindings('wasd'));
        $input->feedInput('w');
        $this->assertSame('up', $input->getAction());
    }

    public function testReadRawKeyReturnsInputBytes(): void
    {
        $input = new TestableInput(new KeyBindings('arrows'));
        $input->feedInput("\e[A");
        $this->assertSame("\e[A", $input->readRawKey());
    }

    public function testConstructedWithCustomPreset(): void
    {
        $input = new TestableInput(new KeyBindings('wasd'));
        $input->feedInput('f');
        $this->assertSame('swap', $input->getAction());
    }
}

class TestableInput extends Input
{
    /** @var resource */
    private $writeEnd;

    public function __construct(KeyBindings $bindings)
    {
        $pair = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        if ($pair === false) {
            throw new \RuntimeException('Failed to create socket pair for TestableInput');
        }

        parent::__construct($bindings);
        stream_set_blocking($pair[0], false);
        $this->setStdin($pair[0]);
        $this->writeEnd = $pair[1];
    }

    public function __destruct()
    {
        if (isset($this->writeEnd)) {
            fclose($this->writeEnd);
        }
    }

    public function feedInput(string $bytes): void
    {
        fwrite($this->writeEnd, $bytes);
    }
}
