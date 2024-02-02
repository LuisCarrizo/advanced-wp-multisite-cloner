<?php 
require __DIR__ . '/location.php';
require __DIR__ . '/wrStartup.php';
require __DIR__ . '/wrDB.php';

$rv=array( );

try {
	//  set time elapsed
	$tIni = microtime(true);

	// parammeter management
	$pp = pageParams();
	if ( empty( $pp['action'] ) ){
		throw new Exception("Incomplete or missing parameters", 1);
	}
	if ( !empty($pp['direct']) ){
		// para el caso que se ejecute directamente el script
		error_reporting(E_ALL);
		echo '<html><body><h1>' . __FILE__ . _now() . '</h1><hr><pre>';
	} else {
		header('Content-Type: application/json; charset=utf-8');
	}

  	// core process
  	// 

	switch ($pp['action']) {
		case 'getFiles':
			$rv['files']  = getFiles( MAIN_FOLDER . 'mscFiles' , 'zip'); 
			break;
		case 'doExport':
			$foo = doExport($pp);
			if ( !empty( $foo['error']) ){
				throw new Exception( $foo['msg'], 1);
			} 
			$rv['stat'] = $foo['stat'];
			break;		
		case 'doImport':
			$foo = doImport($pp);
			if ( !empty( $foo['error']) ){
				throw new Exception( $foo['msg'], 1);
			} 
			$rv['stat'] = $foo['stat'];
			break;	
		case 'doDelete':
			$foo = doDelete($pp);
			if ( !empty( $foo['error']) ){
				throw new Exception( $foo['msg'], 1);
			} 
			$rv['stat'] = $foo['stat'];
			break;	
		case 'doClone':
			$foo = doClone($pp);
			if ( !empty( $foo['error']) ){
				throw new Exception( $foo['msg'], 1);
			} 
			$rv['stat'] = $foo['stat'];
			break;	
	
		default:
			throw new Exception("Action Not Available: " . $pp['action'], 1);
	}
  	// fin OK
	$rv['status'] = 'ok'; 
} catch (Exception $e) {
	$rv['msg'] =  $e->getMessage();
	$rv['data'] = array();
	$rv['pp'] = $pp; 
	$rv['status'] = 'error'; 
} finally {
	// end Script
	$rv['elapsed']  = sprintf('%.4f', (  microtime(true) -  $tIni)  ) . " segundos";
	if ( array_key_exists('stat' , $rv ) ){
		$rv['stat']['Tiempo Total: '] = $rv['elapsed'] ;
	}
	if ( !empty($pp['direct']) ){
		// TODO solo para el caso que se ejecute directamente
		var_export( $rv );
	} else {
		echo json_encode($rv);
	}

	exit;
}

function doClone( $pp ){
	$rv=array();
	try {
		// definiciones genericas
		$stat = array(
			'Clone Init Time: ' =>  _now()
		);
		
		// set isClone flag
		$isClone = true;

		$foo = doExport($pp , $isClone);
		if ( !empty( $foo['error']) ){
			throw new Exception( $foo['msg'], 1);
		} 
		if ( empty( $foo['zipFile']) ){
			throw new Exception( 'doExport() zipFile empty', 1);
		} 
		foreach ($foo['stat'] as $key => $value) {
			$key = '{export} ' . $key;
			$stat[$key] = $value;
		}

		$foo = doImport($pp , $isClone , $foo['zipFile']);
		if ( !empty( $foo['error']) ){
			throw new Exception( $foo['msg'], 1);
		} 
		foreach ($foo['stat'] as $key => $value) {
			$key = '{import} ' . $key;
			$stat[$key] = $value;
		}
		
	} catch (Exception $e) {
		$rv['msg'] = 'doClone() ' . $e->getMessage() ;
		$rv['error'] = true;
		logWrite( $rv['msg'] );
	} finally {
		$stat['Clone End Time: '] = _now();
		$rv['stat'] = $stat;
		return $rv;
	}
}

