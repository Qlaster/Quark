<?php

    try
    {
        $name = trim($_GET['name']);
        if (!$name) throw new Exception('Не указан каталог');

        $APP->catalog->delete($name);
        //~ header('Location: admin/catalogs/config/');
        header("Location: ".$_SERVER['HTTP_REFERER']);
        exit;
    }
    catch (Exception $e)
    {
        echo 'Ошибка: ' . htmlspecialchars($e->getMessage());
    }
