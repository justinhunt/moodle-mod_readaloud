<?php
/**
* Functions to use with PoodLL Audio Recording SDK
* 
* @author Justin Hunt (@link http://www.poodll.com)
* @copyright 2013 onwards Justin Hunt http://www.poodll.com
* @license JustinsPlainEnglishLicense ( http://www.poodll.com/justinsplainenglishlicense.txt )
*
*/
	require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
	require_once(dirname(dirname(__FILE__)).'/lib.php');
	require_once(dirname(__FILE__).'/audiohelper.php');

	$ah = new audiohelper();	
	$datatype = optional_param('datatype', "", PARAM_RAW);    // Type of action/data we are requesting
	$p1  = optional_param('p1', '', PARAM_RAW);  // parameter 1 for gen use 
	$p2 = optional_param('p2', '', PARAM_RAW);  // parameter 2 for gen use
	$p3 = optional_param('p3', '', PARAM_RAW);  // parameter 3 for gen use
	$p4  = optional_param('p4', '', PARAM_RAW);  // parameter 4 for gen use
	$hash  = optional_param('hash', '', PARAM_RAW);  // file or dir hash
	$requestid  = optional_param('requestid', '', PARAM_RAW);  // id of this request
	$filedata  = optional_param('filedata', '', PARAM_RAW);  // the bytestream from direct upload recorders
	$fileext  = optional_param('fileext', '', PARAM_RAW);  // the fileextension from direct upload recorders
	$filename  = optional_param('filename', '', PARAM_RAW);  // the filename from remote upload recorders

	//check the sesskey, don't need any weirdness
	if(!confirm_sesskey($p1)){
	
	}
	
	switch($datatype){
		
		case "uploadfile":
			header("Content-type: text/xml");
			echo "<?xml version=\"1.0\"?>\n";
			if(confirm_sesskey($p1)){
				// p1 = sesskey, p2 = cmid
				$returnxml = $ah->uploadfile($filedata,$fileext, $requestid,$p1, $p2, $p3,$p4);
			}else{
				$returnxml = 'Error<error>Invalid Session Key</error>';
			}
			break;

		default:
			return;

	}

	echo $returnxml;
	return;