function doDelete( $pp  ){
	$rv=array();
	try {
		// definiciones genericas
		$stat = array(
			'Delete Init Time: ' => _now()
		);

		// determina el ID  del site origen
		$siteID = _after( "s" , $pp['siteOri']  );

		// determina el nombre de la red origen
		$siteData = array();
		foreach ($pp['network'] as $value) {
			if ( $pp['netOri'] == $value['id']) {
				$netOri = $value;
				$netOri['siteOriID'] = $siteID;
				$prefix = $netOri['table_prefix'];
				// determina el ID y nombre del site origen
				foreach ($value['sites' ] as $site) {
					if ( $siteID == $site['blog_id'] ){
						$siteData = $site;
						break;
					}
				}
				break;
			}
		}

		// obtiene datos de acceso a la base de datos
		$wpConfig = getWpConfig($pp['network']);
		if ( !empty($wpConfig['error']) ){
			throw new Exception('Error reading  wp-config ' . $wpConfig['msg']);
		}
		$wp = $wpConfig[$netOri['name']]['dbData'];

		// compone full prefix
		$fullPrefix = $wp['tp'] . $siteID . '_';
		$netOri['fullPrefix'] = $fullPrefix;

		// abre acceso a la bd
		$db = new wrDB($wp['host'], $wp['user'],$wp['pass'],$wp['name']);

		// trae la lista de tablas
		$query = "show  table STATUS  from " . $wp['name'] . " LIKE '" . $fullPrefix . "%';";
		$tables = $db->get_results( $query , 0);

		//--------------------------------------------
		// eliminar  tablas
		$counterTables = 0;
		$counterWithRows = 0;
		$dropScript = 'DROP TABLE ';
		foreach ($tables as $vTable) {
			// agrega el separador 
			if ( $counterTables > 0 ) {
				$dropScript .= ' , ';
			}
			// actualiza contador
			$counterTables ++;
			// agrega la tabla
			$dropScript .= '`' . $vTable['Name']  . '`';

			// actualiza estadistica de tablas con datos
			if ( $vTable['Rows'] > 0) {
				$counterWithRows++;
			}
		}
		// finaliza el script
		$dropScript .= ' ;';
		// ejecuta el script y actualiza estadisticas
		$stat['Tables To Drop: '] = $counterTables;
		$stat['Tables with records: '] = $counterWithRows;
		$result = $db->query( $dropScript ) ;
		$stat['Tables Dropped: '] = ($result) ? 'ok': ' Error';

		//--------------------------------------------
		// actualiza las tablas generales

		// *** BLOGS 
		$table =  $prefix . 'blogs';
		$where = array( 'blog_id' => $siteID );
		$result = $db->delete( $table, $where );
		if ( empty($result ) ){
			throw new Exception('Error updating table : ' . $table );
		} 
		$stat['update BLOGS: '] = 'ok';

		// *** SITEMETA
		$table1 =  $prefix . 'sitemeta';
		$table2 =  $prefix . 'blogs';
		$query = "update $table1 set meta_value = (SELECT count(blog_id) FROM $table2) where `meta_key` = 'blog_count' ;";
		$result = $db->query( $query );
		if ( empty($result ) ){
			throw new Exception('Error updating table : ' . $table1 );
		}
		$stat['update SITEMETA: '] = 'ok';

		// *** USERMETA
		$table =  $prefix . 'usermeta';
		$deleteQuery  = 'DELETE FROM ' . $table ;
		$deleteQuery .= " WHERE meta_key like '" . $prefix . $siteID . "_%' ;";
		$result = $db->query( $deleteQuery );
		if ( empty($result ) ){
			throw new Exception('Error updating table : ' . $table );
		}
		$stat['update USERMETA: '] = 'ok';

		// *** registration_log 
		$table =  $prefix . 'registration_log';
		$where = array( 'blog_id' => $siteID );
		$result = $db->delete( $table, $where );
		if ( empty($result ) ){
			throw new Exception('Error updating table : ' . $table );
		} 
		$stat['update registration_log: '] = 'ok';

		//--------------------------------------------
		// *** elimina los archivos 
		$folderSite = _slash($wp['netFolder']) . 'wp-content/uploads/sites/' . $siteID . '/';
		if ( _folderExist($folderSite , false ) ){
			// elimina carpeta temporal y todos sus archivos
			removeDirAndFiles($folderSite);
		}
		// verificacion final
		if ( _folderExist($folderSite , false ) ){
			$stat['Folder Site Deletion: '] = 'ERROR';
		} else {
			$stat['Folder Site Deletion: '] = 'ok';
		}	


		// *** FIN

	} catch (Exception $e) {
		$rv['msg'] = 'doImport() ' . $e->getMessage() ;
		$rv['error'] = true;
		logWrite( $rv['msg'] );
	} finally {
		$stat['FIN del proceso: '] = _now();
		$rv['stat'] = $stat;
		return $rv;
	}
}



