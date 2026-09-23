# Plan: browser speech recognition in the read step

Written 2026-09-23. **Decision taken the same day: option A, implemented.** See section 13 of
`streamingspeechforreadaloud-review.md` for what was done and what it cost. The analysis below is
kept because it records why, and because the trade-off it describes is still live — if spot check
turns out to matter more than expected, option B is the way back.

## How MiniLesson does it

PassageReading does nothing special. It calls `ttrecorder.init(opts)` with only `uniqueid`,
`callback` and `stt_guided`, and lets `ttrecorder` pick the engine. The preference order in
`ttrecorder.js` is:

1. **Browser speech recognition**, if
   `browserRec.will_work_ok() && !stt_guided && !forcestreaming && !using_msspeech && !androidblocked`
2. **Streaming** (AssemblyAI or Azure), if a speech token is present and not guided
3. **upload_transcribe** — post the blob to the Poodll ASR url and wait for a transcript

`will_work_ok()` (`ttbrowserrec.js`) is a capability check only: the browser exposes
`SpeechRecognition` or `webkitSpeechRecognition`, and it is not Brave, and not iOS inside the
Moodle mobile app. **It does not check language.**

`androidblocked` is `is_android && savemedia`. On Android Chrome the web speech API is served by
the platform recogniser, which takes the microphone for itself; a concurrent `getUserMedia`
capture returns silence and also stops the recogniser hearing anything. So on Android, anything
that needs the audio saved has to skip browser rec.

Practice in mod_readaloud works the same way, which is why we have been getting browser rec there.

## Why the read step does not

`show_read_recorder()` sets `forcestreaming => true`, which disables branch 1 outright. That was
deliberate, for two reasons at the time:

- browser rec returns **no word timings**, and the read step needs audio positions
- on Android it cannot save the audio at all, and teacher grading needs the recording

## What has changed since

The first reason is weaker than it was. The absence of word timings now degrades gracefully
rather than breaking:

- wpm falls back to the recorded length (`rectime`) instead of a hard coded 60 seconds
- spot check is feature-detected per attempt and hidden when the transcript has no audio points

And audio is still saved under browser rec on desktop: `ttbrowserrec` runs its own encoder and
hands a blob to `onStop`, and `ttrecorder` uploads it **before** its `usebrowserrec` early return.
Only Android misses out, which `androidblocked` already handles.

So browser rec in the read step would work. The question is whether it should.

## The trade-off that actually matters

Practice and PassageReading score by **comparing text**. Word timings earn them nothing.

The read step also wants **audio positions**, so a teacher can click a misread word on the
grading page and hear just that moment. That only exists when the transcript carries word timings.

So turning browser rec on in the read step is not simply "another engine". On desktop Chrome —
the majority case — it would **remove spot check for most new attempts**, silently, because the
feature detection would correctly hide the button. That is a teacher-facing regression, and it
is the thing to decide before writing code.

## Options

**A. Mirror MiniLesson.** Drop `forcestreaming`, let `ttrecorder` prefer browser rec.

- Widest language coverage, simplest change, no streaming token or per-minute cost in the common case
- Loses spot check and precise wpm for most attempts
- Android still gets streaming, because `androidblocked` is set by `savemedia`

**B. Streaming first, browser rec only where streaming cannot go.** Keep `forcestreaming` when
`can_streaming_transcribe()` is true, and use browser rec for languages the streaming engine does
not cover.

- Keeps timings everywhere they are available today
- Widens in-page coverage to languages that currently fall back to the iframe
- More moving parts, and one unresolved problem below

**C. Make it a setting.** Site or activity level, "prefer browser recognition in the read step".

- Lets a site trade spot check for cost and coverage
- One more setting to explain, and the wrong default still hurts

**Recommendation: B**, because it is the only one that adds coverage without taking anything away.
A is worth it only if spot check turns out to be little used — which is worth checking before
assuming.

## The unresolved problem in B

The server picks the template branch, but browser capability is only knowable in the client. For a
language streaming cannot handle, the server would have to render the in-page recorder hoping
browser rec is available. If it is not, `ttrecorder` falls through to branch 3,
`upload_transcribe`, which posts to `utils::fetch_lang_server_url($region, 'transcribe')`.

That is not necessarily bad — Poodll's own ASR likely covers more languages than AssemblyAI
streaming, and it returns a transcript synchronously. But it is a third path with its own
behaviour, and it is **not** what was asked for when we chose the iframe as the fallback.

Three ways out, in increasing effort:

1. Accept `upload_transcribe` as the fallback-of-the-fallback and verify it behaves for the read
   step (does it return something `parse_streaming_results()` can consume? almost certainly not,
   it has no word timings and a different shape)
2. Probe capability client-side on the read step and ask the server for the right recorder before
   rendering
3. Render the iframe by default for those languages and let js swap in the in-page recorder when
   it detects browser rec — most complex, best result

## Suggested sequence

1. Decide whether losing spot check on desktop is acceptable — that single answer picks A or B
2. If B: confirm what `upload_transcribe` actually returns for a passage reading, since that
   decides which of the three ways out is needed
3. Implement behind the existing `streamingread` setting so it can be turned off wholesale
4. Test on desktop Chrome, desktop Firefox (no browser rec, should stream), and Android Chrome
   (should stream, because `savemedia` blocks browser rec)

## Related

Current language coverage and the gate that enforces it are described in
`streamingspeechforreadaloud-review.md`. In short: AssemblyAI streaming covers English plus
Spanish, French, German, Italian and Portuguese; Azure covers far more but only when the site has
its own working Azure key. Everything else falls back to the iframe recorder.
