# Streaming Speech for ReadAloud — implementation review

Review of `forclaude/streamingspeechforreadaloud.md`. Written 2026-09-20.

**Porting this to another plugin?** Start with `streaming-port-learnings.md` in this folder —
the transferable checklist, the fixes that must travel with the streaming stack, the
mod_minilesson back-port, and a reconnaissance of mod_solo. This document is the
readaloud-specific account behind it.

## Summary of position

- The spec's VTT premise is incorrect. There is no `.vtt` anywhere in the plugin. The real
  dependency is **per-word timings inside `fulltranscript`**, which is a bigger deal than VTT.
- Word timings are obtainable: AssemblyAI v3 `Turn` messages carry a `words[]` array that
  `ttstreamer.js` currently discards. Capture it and nothing downstream has to degrade.
- I recommend the **local transcript path**, not a new Cloud Poodll endpoint. The local
  plumbing already exists and is unused.
- The highest practical risk is **token refresh mid-recording corrupting long readings**.
- Mobile is not a separate decision — the app embeds the same web page in an iframe.

---

## 1. The VTT premise is wrong

There is no reference to `.vtt`, `webvtt` or `subtitle` handling in any PHP, JS, Mustache or
CSS file of mod_readaloud. `aigrade::fetch_transcripts()` (`classes/aigrade.php:296-303`)
fetches only:

- `{filename}.txt` → `transcript`
- `{filename}.json`, falling back to `{filename}.gjson` → `fulltranscript`

Spot check does not use VTT either. `doPlaySpotCheck()` (`amd/src/gradenowhelper.js:517`)
reads `audiostart` / `audioend` from `sessionmatches`. Those are populated by
`utils::fetch_audio_points_json()` (`classes/utils.php:1088`) from the **word-level `items`
array in `fulltranscript`**.

So the question is not "can we live without VTT" — we already do. The question is
**"can we produce word-level timings client-side"**, and the answer is yes.

### What actually breaks without word timings

| Consumer | Behaviour with no timings | Severity |
|---|---|---|
| `fetch_audio_points()` (`utils.php:1068`) | Non-JSON fulltranscript → every match gets `audiostart = audioend = 0`. Spot check silently plays from 0.0 every time. | Worse than removing the feature — it looks broken rather than absent |
| `fetch_duration_from_transcript()` (`utils.php:1007`) | Returns 0 → `do_diff()` falls to hard-coded `$sessiontime = 60` (`aigrade.php:379`) | **Critical.** WPM is a headline ReadAloud metric and becomes meaningless |
| `sync_modelaudio_breaks()` / `guess_modelaudio_breaks()` | Unaffected — separate path via `readaloud_modelaudio_adhoc` | None |

The WPM consequence is the one that would make the feature look broken to a teacher, and it
is not mentioned in the spec.

### The timings are already on the wire

`ttstreamer.handlefinalresponse()` (`amd/src/ttstreamer.js:246`) reads only
`payload.transcript`. AssemblyAI v3 `Turn` payloads also carry `words[]`, each with `start`
and `end` in milliseconds relative to session start, plus `text` and `confidence`. Capturing
that array is a small change and feeds `fetch_audio_points_json` directly.

---

## 2. Cloud endpoint vs local transcript — recommend local

The spec leans toward a new Cloud Poodll endpoint so that `aigrade.php` works unchanged.
I would go the other way.

### The local path is already built and unused

- `mod_readaloud_submit_streaming_attempt` registered at `db/services.php:20`
- Implemented at `classes/external.php:303-360`
- Calls `aigrade::process_streaming_transcripts()` (`classes/aigrade.php:258`)

This is a complete client-transcript path, end to end, that **nothing in JS ever calls**. It
is left over from the defunct Amazon streaming era (`constants::TRANSCRIBER_AMAZONSTREAMING
= 4 //defunct`). It needs its JSON shape updated and wiring from `read.js`.

### Against the cloud endpoint

- Cloud server work outside this repo, plus version-compat with older plugin releases forever.
- Keeps the async poll, the `readaloud_s3_adhoc` task and its 24-hour retry loop — for data
  the browser already holds synchronously.
- Write-to-S3-then-curl-it-back adds seconds to minutes of latency and a class of failure
  modes (eventual consistency, the `AccessDenied` check at `aigrade.php:297`) for no gain.