function doExport( $pp , $isClone = false ) {
	$rv=array();
	try {
		// definiciones genericas
		$stat = array(
			'Export Init time: ' =>  _now()
		);

		// crear carpetas
		$mscFolder = _folderExist( MAIN_FOLDER . "mscFiles/" , true);
		if ( false === $mscFolder ){
			throw new Exception('Error accessing mscFiles folder: ' . $mscFolder);
		}
		$tempFolder = _folderExist( $mscFolder . 'temp/' , true);
		if ( false === $tempFolder ){
			throw new Exception('Error accessing temp folder: ' . $tempFolder);
		}
		$pid    = date("Ymd_") . time();
		// $pid = '20240105_180000';				// TODO for tests
		$folder = _folderExist( _slash( $tempFolder ) . $pid , true);
		if ( false === $folder ){
			throw new Exception('Error accessing process folder: ' . $folder);
		}

		// determina el ID  del site origen
		$siteID = _after( "s" , $pp['siteOri']  );

		// determina el nombre de la red origen
		$siteData = array();
		foreach ($pp['network'] as $value) {
			if ( $pp['netOri'] == $value['id']) {
				$netOri = $value;
				$netOri['siteOriID'] = $siteID;
				// determina el ID y nombre del site origen
				foreach ($value['sites' ] as $site) {
					if ( $siteID == $site['blog_id'] ){
						$siteData = $site;
						break;
					}
				}
				break;
			}
		}

		// obtiene datos de acceso a la base de datos
		$wpConfig = getWpConfig($pp['network']);
		if ( !empty($wpConfig['error']) ){
			throw new Exception('Error reading  wp-config ' . $wpConfig['msg']);
		}
		$wp = $wpConfig[$netOri['name']]['dbData'];

		$siteFolder =   _slash($wp['netFolder']) . 'wp-content/uploads/sites/' . $siteID . '/';

		// compone full prefix
		$fullPrefix = $wp['tp'] . $siteID . '_';
		$netOri['fullPrefix'] = $fullPrefix;

		// abre acceso a la bd
		$db = new wrDB($wp['host'], $wp['user'],$wp['pass'],$wp['name']);
			
		// trae la lista de tablas
		$query = "show  table STATUS  from " . $wp['name'] . " LIKE '" . $fullPrefix . "%';";
		$tables = $db->get_results( $query , 0);

		// crea el archivo de metadata
		$metaDataFile = _slash($folder) . 'mscMetaData.php';
		$metaData = "<?php " . "\n";
		// agregado el 2024-01-12 - se elimina los sites
		unset( $netOri['sites']);
		$metaData .= '$netOri = ' . var_export($netOri , true) . ";\n";
		// agregado el 2024-01-12 - la ruta completa de la carpeta del site
		$siteData['siteFolder'] = $siteFolder;
		$metaData .= '$siteOri = ' . var_export($siteData , true) . ";\n";

		$stat['Total Original Tables: '] =  count($tables);
		$stat['Processed tables: '] = 0;
		$stat['Tables with records: '] = 0;

		file_put_contents( $metaDataFile , $metaData);

		// loop sobre todas las tablas del sitio
		$create=array();
		$maxBytesPerfile = 5242880;
		foreach ($tables as $vTable) {
			// actualiza estadistica
			$stat['Processed tables: ']++;
			// genera el archivo de creacion de tablas 
			$tableName = $vTable['Name'];
			$query		= 'SHOW CREATE TABLE `' . $tableName . '` ;';
            $result		= $db->get_row( $query ) ;
			// le quita el prefijo al nombre de la tabla
			$tableName = str_replace($fullPrefix , '' , $result[0] );
			// guarda el script de creacion de la tabla
			$create[$tableName]	= str_replace($fullPrefix , '@#NEW-PREFIX#@' , $result[1]); 

			// genera el archivo con el insert de datos
			if ( $vTable['Rows'] > 0) {
				// actualiza estadistica
				$stat['Tables with records: ']++;
				// calcula las iteraciones para un tamaño de archivo definido en $maxBytesPerFile = 5 MB
				if ( $vTable['Data_length'] > $maxBytesPerfile ){
					$rowsPerFile = ceil($maxBytesPerfile /  $vTable['Avg_row_length'] );
					$iterations = ceil( $vTable['Rows'] / $rowsPerFile);
				} else {
					$iterations = 1;
					$rowsPerFile = $vTable['Rows'];
				}

				// compone la clausula order by
				$query = 'SHOW COLUMNS  FROM ' . $vTable['Name'] . " WHERE `Key`  = 'PRI' ;";
				$columns = $db->get_results( $query , 2) ;
				if ( count($columns) == 0 ) {
					$orderby = ' ';
				} else {
					$pk = [];
					foreach ($columns as $column) {
						$pk[] = $column[0];
					}
					$orderby = ' ORDER BY ' . implode(',' , $pk);
				}

				// file base name
				$inserFileBaseName = _slash($folder) . $tableName;
				// loop
				for ($i=0; $i < $iterations ; $i++) { 
					// datos genericos
					$index           = $i + 1;
					$fileName        = $inserFileBaseName . '_' . sprintf('%04d', $index) . '.sql';

					// compone el query de lectura y lee los registros a insertar
					$query  = 'SELECT * from `' . $vTable['Name'] . '` ';
					$query .= $orderby;
					if ( $iterations > 1){
						$query .= ' LIMIT ' . $rowsPerFile . ' OFFSET ' . $i * $rowsPerFile . ' ; ' ;
					}
					$query .= ' ;';

					// ejecuta la consulta
					$dataRows = $db->get_results( $query , 2) ;
					$strRows = [];
					// si la consulta está vacia, se saltea la tabla
					if ( empty( $dataRows) ){
						continue;
					}
					foreach ($dataRows as $dataRow) {
						$strRow = '(' . implode(',' ,  $db->escapeArray($dataRow) ) . ')';
						$strRows[] = $strRow;
					}

					unset( $dataRows);

					// crea el archivo
					// escribe la cabecera y descripcion
					$fh = fileCreate($fileName);
					if ( !empty( $fh['error'] ) ){
						throw new Exception('Error writing file: ' . $fileName . ' - ' . $fh['msg'] );
					} else {
						$h = $fh['h'];
					}
					// escribe la primera parte de la clausula INSERT
					$tableFixed = '@#NEW-PREFIX#@' . $tableName;
					$text =  'INSERT INTO `' . $tableFixed  . '` VALUES ' ;
					$foo = fileWrite($h, $text);
					if ( !empty( $foo['error'] ) ){
						throw new Exception('-EW001- Error writing file: ' . $fileName  );
					}

					// graba el archivo SQL con los datos de las filas
					$foo = fileWrite($h, implode(",\n", $strRows) . '; '  );

					// libera recursos
					fclose($h);
					unset( $strRows);

					// agregado el 2024-01-16 - opciones del sitio
					if ( 'options' == $tableName ){
						$q2 = 'SELECT * from  `' . $vTable['Name'] . '` ';
						$q2 .= "WHERE `option_name` = 'active_plugins' ;";
						$activePlugins = $db->get_results($q2 , 0);
						$siteActivePlugins = unserialize($activePlugins[0]['option_value']);
						$q2 = 'SELECT * from  `' . $vTable['Name'] . '` ';
						$q2 .= "WHERE `option_name` = 'template' ;";
						$siteTemplate = $db->get_results($q2 , 0);
						$q2 = 'SELECT * from  `' . $vTable['Name'] . '` ';
						$q2 .= "WHERE `option_name` = 'stylesheet' ;";
						$siteStylesheet = $db->get_results($q2 , 0);
						$metaData2  = '$siteStyleSheet = "' . $siteStylesheet[0]['option_value'] . '" ;' . "\n";
						$metaData2 .= '$siteTemplate = "' . $siteTemplate[0]['option_value'] . '" ;' . "\n";
						$metaData2 .= '$sitePluginsActive = ' .  var_export($siteActivePlugins , true). ' ;' . "\n";
						file_put_contents( $metaDataFile , $metaData2 , FILE_APPEND);
					}
				}
			}
		}
		// graba el archivo de creacion de tablas
		$createFile = _slash($folder) . 'mscCreate.php';
		$fileData = "<?php " . "\n";
		$fileData .= '$createTables = ' . var_export($create , true) . ";\n";
		file_put_contents( $createFile , $fileData);

		// --------------------------------------------
		// inicio proceso de zip de la carpeta wp-content/uploads
		$siteFolderData = folderSize( $siteFolder  );
		$stat['Uploads Folder: '] = str_replace("\\" , "/" , $siteFolder);
		$stat['Total Files: '] = $siteFolderData['files'] ;
		$stat['Total Bytes: '] = FileSizeConvert($siteFolderData['bytes'] );			

		// crea el archivo .zip con los uploads
		$zipFile = _slash( $folder ) . 'upload.zip';
		$zip = new \ZipArchive();
		$zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
		
		// loop files
		$folderNormalized = _slash(str_replace("\\" , "/" , $siteFolder));
		$folderNormalized = str_replace("//" , "/" , $folderNormalized);
		foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folderNormalized)) as $file){
			if( $file->isFile() && !$file->isDir() ){
				// Get real and relative path for current file
				$source = $file->getRealPath()  ;
				$source = str_replace("\\" , "/" , $source); 
				$source = str_replace("//" , "/" , $source);  
				$relativePath =  _after($folderNormalized, $source);  
				$zip->addFile($source, $relativePath);
			}
		}
		$zip->close();

		$zipSize = filesize($zipFile);
		$stat['Tamaño archivo zip de uploads: '] = FileSizeConvert($zipSize);	

		// ------------------------------------------------------
		// inicio zip final
		//
		// calcula tamaño de los archivos del proceso
		$siteFolderData = folderSize( $folder  );
		$stat['Carpeta Temporaria: '] = str_replace("\\" , "/" , $folder);
		$stat['Total de Archivos: '] = $siteFolderData['files'] ;
		$stat['Total de Bytes: '] = FileSizeConvert($siteFolderData['bytes'] );	

		// arhivo ZIP
		$zipFile  = _slash($mscFolder) ;
		$zipFile .= str_replace(" ","-",$netOri['name']) . '__' ;
		$zipFile .= str_replace(" ","-",$siteData['name']) . '__';
		$zipFile .= $pid . '.zip';

		// create zip file
		// 
		$zip = new \ZipArchive();
		$zip->open($zipFile, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
		
		// iterate files
		// $ii = 0;
		$folderNormalized = _slash(str_replace("\\" , "/" , $folder));
		foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder)) as $file){
			if( $file->isFile() && !$file->isDir() ){
				// Get real and relative path for current file
				$source = $file->getRealPath()  ;
				$source = str_replace("\\" , "/" , $source);         
				$relativePath =  _after($folderNormalized, $source); 
				// Add current file to archive
				$zip->addFile($source, $relativePath);
			}
		}
		$zip->close();
		$zipSize = filesize($zipFile);
		
		$stat['Tamaño archivo zip final: '] = FileSizeConvert($zipSize);	
		$stat['Archivo zip final: '] = $zipFile;
		
		// ----------------------------------------
		// elimina carpeta temporal y todos sus archivos
		removeDirAndFiles($folder);

		// --- agregado por doClone
		$rv['zipFile'] = $zipFile;

	} catch (Exception $e) {
		$rv['msg'] = 'doExport() ' . $e->getMessage() ;
		$rv['error'] = true;
		logWrite( $rv['msg'] );
	} finally {
		$stat['FIN del proceso: '] = _now();
		$rv['stat'] = $stat;
		return $rv;
	}
}

