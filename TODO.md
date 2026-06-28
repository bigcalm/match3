# TODO — Match-3 CLI

## Done

| Item | Status |
|---|---|
| Issue 1 — `processCascade()` toClear set-subtraction | ✅ |
| Issue 2 — HUD always emits 4 lines, click offset stable | ✅ |
| Issue 4 — Duplicate adjacency check removed | ✅ |
| Issue 5 — Timer drift eliminated (shared `elapsedSeconds`) | ✅ |
| Issue 6 — Redundant `enableRawMode` in `Game::play()` | ✅ |
| Test gaps — `GameTest.php` (9 tests), `$disableAnimations` flag | ✅ |
| Level-complete splash overlay | ✅ |
| Game-over stats screen | ✅ |
| Pause key (timer mode, hides grid, freezes timer) | ✅ |
| High-score file resilience (atomic write, `.bak` fallback) | ✅ |
| Centred title box helper (`Renderer::renderTitleBox()`) | ✅ |
| Custom key bindings auto-detect from `data/custom_bindings.json` | ✅ |
| Issue 3 — Score-only goals confirmed, branch deleted, docs updated | ❌ won't fix |

---

## Remaining

### Architecture (nice-to-have, moderate refactor)

- **Constructor injection for Game dependencies** ✅
  `Grid`, `Level`, `Renderer`, `Input` are all optional constructor params
  with defaults. `GameTest::testLevelCompletionTakesPriorityOverMoveLimit`
  now passes a pre-configured Grid directly instead of replacing it via
  reflection.

### Skipped (out of scope for a terminal game of this size)

- Multi-theme colour schemes / unicode vs ascii fallback
- Replay system / move history
- Network leaderboard
- Configuration beyond `data/settings.json`
