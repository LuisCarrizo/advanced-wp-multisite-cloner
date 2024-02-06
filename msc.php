<?php 

require __DIR__ . '/location.php';
require __DIR__ . '/wrStartup.php';
require __DIR__ . '/wrDB.php';

//https://help.dreamhost.com/hc/en-us/articles/216363187-Password-protecting-your-site-with-an-htaccess-file


// lee la configuracion de las redes
require __DIR__ . '/networks.php';
$network = array();
$netOri = '';
$netDes = '<option value="0"  >OFFLINE</option>';
$id = 1;
foreach ($networks[$location ] as $vNet) {
	$name = $vNet['name'];
	$network[$name] = $vNet ;
	$network[$name]['id'] = $id;
	$option = '<option value="' . $id++ . '">'. $name . '</option>';
	$netOri .= $option;
	$netDes .= $option;
}

// obtiene la info de wp-config
$wpConfig = getWpConfig( $network , true);
if ( empty( $wpConfig['error']) ){
	$network = $wpConfig['network'];
	$siteMatrix = $wpConfig['siteMatrix'];
} 


// get files list
$fileList =  getFiles( MAIN_FOLDER . 'mscFiles' , 'zip'); 

?>

<!DOCTYPE html>
<html>
<head>
	<title>Adv.WP.Cloner</title>
	<link rel="icon" type="image/x-icon" href="/favicon.ico" />
	<meta charset="utf-8">
	<meta name="author" content="Wikired Argentina">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"	rel="stylesheet" type="text/css" >
