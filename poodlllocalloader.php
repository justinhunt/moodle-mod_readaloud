<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

require_once('../../config.php');
header("Pragma: no-cache");
header("Expires: -1");
header("Cache-Control: no-cache");
// Use server url from config.
$cloudpoodllurl = s(\mod_readaloud\utils::get_cloud_poodll_server());
?>
<!DOCTYPE html>
<html>
<head>
    <title>Poodll Recorder</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="<?php echo $cloudpoodllurl ?>/local/cpapi/cloudstyles.css?v=023">
    <link rel="stylesheet" type="text/css" href="<?php echo $cloudpoodllurl ?>/local/cpapi/assets/font-awesome/css/font-awesome.min.css?v=023">
    <script type="text/javascript">
        M={};
        M.cfg = {};
        M.cfg.wwwroot = "<?php echo $cloudpoodllurl ?>";
    </script>
    <script type="text/javascript" src="<?php echo $cloudpoodllurl ?>/local/cpapi/fastpoodllconfig.js?v=002"></script>
    <script type="text/javascript" src="<?php echo $cloudpoodllurl ?>/local/cpapi/lib/requirejs/require.js"></script>
    <script type="text/javascript" src="<?php echo $cloudpoodllurl ?>/local/cpapi/poodllloader.min.js?v=012"></script>

    <style type="text/css">
        /* BMR video recorder */
        #AUTOID, .iframe-bmr {
            max-width: 410px;
        }

        #AUTOID .poodll_mediarecorderbox_bmr {
            width: auto !important;
        }

        #AUTOID, .iframe-bmr, #settingsicon_filter_poodll_controlbar_AUTOID {
            bottom: 0px;
            right: 0px;
        }

        #AUTOID .poodll_mediarecorder_audio .poodll_mediarecorderbox_readaloud {
            border: 0 !important;
        }

        #AUTOID .poodll_mediarecorderbox_readaloud .poodll_start-recording_readaloud,
        #AUTOID .poodll_mediarecorderbox_readaloud .poodll_stop-recording_readaloud {
            background-color: purple !important;
            color: #FFF !important;
        }

        #AUTOID .poodll_mediarecorderbox_readaloud .poodll_status_readaloud {
            font-family: "Raleway" !important;
        }

        /*end*/
    </style>
</head>
<body>
<div id="AUTOID"></div>
</body>
</html>