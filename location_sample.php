<?php 
$location  = 'work';
//agregado el 2024-01-14
if ( !defined('MAIN_FOLDER' ) ){
    define('MAIN_FOLDER' ,  'c:/dev/web2/__temp/') ;
}
if ( !defined('NETWORK_ROOT')){
    define('NETWORK_ROOT' ,dirname(__DIR__, 3) . '/');
}
if ( $_SERVER['REQUEST_METHOD'] == 'GET' && !empty ($_GET) && array_key_exists('show' , $_GET)) {
    echo 'Main Folder = ' . MAIN_FOLDER . '  /  Network Root = ' . NETWORK_ROOT;
    die;
}