function doImport( $pp , $isClone = false , $cloneZipFile = false ){
	$rv=array();
	try {
		// definiciones genericas
		$stat = array(
			'Import Init Time: ' => _now()
		);

		if ( $isClone ){
			$pp['file'] = $cloneZipFile;
		}

		//--------------------------------------------
		// datos de la Red Destino
		foreach ($pp['network'] as $key => $value) {
			if ( $pp['netDes'] == $value['id'] ){
				$netDes = $value;
				// se crean variables independientes para facilitar el uso
				$prefix = $netDes['table_prefix'];
				$urlNet = $netDes['url'];
				break;
			} 
		}

		//--------------------------------------------
		// se conecta a la base de datos
		// obtiene datos de acceso a la base de datos
		$wpConfig = getWpConfig($pp['network']);
		if ( !empty($wpConfig['error']) ){
			throw new Exception('Error reading wp-config ' . $wpConfig['msg']);
		}
		$wp = $wpConfig[$netDes['name']]['dbData'];

		// conexion
		$db = new wrDB($wp['host'], $wp['user'],$wp['pass'],$wp['name']);

		//--------------------------------------------
		// crear carpeta temporal del proceso
		$mscFolder = _folderExist( MAIN_FOLDER . 'mscFiles/' , 'zip'); 
		if ( false === $mscFolder ){
			throw new Exception('Error accesing folder mscFiles ');
		}
		$tempFolder = _folderExist( $mscFolder . 'temp/' , true);
		if ( false === $tempFolder ){
			throw new Exception('Error  accesing temp folder  ');
		}
		$pid    = date("Ymd_") . time();
		// $pid = '20240105_180000';				// TODO solo para debug
		// $folder es la carpeta donde se descomprime el archivo .zip
		$folder = _folderExist( _slash( $tempFolder ) . $pid , true);
		if ( false === $folder ){
			throw new Exception('Error creating process folder ');
		}
		$stat['Temp folder: '] = $folder;

		//--------------------------------------------
		// extrae el archivo de metadata del proceso
		$stat['ZIP file: '] = $pp['file'];
		$zipFile = $pp['file'];
		$zip = new \ZipArchive();
		$res = $zip->open($zipFile, \ZipArchive::RDONLY );
		if ( true !== $res ){
			throw new Exception('Error opeing ZIP file: ' . $res );
		}
		$res = $zip->extractTo( $folder , 'mscMetaData.php');
		if ( false  === $res ){
			throw new Exception('Error extracting metaData file' );
		}
		$zip->close();
		require(_slash($folder) . 'mscMetaData.php' );
		// borra datos innecesarios (ver de no agrgarlos en doExport)
		if ( !empty( $netOri['sites'] ) ){
			unset( $netOri['sites']);
		}	

		// agregado el 2024-02-01 newSubFolder
		if ( empty($pp['newSubFolder']) ){
			$newSubFolder = $siteOri['path'];
		} else {
			$stat['donde 2: '] = 'aka';
			$newSubFolder = $pp['newSubFolder'];
		}

		// valida prefijo y subfijo = /
		if ( '/' != _left($newSubFolder , 1 ) ){
			$newSubFolder = '/' . $newSubFolder;
		}
		if ( '/' != _right($newSubFolder , 1 ) ){
			$newSubFolder .= '/' ;
		}
		$stat['newSubFolder: '] = $newSubFolder;

		//--------------------------------------------
		// *** creacion del sitio
		// valida que no exista un sitio en la carpeta destino seleccionada
		$table =  $prefix . 'blogs';
		$query = 'select * from ' . $table . " where path = '" . $newSubFolder . "'";
		$result	= $db->get_row( $query );
		if ( !empty($result) ){
			throw new Exception('There is already a site with those settings' );
		} else {
			// lee un registro para tomar como base
			$query = 'select * from ' . $table . ' LIMIT 1';
			$insertData	= $db->get_row( $query , 3);
			// se modifican los datos necesarios
			unset($insertData['blog_id']);
			$insertData['path'] = $newSubFolder;
			$insertData['registered'] = '@@NOW()';
			$insertData['last_updated'] = '@@CURRENT_TIMESTAMP';
			// se crea el registro en la tabla blogs
			$newSiteID = $db->insert( $table, $insertData, false , false , true);
			if ( false === $newSiteID ){
				throw new Exception('Error inserting table: ' . $table );
			}
		}
		$prefixNew = $prefix . $newSiteID . '_';

		// obtiene el nombre de las carpeta y URL destino
		// $urlSite= _slash($urlNet , false ) . $siteOri['path'];
		$urlSite= _slash($urlNet , false ) . $newSubFolder;
		$folderNet = _slash($wp['netFolder']);
		$folderSite = _slash($wp['netFolder']) . 'wp-content/uploads/sites/' . $newSiteID . '/';

		$urlNetOri = $netOri['url'];
		$folderSiteOri = $siteOri['siteFolder'] ;
		$prefixOri = $netOri['fullPrefix'];

		// *** actualiza tabla SITEMETA
		$table1 =  $prefix . 'sitemeta';
		$table2 =  $prefix . 'blogs';
		$query = "update $table1 set meta_value = (SELECT count(blog_id) FROM $table2) where `meta_key` = 'blog_count' ;";
		$result = $db->query( $query );
		if ( empty($result ) ){
			throw new Exception('Error updating table: ' . $table1 );
		}
		
		// *** actualiza tabla USERMETA
		// busco id y mail de los usuarios con rol de administrador en la red
		$table1 =  $prefix . 'usermeta';
		$table2 =  $prefix . 'users';
		$query = "SELECT a.user_id , u.user_email FROM $table1  a INNER JOIN $table2 u ON a.user_id = u.ID ";
		$query .= " where a.meta_key = '" . $prefix . "user_level' and a.meta_value = 10;";
		// ejecuta la consulta
		$result = $db->get_results( $query , 0) ;
		if ( empty($result ) ){
			throw new Exception('Error, admin users not found');
		}
		$adminUsers = array();
		foreach ($result as $k1 => $v1) {
			// $id = $v1
			$adminUsers[$v1['user_id']] = $v1['user_email'];
		}

		// busco registros a duplicar de la tabla usermeta
		$table1 =  $prefix . 'usermeta';
		$keys = array(
			'user_level',
			'capabilities',
			'dashboard_quick_press_last_post_id',
			'bfu_upgrade_notice_dismissed',
			'persisted_preferences',
			'user-settings',
			'user-settings-time',
		);

		$metaKeys = array_map(
			fn($key) => "'" . $prefix . $key . "'", $keys
		);

		$filterAdmins = ' (' . implode(', ', array_keys($adminUsers)) . ')';
		$filterMetaKeys = ' (' . implode(', ', $metaKeys) . ')';
		$query = "SELECT distinct meta_key ,meta_value FROM $table1 where ";
		$query .= ' user_id in ' . $filterAdmins ;
		$query .= ' AND meta_key  in ' . $filterMetaKeys ;
		// ejecuta la consulta
		$userMetadata = $db->get_results( $query , 0) ;
		if ( empty($userMetadata ) ){
			throw new Exception("Error, user's metadata not  found");
		}
		
		// prepara datos para insert_multi
		$imFields = array( 'user_id' , 'meta_key' , 'meta_value' );
		$imRecords = array();
		foreach ($adminUsers as $uk => $uu) {
			foreach ($userMetadata as $mm) {
				$imRecords[] = [
					$uk,
					str_replace($prefix,$prefixNew,$mm['meta_key']),
					$mm['meta_value'],
				];
			}
		}
		$table =  $prefix . 'usermeta';
		$pFilter = false ;
		$log = false ;
		$audit = false;
		$result = $db->insert_multi( $table, $imFields, $imRecords , $pFilter  , $log  , $audit);

		// *** actualiza tabla REGISTRATION_LOG
		// prepara datos para insert_multi
		$imFields = array( 'email' , 'ip' , 'blog_id' , 'date_registered');
		$imRecords = array();
		foreach ($adminUsers as $uk => $uu) {
			$imRecords[] = [
				$uu,
				$_SERVER['REMOTE_ADDR'],
				$newSiteID ,
				'@@NOW()'
			];
		}
		$table =  $prefix . 'registration_log';
		$pFilter = false ;
		$log = false ;
		$audit = false;
		$result = $db->insert_multi( $table, $imFields, $imRecords , $pFilter  , $log  , $audit);

		// *** inicio proceso de restaurar la base de datos
		// descomprimir zip completo
		$zipFile = $pp['file'];
		$zip = new \ZipArchive();
		$res = $zip->open($zipFile, \ZipArchive::RDONLY );
		if ( true !== $res ){
			throw new Exception('Error opening ZIP file: ' . $res );
		}
		$res = $zip->extractTo( $folder );
		if ( false  === $res ){
			throw new Exception('Error extracting ZIP' );
		}
		$zip->close();

		// *** loop de creacion tablas
		$ctResult = array();
		require(_slash($folder) . 'mscCreate.php' );
		foreach ($createTables as $kct => $vct) {
			$query = str_replace('@#NEW-PREFIX#@' , $prefixNew , $vct);
			if ( true === $db->query($query) ){
				$ctResult[$kct] = 'ok'; 
			} else {
				$ctResult[$kct] = 'error';
			}
		}
		$stat['Table creation: '] = var_export($ctResult , true) ; 

		// *** loop de insert datos de  tablas
		$sqlResult = array();
		$sqlFiles = glob( _slash($folder) . '*.sql');
		foreach ($sqlFiles as $sqlFile) {
			$tableName =  basename($sqlFile , '.sql') ;
			$tableNameShort =  _before_last('_', $tableName);
			$query = file_get_contents( $sqlFile );
			$query = str_replace( '@#NEW-PREFIX#@' , $prefixNew , $query);

			if ( in_array($tableNameShort , ['comments','posts' , 'options' , 'wc_product_download_directories']) ){
				$query = str_replace( $urlNetOri , $urlNet  , $query);
				if ( in_array($tableNameShort , ['wc_product_download_directories']) ){
					$fsOri = str_replace("\\" , "/" , $folderSiteOri);
					$fsNew = str_replace("\\" , "/" , $folderSite);
					$fsOri = str_replace("//" , "/" , $fsOri);
					$fsNew = str_replace("//" , "/" , $fsNew);
					$query = str_replace( $fsOri , $fsNew  , $query);
				}
				if ( in_array($tableNameShort , ['options']) ){
					$query = str_replace( $prefixOri , $prefixNew  , $query);
				}

			}
		
			if ( true === $db->query($query) ){
				$sqlResult[$tableName] = 'ok'; 
			} else {
				$sqlResult[$tableName] = 'error';
			}
		}
		$stat['Table Import: '] = var_export($sqlResult , true) ;

		// *** actualiza el mail de administrador del nuevo sitio (replica el del principal)
		$tableNew = $prefixNew . 'options';
		$tableMain = $prefix . 'options';
		$query = "update " . $tableNew ;
		$query .= " set option_value = (SELECT option_value from " . $tableMain;
		$query .= " WHERE option_name = 'admin_email') ";
		$query .= " WHERE option_name = 'admin_email' ; " ;

		// *** descarga archivos del sitio
		// crear carpeta en wp-content/uploads
		if ( false === _folderExist($folderSite , true ) ){
			throw new Exception('Error creating destination folder: ' . $folderSite );
		}
		// descromprime ZIP de uploads
		$zipFile = _slash($folder) . 'upload.zip';
		$zip = new \ZipArchive();
		$res = $zip->open($zipFile, \ZipArchive::RDONLY );
		if ( true !== $res ){
			throw new Exception('Error opening uploads ZIP file: ' . $res );
		}
		$res = $zip->extractTo( $folderSite );
		if ( false  === $res ){
			throw new Exception('Error extracting uploads ZIP ' );
		}
		$zip->close();

		// ----------------------------------------
		// elimina carpeta temporal y todos sus archivos
		removeDirAndFiles($folder);

	} catch (Exception $e) {
		$rv['msg'] = 'doImport() ' . $e->getMessage() ;
		$rv['error'] = true;
		logWrite( $rv['msg'] );
	} finally {
		$stat['END of process: '] = _now();
		$rv['stat'] = $stat;
		return $rv;
	}
}



