<?php

$options = array(
    array(
        'name' => 'Sitio',
        'actions' => array(
            array(
                'name' => 'visitar', 
                'icon' => 'far fa-play-circle  fa-lg', 
                'url' => ''),
            array(
                'name' => 'administrar', 
                'icon' => 'fas fa-cogs fa-lg', 
                'url' => 'wp-admin/'),
            array(
                'name' => 'Personalizar', 
                'icon' => 'fas fa-edit fa-lg', 
                'url' => '/wp-admin/customize.php?return=%2Fwp%2Fwr%2Fwp-admin%2F'),

        )
    ),
    array(
        'name' => 'Plugins',
        'actions' => array(
            array(
                'name' => 'Ver', 
                'icon' => 'far fa-play-circle  fa-lg', 
                'url' => 'wp-admin/plugins.php'),
            array(
                'name' => 'Agregar', 
                'icon' => 'fas fa-plus-circle fa-lg', 
                'url' => 'wp-admin/plugin-install.php'),
            array(
                'name' => 'Actualizar', 
                'icon' => 'fas fa-sync fa-lg', 
                'url' => '__admin/pluginUpdate.php?dest='),
        )
    ),
    array(
        'name' => 'Páginas',
        'actions' => array(
            array(
                'name' => 'Ver', 
                'icon' => 'far fa-play-circle  fa-lg', 
                'url' => 'wp-admin/edit.php?post_type=page'),
            array(
                'name' => 'Agregar', 
                'icon' => 'fas fa-plus-circle fa-lg', 
                'url' => 'wp-admin/post-new.php?post_type=page'),
        )
    ),
    array(
        'name' => 'Productos',
        'actions' => array(
            array(
                'name' => 'Ver', 
                'icon' => 'far fa-play-circle  fa-lg', 
                'url' => 'wp-admin/edit.php?post_type=product'),
            array(
                'name' => 'Agregar', 
                'icon' => 'fas fa-plus-circle fa-lg', 
                'url' => 'wp-admin/post-new.php?post_type=product'),
        )
    ),
    array(
        'name' => 'Atributos de Prod.',
        'actions' => array(
            array(
                'name' => 'Editar', 
                'icon' => 'fas fa-cogs fa-lg', 
                'url' => 'wp-admin/edit.php?post_type=product&page=product_attributes'),
        )
    ),
    array(
        'name' => 'Categorias de Prod.',
        'actions' => array(
            array(
                'name' => 'Editar', 
                'icon' => 'fas fa-cogs fa-lg', 
                'url' => 'wp-admin/edit-tags.php?taxonomy=product_cat&post_type=product'),
        )
    ),

    array(
        'name' => 'Etiquetas de Prod.',
        'actions' => array(
            array(
                'name' => 'Editar', 
                'icon' => 'fas fa-cogs fa-lg', 
                'url' => 'wp-admin/edit-tags.php?taxonomy=product_tag&post_type=product'),
        )
    ),

    array(
        'name' => 'Menúes',
        'actions' => array(
            array(
                'name' => 'Editar', 
                'icon' => 'fas fa-cogs fa-lg', 
                'url' => 'wp-admin/nav-menus.php'),
        )
    ),

    array(
        'name' => 'Multimedia',
        'actions' => array(
            array(
                'name' => 'Ver', 
                'icon' => 'far fa-play-circle  fa-lg', 
                'url' => 'wp-admin/upload.php'),
            array(
                'name' => 'Agregar', 
                'icon' => 'fas fa-plus-circle fa-lg', 
                'url' => 'wp-admin/media-new.php'),
            array(
                'name' => 'Fom Server', 
                'icon' => 'fas fa-cogs fa-lg', 
                'url' => 'wp-admin/upload.php?page=add-from-server'),
        )
    ),

    array(
        'name' => 'Temas',
        'actions' => array(
            array(
                'name' => 'Editar', 
                'icon' => 'fas fa-cogs fa-lg', 
                'url' => 'wp-admin/themes.php'),
        )
    ),

);