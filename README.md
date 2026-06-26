# Match-3 CLI Game

A terminal-based Match-3 puzzle game (Bejeweled / Candy Crush style) written in PHP with no external dependencies.

Swap adjacent gems to form lines of 3+, clear them, and trigger cascading matches. Play through 20 levels with goals, track your score, and compete on the high score board.

## Requirements

- PHP 8.3+

## Install

```bash
composer install
```

## Play

```bash
php bin/play
```

## How to play

- **Arrow keys** / `WASD` / `HJKL` — move the cursor
- **Space** — select a gem, then swap with an adjacent gem
- **Enter** — confirm
- **Q** / **Escape** — quit

You can also click gems with the mouse.

### Key bindings

Three built-in presets (`arrows`, `wasd`, `hjkl`). Default is arrows. Custom bindings can be loaded from a JSON file. See `AGENTS.md` for the full action list.

## Rules

- Form a line of 3+ matching gems horizontally or vertically to clear them.
- New gems fall from above, potentially creating chain reactions (cascades).
- Each level has a goal (score target, clear gems of a colour, etc.). Reach it to advance.
- Running out of valid moves ends the game. Exceeding the move limit fails the level.
- Score: 30 pts (3-match), 60 pts (4-match), 100 pts (5-match). Cascades earn a ×2 multiplier per step.

## Project structure

```
src/
├── Game.php              # Main loop and state machine
├── Grid.php              # Gem grid, match detection, cascade
├── HighScoreBoard.php    # Persistent high scores
├── Input.php             # Raw terminal input
├── KeyBindings.php       # Key binding presets and custom maps
├── Level.php             # Level definitions and goals
└── Renderer.php          # ANSI frame rendering
data/
└── high_scores.json      # Persisted high scores
bin/
└── play                  # Entry point
```

## License

MIT