// function doOptions( $array = false , $options = false , $view = false) {
//     global $session;
// 	$html = '';
// 	if ( false === $array || false === $options ){
// 		return '';
// 	}
// 	$selected = ' selected';
// 	if ( $options['all'] ){
// 		if ( $view ) {
// 			$html .= '<option value="0" selected>' . '(' . $view . ') Todos</option>'. "\n" ;
// 		} else {
// 			$html .= '<option value="0" selected>Todos</option>'. "\n" ;	
// 		}
		
// 		$selected = '';
// 	}

// 	foreach ($array as $key => $value) {
// 		if ( $view == 'dependencia') {
// 			$name = $value['nombre'];
// 			if ( $value['nombre'] != $value['titulo'] && !empty( trim($value['titulo'])) ){
// 				$name .= ' (' . $value['titulo'] . ' )';
// 			}
// 		} else {
// 			$name = ( true ===  $options['titulo'] && !empty($value['titulo']) ) ? $value['titulo'] : $value['nombre'];
// 		}
		
// 		if ( $view == 'equipo' and false === $options['all'] ){
// 		    $selected = '';
//             if ( !empty( $session['uid'] )  ){
//                 //$selected = ($value['id'] == $_SESSION['wr'][_appName]['uid'] ) ? ' selected' : ' ';
//                 $selected = ($value['id'] == $session['uid'] ) ? ' selected' : ' ';
//             }		
// 		}
// 		// modificado el 2021-01-13
// 		// if ( $view == 'dependencia') {
// 		// 	if ( $value['orden']  == 100 ){
// 		// 		$html .= '<optgroup label="Abiertos" id="dependenciaOG1" value="OG1">';
// 		// 	}
// 		// 	if ( $value['orden']  == 160 ){
// 		// 		$html .= '</optgroup>';
// 		// 		$html .= '<optgroup label="Cerrados">';
// 		// 	}
// 		// }
// 		if ( $view == 'dependencia') {
// 			// if ( $value['orden']  < 3 ){
// 			// 	$html .= '<optgroup label="Abiertos" id="dependenciaOG1" value="OG1">';
// 			// }
// 			if ( $value['orden']  == 100 ){
// 				$html .= '</optgroup>';
// 				$html .= '<optgroup label="Cerrados">';
// 			}
// 		}


