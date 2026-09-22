# Porting the streaming recorder to another Poodll plugin

Portable notes from replacing the cloud.poodll.com iframe recorder with the in-page streaming
recorder in mod_readaloud's read step (2026-09, branch `streaming-read-step`).

This is the transferable version. The readaloud-specific account, with file and line references,
is in `streamingspeechforreadaloud-review.md` in this folder.

---

## 1. Set these up before writing any code

Both of these cost real time when left until late.

**A browser.** Three rounds of CSS were guessed blind and two of them were wrong, because there
was no way to look at the page. On this machine:

```
sudo npx --yes playwright install-deps chromium   # needs a real terminal, sudo has no tty under !
npm i playwright && npx playwright install chromium
```

Then drive it from a script. The valuable part is not screenshots, it is measurement:

```js
await page.locator('canvas.mod_readaloud_ttrec_waveForm').boundingBox();
await page.evaluate(() => getComputedStyle(el).position);
```

One `boundingBox()` call would have answered in seconds what took three attempts to guess.

**A test user, already past the prerequisites.** Activity steps gate each other, so a fresh
student cannot reach the step being worked on. Create the user, enrol it, and set its attempt
status bitmask so the target step is unlocked. Make it a site admin if settings need checking.

---

## 2. Know which half of the job you are in

Two quite different shapes:

- **The streaming stack already exists in the plugin** (readaloud: practice mode used it). Then
  the job is mostly "make this step use the other recorder", plus the grading integration.
- **The plugin has no streaming stack** (solo). Then the stack has to be ported first, and that
  is a separate piece of work with its own verification, before any integration starts.

Check with:

```
ls mod/<plugin>/amd/src/ | grep -E "ttstreamer|ttazure|ttrecorder|ttaudiohelper|mediauploader"
```

The stack is roughly: `ttrecorder.js` (orchestrator, reads config from data attributes),
`ttaudiohelper.js` (mic, canvas, wav encoding), `ttstreamer.js` (AssemblyAI), `ttazure.js`
(Azure), `ttbrowserrec.js` (Chrome speech), `ttmsspeech.js`, `ttwavencoder.js`,
`mediauploader.js` (presigned upload), `timer.js`, plus `templates/ttrecorder.mustache`.

These files are near-identical between plugins, so they port almost verbatim. Port the fixed
versions — see section 5.

---

## 3. Challenge the brief before planning

The readaloud brief said the blocker was a missing `.vtt` file. There is no `.vtt` anywhere in
the plugin. The real dependency was **word-level timings inside the full transcript json**,
which nothing in the brief mentioned and which had much wider consequences (wpm, spot check).

Spend the first pass confirming what the code actually depends on, and say so plainly if the
brief is wrong. It reshaped the whole plan here.

**Do not guess external APIs.** The Azure word-timing parameters were taken from the MS Speech
SDK source (`ConnectionFactoryBase.setCommonUrlParams`, `SpeechContext.setWordLevelTimings`),
not from memory. The obvious guess — that `format=detailed` alone gives word timings — is wrong;
it needs `wordLevelTimestamps=true` as well.

---

## 4. Look for plumbing that already exists

`mod_readaloud_submit_streaming_attempt` was registered in `db/services.php`, fully implemented
in `external.php`, and called from nowhere — left over from a defunct Amazon streaming era. It
needed its json shape updated and wiring from js, and that was most of the server side done.

Before building a new web service or a new grading path, grep for a dead one.

---

## 5. Carry these fixes with the stack

Both are in the readaloud versions of these files and should travel with any port.

**Reconnect rebasing (`ttstreamer.js`, `ttazure.js`).** A token refresh closes the socket and
opens a new one. The new session restarts `turn_order` and word offsets at zero. Without
handling, the new turns overwrite the old ones and everything spoken before the refresh is lost,
silently. Fix is an audio clock that survives the socket (`audioseconds`, advanced from pcm
buffer length at the fixed 16kHz rate), plus `sessionoffset` and `turnbase` rolled on each new
socket. The MS Speech SDK does the same thing internally in
`DetailedSpeechPhrase.updateOffsets()`.

This matters far more than it looks. The refresh timer starts when the **step is entered**, not
when recording starts, so a student who reads the passage through before pressing record can hit
a refresh during a perfectly ordinary one-minute reading. Token lifetimes are 9 minutes (Azure)
and 10 (AssemblyAI).

**Word timings.** AssemblyAI v3 `Turn` messages carry `words[]` with ms offsets; it was being
discarded. Azure needs `&format=detailed&wordLevelTimestamps=true` on the socket url, and then
reports `Offset`/`Duration` in 100-nanosecond ticks inside `NBest[0].Words`. Both are rebased
onto the recording timeline before being handed out.

### Tracked item: back-port to mod_minilesson

**mod_minilesson has the reconnect bug today.** `minilesson/amd/src/ttstreamer.js:95-100`:

```js
this.socket.onopen = () => {
    that.finaltext = '';
    that.finals = [];        // wipes everything on a token refresh
```

No `turnbase`, no `sessionoffset`. PassageReading recordings are short so it rarely fires, but
the defect is real and silent when it does. `ttazure.js` there is functionally identical to
readaloud's pre-fix version too.

