# Shadow step: current state

Notes recorded 2026-09-20, found incidentally while wiring streaming speech into the read step.
Not acted on — shadow is understood to be unavailable at the moment. This is a record of what
the code currently does, so whoever picks shadow up again knows what they are walking into.

## Summary

Shadow is half wired. The plumbing (constants, steps bitmask, grading flag, web service
parameter, model audio playback hooks) is largely in place, but there is no shadow template and
the step cannot be switched on from the activity form. There are also two latent `letsshadow`
bugs that would bite immediately if it were re-enabled.

## What exists and works

- `constants::STEP_SHADOW = 4`, included in `constants::STEPS` as `step_shadow`.
- Site setting default has shadow **off**: `settings.php:217` defaults the steps to
  `[STEP_LISTEN => 1, STEP_PRACTICE => 1, STEP_SHADOW => 0, STEP_READ => 1, STEP_QUIZ => 1]`.
- Site setting `disableshadowgrading` exists (`settings.php:338`) and is honoured server side in
  `submit_regular_attempt`, and now also in `submit_streaming_attempt`.
- `show_recorder()` passes `hints->shadowing` to the iframe recorder based on
  `$moduleinstance->enableshadow`.
- `activitycontroller` has the full navigation path: a `letsshadow` flag, a
  `startshadowbutton` handler that sets it true (`activitycontroller.js:537-542`), and
  `doreadinglayout()` which picks the mode from it (`activitycontroller.js:814`).
- `getJsPropsForMode("read")` passes `letsshadow` through into the read module's opts
  (`activitycontroller.js:955`).

## What is missing or broken

### 1. There is no shadow template

`getTemplateForMode()` maps shadow onto the listen template:

```js
case "shadow":
    return "mod_readaloud/listen"; // TEMP: reuse listen for shadow
```

`templates/shadow.mustache` does not exist. The listen template has no recorder block of any
kind, so entering shadow mode renders a page with nothing to record into. `renderMode()` does
call `read.init()` for shadow (`mode === "read" || mode === "shadow"`), but there is no recorder
DOM for it to bind to.

This is true for both recorders — it is not a streaming-specific problem. Under the iframe
recorder `recorderhelper.init()` fails silently; under the streaming recorder the read step's
guard skips initialisation and logs.

### 2. The step cannot be enabled from the activity form

The shadow checkbox is commented out in the steps group (`classes/utils.php:2767`):

```php
// $mform->createElement('advcheckbox', 'step_shadow', '', get_string('enableshadow', constants::M_COMPONENT)),
```

So even though the bitmask supports it, a teacher has no way to turn the step on.

### 3. Two `letsshadow` bugs in read.js

`letsshadow` arrives on the module as `opts.letsshadow`. Two places read it off the module
object directly instead, where it is never set, so both evaluate as `undefined`:

| Line | Code | Effect if shadow were live |
|---|---|---|
| `read.js:111` | `dd.opts.stepshadow_enabled && dd.letsshadow` | The model audio is never paused when recording ends, so it keeps playing after the student stops |
| `read.js:284` | `that.opts.stepshadow_enabled && that.letsshadow` | `shadowing` is always submitted as 0, so `disableshadowgrading` never takes effect and shadowed attempts are always graded |

Compare `read.js:70`, which gets it right with `dd.opts.letsshadow`.

Note: the streaming submit added on 2026-09-20 (`send_streaming_submission`, `read.js:255`)
deliberately uses the correct `that.opts.letsshadow`. So the two submit paths currently disagree
with each other. That is intentional — the new one is right — but it means fixing `read.js:284`
should be done rather than copying the old form.

## If shadow is picked up again

Rough order:

1. Build `templates/shadow.mustache`, or make the read template handle both modes. It needs a
   recorder block; if streaming is on it should use the same `{{> mod_readaloud/ttrecorder}}`
   partial the read step now uses, with the same `readstreaming` / `readrecorder` context.
2. Point `getTemplateForMode()` at it.
3. Uncomment the `step_shadow` checkbox in `utils.php`.
4. Fix the two `letsshadow` references above.
5. Decide what shadowing means for streaming recognition specifically: the model audio plays out
   loud while the student reads, so it bleeds into the microphone. Under the old iframe flow that
   audio went to server side recognition; with in-browser streaming the model voice is being fed
   into the recogniser live alongside the student. That may degrade the transcript badly. Worth
   testing before enabling shadow on streaming activities — it may need to stay on the iframe
   path, or require headphones.

Point 5 is the one that is a design question rather than a code fix, and it is the reason this
is worth reading before re-enabling shadow rather than after.