// 		$html .= '<option value="' . $value['id'] . '" ' . $selected ;
// 		if ( $view == 'dependencia') {
// 			$html .= ' orden="' . $value['orden'] . '" ';
// 			if ( $value['orden']  > 99 && $session['rol'] < 7 ){
// 				$html .= ' disabled ';
// 			}
// 		}
// 		if ($options['extended'] === true ){
// 			$html .= ' ap="' . $value['ap'] . '" '; 
// 			$html .= ' og="' . $value['og'] . '" '; 
// 			$html .= ' concepto="' . $value['concepto'] . '" '; 
// 		}
// 		$html .= ' >' .  $name . '</option>' . "\n" ;
// 		if ($selected ){
// 			$selected = '';
// 		}
// 	}
// 	if ( $view == 'dependencia') {
// 		$html .= '</optgroup>';
// 	}

// 	return $html;
// }

function pageParams(){
	$rv = array();
	try {
		if ( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			if ( empty ($_POST) ){
				$rv = json_decode(file_get_contents('php://input'), true); 
			} else {
				$rv = $_POST;
			}
		} else if ( $_SERVER['REQUEST_METHOD'] == 'GET' && !empty ($_GET)) {
			$rv = $_GET;
			// if ( !empty($_GET['manual']) ){
			// 	// default values, just for tests
			// 	$rv['direct'] = true;
			// 	switch( $_GET['manual'] ){
			// 		case 'getFiles':
			// 			$rv['action']     = 'getFiles';
			// 			break;
			// 		case 'doClone':
			// 			$rv = array(
			// 				'action' => 'doImport',
			// 				'network' =>
			// 					array(
			// 						'Wikired' =>
			// 							array(
			// 								'name' => 'Wikired',
			// 								'url' => 'http://wikired.net/',
			// 								'folder' => '/wpmain/',
			// 								'id' => 1,
			// 								'table_prefix' => 'wp_main_',
			// 								'sites' =>
			// 									array(
			// 										'Wikired Demos' =>
			// 											array(
			// 												'blog_id' => '2',
			// 												'site_id' => '1',
			// 												'domain' => 'wikired.net',
			// 												'path' => '/demos/',
			// 												'registered' => '2024-01-02 17:39:06',
			// 												'last_updated' => '2024-01-02 17:39:07',
			// 												'public' => '1',
			// 												'archived' => '0',
			// 												'mature' => '0',
			// 												'spam' => '0',
			// 												'deleted' => '0',
			// 												'lang_id' => '0',
			// 												'name' => 'Wikired Demos',
			// 											),
			// 									),
			// 							),
			// 						'CRP' =>
			// 							array(
			// 								'name' => 'CRP',
			// 								'url' => 'http://crp.net/',
			// 								'folder' => '/wpcrp/',
			// 								'id' => 2,
			// 								'table_prefix' => 'crp_wp_',
			// 								'sites' =>
			// 									array(
			// 										'alta Manual' =>
			// 											array(
			// 												'blog_id' => '2',
			// 												'site_id' => '1',
			// 												'domain' => 'crp.net',
			// 												'path' => '/manual/',
			// 												'registered' => '2024-01-02 17:39:53',
			// 												'last_updated' => '2024-01-02 17:39:56',
			// 												'public' => '1',
			// 												'archived' => '0',
			// 												'mature' => '0',
			// 												'spam' => '0',
			// 												'deleted' => '0',
			// 												'lang_id' => '0',
			// 												'name' => 'alta Manual',
			// 											),
			// 										'Tienda Ropa' =>
			// 											array(
			// 												'blog_id' => '3',
			// 												'site_id' => '1',
			// 												'domain' => 'crp.net',
			// 												'path' => '/tienda01/',
			// 												'registered' => '2024-01-02 17:40:25',
			// 												'last_updated' => '2024-01-02 17:49:15',
			// 												'public' => '1',
			// 												'archived' => '0',
			// 												'mature' => '0',
			// 												'spam' => '0',
			// 												'deleted' => '0',
			// 												'lang_id' => '0',
			// 												'name' => 'Tienda Ropa',
			// 											),
			// 									),
			// 							),
			// 					),
			// 				'netOri' => '2',
			// 				'netDes' => '1',
			// 				'siteOri' => 'n2s3',
			// 				'file' => "false",
			// 			);
			// 			break;
			// 		case 'doExport':
			// 			$rv  = array(
			// 				'action' => 'doExport',
			// 				'network' =>
			// 					array(
			// 						'Wikired' =>
			// 							array(
			// 								'name' => 'Wikired',
			// 								'url' => 'http://wikired.net/',
			// 								'folder' => '/wpmain/',
			// 								'id' => 1,
			// 								'table_prefix' => 'wp_main_',
			// 								'sites' =>
			// 									array(
			// 										'Wikired Demos' =>
			// 											array(
			// 												'blog_id' => '2',
			// 												'site_id' => '1',
			// 												'domain' => 'wikired.net',
			// 												'path' => '/demos/',
			// 												'registered' => '2024-01-02 17:39:06',
			// 												'last_updated' => '2024-01-02 17:39:07',
			// 												'public' => '1',
			// 												'archived' => '0',
			// 												'mature' => '0',
			// 												'spam' => '0',
			// 												'deleted' => '0',
			// 												'lang_id' => '0',
			// 												'name' => 'Wikired Demos',
			// 											),
			// 									),
			// 							),
			// 						'CRP' =>
			// 							array(
			// 								'name' => 'CRP',
			// 								'url' => 'http://crp.net/',
			// 								'folder' => '/wpcrp/',
			// 								'id' => 2,
			// 								'table_prefix' => 'crp_wp_',
			// 								'sites' =>
			// 									array(
			// 										'alta Manual' =>
			// 											array(
			// 												'blog_id' => '2',
			// 												'site_id' => '1',
			// 												'domain' => 'crp.net',
			// 												'path' => '/manual/',
			// 												'registered' => '2024-01-02 17:39:53',
			// 												'last_updated' => '2024-01-02 17:39:56',
			// 												'public' => '1',
			// 												'archived' => '0',
			// 												'mature' => '0',
			// 												'spam' => '0',
			// 												'deleted' => '0',
			// 												'lang_id' => '0',
			// 												'name' => 'alta Manual',
			// 											),
			// 										'Tienda Ropa' =>
			// 											array(
			// 												'blog_id' => '3',
			// 												'site_id' => '1',
			// 												'domain' => 'crp.net',
			// 												'path' => '/tienda01/',
			// 												'registered' => '2024-01-02 17:40:25',
			// 												'last_updated' => '2024-01-02 17:49:15',
			// 												'public' => '1',
			// 												'archived' => '0',
			// 												'mature' => '0',
			// 												'spam' => '0',
			// 												'deleted' => '0',
			// 												'lang_id' => '0',
			// 												'name' => 'Tienda Ropa',
			// 											),
			// 									),
			// 							),
			// 					),
			// 				'netOri' => '2',
			// 				'netDes' => '0',
			// 				'siteOri' => 'n2s3',
			// 				'file' => false,
			// 			);
			// 			break;
			// 		case 'doImport':
			// 			$rv = array(
			// 				'action' => 'doImport',
			// 				'network' =>
			// 					array(
			// 						'Wikired' =>
			// 							array(
			// 								'name' => 'Wikired',
			// 								'url' => 'http://wikired.net/',
			// 								'folder' => '/wpmain/',
			// 								'id' => 1,
			// 								'table_prefix' => 'wp_main_',
			// 								'sites' =>
			// 									array(
			// 										'Wikired Demos' =>
			// 											array(
			// 												'blog_id' => '2',
			// 												'site_id' => '1',
			// 												'domain' => 'wikired.net',
			// 												'path' => '/demos/',
			// 												'registered' => '2024-01-02 17:39:06',
			// 												'last_updated' => '2024-01-02 17:39:07',
			// 												'public' => '1',
			// 												'archived' => '0',
			// 												'mature' => '0',
			// 												'spam' => '0',
			// 												'deleted' => '0',
			// 												'lang_id' => '0',
			// 												'name' => 'Wikired Demos',
			// 											),
			// 									),
			// 							),
			// 						'CRP' =>
			// 							array(
			// 								'name' => 'CRP',
			// 								'url' => 'http://crp.net/',
			// 								'folder' => '/wpcrp/',
			// 								'id' => 2,
			// 								'table_prefix' => 'crp_wp_',
			// 								'sites' =>
			// 									array(
			// 										'alta Manual' =>
			// 											array(
			// 												'blog_id' => '2',
			// 												'site_id' => '1',
			// 												'domain' => 'crp.net',
			// 												'path' => '/manual/',
			// 												'registered' => '2024-01-02 17:39:53',
			// 												'last_updated' => '2024-01-02 17:39:56',
			// 												'public' => '1',
			// 												'archived' => '0',
			// 												'mature' => '0',
			// 												'spam' => '0',
			// 												'deleted' => '0',
			// 												'lang_id' => '0',
			// 												'name' => 'alta Manual',
			// 											),
			// 										'Tienda Ropa' =>
			// 											array(
			// 												'blog_id' => '3',
			// 												'site_id' => '1',
			// 												'domain' => 'crp.net',
			// 												'path' => '/tienda01/',
			// 												'registered' => '2024-01-02 17:40:25',
			// 												'last_updated' => '2024-01-02 17:49:15',
			// 												'public' => '1',
			// 												'archived' => '0',
			// 												'mature' => '0',
			// 												'spam' => '0',
			// 												'deleted' => '0',
			// 												'lang_id' => '0',
			// 												'name' => 'Tienda Ropa',
			// 											),
			// 									),
			// 							),
			// 					),
			// 				'netOri' => '2',
			// 				'netDes' => '1',
			// 				'siteOri' => 'n2s3',
			// 				'file' => "C:/dev/web/wpcrp/@dm1n/mscFiles/CRP__Tienda-Ropa__hardcoded.zip",
			// 			);
			// 			break;
			// 	}
			// }
		}
	
		// validations
		if ( !is_array( $rv ) || empty( $rv) ){
			throw new Exception('Missing parammeters');
		} 
		if ( !array_key_exists('action', $rv) || empty($rv['action']) ){
			throw new Exception('Missing parammeter: action ');
		}

	} catch (Exception $e) {
		$rv['msg'] = 'pageParams() ' . $e->getMessage() ;
		$rv['error'] = true;
		logWrite( $rv['msg'] );
	} finally {
		return $rv;
	}
}

