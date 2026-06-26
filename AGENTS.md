# Match-3 CLI Game

A terminal-based Match-3 puzzle game (Bejeweled/Candy Crush style) written in PHP with no external dependencies.

**Design doc:** `PLAN.md` defines the architecture, component responsibilities, data flow, and game logic. Keep it in sync whenever design decisions change.

## Git policy

- Never commit unless explicitly instructed.
- When asked to commit, make small atomic commits — one logical change per commit — so code review is easier to follow.

## Setup

- `composer install` — installs dependencies (PSR-4 autoloading).
- Entry point: `bin/play`.

## Build and test

- No build step (interpreted PHP).
- Run: `php bin/play`
- Lint: `php -l src/`
- Tests: `./vendor/bin/phpunit` (PHPUnit under `tests/`, namespace `Match3\Tests\`).

## Code style

- PSR-12 coding style.
- PSR-4 autoloading, namespace `Match3\`.
- No comments unless the logic is non-obvious (e.g. escape sequence disambiguation).
- Class-per-file, filename matches class name.
- Properties typed but no docblocks for typed properties unless needed for array shapes.
- Stationary properties on `Grid` use `int` constants, not `const`.

## Architecture

### Component mapping (see PLAN.md for full detail)

| Class | Responsibility |
|---|---|
| `Grid` | 2D gem array, random fill, swap, match detection, cascade, `hasValidMoves()` |
| `Renderer` | Frame redraw via ANSI escapes, double-buffered string, cursor overlay |
| `Input` | Raw `fread(STDIN, …)` loop, delegates byte sequences to `KeyBindings` |
| `KeyBindings` | Loads preset or custom JSON map, `getAction(bytes) → string` |
| `Level` | Goal definitions, `isComplete(state)`, move limit, level table |
| `HighScoreBoard` | Load/save `data/high_scores.json`, prompt name, show top 10 |
| `Game` | Main loop: input → logic → render, state machine |

### Data flow

```
init: Grid::fillRandom(), Input::enableRawMode(), load Level(1)
loop:
  Renderer::draw(grid, cursor, score, moves, level, goals)
  Input::getAction() → action
  on swap:
    Grid::swap()
    if Grid::findMatches(): validMoves++, cascade, score, check goals
    else: swap back, invalidMoves++
  check game-over (no moves or move limit hit)
  on game-over: HighScoreBoard::save()
cleanup: Input::restoreTerminal()
```

### Terminal handling

- Raw mode: `stty -icanon -echo min 1 time 0`, restore with `stty sane`.
- Mouse: enable `\e[?1000h\e[?1006h`, disable `\e[?1000l\e[?1006l`.
- Always call `register_shutdown_function()` to restore terminal on crash.
- Double-buffer output (build one string, `echo` once) to avoid flicker.
- Arrow keys arrive as 3-byte sequences (`\e[A`). `\e` prefix is ambiguous with standalone Escape — use a short timeout after `\e` to disambiguate.

### Match & cascade

- Scan each row for runs ≥ 3, then each column. Collect into a set (overlapping runs OK).
- Remove matched cells, shift columns down, fill top with random gems, repeat until stable.

### Levels

- Goal types: target score, clear N gems of a colour, survive N cascades.
- Level table (20 levels) defined in `Level` constructor or a static method. Harder = fewer gem types, larger grid, higher targets, fewer moves.
- Game-over when no valid moves remain (`Grid::hasValidMoves()`). Level fail when move limit exceeded.

### Key bindings

- Three built-in presets: `arrows` (default), `wasd`, `hjkl`.
- Custom: JSON file mapping key names or byte sequences to actions.
- Actions: `up`, `down`, `left`, `right`, `select`, `swap`, `quit`, `confirm`, `cancel`.

### Scoring

- 3-match: 30 pts, 4-match: 60 pts, 5-match: 100 pts.
- Cascade step multiplier: ×2 per successive cascade.
- High-score tie-breaker: fewer `invalidMoves` ranks higher.

## Gotchas

- `readline()` blocks for a full line. Use `fread(STDIN, 1)` on a raw terminal for single-key input.
- Mouse SGR coordinates are 1-based; account for HUD/border offset when mapping to grid cells.
- `data/high_scores.json` may be missing or corrupted — handle gracefully (start empty).
- Level targets need play-testing; start generous and tighten.
- PHP `readline` is not used. Use raw `fread` + ANSI escapes for all I/O.
- Minimum PHP version: 8.3 (Ubuntu 24.04 LTS default, security support until Dec 2027).