- Makes results hostage to the audio upload succeeding.

### For local

- Instant results. Removes the `readreporthelper` polling wait entirely for streaming attempts.
- Zero cloud changes; ships on the plugin's own release cycle.
- The "existing code works unchanged" benefit is smaller than it looks — it is one branch in
  one constructor, and the other branch is already written.

### The question that would flip this

Does anything outside Moodle consume the `.txt` / `.json` files in S3 — Poodll-side admin
tooling, error-estimate reporting, support diagnostics? If yes, the cloud endpoint wins on
operational grounds. If no, local.

Recommended shape either way: audio still goes to the presigned upload URL (as MiniLesson
PassageReading does), transcript posts to Moodle.

---

## 3. Risks, in priority order

### 3.1 Token refresh mid-recording will corrupt long readings — highest risk

`ttstreamer.updatetoken()` (`ttstreamer.js:108`) closes the socket and calls
`preparesocket()`. On the new socket, `turn_order` restarts at 0 and word timestamps restart
at 0. `finals[payload.turn_order]` then **overwrites** earlier turns, and the timeline resets.

Practice recordings are ≤15s (`maxtime` is hardcoded to 15 in `renderer.php:393`), so this
never fires today. A read-step passage runs for minutes and AssemblyAI tokens are
short-lived, so it will fire routinely.

Needs: a cumulative audio-time offset carried across socket generations, turn-order rebasing,
and ideally deferring refresh until the recording ends. Because the AudioContext is created
at a fixed 16000 Hz for streaming (`ttaudiohelper.js:34`), sample count is a reliable audio
clock for the offset.

### 3.2 Transcript post-processing parity — silent scoring regression

`fetch_transcripts()` runs language-specific massaging that `process_streaming_transcripts()`
does not:

- `alphabetconverter::words_to_numbers_convert()` (en and default)
- `alphabetconverter::words_to_suji_convert()` (ja)
- `alphabetconverter::ss_to_eszett_convert()` (de)

`process_streaming_transcripts()` only calls `diff::cleanText()`. A student reading "1995"
would be scored wrong on streaming and right on iframe, for the same reading. Extract the
massaging into a shared helper and call it from both paths.

### 3.3 The read step must not fall back to browser speech recognition

`ttrecorder.js:204` prefers Chrome's `SpeechRecognition` whenever it is available and the
activity is not guided and `forcestreaming` is off. For the read step that means:

- no word timings, ever
- on Android, no audio file at all (the `androidblocked` path at `ttrecorder.js:198`)

Teacher grading and spot check require the audio. Force `forcestreaming = true` for the read
step. This is a deliberate divergence from practice and from MiniLesson PassageReading.

### 3.4 The audio URL is reported before the file exists

`ttrecorder.js:150` fires `mediasaved` synchronously immediately after calling `uploadBlob()`,
and `mediauploader.update_filenames()` rewrites `config.s3filename` later inside the xhr
ready-state handler. With `transcode=1` the mp3 name is only valid once transcoding finishes.

ReadAloud stores that value into `attempt->filename` and the report page plays it straight
away, so the player will 404 or sit silent. The current iframe recorder does not have this
problem — `awaitingprocessing` only fires once the file is really there.

MiniLesson sidesteps it by showing the local blob URL
(`item/passagereading/amd/src/itemtype.js:152`). Options: wait for the real
`doUploadCompleteCallback`, or play the blob locally for the immediate post-read report and
keep a poll for the durable URL.

### 3.5 `rectime` is accepted and discarded

`utils::create_update_attempt($filename, $rectime, $readaloud, $gradeable)`
(`utils.php:1972`) never uses `$rectime`. It is the natural WPM fallback when word timings
are unavailable.

Do **not** write it to `attempt->sessiontime`: `do_diff()` treats a non-null value there as an
authoritative *human* evaluation and it overrides everything else (`aigrade.php:362`). Store
it on the aidata record and add an explicit fallback branch instead.

### 3.6 Gate on the right setting

`renderer.php:576` derives `stt_guided` from `$moduleinstance->transcriber` — that is the
**line** transcriber, used by practice. The read step's setting is
`$moduleinstance->stricttranscribe`, tested via `utils::do_strict_transcribe()`
(`utils.php:263`). Two similarly named settings; easy to wire to the wrong one.