</head>
<body>

	<article id="modalConfirm" style="display: none;"  >
		<h3 id="mcTitle" value="0" class="text-center">Please confirm process</h3>
		<div class="container">

			<div class="row py-3">
				<div class="col-2 offset-3">
				<button type="button" 
					class="btn btn-outline-danger btn-sm  fs14 " 
					id="mcCancel">Cancel</button>
				</div>
				<div class="col-2 offset-2">
				<button type="button" 
					class="btn btn-outline-success btn-sm  fs14 " 
					id="mcOk">OK</button>
				</div>
			</div>   

			<div class="row pt-2">
				<div class="col-3 text-end">
				<span class="lc">Action</span>
				</div>
				<div class="col-8 wrCol border border-primary">
					<span class="wrLabel fs12" id="mcAction">Action</span>
				</div>
			</div>

			<div class="row pt-1">
				<div class="col-3 text-end">
				<span >Network Destination</span>
				</div>
				<!-- class="text-start"  -->
				<div class="col-8 border border-primary">
					<span id="mcDesNetwork">Network Destination</span>
				</div>
			</div>

			<div class="row pt-1">
				<div class="col-3 text-end">
				<span >Site Destination New SubFolder</span>
				</div>
				<!-- class="text-start"  -->
				<div class="col-8 border border-primary">
					<span id="mcDesNewFolder">Site Destination New SubFolder</span>
				</div>
			</div>


			<div class="row pt-1">
				<div class="col-3 text-end">
				<span >Source Network</span>
				</div>
				<!-- class="text-start"  -->
				<div class="col-8 border border-primary">
					<span id="mcOriNetwork">Source Network</span>
				</div>
			</div>

			<div class="row pt-1">
				<div class="col-3 text-end">
				<span >Source Site</span>
				</div>
				<!-- class="text-start"  -->
				<div class="col-8 border border-primary">
					<span id="mcOriSite">Source Site</span>
				</div>
			</div>

			<div class="row pt-1">
				<div class="col-3 text-end">
				<span >Source File</span>
				</div>
				<!-- class="text-start"  -->
				<div class="col-8 border border-primary">
					<span id="mcOriFile">Source File</span>
				</div>
			</div>

			<div class="row pt-5">
				<div class="col-10  offset-1 ">
					<div class="alert alert-danger" role="alert">
					Warning! This operation can't be undone.<br>
					Please verify you have a good backup of the network!!!
					</div>
				</div>
			</div>

		</div> <!-- end container -->

	</article>


	<main class="container-fluid">
		<h1 class="text-center">Advanced WP Multisite Cloner </h1>
		<br>

		<form>
			<div class="row text-center mb-3" >
				<div class="col-3 wrCol offset-3 border d-grid gap-3 text-primary-emphasis bg-primary-subtle">
					<span class="wrLabel">SOURCE</span>
				</div>
				<div class="col-3 wrCol offset-1 border d-grid gap-3  text-primary-emphasis bg-primary-subtle">
					<span class="wrLabel ">DESTINATION</span>
				</div>
			</div>
			<div class="row  mb-3" >
				<div class="col-2 wrCol offset-1  text-primary-emphasis bg-primary-subtle">
					<span class="wrLabel">NETWORKS</span>
				</div>
				<div class="col-3 wrCol">
					<select class="form-select form-select-sm  font-weight-bold   fs12" 
						title="Source Network"
						id="netOri">
						<?php echo $netOri ; ?>
					</select>
				</div>
				<div class="col-3 wrCol  offset-1">
					<select class="form-select form-select-sm  font-weight-bold   fs12" 
						title="Destination Network"
						id="netDes">
						<?php echo $netDes ; ?>
					</select>
				</div>

			</div>
			<div class="row mb-3">
				<div class="col-2 wrCol offset-1  text-primary-emphasis bg-primary-subtle">
					<span class="wrLabel">SITES</span>
				</div>
				<div class="col-3 wrCol">
					<select class="form-select form-select-sm  font-weight-bold   fs12" 
						title="Source Site"
						id="siteOri">
					</select>
				</div>
				<div class="col-3 wrCol  offset-1">
					<input type="text" 
						class="form-control" 
						id="newSubFolder" 
						title="Destination Site New SubFolder (optative)"
						placeholder="(optative) new subfolder">
				</div>

			</div>
			<div class="row mb-3">
				<div class="col-2 wrCol offset-1 text-primary-emphasis bg-primary-subtle">
					<span class="wrLabel">FILE</span>
				</div>
				<div class="col-6 wrCol">
					<select class="form-select form-select-sm  font-weight-bold   fs12" 
						title="Files Available to import"
						id="fileList">
						<?php echo $fileList['options'] ; ?>
					</select>
				</div>
				<div class="col-1 wrCol">
					<button class="btn btn-link" 
						id="bRefresh"
						title="Refresh file list">
						<i class="fa-solid fa-arrows-rotate"></i>
					</button>
				</div>
			</div>

			<div class="row mt-4" >
				<div class="col-2 d-grid gap-2 offset-1 ">
					<!-- class="btn btn-outline-success btn-block   "  -->
					<button type="button" 
						class="btn btn-outline-success btn-block   " 
						id="bExport">Export</button>
				</div>
				<div class="col-2 d-grid gap-2  ">
					<button type="button" 
						class="btn btn-outline-warning btn-block   " 
						id="bClone">Export & Import</button>
				</div>
				<div class="col-2 d-grid gap-2  ">
					<button type="button" 
						class="btn btn-outline-danger btn-block   " 
						id="bImport">Import</button>
				</div>
				<div class="col-2 d-grid gap-2  ">
					<button type="button" 
						class="btn btn-outline-danger btn-block   " 
						title="Delete permantly the selected Site . &#013; CAUTION: this action can´t be undone"
						id="bDelete">Delete Site</button>
				</div>
			</div>
			</div>

			<div class="row mt-4" >
				<div class="col-10 d-grid gap-10 offset-1  ">
					<div class="alert alert-secondary d-none" 
					role="alert" 
					id="alerta">

						<div class="spinner-border text-primary" 
							id= "spinner"
							role="status">
							<span class="visually-hidden">Loading...</span>
						</div>
						<span id="mensaje">Process Result</span>
						<!-- data-bs-dismiss="alert"  -->
						<button type="button" class="btn-close position-absolute top-0 end-0 m-2" 
							aria-label="Close" id="closeAlert"></button>
					</div>
				</div>
			</div>

		</form>
	</main>