Back-port `ttstreamer.js` and `ttazure.js` from readaloud as a small standalone change,
independent of any Solo work. Minilesson does not need the word timings (PassageReading scores
by comparing text, not by audio position) but it does need the reconnect fix, and the two are
in the same edit.

---

## 6. Traps that cost time here

**Verify assumptions about the upload pipeline.** A fallback was built that, on a failed
streaming transcript, registered the server-side adhoc task to transcribe the audio instead.
It could never work: `ttrecorder.js` configures its uploader with `transcribe = 0`, so the cloud
never transcribes that audio. The task polled for files that would never exist and the student's
report polled behind it forever. Check what the uploader actually asks the cloud to do.

**An empty or failed result must be a valid outcome, not an error.** Silence has to grade as a
zero and resolve the report, the way the upload path already does. Anything that leaves the
attempt without a transcript leaves the student watching a countdown indefinitely. Cap the
polling as well, whatever the cause.

**Shared files need gating.** `ttrecorder.js` and `ttrecorder.mustache` are used by several item
types. New behaviour goes behind a data attribute that defaults to off (`savemedia`,
`transcribemedia`, `showtimer` all follow this), so the step being worked on opts in and nothing
else changes. Never flip a shared default to suit one caller.

**Know when the step's js is initialised.** `read.init()` is called twice: once at page load and
again after the template renders. The first call found no recorder markup, read every data
attribute as `undefined` (including `forcestreaming`, so it chose browser rec), and then died on
a missing canvas, which aborted the whole controller. Guard on the markup being present.

**The audio url and the transcript arrive separately.** The iframe reported a submission only
once the audio was safely uploaded. The streaming recorder fires `mediasaved` as soon as the
upload *starts* and `speech` about a second after the socket closes. Collect both, submit on
whichever lands second, and add a timeout backstop — an empty transcript fires no `speech` event
at all, so without one the student is stranded.

**Fire the step-complete callback from the ajax callback, not before it.** Moving on
immediately means the results check can beat the grade being written, and then sits through a
retry delay for nothing.

**Transcript post-processing parity.** The upload path ran language-specific conversions
(numbers, kana, eszett) that the streaming path did not, so the same reading scored differently
depending on the recorder. Extract the massaging and call it from both.

**Feature-detect from the attempt, not the activity settings.** A teacher can change the
transcriber after students have read. Whether spot check works depends on whether *that
attempt's* transcript carried timings.

---

## 7. Layout of the ttrecorder partial

The canvas element's intrinsic size is never set in html; it stays at the default 300x150. The
drawing code works in a space of `waveHeight * 2` tall and centres the trace on `waveHeight`, so
the trace renders across the **middle** of the canvas with half the box empty above and below.

Consequence: a record button left in normal flow always looks stranded well below the wave, and
no canvas height or gap value fixes it. Every container that uses this partial has to position
the button absolutely over the canvas, as `_practice.scss` does:

```scss
.mod_readaloud_ttrec_waveButtonContainer {
    position: relative;
    canvas.mod_readaloud_ttrec_waveForm { height: 46px; width: 100%; }
    button.mod_readaloud_ttrec_waveButton { position: absolute; }
}
```

Related but cosmetic and not worth chasing: `drawWave` steps across `canvas.width() * 2`
coordinates on a 300-wide canvas, so the trace shows only the first ~58% of the audio buffer
stretched across the full visible width. It fills the width; it just carries less of the buffer
than intended.

---

## 8. Cost and fairness decisions that belong to the product owner

Surface these early rather than deciding them in code.

- **Vocab biasing is lost.** The iframe path passes the passage hash to bias transcription;
  streaming has no equivalent. Accuracy on proper nouns will differ from what teachers are used
  to, so existing activities may score differently.
- **Safety net vs cost.** Uploading with `transcribe = 1` gives a server-side transcript that is
  never read when streaming works — `aigrade`/`aitranscript` only fetch when they do not already
  have one — so it cannot interfere. It costs a second transcription on every recording. Without
  it, a technical streaming failure scores the student zero.
- **Client-supplied grades.** The transcript is posted by the browser and becomes the grade. Same
  trade-off MiniLesson already makes, but worth a conscious decision on a graded activity.

---

## 9. Notes specific to mod_solo

From a reconnaissance pass, not from doing the work.

- **No streaming stack at all.** Only `cloudpoodll.js`, `cloudpoodllloader.js`,
  `recorderhelper.js`, `recordercontroller.js`. The whole stack in section 2 has to be ported
  first. This is the main reason Solo is a bigger job than readaloud was.
- **The architecture rhymes.** `classes/aitranscript.php` is the analogue of readaloud's
  `aigrade.php`, including the same `$this->attemptdata->filename . '.txt'` / `.json` / `.gjson`
  fetch pattern. `external.php` already has `check_for_results()` and `submit_step()`.
- Recorder markup lives in `templates/mediarecorder.mustache`,
  `audiorecordercontainer.mustache`, `videorecordercontainer.mustache`,
  `stepmediarecord.mustache`.
- Solo records **video as well as audio**, which readaloud does not. The streaming stack is
  audio-only. Decide early whether streaming applies to the audio path only.

Suggested order: port the stack (with the section 5 fixes) and verify it in isolation, then do a
same / different / missing map of Solo's attempt and grading model against readaloud's, then
integrate one step at a time.
