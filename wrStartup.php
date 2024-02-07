<?php
/*----------------------------------------------------------------------------------------
** File:        wrStartUp.php
** Created:     2018-08-08
** Author:      Luis Carrizo
** Homepage:    wikired.com.ar 
**------------------------------------------------------------------------------
** modificaciones
** 2023-11-25 se rehace para incorporar MZ
** ---------------------------------------------------------------------------*/

if ( !defined('_ff')){define('_ff' , '<br>' . PHP_EOL);}     // solo para debug



// 0. set SESSION on
try {
	$errorLevel = error_reporting();
	error_reporting(0);
	if ( function_exists('session_status') ){
		$sessionStatus = (session_status() == PHP_SESSION_NONE) ? false : true;	
	} else {
		$sessionStatus = ( empty(session_id()) ) ? false : true;
	}
	if ( $sessionStatus === false) {	
		session_start();
	}
} catch (Exception $e) {
	$msg = __FILE__ . $e->getMessage() . PHP_EOL;
} finally {
	error_reporting($errorLevel);
}

// 1. try catch
try {
	// 1. basic  settings and functions
	require __DIR__ . '/mscFunctions.php';

	// 2. basic  settings
	ini_set('max_execution_time', 1200);
	set_time_limit(1200);
	error_reporting(E_ALL);

	$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
	$caller = ($backtrace ) ? $backtrace[count($backtrace) -1]['file']  :'unkown';

	// echo 'Yo Soy: ' . __FILE__ . ' - ' .__DIR__ . _ff;

} catch (Exception $e) {
	$catchMsg = $e->getMessage();
	$logFolder = __DIR__ . '/';
	$phpLogFile = $logFolder . 'php_error.log';
	$appLogFile = $logFolder . 'app.log';
	ini_set('error_log', $phpLogFile);
	error_log($catchMsg , 0);
	error_log($catchMsg , 3 , $appLogFile);
}
	
// ****************************************************************************
// Specific  Use Functions