</body>
<footer class="js scripts">
	<script src="https://cdn.wikired.com.ar/jquery/jquery.min.js"></script>
	<script src="https://cdn.wikired.com.ar/underscore/underscore-min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">	</script>
	<script src="https://kit.fontawesome.com/51c486d49e.js" crossorigin="anonymous"></script>
	<script src="https://cdn.wikired.com.ar/utils/jquery.blockUI.js"></script>

	<?php 
		echo '<script  type="text/javascript"> ' . "\n";
		echo 'const siteMatrix = ' . json_encode( $siteMatrix )  . " ; \n" ;
		echo 'const networkData = ' . json_encode( $network )  . " ; \n" ;
		echo 'var fileList = ' . json_encode( $fileList )  . " ; \n" ;
		echo '</script> ' . "\n";
	?>

	<script  type="text/javascript">

		var modalAction = '';

		// Jquery document.ready
		$(function() {
			// define los eventos

			$('#netOri').change(function(e) {
				e.preventDefault();
				doPopulate('#siteOri', $(this).val());
			});
			$('#bRefresh').click(function(e) {
				e.preventDefault();
                doRefresh();
            });
			$('#bExport').click(function(e) {
				e.preventDefault();
                doAction('doModal','doExport');
            });
			$('#bImport').click(function(e) {
				e.preventDefault();
				doAction('doModal','doImport');
                
            });
			$('#bClone').click(function(e) {
				e.preventDefault();
				doAction('doModal','doClone');
                
            });
			$('#bDelete').click(function(e) {
				e.preventDefault();
				doAction('doModal','doDelete');
                
            });
			$('#closeAlert').click(function(e) {
				e.preventDefault();
                showResult();
            });
			// modal buttons
			$('#mcOk').click(function(e) {
				e.preventDefault();
				$.unblockUI();
                doAction(modalAction);
            });
			$('#mcCancel').click(function(e) {
				e.preventDefault();
				$.unblockUI();
				alertColor = 'warning';
                showResult( 'Action Cancelled' );	
            });			
			
			// inicializa las opciones de sitios
			doPopulate('#siteOri', $('#netOri').val());

		})

		// define el color default del alerta
		var alertColor = 'default';

		const delay = ms => new Promise(res => setTimeout(res, ms));

		async function doAction( $action = false , $modalAction = false){	
			try{
				if ( false == $action ){
					throw 'Missing Action ';
				}
				switch ($action) {
					case 'doModal':
						modalAction = $modalAction;
						// populate modal fields
						const fileID = $('#fileList option:selected').val() ;
						if ( fileID > 0 ) {
							fileFullName = fileList.files[fileID].fullName;
						} else {
							fileFullName = 'No file selected ';
						}
						let _newSubFolder =  '';
						if (  $('#newSubFolder').val() ) {
							_newSubFolder =  $('#newSubFolder').val() 
						}

						$('#mcAction').text($modalAction) ;
						$('#mcDesNetwork').text( $('#netDes option:selected').text() )  ;
						$('#mcOriNetwork').text( $('#netOri option:selected').text() ) ;
						$('#mcOriSite').text( $('#siteOri option:selected').text() ) ;
						$('#mcDesNewFolder').text( _newSubFolder ) ;
						$('#mcOriFile').text( fileFullName );

						// show modal dialog
						$.blockUI({
							message: $('#modalConfirm'),
							centerY: false,
							centerX: true,
							css: {
								width: '60vw',
								left: '20vw',
								height: '60vh',
								top: '60px',
								'text-align': 'left'
							}
						});
						break;

					case 'doExport':
						doExport();
						break;

					case 'doClone':
						doClone();
						break;

					case 'doImport':
						doImport();
						break;

					case 'doDelete':
						doDelete();
						break;
						
					default:
						throw 'Action Not Defined ';
						break;
				}

			} catch (error) {
				let finalMsg = 'Process doAction() :  ' + "\n <hr>";
				finalMsg += error.toString();
				alertColor = 'error';
				showResult( finalMsg );
			}	
		}

		function showResult( msg = false , waitMsg = 'processing...' ){
			let hide = false;
			if ( msg === false ){
				hide = true;
			} else if (msg == 'wait'){
				alertColor = 'default';
				$('#spinner').removeClass('d-none');
				$('#mensaje').html(waitMsg);
			} else {
				$('#spinner').addClass('d-none');
				$('#mensaje').html(msg);
			}
			// cambia el color del alert
			let newClass = 'alert '
			switch (alertColor) {
				case 'error':
					newClass += 'alert-danger'
					break;
				case 'ok':
					newClass += 'alert-success'
					break;
				case 'warning':
					newClass += 'alert-warning'
					break;
				case 'primary':
					newClass += 'alert-primary'
					break;
				default:
					newClass += 'alert-secondary';
					break;
			}
			$("#alerta").removeClass();
			$('#alerta').addClass(newClass);
			if ( hide ) {
				$('#alerta').addClass('d-none');
			} else {
				$('#alerta').removeClass('d-none');
			}

		}

		async function doPopulate($object, $selectedOption , $multiple = false){
			let foo = await populateSites($object, $selectedOption , $multiple );
		}

		async function doRefresh(){
			showResult( 'wait' , 'actualizando lista de archivos');
			await delay(1000);
			const options = {
        		method: 'POST',
        		headers: {'Content-Type': 'application/json' },
        		body: JSON.stringify({action:'getFiles'}),
        		cache: 'no-cache'
    		};
			const dataResponse = await fetch('./mscAjax.php', options);
    		const dataJson = await dataResponse.json();
			let finalMsg;
			
			if (dataJson.status != 'ok') {
        		finalMsg = 'Error upating file list: ' + "\n" + dataJson.msg;
				alertColor = 'error';
		    } else {
				// $('#fileList').list = dataJson.files.options;
				$('#fileList').html(dataJson.files.options);
				fileList = dataJson.files;			
				finalMsg = 'Files list updated OK';
				alertColor = 'ok';
			}
			showResult( finalMsg );	
		}

		async function doClone(){
			let finalMsg = 'Process Clone() :  ' + "\n <hr>";
			try {
				showResult( 'wait' , 'cloning...');
				// se validan los parametros
				if ( !$('#netOri option:selected').val() ){
					throw 'No Source Network selected ';
				}
				if ( !$('#siteOri option:selected').val() ){
					throw 'No Source Site selected ';
				}
				if ( !$('#netDes option:selected').val() ){
						throw 'No Destination Network selected ';
				}
				// prepara parametros
				const fileID = $('#fileList option:selected').val() ;
				if ( fileID != 0 ) {
					fileFullName = fileList.files[fileID].fullName;
				} else {
					fileFullName = false;
				}
				let _newSubFolder =  false;
				if (  $('#newSubFolder').val() ) {
					_newSubFolder =  $('#newSubFolder').val() 
				}

				const postParam = {
					action:'doClone',
					network: networkData,
					netOri : $('#netOri option:selected').val()    ,
					netDes : $('#netDes option:selected').val()    ,
					siteOri : $('#siteOri option:selected').val()    ,
					file : fileFullName   ,
					newSubFolder : _newSubFolder   ,
				};
				const options = {
					method: 'POST',
					headers: {'Content-Type': 'application/json' },
					body: JSON.stringify(postParam),
					cache: 'no-cache'
				};
				const dataResponse = await fetch('./mscAjax.php', options);
				const dataJson = await dataResponse.json();
				// let finalMsg;
				// console.info(dataJson);			
				if (dataJson.status != 'ok') {
					finalMsg += 'Error in process ' + "\n" + dataJson.msg;
					alertColor = 'error';
				} else {
					// $('#fileList').list = dataJson.files.options;
					// $('#fileList').html(dataJson.files.options);
					finalMsg += '';
					$i = 0
					_.each(dataJson.stat, function(element, index, list) {
						finalMsg += index + element + "\n <br>";
					});
					finalMsg += "\n <hr>";
					alertColor = 'ok';
				}
			} catch (error) {
				finalMsg += error.toString();
				alertColor = 'error';
			}
			showResult( finalMsg );	
		}

		async function doExport(){
			finalMsg = 'Process Export() :  ';
			try {
				showResult( 'wait' , 'exporting...');
				// se validan los parametros
				if ( !$('#netOri option:selected').val() ){
					throw 'No Source Network selected ';
				}
				if ( !$('#siteOri option:selected').val() ){
					throw 'No Source Site selected ';
				}
				// prepara parametros
				const fileID = $('#fileList option:selected').val() ;
				if ( fileID != 0 ) {
					fileFullName = fileList.files[fileID].fullName;
				} else {
					fileFullName = false;
				}
				const postParam = {
					action:'doExport',
					network: networkData,
					netOri : $('#netOri option:selected').val()    ,
					netDes : $('#netDes option:selected').val()    ,
					siteOri : $('#siteOri option:selected').val()    ,
					file : fileFullName   ,
				};
				const options = {
					method: 'POST',
					headers: {'Content-Type': 'application/json' },
					body: JSON.stringify(postParam),
					cache: 'no-cache'
				};
				const dataResponse = await fetch('./mscAjax.php', options);
				const dataJson = await dataResponse.json();
				let finalMsg;
				// console.info(dataJson);			
				if (dataJson.status != 'ok') {
					finalMsg = 'Error in process ' + "\n" + dataJson.msg;
					alertColor = 'error';
				} else {
					// $('#fileList').list = dataJson.files.options;
					// $('#fileList').html(dataJson.files.options);
					finalMsg = '';
					$i = 0
					_.each(dataJson.stat, function(element, index, list) {
						finalMsg += index + element + "\n <br>";
					});
					finalMsg += "\n <hr>";
					alertColor = 'ok';
				}
			} catch (error) {
				finalMsg += error.toString();
				alertColor = 'error';
			}
			showResult( finalMsg );	
		}

		async function doImport(){
			finalMsg = 'Process Import() :  ';
			try {
				showResult( 'wait', 'importing');
				// se validan los parametros
				const fileID = $('#fileList option:selected').val() ;
				if ( fileID != 0 ) {
					fileFullName = fileList.files[fileID].fullName;
				} else {
					throw 'No file selected ';
				}
				if ( !$('#netDes option:selected').val() ){
					throw 'No Destination Network selected ';
				}
				
				let _newSubFolder =  false;
				if (  $('#newSubFolder').val() ) {
					_newSubFolder =  $('#newSubFolder').val() 
				}

				// prepara parametros para ajax

				const postParam = {
					action:'doImport',
					network: networkData,
					netOri : $('#netOri option:selected').val()    ,
					netDes : $('#netDes option:selected').val()    ,
					siteOri : $('#siteOri option:selected').val()    ,
					file : fileFullName   ,
					newSubFolder : _newSubFolder   ,
				};

				const options = {
					method: 'POST',
					headers: {'Content-Type': 'application/json' },
					body: JSON.stringify(postParam),
					cache: 'no-cache'
				};
				const dataResponse = await fetch('./mscAjax.php', options);
				const dataJson = await dataResponse.json();
				// let finalMsg;
				// console.info(dataJson);			
				if (dataJson.status != 'ok') {
					finalMsg = 'Error en proceso ' + "\n" + dataJson.msg;
					alertColor = 'error';
				} else {
					// $('#fileList').list = dataJson.files.options;
					// $('#fileList').html(dataJson.files.options);
					finalMsg = '';
					$i = 0
					_.each(dataJson.stat, function(element, index, list) {
						finalMsg += index + element + "\n <br>";
					});
					finalMsg += "\n <hr>";
					alertColor = 'ok';
				}
			} catch (error) {
				finalMsg += error.toString();
				alertColor = 'error';
			}

			showResult( finalMsg );	
		}


		async function doDelete(){
			finalMsg = 'Process Delete Site  :  ';
			try {
				showResult( 'wait', 'deleting Site');
				// se validan los parametros
				const fileID = $('#fileList option:selected').val() ;
				if ( fileID != 0 ) {
					fileFullName = fileList.files[fileID].fullName;
				} else {
					fileFullName =  'void';
				}
				console.log( 'fileID ' + fileID);
				if ( !$('#siteOri option:selected').val() ){
					throw 'No Source Site selected ';
				}

				// prepara parametros para ajax

				const postParam = {
					action:'doDelete',
					network: networkData,
					netOri : $('#netOri option:selected').val()    ,
					netDes : $('#netDes option:selected').val()    ,
					siteOri : $('#siteOri option:selected').val()    ,
					file : fileFullName   ,
				};
				const options = {
					method: 'POST',
					headers: {'Content-Type': 'application/json' },
					body: JSON.stringify(postParam),
					cache: 'no-cache'
				};
				const dataResponse = await fetch('./mscAjax.php', options);
				const dataJson = await dataResponse.json();		
				if (dataJson.status != 'ok') {
					finalMsg = 'Error en proceso ' + "\n" + dataJson.msg;
					alertColor = 'error';
				} else {
					finalMsg = '';
					$i = 0
					_.each(dataJson.stat, function(element, index, list) {
						finalMsg += index + element + "\n <br>";
					});
					finalMsg += "\n <hr>";
					alertColor = 'ok';
				}
			} catch (error) {
				finalMsg += error.toString();
				alertColor = 'error';
			}
			showResult( finalMsg );	
		}

		// populate site options
		async function populateSites($object, $selectedOption , $multiple = false){
			if ( false === $multiple ){
				var html = '';
				var selected = ' selected';

				// para  agregar la opcion de TODOS
				// no está habilitado en esta lógica
				// html += '<option value="0" ';
				// html += ' ap="" ';
				// html += ' og="" ';
				// html += ' concepto="0" ';
				// html += selected + '>(plan) Todos</option>';
				// selected = '';
				_.each(siteMatrix, function(element, index, list) {
					if (0 == $selectedOption || element.netID == $selectedOption) {
						html += '<option value="' + index + '" ';
						html += selected + '>' + element.siteName + '</option>';
						selected = '';
					}
				});
				$($object).html(html);
			} else {
				/* no habilitado por ahora la opcion de TODOS los sites
				// arma los select multiples
				// si no se selecciono ningun concepto, entonces toma todos los planes
				if ( $selectedOption.length == 0 ){
					_.each( conData ,function(op, i) {
						$selectedOption.push(op.id);
					});            
				}
				// arma las opciones para el select de Plan
				let newData = [];
				// si se selecciono mas de un Concepto, se agrupan los planes por Concepto
				const doGroups =  ($selectedOption.length > 1) ? true : false;
				_.each($selectedOption, function(concepto, ind, lis) {
					let newGroup = {label: $('#con_d option[value="'+concepto+'"]').text(), children : [] };
					let newTemp  = [];
					_.each(planData, function(plan, index, list) {
						if (plan.concepto == concepto) {
							let newItem = {value: plan.id , label : plan.nombre};
							newTemp.push(newItem);
						}
					});

					if ( doGroups ){
						newGroup.children = newTemp;
						newData.push(newGroup);
					} else {
						newData = newTemp;
					}
				});

				$($object).multiselect('rebuild');
				$($object).multiselect('dataprovider', newData );
				*/
			}
			return true;
		}

		function _log($msg , $title = false , $time = false){
			if ($title){
				console.info( '*** ' + $title + ' ------------------------');
			}
			if ($time){
				const $ahora = new Date().getTime() / 1000;
				console.log( '*** ' + $ahora); 
			}
			if ( typeof $msg == 'boolean'){
				$msg = $msg.toString();
			}
			console.log($msg);    
		}

		function _debug($msg , $title = false , $time = false){
			_log($msg , $title  , $time);
		}
	</script>
</footer>
</html>

<?php 

// SOLO PARA DEBUG !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!


// _debug( $network['CRP']['sites'] , 'network');
// _debug( $siteMatrix , 'sitematrix');
//_debug( $network , 'network');

// $wpconfig = getWpConfig($network);

// _debug( $wpconfig , 'wpConfig');


// logWrite('network',$network);
// logWrite('siteMatrix',$siteMatrix);


