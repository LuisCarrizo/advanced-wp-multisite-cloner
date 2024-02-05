<?php 
$location  = 'home';
//agregado el 2024-01-14
if ( !defined('MAIN_FOLDER' ) ){
    define('MAIN_FOLDER' ,  'h:/web2/temp/') ;
}
if ( !defined('NETWORK_ROOT')){
    define('NETWORK_ROOT' ,dirname(__DIR__, 2) . '/');
}
//echo NETWORK_ROOT;