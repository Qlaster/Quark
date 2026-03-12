<?php

/*
 * import.php
 *
 * Импорт CSV файлов в каталог
 *
 *
 */


try
{

	if (!$_FILES['document']) throw new Exception('Не передан файл');
	if ($_FILES['document']['error']) throw new Exception('Возникла ошибка загрузки');

	if (!$APP->catalog->listing()[$_POST['catalog']]) throw new Exception('Каталог не существует');

	$options = ['delimiter'=>$_POST['delimiter'], 'quotes'=>$_POST['quotes']];

	$APP->catalog->items($_POST['catalog'])->import($_FILES['document']['tmp_name'], $options, $_POST['field']);

}
catch (Exception $e)
{
	http_response_code(400);
	echo $e->getMessage();
}


