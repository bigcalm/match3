<?php

namespace Match3\Tests;

use Match3\KeyBindings;
use PHPUnit\Framework\TestCase;

class KeyBindingsTest extends TestCase
{
    public function testArrowsPreset(): void
    {
        $kb = new KeyBindings('arrows');
        $this->assertSame('up', $kb->getAction("\e[A"));
        $this->assertSame('down', $kb->getAction("\e[B"));
        $this->assertSame('left', $kb->getAction("\e[D"));
        $this->assertSame('right', $kb->getAction("\e[C"));
        $this->assertSame('select', $kb->getAction(' '));
        $this->assertSame('confirm', $kb->getAction("\n"));
        $this->assertSame('quit', $kb->getAction('q'));
        $this->assertSame('cancel', $kb->getAction("\e"));
    }

    public function testWasdPreset(): void
    {
        $kb = new KeyBindings('wasd');
        $this->assertSame('up', $kb->getAction('w'));
        $this->assertSame('left', $kb->getAction('a'));
        $this->assertSame('down', $kb->getAction('s'));
        $this->assertSame('right', $kb->getAction('d'));
        $this->assertSame('swap', $kb->getAction('f'));
    }

    public function testHjklPreset(): void
    {
        $kb = new KeyBindings('hjkl');
        $this->assertSame('up', $kb->getAction('k'));
        $this->assertSame('left', $kb->getAction('h'));
        $this->assertSame('down', $kb->getAction('j'));
        $this->assertSame('right', $kb->getAction('l'));
    }

    public function testUnknownPresetFallsBackToArrows(): void
    {
        $kb = new KeyBindings('nonexistent');
        $this->assertSame('up', $kb->getAction("\e[A"));
    }

    public function testUnknownKeyReturnsNull(): void
    {
        $kb = new KeyBindings('arrows');
        $this->assertNull($kb->getAction('z'));
    }

    public function testCustomJsonLoad(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'kb_test_');
        file_put_contents($tmpFile, json_encode(['x' => 'quit']));

        $kb = new KeyBindings();
        $kb->loadCustom($tmpFile);
        $this->assertSame('quit', $kb->getAction('x'));

        unlink($tmpFile);
    }

    public function testCustomJsonHandlesMissingFile(): void
    {
        $kb = new KeyBindings();
        $kb->loadCustom('/nonexistent/file.json');
        $this->assertNotNull($kb->getAction(' '));
    }

    public function testLoadPresetSwitchesBindings(): void
    {
        $kb = new KeyBindings('arrows');
        $kb->loadPreset('wasd');
        $this->assertNull($kb->getAction("\e[A"));
        $this->assertSame('up', $kb->getAction('w'));
    }

    public function testCustomJsonKeyNames(): void
    {
        $tmpFile = tempnam(sys_get_temp_dir(), 'kb_test_');
        file_put_contents($tmpFile, json_encode([
            'up' => 'up',
            'space' => 'select',
            'escape' => 'quit',
        ]));

        $kb = new KeyBindings();
        $kb->loadCustom($tmpFile);
        $this->assertSame('up', $kb->getAction("\e[A"));
        $this->assertSame('select', $kb->getAction(' '));
        $this->assertSame('quit', $kb->getAction("\e"));

        unlink($tmpFile);
    }
}
