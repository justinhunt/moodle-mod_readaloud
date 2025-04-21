<?php
require_once('../../config.php');
header("Pragma: no-cache");
header("Expires: -1");
header("Cache-Control: no-cache");
// Get cloud poodll url from cp parameter to this page.
$cloudpoodllurl = optional_param('cloudpoodllurl', '', PARAM_URL);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Poodll Recorder</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" type="text/css" href="<?php echo $cloudpoodllurl ?>/local/cpapi/cloudstyles.css?v=023">
    <link rel="stylesheet" type="text/css" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
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

        /*end*/
    </style>
</head>
<body>
    <div id="AUTOID"></div>
</body>
</html>