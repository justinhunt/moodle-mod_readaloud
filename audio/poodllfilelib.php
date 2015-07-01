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
	$datatype = $ah->optional_param('datatype', "");    // Type of action/data we are requesting
	$p1  = $ah->optional_param('p1', '');  // parameter 1 for gen use 
	$p2 = $ah->optional_param('p2', '');  // parameter 2 for gen use
	$p3 = $ah->optional_param('p3', '');  // parameter 3 for gen use
	$p4  = $ah->optional_param('p4', '');  // parameter 4 for gen use
	$hash  = $ah->optional_param('hash', '');  // file or dir hash
	$requestid  = $ah->optional_param('requestid', '');  // id of this request
	$filedata  = $ah->optional_param('filedata', '');  // the bytestream from direct upload recorders
	$fileext  = $ah->optional_param('fileext', '');  // the fileextension from direct upload recorders
	$filename  = $ah->optional_param('filename', '');  // the filename from remote upload recorders

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