### 3.7 Vocab biasing is lost

The iframe path passes `transcribevocab` (passagehash or corpushash) and, for whisper, the
passage itself as a prompt (`renderer.php:521-535`). AssemblyAI v3 streaming has no
equivalent in this code path. Acceptable given the feature is gated to Open STT, but expect
measurably worse accuracy on proper nouns and unusual words than the current upload path for
the same student.

### 3.8 China and Azure

This mostly falls out for free: `utils::fetch_streaming_token()` (`utils.php:3861`) tries
`fetch_azure_token()` first and only falls back to AssemblyAI. `fetch_azure_token()` handles
`.cn` and `.us` domains correctly (`utils.php:1540`).

Caveats:

- It is a **site-wide** switch. A site that configures an Azure key gets Azure everywhere,
  not only in China. Worth being explicit about in the settings UI.
- `ttazure.js` read only `res.DisplayText` from `speech.phrase` — **no word timings**.
  Fixed and confirmed working against a live Azure endpoint, see section 8.
- `ttazure.finish()` (`ttazure.js:246`) sends no end-of-stream and just waits 1000ms. Fine
  for a 15s practice line, likely to truncate the tail of a two-minute reading.
- Keep the streaming path on `fetch_azure_token()`, not `fetch_msspeech_token()` — the latter
  deliberately rewrites `chinaeast2` to `eastus` (`utils.php:1636`), which sends China traffic
  back out through the firewall.

### 3.9 Spot check should be feature-detected, not removed

The spec proposes removing the spot check option. Don't remove it globally — existing
iframe-recorded attempts still have timings, and a teacher may switch the transcriber after
students have already read.

Detect per attempt at render time in `passagehelper.php:257` (are the match audio points all
zero?) and hide the button for that attempt only. Decide from the attempt data, never from
the current activity setting.

### 3.10 Trust boundary

`submit_streaming_attempt` takes `awsresults` as `PARAM_RAW` from the client and it becomes
the grade. A student can POST a perfect transcript. This is the same trade-off MiniLesson
already makes, but the read step is the graded, higher-stakes one, so it deserves a conscious
decision rather than inheritance.

Separately: neither `submit_regular_attempt` nor `submit_streaming_attempt` calls
`self::validate_context($modulecontext)`. That is a pre-existing gap worth closing while in
the area.

### 3.11 Long recordings

- `maxtime` is hardcoded to 15 for practice (`renderer.php:393`). The read step needs
  `$moduleinstance->timelimit`, plus `allowearlyexit` semantics and a "no time limit" case —
  confirm `timer.init(0)` behaves.
- `ttaudiohelper` uses a deprecated `ScriptProcessorNode` plus an accumulating in-memory WAV
  encoder. A 5–10 minute reading is fine memory-wise, but ScriptProcessorNode is glitchy on
  long sessions and in background tabs.
- `enablesilencedetection` is correctly set false when streaming (`ttaudiohelper.js:130`) —
  otherwise a pause mid-passage would end the reading.

---

## 4. Mobile

**Correction to an earlier assumption: the mobile app does not need to "stay on the iframe",
because it has no recorder of its own.**

`classes/output/mobile.php` renders exactly one template, `mobile_view_page.mustache`, which
is a `core-iframe` pointing at `view.php?id=N&embed=2` with `allow="microphone; camera"`. The
app runs the same web code as a browser and inherits whatever the read step renders. There is
no mobile-vs-web recorder switch to make.

The baseline is better than expected: **practice mode already runs `ttrecorder` inside that
same mobile embed today**, so streaming, `getUserMedia` in a nested iframe, AudioContext and
the WebSocket are all already proven in the app WebView.

What remains genuinely mobile-specific is narrower, and is the same risk set as desktop only
sharper:

- multi-minute `ScriptProcessorNode` capture in a Cordova WebView
- backgrounding the app mid-reading — iOS suspends the AudioContext and drops the socket;
  the reconnect work in 3.1 covers this
- a several-MB WAV upload over mobile data

---

## 5. Suggested implementation order

1. **`ttstreamer.js`** — capture `payload.words`; rebase turn order and timestamps across
   socket reconnects; expose a structured final result alongside the plain text.
