<?php
/*
$HeadURL$
$LastChangedRevision$
*/

	// Make sure we display all errors that occur during initialization
	error_reporting(E_ALL);
	ini_set("display_errors","1");

	// Use buffering to ensure bogus whitespace in config.php is ignored
	ob_start(NULL, 1024);
	$here = dirname(__FILE__);
	include './textpattern/config.php';
	ob_end_clean();

	if (!isset($txpcfg['txpath']) )	{ 
		header('Status: 503 Service Unavailable'); header('HTTP/1.0 503 Service Unavailable');
		exit('Please check config.php'); 
	}

	include $txpcfg['txpath'].'/publish.php';
	textpattern();
?>
