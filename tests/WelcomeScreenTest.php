<?php

namespace Match3\Tests;

use Match3\KeyBindings;
use Match3\WelcomeScreen;
use PHPUnit\Framework\TestCase;

class WelcomeScreenTest extends TestCase
{
    private function runSilent(WelcomeScreen $welcome): array
    {
        ob_start();
        $result = $welcome->run();
        ob_end_clean();
        return $result;
    }

    // Default cursor is 0 (MODE_ROW).
    // ITEM_COUNT = 6: MODE_ROW=0, PRESET_ROW=1, MOUSE_ROW=2, START_ROW=3, LEADERBOARD_ROW=4, QUIT_ROW=5.

    public function testQuitKey(): void
    {
        $input = new TestableInput(new KeyBindings('arrows'));
        $welcome = new WelcomeScreen($input);
        $input->queueAction('quit');
        $this->assertSame(['action' => 'quit'], $this->runSilent($welcome));
    }

    public function testSelectStartFromDefault(): void
    {
        $input = new TestableInput(new KeyBindings('arrows'));
        $welcome = new WelcomeScreen($input);
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('select');
        $result = $this->runSilent($welcome);
        $this->assertSame('start', $result['action']);
        $this->assertSame('moves', $result['mode']);
        $this->assertSame('arrows', $result['preset']);
        $this->assertSame('drag', $result['mouseMode']);
    }

    public function testNavigateDownToLeaderboardAndSelect(): void
    {
        $input = new TestableInput(new KeyBindings('arrows'));
        $welcome = new WelcomeScreen($input);
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('select');
        $this->assertSame(['action' => 'leaderboard'], $this->runSilent($welcome));
    }

    public function testNavigateDownToQuitAndSelect(): void
    {
        $input = new TestableInput(new KeyBindings('arrows'));
        $welcome = new WelcomeScreen($input);
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('select');
        $this->assertSame(['action' => 'quit'], $this->runSilent($welcome));
    }

    public function testNavigateUpFromDefaultStaysAtTop(): void
    {
        $input = new TestableInput(new KeyBindings('arrows'));
        $welcome = new WelcomeScreen($input);
        $input->queueAction('up');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('select');
        $this->assertSame(['action' => 'quit'], $this->runSilent($welcome));
    }

    public function testChangeModeToTimer(): void
    {
        $input = new TestableInput(new KeyBindings('arrows'));
        $welcome = new WelcomeScreen($input);
        $input->queueAction('right');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('select');
        $result = $this->runSilent($welcome);
        $this->assertSame('start', $result['action']);
        $this->assertSame('timer', $result['mode']);
    }

    public function testChangePresetToWasd(): void
    {
        $input = new TestableInput(new KeyBindings('arrows'));
        $welcome = new WelcomeScreen($input);
        $input->queueAction('down');
        $input->queueAction('right');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('select');
        $result = $this->runSilent($welcome);
        $this->assertSame('start', $result['action']);
        $this->assertSame('wasd', $result['preset']);
    }

    public function testChangePresetToHjkl(): void
    {
        $input = new TestableInput(new KeyBindings('arrows'));
        $welcome = new WelcomeScreen($input);
        $input->queueAction('down');
        $input->queueAction('right');
        $input->queueAction('right');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('select');
        $result = $this->runSilent($welcome);
        $this->assertSame('start', $result['action']);
        $this->assertSame('hjkl', $result['preset']);
    }

    public function testConfirmKeyAlsoSelects(): void
    {
        $input = new TestableInput(new KeyBindings('arrows'));
        $welcome = new WelcomeScreen($input);
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('confirm');
        $result = $this->runSilent($welcome);
        $this->assertSame('start', $result['action']);
        $this->assertSame('drag', $result['mouseMode']);
    }

    public function testLeftRightNoOpOnActionRows(): void
    {
        $input = new TestableInput(new KeyBindings('arrows'));
        $welcome = new WelcomeScreen($input);
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('left');
        $input->queueAction('right');
        $input->queueAction('select');
        $result = $this->runSilent($welcome);
        $this->assertSame('start', $result['action']);
        $this->assertSame('drag', $result['mouseMode']);
    }

    public function testChangeMouseModeToClick(): void
    {
        $input = new TestableInput(new KeyBindings('arrows'));
        $welcome = new WelcomeScreen($input);
        $input->queueAction('down');
        $input->queueAction('down');
        $input->queueAction('right');
        $input->queueAction('down');
        $input->queueAction('select');
        $result = $this->runSilent($welcome);
        $this->assertSame('start', $result['action']);
        $this->assertSame('click', $result['mouseMode']);
    }

    public function testNullActionDoesNotCrash(): void
    {
        $input = new TestableInput(new KeyBindings('arrows'));
        $welcome = new WelcomeScreen($input);
        $input->queueAction('quit');
        $this->assertSame(['action' => 'quit'], $this->runSilent($welcome));
    }
}
