<?php 
$networks = array(
	'host' => array(
		// host
		array(
			'name'   => 'Wikired @host ',
			'url'    => 'https://wikired.com.ar/',
			'folder' => 'public_html/',
			'main'   => 'https://wikired.com.ar/'),
		array(
			'name'   => 'Demos @host',
			'url'    => 'https://demo.wikired.com.ar/',
			'folder' => 'wr.demo/',
			'main'   => 'https://demo.wikired.com.ar'),
		array(
			'name'   => 'CRP @host',
			'url'    => 'https://crp.wikired.com.ar/',
			'folder' => 'wr.crp/',
			'main'   => 'https://crp.wikired.com.ar'),
	),
	'home' => array(
		// localhost at home
		array(
			'name'   => 'Wikired @home',
			'url'    => 'http://wikired.net/',
			'folder' => 'wp_wr/',
			'main'   => 'http://wikired.net/'),
		array(
			'name'   => 'CRP @home',
			'url'    => 'http://crp.net/',
			'folder' => 'wp_crp/',
			'main'   => 'http://wikired.net/'),
	),
	'work' => array(
		// localhost at work
		array(
			'name'   => 'Wikired @work',
			'url'    => 'http://wikired.net/',
			'folder' => 'wpmain/',
			'main'   => 'http://wikired.net/'),
		array(
			'name'   => 'CRP @work',
			'url'    => 'http://crp.net/',
			'folder' => 'wpcrp/',
			'main'   => 'http://wikired.net/'),
		array(
			'name'   => 'Test @work',
			'url'    => 'http://wptest.net/',
			'folder' => 'wptest/',
			'main'   => 'http://wikired.net/'),
	),
);

$awpmsc = array(
	'host'	=> 'https://adminwp.wikired.com.ar/msc.php',
	'home'	=> 'http://crp.net/@dm1n/advanced-wp-multisite-cloner/msc.php',
	'work'	=> 'http://crp.net/@dm1n/advanced-wp-multisite-cloner/msc.php'
);