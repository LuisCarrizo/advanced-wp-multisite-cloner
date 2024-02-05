<?php 
require __DIR__ . '/location.php';
require __DIR__ . '/wrStartup.php';
require __DIR__ . '/wrDB.php';
require __DIR__ . '/networks.php';
require __DIR__ . '/siteOptions.php';
?>

<!DOCTYPE html>
<html>
    <head>
        <title>Sitios WP</title>
        <link rel="icon" type="image/x-icon" href="/favicon.ico" />
        <meta charset="utf-8">
        <meta name="author" content="Wikired Argentina">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"	rel="stylesheet" type="text/css" >
        <!-- <link href="https://cdn.wikired.com.ar/font-awesome/v572/css/all.min.css"     			rel="stylesheet" type="text/css" > -->
    </head>
<body>

    <main class="container-fluid">
        <h1 class="text-center">My Wordpress Site Selector and Admin</h1>
        <hr>

        <nav >
            <div class="nav nav-tabs" id="nav-tab" role="tablist">
                <?php 
                $first = true;   
                foreach ($networks as $location => $val) {
                    echo '<button class="nav-link ';
                    if ($first ){
                        echo 'active';
                    }
                    echo '" id="nav-tab-' . $location . '" ';
                    echo 'data-bs-toggle="tab" data-bs-target="#nav-' . $location . '" ';
                    echo 'aria-controls="nav-' . $location . '" ';
                    echo 'type="button" role="tab" ';
                    echo 'aria-selected="';
                    if ($first ){
                        $first = false;
                        echo 'true';
                    } else {
                        echo 'false';
                    }
                    echo '" >' . $location . '</button>';
                    echo "\n";
                }
                ?>
            </div>
        </nav>
    
        <div class="tab-content" id="nav-tabContent">
        <?php 
                $first = true;   
                foreach ($networks as $location => $sites) {
                    echo '<div class="tab-pane fade  ';
                    if ($first ){
                        $first = false;
                        echo 'show active';
                    }
                    echo '" id="nav-' . $location . '" ';
                    echo 'role="tabpanel" aria-labelledby="nav-tab-' . $location . '" ';
                    echo 'aria-controls="nav-' . $location . '" ';
                    echo 'tabindex="0">';
                    // sites loop
                    echo '<table class="table table-hover">';
                    // thead loop
                    echo '<thead>';
                    echo '  <tr class="text-center">';
                    echo '    <th scope="col">Concept / Site<br></th>';
                    foreach ($sites as $site) {
                        echo '<th scope="col">' . $site['name'] . '</th>' . "\n";
                    }
                    echo '</tr>';
                    echo '</thead>';
                    // END thead loop
                    // main loop
                    foreach ($options as  $option) {
                        echo '<tr>' . "\n";
                        echo '<th scope="row"  class="text-center">' . $option['name'] . '</td>' . "\n";
                        foreach ($sites as $key => $site) {
                            echo '<td class="text-center">'. "\n";
                            foreach ($option['actions'] as  $action) {
                                if ( 'Plugins' == $option['name'] && 'Actualizar' == $action['name'] ){
                                    //$url = $action['url'] . $site['folder'] ;
                                    $url = $site['main'] . $action['url'] . $site['folder'] ;
                                } else {
                                    $url = $site['url'] . $action['url'] ;
                                }
                                echo '<a href="' .  $url . '" target="_blank" ';
                                echo ' title="' . $action['name'] . '" class="p-2" >' . "\n";
                                echo '<i class="' . $action['icon'] . ' "></i>'  . "\n";
                                echo '</a>'  . "\n";
                            }
                            echo '</td >'. "\n";
                        }
                        echo '</tr>' . "\n";
                    }
                    // END main loop





                    echo '</table>';
                    // END sites loop
                    echo '</div>' ;
                    echo "\n";
                }
        ?>
        </div>
    </main> 
</body>
<footer class="js_scripts">
    <script src="https://cdn.wikired.com.ar/jquery/jquery.min.js"></script>
	<script src="https://cdn.wikired.com.ar/underscore/underscore-min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">	</script>
	<script src="https://kit.fontawesome.com/51c486d49e.js" crossorigin="anonymous"></script>
	<script src="https://cdn.wikired.com.ar/utils/jquery.blockUI.js"></script>
</footer>



</html>