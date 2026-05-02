<?php
    error_reporting(E_ALL & ~E_NOTICE);

    if (!$_SERVER['HTTP_REFERER'])
        $_SERVER['HTTP_REFERER'] = 'admin/content/objects/timeline?collection='.$_GET['collection'].'&name='.$_GET['name'];

    $APP->objects->timeline->restore([
        'collection' => $_GET['collection'],
        'name'       => $_GET['name'],
        'date'       => $_GET['date']
    ]);

    header("Location: ".$_SERVER['HTTP_REFERER']);
