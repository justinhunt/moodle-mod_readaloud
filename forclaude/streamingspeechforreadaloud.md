#    Implement Streaming Speech on ReadAloud

    The Poodll activity Minilesson includes a PassageReading activity for students.
    This uses streaming audio recording and browser based speech recognition where it can.

    The Poodll ReadAloud activity (mod_readaloud) uses a similar streaming system for its line-by-line readings (Practice mode). But in its read mode where the student reads from start to end, it uses a recorder in an iframe loaded from cloud.poodll.com. 
    
## The Project
    In the read step, we want to add an option to replace that iframe recorder with our new streaming recorder when Open STT is selected as the transcriber.

    This will have a better user experience and be a lot simpler than the current system. However some users will be using Guided STT as the transcriber which does not stream. So we will need to support both iframe based recorders and our streaming recorders. 

    Currently the iframe based recording is submitted to the cloud where it is processed, first with file conversion and then with speech recognition. When the converted audio and transcript arrives in the cloud in the expected location, ReadAloud notices and commences its local processing (checking reading accuracy, etc). The transcript is two files [filename].vtt and [filename].txt. The "noticing" is done in classes aigrade.php
    
## Notes for the developer
    The VTT file is not currently produced by our streaming system, so any ReadAloud code that relies on it will fail. But that is just two places I believe:
1. the spot check function on the manual grading page I think, and if there is no VTT file then, we can just remove the option to "spot" check from that location.
2. the "noticing" of transcripts in classes aigrade.php. If we move the transcription process to the client, we need to ensure this "noticing" mechanism supports the lack or an empty .vtt file.

Regarding VTT it might also be possible to get a VTT file from the transcriber platform .. but I feel we do not actually need it.

    
    The audio file can be uploaded directly to the provided upload url in the same way it is done in MiniLesson PassageReading activity. That is to a presigned upload url which then puts it into the queue for processing. The transcript that arrives in JS could either be saved locally by the moodle server without passing to the cloud for storage. Or we could create a cloud poodll server endpoint to receive the transcript from the streaming recorder JS client. The benefit of the cloud poodll server endpoint is that the existing ReadAloud code that checks for audio and transcript (aigrade.php) will work mostly unchanged. Otherwise we will need to carefully  integrate support for local only transcripts
    
    I suggest we try the cloud poodll server endpoint step. But I am interested in your opinion too.
    
    For our China based users AssemblyAI is not a good option. Access over the firewall is poor. Our streaming code currently use Azure in that case. So this new system shoud support that too