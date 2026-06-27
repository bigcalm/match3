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

    public function testParseMouseAcceptsMInClickMode(): void
    {
        $input = new Input(new KeyBindings('arrows'));
        $input->setMouseMode('click');
        $m = new \ReflectionMethod(Input::class, 'parseMouse');
        $m->setAccessible(true);
        $this->assertSame([15, 18], $m->invoke($input, "\e[<0;15;18M"));
    }

    public function testParseMouseRejectsMInClickMode(): void
    {
        $input = new Input(new KeyBindings('arrows'));
        $input->setMouseMode('click');
        $m = new \ReflectionMethod(Input::class, 'parseMouse');
        $m->setAccessible(true);
        $this->assertNull($m->invoke($input, "\e[<0;15;18m"));
    }

    public function testGetActionWithZeroTimeoutReturnsNull(): void
    {
        $input = new Input(new KeyBindings('arrows'));
        $this->assertNull($input->getAction(0));
    }
}