2. **`utils::parse_streaming_results()`** — rewrite for the AssemblyAI v3 shape. It currently
   parses the dead Amazon `Alternatives` / `Items` format (`utils.php:2032`). Target the
   structure `fetch_audio_points_json` and `fetch_duration_from_transcript_json` already
   consume: `results.items[]` with `alternatives[0].content`, `start_time`, `end_time`,
   `type: "pronunciation"`.
3. **Shared transcript massaging** — extract from `fetch_transcripts()`; call from
   `process_streaming_transcripts()` too.
4. **Renderer** — a `show_read_recorder()` plus a read-step ttrecorder template variant with
   `savemedia=1`, `forcestreaming=1`, `maxtime` from `timelimit`. Select it in `read.mustache`
   when `stricttranscribe` is on and a streaming token is available; otherwise keep the
   existing iframe block.
5. **`read.js`** — swap `recorderhelper` for `ttrecorder`; map `mediasaved` plus the final
   speech result onto `submit_streaming_attempt`; pass rectime.
6. **`submit_streaming_attempt`** — add `validate_context`, wire rectime, trigger
   `attempt_submitted` (the regular path does this, the streaming path does not), skip
   `register_aws_task`.
7. Feature-detect spot check; add the activity and site settings; bump `version.php`;
   rebuild AMD with `grunt amd --root=public/mod/readaloud`.

**Prototype first:** steps 1 and 2 only. The two riskiest unknowns are word-timing fidelity
from AssemblyAI v3 over a multi-minute stream, and reconnect behaviour. Both are cheap to
test in isolation and both invalidate the rest of the plan if they don't hold up.

## 6. Dead or inconsistent code to clean up in passing

- `utils::parse_streaming_results()` — parses the legacy Amazon shape; currently unreachable.
- `constants::TRANSCRIBER_AMAZONSTREAMING = 4 //defunct` and
  `definitions.js: transcriber_amazonstreaming: 4`.
- `submit_streaming_attempt` — registered but never called from JS.
- `create_update_attempt()` — accepts `$rectime` and drops it.
- Token-fetch gating is inconsistent: `show_practice()` uses `if ($isenglish)`
  (`renderer.php:414`) while `item_shortanswer` uses `if ($isenglish || true)`
  (`item_shortanswer.php:79`). Decide deliberately for the read step.
- `utils::can_streaming_transcribe()` (`utils.php:225`) whitelists only en-AU/GB/US, es-US,
  fr-FR/CA across six regions, and is not consulted by the current streaming path at all.

---

## 7. Prototype status (steps 1 and 2 done)

Implemented and validated on 2026-09-20.

**Changed files**

- `amd/src/ttstreamer.js` — word timing capture plus reconnect-safe bookkeeping
- `amd/src/ttaudiohelper.js` — `onfinalspeechcapture` signature carries word results
- `amd/src/ttrecorder.js` — `gotRecognition()` forwards timings as `message.speechresults`
  (`read.js:on_speech` already reads `eventdata.speechresults`, so the slot existed)
- `classes/utils.php` — `parse_streaming_results()` rewritten for the timed-word shape

**How it works**

`ttstreamer` keeps an audio clock (`audioseconds`), advanced from the PCM buffer length at
the fixed 16000 Hz streaming sample rate, so it tracks the recording rather than the socket.
Each new socket generation calls `rollgeneration()`, which parks the clock as `sessionoffset`
and pushes `turnbase` past the previous generation's turns. Word timings arrive relative to
session start and get `sessionoffset` added, giving times relative to the start of the
recording — which is what the audio file and the grading UI are indexed against.

**Verified**

- Round trip into the real consumers: `fetch_duration_from_transcript_json()` returned the
  true 62s rather than the hard-coded 60s guess, and `fetch_audio_points_json()` matched
  words to correct start/end times, including words recorded after a simulated token refresh.
- Reconnect scenario driven through the actual module: turns from before the refresh survive,
  turn numbering does not collide, timings stay monotonic across the boundary.
- **The reconnect bug in 3.1 is confirmed live in the committed code.** Running the same
  scenario against `HEAD`, a reading of "Oh lady the" followed by a token refresh and
  "moon rises" returns only `"moon rises"` — everything read before the refresh is silently
  discarded. It does not bite today only because practice recordings are capped at 15s.