function fileCreate($fileName , $fileHeader = false, $fileDescription = false){
	// open / create file
	$rv = array();
	try {
		$h = fopen($fileName, 'w'); 
		if ( false === $h ) {
			throw new Exception('Error creating file: ' . $fileName);
		}
		// write header
		if ( $fileHeader) {
			$foo = fileWrite( $h , $fileHeader );
			if ( !empty( $foo['error']) ){
				throw new Exception('Error writing file header: ' . $fileName);
			}
		}
		// write description
		if ( $fileDescription) {
			$foo = fileWrite( $h , $fileDescription );
			if ( !empty( $foo['error']) ){
				throw new Exception('Error writing file description: ' . $fileName);
			}
		}		
		// return file handler
		$rv['h'] = $h;
	} catch (Exception $e) {
		$rv['msg'] = 'fileCreate() ' . $e->getMessage() ;
		$rv['error'] = true;
	} finally {
		return $rv;
	}

	
}

function fileWrite($h , $text ){
	$rv = array();
	try {
		$eol = "\r\n";
		$bytes = fwrite($h, $text . $eol );
		if ( false === $bytes ){
			throw new Exception('Error writing to file.');
		}
		$rv['bytes '] = $bytes;
	} catch (Exception $e) {
		$rv['msg'] = 'fileWrite() ' . $e->getMessage() ;
		$rv['error'] = true;
	} finally {
		return $rv;
	}
}

function folderSize( $dir){
	$rv = array(
		'bytes' => 0,
		'files' => 0
	);
	try {
		foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file){
			$rv['bytes'] += $file->getSize();
			$rv['files']++;
		}
	} catch (Exception $e) {
		$rv['msg'] = 'folderSize() ' . $e->getMessage() ;
		$rv['error'] = true;
	} finally {
		return $rv;
	}
}

function removeDirAndFiles($dir) {
	if (is_dir($dir)) {
		$objects = scandir($dir);
		foreach ($objects as $object) {
			if ($object != "." && $object != "..") {
				if (filetype($dir."/".$object) == "dir") 
				removeDirAndFiles($dir."/".$object); 
				else unlink   ($dir."/".$object);
			}
		}
		reset($objects);
		rmdir($dir);
	}
}