Introduction to the PoodLL Audio SDK Sample Project
===================================================
The PoodLL MP3 Recorder records locally to MP3 and uploads it to the server when the student presses "stop." The recorder itself takes quite a few parameters. In this sample project you can see the parameters in the function "fetchMP3Recorder" in the file poodllfilelib.php.  

In this sample project, some of those parameters can be passed to the function fetchRecorder function. That function will display the MP3 recorder if the device is a regular PC, or an HTML5 upload form if the device is thought to be a mobile device. 

The main parameters that can be passed in are:
posturl : The url of the file which will recieve and process the ajax upload.
uploadcontrol: The DOM id of the control on the page which will be set with the filename of the uploaded file, once it has been processed.
callbackjs: The name of a function on the page which will be called after the file upload has completed successfully
p1:  An optional paramater that will be passed to the ajax script that processes the upload.
p2: An optional paramater that will be passed to the ajax script that processes the upload.
p3: An optional paramater that will be passed to the ajax script that processes the upload.
p4: An optional paramater that will be passed to the ajax script that processes the upload.

Other settings that are not exposed, but could be, are set with default values in the "fetchMP3Recorder" function. These include quality settings for the audio recorder. The user can override the quality settings tab on the recorder itself. The "autosubmit" parameter automatically sends recordings to the server when the user stops recording. If set to false a fairly unattractive submit button is shown, and the user will need to press that to submit the recording.

The recorder itself is embedded using the embed-compressed.js script. This is a part of the OpenLaszlo SDK we use to make the recorder. This only needs to be included once on the page, but will need to be included before the call to load the recorder is made. So loading it in the page header is probably  a good idea.

Using the Sample Project
==========================
To use the sample project, upload it your web server and configure the settings. The settings are: POODLLURL, SAVEDIR, FFMPEGPATH and CONVERTDIR. They should all be set in the file poodllfilelib.php. For POODLLURL, set the URL of the grec folder. For SAVEDIR, set the PATH of the folder on the server to which the audio files should be written. Make sure that folder is writeable by the webserver.
Set FFMPEGPATH to the path to FFMPEG.  If you do not wish to use FFMPEG, then set FFMPEGPATH as an empty string. The CONVERTDIR property is the directory into which FFMPEG should save the converted files. A sample configuration will look like this:
define('POODLLURL', 'http://m23.poodll.com/grec');
define('SAVEDIR','/var/www/moodle/23x/grec/out/');
define('FFMPEGPATH','ffmpeg');
define('CONVERTDIR','/var/www/moodle/23x/grec/out/');

Once the grec folder is in place and configuration complete, point your browser to grec/index.php. You should be able to record some audio, and the files will appear in the SAVEDIR. How you play them back and manage them is up to you of course.

In the sample project, the filename is created randomly on the server and passed back to the recorder on the page. The callbackjs  javascript function on the page is called and an HTML5 audio tag is created for that file. If you are using Chrome or Safari browser you will then be able play back the recording you just made.

HTML5 Uploading and FFMPEG
==========================
After HTML5 uploading, you will probably want to convert the raw files to MP3 files. That way there will be minimal branching logic later on when you go to playback the files, since they will all be MP3 files. The simplest way to do this is to use FFMPEG and convert them
This SDK is not really about FFMPEG so I will not cover it in too much detail. I will just explain how to install it on Ubuntu.

To install:
sudo apt-get install ffmpeg

Then you will need to install the codecs that enable mp3 encoding:
sudo apt-get install ubuntu-restricted-extras libmp3lame0

When we actually do the encoding we use a command like the following:
ffmpeg -i somefile.mov somefile.mp3

So after installing FFMPEG, try converting files on the command line using the above syntax. If it works you are ready to start converting with PoodLL too.

If no ffmpeg path is set at the top of the file, then it simply won't convert. If FFMPEG is set on the system path, then simply putting
'ffmpeg' as the path is enough.

See this page for more details:
http://ffmpeg.org/trac/ffmpeg/wiki/Using%20FFmpeg%20from%20PHP%20scripts