- Moodle codechecker: no findings in the new `parse_streaming_results` (the reported lines
  fall in the pre-existing `sync_modelaudio_breaks` that follows it).
- eslint on `ttstreamer.js`: errors unchanged at 22, all pre-existing. The added code
  contributes style warnings of the kinds the file already carries throughout.
- AMD rebuilt via `grunt amd --root=public/mod/readaloud`.

**Known wrinkles**

- `start_time` / `end_time` are deliberately snake_case in the JS word objects so they match
  the upload transcriber's json shape that PHP already parses. eslint flags them as camelcase
  warnings; renaming would mean mapping the names in PHP instead.
- Both transcribers were confirmed returning word timings against live services on
  2026-09-20 (see section 8). What remains untested is the reconnect rebasing, which cannot
  fire in practice mode because recordings are capped at 15s there.

**Not yet done** — steps 3 to 7 in section 5. Nothing is wired into the read step yet; the
timings are produced and correctly consumed, but `read.js` still uses the iframe recorder, so
there is no behaviour change for users from this prototype alone.

---

## 8. Azure word timings

Done. `amd/src/ttazure.js` now produces the same timed-word output as the AssemblyAI path, so
both streaming transcribers feed `parse_streaming_results()` identically and the China route
is no longer second class.

### How the timings are requested

Two query parameters on the recognition socket:

```
&format=detailed
&wordLevelTimestamps=true
```

`format=detailed` is documented for this endpoint and moves results from `DisplayText` into
an `NBest` array. It does **not** by itself include word timings — the official REST docs show
`NBest` entries carrying only `Confidence`, `Lexical`, `ITN`, `MaskedITN` and `Display`, with
a `Words` array appearing only under pronunciation assessment.

The separate `wordLevelTimestamps` parameter is what adds `Words`. This was taken from the MS
Speech SDK source rather than guessed: `ConnectionFactoryBase.setCommonUrlParams()` maps
`PropertyId.SpeechServiceResponse_RequestWordLevelTimestamps` onto
`QueryParameterNames.EnableWordLevelTimestamps`, which is the literal string
`"wordLevelTimestamps"`.

### Response shape

Per `DetailedSpeechPhrase.ts` in the SDK, each `NBest` entry gains:

```
Words: [ { Word: "oh", Offset: 3200000, Duration: 2200000 }, ... ]
```

`Offset` and `Duration` are in **100-nanosecond ticks** (10,000,000 to the second), relative
to the start of the session — not the start of the recording.

### Reconnect handling

