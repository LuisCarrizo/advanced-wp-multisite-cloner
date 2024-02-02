<?php

// system wide constants

if ( !defined('_ff')){
	define('_ff' , '<br>' . PHP_EOL);
}
if ( !defined('_hr')){
	define('_hr' , '<hr>' . PHP_EOL);
}
if ( !defined('EOL')){
	define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');
}

if (!defined('ROOT')) {
	if ( defined ('ROOT_FOLDER') ){
		define('ROOT', ROOT_FOLDER);
	} else {
		// por defecto baja 2 carpetas, pero lo correcto es definirlo en el script originador
		define('ROOT', dirname(__DIR__, 2) . '/');
	}
}

if (!defined('APP_NAME')) {
	if ('cli' == PHP_SAPI) {
		define('APP_NAME' , '_default');
	} else {
		define('APP_NAME' ,  $_SERVER['HTTP_HOST'] );

	}
}
if (!defined('APP_ROOT')) {
		define('APP_ROOT', ROOT .  APP_NAME . '/');
}
if ( !function_exists('_folderExist') ){
	function _folderExist($folder = null , $create = false) {
		// If it exist, check if it's a directory
		if( file_exists($folder) ){
			if( is_dir($folder) ) {
				return $folder;
			} else {
				// exist but isn´t a folder
				return false;
			}
		} else {
			if ($create) {
				if (mkdir($folder , 0777, true)){
					return $folder;
				} else {
					return false;
				} 
			} else {
				return false;
			}
		}
	}
}

if ( !function_exists('_slash') ){
	function _slash($str , $action = 'add'){
		$errorLevel = error_reporting();
		error_reporting( 0 );
		try {
			if ( !is_string ($str) ){
				throw new Exception('invalid string ');
			}   
			if ( true === $action  || 'add' == $action) {
				if ( '/' != substr($str, -1) ) {
					$str .= '/';  
				}
			} else {
				if ( '/' == substr($str, -1) ) {
					$str = substr($str , 0 , strlen($str)-1); 
				}     
			}
		} catch (Exception $e) {
			error_log($e->getMessage() , 0);
		}
		error_reporting($errorLevel);
		return $str;
	}
}

if ( !defined('LOGS') ){
	define('LOGS'					, _slash(__DIR__ ) );
}

// logs folder and file
ini_set('error_log', LOGS . 'php_error.log');

// echo __FILE__ . ' - ' .__DIR__ . _ff;
// echo 'root: ' . ROOT . _ff;
// echo 'app name: ' .APP_NAME. _ff;
// echo 'app root: ' .APP_ROOT. _ff;
// echo 'dir: ' . __DIR__ . _ff;
// echo _hr;
// echo 'Yo Soy: ' . __FILE__ . ' - ' .__DIR__ . _ff;