`ttazure.js` gets the same audio clock and generation offset as `ttstreamer.js`. This is not
over-engineering: the MS SDK does exactly the same thing internally, in
`DetailedSpeechPhrase.updateOffsets()`, which applies a per-connection `baseOffset` to
`Offset` and to every word's `Offset`. Two of the SDK's own open issues (#394, #564) are about
word-level timestamps going wrong under continuous recognition, which is the same class of bug.

### Deliberately not done: the speech.context route

The SDK also requests word timings through a `speech.context` message —
`SpeechContext.setWordLevelTimings()` sets `phraseOutput.format` to `Detailed` and pushes the
`WordTimings` option, giving:

```json
{"phraseOutput": {"format": "Detailed", "detailed": {"options": ["WordTimings"]}}}
```

I implemented this and then removed it. Reasons:

- The plugin's Azure client never sends `speech.config`, which the SDK always sends before
  `speech.context`. Sending a context message without it may be rejected.
- A rejected frame trips `socket.onerror`, which calls `doclosesocket()` — that would break
  recognition outright, turning a missing-timings problem into a no-transcription problem.
- It cannot be verified from here.

The query parameters are additive and cannot break an otherwise working connection, so they
are the safe first move. **If word timings do not appear in testing against a real Azure key,
the `speech.context` message above is the next thing to try** — and `speech.config` should
probably be sent first when doing so.

### Verified

**Confirmed live on 2026-09-20.** Exercised through practice mode with `alternatestreaming`
enabled, against both real services:

- Azure: `TT Azure Streamer final capture with 4 timed words`
- AssemblyAI: `sending final speech capture event with 3 timed words`

Word timings come back from both, so the query parameter route works and the `speech.context`
fallback discussed above is not needed.

Also driven through the module's real `onmessage` parsing with a fake socket:

- The URL is built with both parameters.
- Tick conversion is correct, and words land on the recording's timeline.
- Across a simulated token refresh the accumulated text survives, timings stay monotonic, and
  a word spoken at 1.44s into the second session is correctly reported at 61.44s.
- **Graceful degradation:** if the service ignores `format=detailed` and returns simple
  `DisplayText`, the parser falls back to it and returns the transcript with zero words rather
  than failing. Worst case is the behaviour we have today, never a regression.
- eslint on `ttazure.js`: errors 22 to 21 (one `console.debug` became `log.debug`); the added
  style warnings are the kinds the file already carries.

---

## 9. Read step wired up (steps 3 to 7)

Implemented 2026-09-20. Version bumped to 2026092000 (2.1.32), upgrade run, AMD rebuilt.

**Decision taken:** the local transcript path, per section 2. The browser posts the transcript
straight to Moodle and no new Cloud Poodll endpoint is involved. Reversible — it is one branch
in `submit_streaming_attempt`.

### How to turn it on

Two conditions, both required:

1. Site setting **"Stream the reading step"** (`streamingread`) — off by default, so nothing
   changes for existing sites until an admin opts in.
2. The activity's **passage transcriber set to Open STT** (`stricttranscribe`). Guided STT
   activities keep the iframe recorder, because guided transcription has no streaming
   equivalent — it steers the transcript towards the passage server side.

If either fails, or no streaming token can be fetched, `show_read_recorder()` returns empty and
the template falls back to the iframe. The fallback is a template branch, not a JS decision, so
there is no flash of the wrong recorder.

### What changed

| File | Change |
|---|---|
| `classes/aigrade.php` | New `clean_and_convert_transcript()`, shared by both transcript paths (fixes 3.2). Constructor and `process_streaming_transcripts()` take `$rectime`; `do_diff()` falls back to it instead of the hard-coded 60s (fixes 3.5) |
| `classes/external.php` | `submit_streaming_attempt` gains `validate_context` + `require_capability` (3.10), a `shadowing` param, the `attempt_submitted` event, and a fallback to the server side adhoc task when no usable transcript arrives |
| `classes/output/renderer.php` | New `can_stream_read()` and `show_read_recorder()`; `readstreaming` / `readrecorder` added to the template context and the AMD data |
| `classes/passagehelper.php` | New `has_audio_points()`; passes `canspotcheck` to the grading UI (3.9) |
| `classes/constants.php` | `M_READ_TTRECORDER` |
| `templates/read.mustache` | Branches between the streaming recorder and the iframe |
| `amd/src/read.js` | Streaming path: inits `ttrecorder`, collects the audio url and transcript, submits via `submit_streaming_attempt` |
| `amd/src/gradenowhelper.js` | Hides the spot check button when the attempt has no timings |
| `settings.php`, `lang/en/readaloud.php` | The `streamingread` setting and its strings |

### The two-signal submit

Worth knowing, because it is the fiddliest part. The iframe told us about a submission only
once the audio was safely uploaded. The streaming recorder does not: `mediasaved` fires as soon
as the upload is kicked off, and `speech` fires about a second after the recogniser closes its
socket. `read.js` therefore collects both and submits on whichever lands second.

There is also an 8 second backstop. `ttrecorder.gotRecognition()` swallows an empty transcript
and never fires a `speech` event, so a reading the recogniser heard nothing in would otherwise
leave the student stuck on the recording screen forever. The backstop submits with an empty
transcript, and the server then registers the adhoc task and transcribes the uploaded audio
server side — so a failed streaming recognition degrades to today's behaviour rather than
losing the attempt.

### Shadow step

Shadow mode reuses the listen template and has no recorder block, so it is unaffected by this
work in either direction. It is currently not available anyway. See
`shadow-step-current-state.md` for what was found, including two latent `letsshadow` bugs and
one design question specific to streaming (the model audio plays out loud into the microphone
while the recogniser is listening).

### Still outstanding

- **3.7, vocab biasing**, is inherent to streaming and not addressed.
- Reconnect rebasing is still only unit-tested; it needs a reading longer than the token
  lifetime to exercise for real. Now possible, since the read step uses the activity time limit
  rather than practice's 15 second cap.
- The dead-code cleanup in section 6 has not been done, beyond `parse_streaming_results`.

### What to test

1. **Regression first:** with `streamingread` off, confirm the read step still uses the iframe
   and behaves exactly as before. This is the path all existing sites stay on.
2. Turn `streamingread` on, set an activity to Open STT, and read a passage. Expect the in page
   recorder, and results immediately on finishing rather than after a poll.
3. Check WPM looks sane — that is the number that was broken without word timings.
4. On the teacher grading page, confirm spot check plays the right slice of audio.
5. A Guided STT activity should still show the iframe recorder.
6. Deliberately say nothing into the recorder, and confirm the 8 second backstop submits and
   the server side fallback picks it up.

---

## 10. Post-test fixes

Tested 2026-09-20. Confirmed working: no regression with streaming disabled, and the streaming
path works end to end with it enabled. Three issues came out of that test, all now fixed.

### Recorder layout was broken in the read step

The waveform and the record button sat about 200px apart. The ttrecorder styles were scoped to
the practice and quiz containers only (`scss/readaloud/_practice.scss`, `_quiz.scss`), and
`_read.scss` was an empty placeholder, so in the read step the canvas fell back to its intrinsic
300x150.

Worth knowing for any future reuse of this partial: the canvas element's width and height
attributes are never set in html. The drawing code works in a coordinate space of
`canvas.width() * 2` by `waveHeight * 2`, and the canvas is sized purely by css. So every
container that uses `mod_readaloud/ttrecorder` has to bring its own sizing or it will look
broken. Added the read step equivalent to `_read.scss`.

`styles.css` is generated — rebuilt with sass from `scss/styles.scss`. Note `styles.css.map` is
referenced by the compiled css but is **not** tracked in git, so do not commit one.

### 3.4 fixed: local playback while the cloud copy lands

`mediasaved` carries a `bloburl` alongside the optimistic cloud url. `read.js` now passes it
through `on_complete`, and `readreporthelper.show_local_audio()` renders the audio player
against the blob immediately. `check_for_audio()` swaps in the real url once it returns a 200,
exactly as before. Because the read report template may not be on the page yet when this is
called, it retries every 500ms for about 10 seconds before giving up.

So the student can play their reading back straight away, and the optimistic url is no longer
user visible. This is the approach MiniLesson PassageReading already uses.

### The 15 second wait before the first results check

`start_check_for_results()` hard coded a 15 second countdown before the first check, which makes
sense for the upload path (the audio has to reach the cloud and be transcribed) but is pure dead
time for a streaming attempt that was graded before the student ever reached the report.

It now takes an optional first wait, and `activitycontroller` passes 1 for streaming and 15 for
the upload path.

**There was also a race hiding behind this.** `do_streaming_submit()` called `on_complete()`
immediately after firing the submission ajax, not after it returned. With the old 15 second wait
that never mattered. With a 1 second wait the first check could beat the grade being written,
get `ready:false`, and then sit through the 10 second retry — the exact delay the change was
meant to remove. `on_complete()` now fires from the ajax callback, on both success and failure,
so the student is moved on either way rather than stranded on the recording screen.

### Countdown timer added (was: no visible countdown in the read step)

`ttrecorder.js` looks for `.timerstatus_<uniqueid>` to display the remaining time, but
`templates/ttrecorder.mustache` has no such element. The timer itself works — a reading stops
automatically when the activity time limit is reached — but the student cannot see the time
counting down.

Now fixed, with a progress bar and an mm:ss readout.

**Why it is not `progresstimer.js`.** readaloud has two existing patterns for this:

- `progressTimer` (`qi_speakinggapfill`, `qi_listeninggapfill`) is a jQuery plugin that runs its
  **own** `setInterval` against `new Date()`, started when you call it.
- `timerstatus` (`qi_freespeaking`, `qi_freewriting`) is driven by ttrecorder's own timer.

The read step needs the second. `progressTimer` starts counting the moment it is called, which
in those quiz items is when the item appears on screen. In the read step the student presses
record when they are ready, often well after the template renders, so a bar started on render
would already be part drained before they said a word. It would also be a second countdown that
could drift away from the one actually enforcing the limit.

So the bar is driven from `this.timer` inside ttrecorder, in a new
`update_timer_display()` called from the existing `handle_timer_update()`. The bar and the
enforcement cannot disagree, because they are the same timer.

**Kept additive.** The markup is gated behind `{{#showtimer}}` in `ttrecorder.mustache`, which
only `show_read_recorder()` sets. Practice and the speaking quiz items render exactly as before.
The legacy `.timerstatus_<uniqueid>` behaviour and `timer.fetch_display_time()` are untouched,
because `qi_freespeaking` depends on both; the mm:ss readout uses a new
`fetch_short_display_time()` and new class names.

Behaviour:

| Time limit | Display | Bar |
|---|---|---|
| Set | Counts down, mm:ss | Fills to 100%, turns red in the last 10 seconds |
| Not set (`timelimit` 0) | Counts up, mm:ss | Not rendered, since there is nothing to fill |

Verified the formatting and bar arithmetic for both cases, including that with no limit the
timer counts up and never auto-stops (the auto-stop guard requires `initseconds > 0`).

---

## 11. Second round of fixes

### Recorder layout, properly this time

My first two attempts at this were wrong, both because I was tuning canvas height and gaps when
the layout itself was the problem. The read step now mirrors `_practice.scss` exactly:

```scss
.mod_readaloud_ttrec_waveButtonContainer {
    position: relative;
    canvas.mod_readaloud_ttrec_waveForm { height: 46px; width: 100%; }
    button.mod_readaloud_ttrec_waveButton { font-size: 24px; position: absolute; }
}
```

**`position: absolute` on the button is the whole fix.** It takes the button out of flow so it
sits *over* the waveform instead of below it. The trace is drawn across the middle of the canvas
with half the box empty above and below it, so a button left in normal flow always looks
stranded a long way beneath the wave, no matter how the canvas is sized. The button is 46px
square (`button.control` in `_buttons.scss`) which matches the canvas height.

Anything else that reuses `mod_readaloud/ttrecorder` needs this same arrangement.

### Empty reading hung the report forever

Submitting silence left the student on the read report checking for a result that could never
arrive. Root cause was a wrong assumption in section 9's fallback.

`ttrecorder.js` configures its uploader with `transcribe = 0` (`ttrecorder.js:82`), so **the
cloud never transcribes audio uploaded by the streaming recorder**. The fallback added in
section 9 called `utils::register_aws_task()` whenever the browser sent no usable transcript,
which meant the adhoc task sat polling for a `.txt` and `.json` that would never be written,
retrying for 24 hours, while the student's report polled behind it indefinitely.

Fixed by treating an empty word list as what it actually is — a valid result meaning the
recogniser heard nothing:

- `utils::parse_streaming_results()` no longer returns false for an empty word list. It returns
  a normal transcript structure with an empty transcript and no items.
- `submit_streaming_attempt` no longer registers the adhoc task. Malformed input is substituted
  with the same empty structure.

The attempt is then graded through the ordinary path and scores zero, which is exactly what the
upload transcriber does when handed a silent recording, and the report resolves immediately.

**Known trade-off.** Because the cloud is not transcribing these uploads, there is no second
opinion. If streaming recognition fails for a technical reason on a reading the student actually
performed well, they get a zero rather than a server side transcript. Setting `transcribe = 1`
on the read step's uploader would restore that safety net at the cost of paying for
transcription on every reading, including the vast majority where streaming worked. Left as is;
worth a decision if failures turn out to be common.

### Polling can no longer run forever

Independently of the above, `check_for_results()` retried without limit. It now gives up after
60 checks (roughly 10 minutes) and shows `resultsnotready`, because whatever the cause, a
countdown that never ends is never the right answer.

`check_for_audio()` is still unbounded, but it increases its own wait by 500ms each time so it
backs off steeply, and with local blob playback the student has something to listen to
regardless. Left alone.

### Note on verification

There is no browser available in this session and no headless browser installed, so none of the
visual work here was checked by looking at it — it was reasoned from the css and the drawing
code, and confirmed by the person testing. That is worth knowing when reading the layout
reasoning above: the mechanism is real, but the first two attempts at applying it were wrong.
