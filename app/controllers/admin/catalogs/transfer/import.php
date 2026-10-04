<?php

/*
 * import.php
 *
 * Импорт CSV файлов в каталог
 *
 *
 */


//Статика сообщений — компаньон-ini [view] + перевод
$cfg = $APP->config->get();
$M   = $APP->l10n->translate(array_replace_recursive([], (array) $cfg['view']))['messages'];

try
{

	if (!$_FILES['document']) throw new Exception($M['nofile']['text']);
	if ($_FILES['document']['error']) throw new Exception($M['uploadfail']['text']);

	if (!$APP->catalog->listing()[$_POST['catalog']]) throw new Exception($M['nocatalogexists']['text']);

	$options = ['delimiter'=>$_POST['delimiter'], 'quotes'=>$_POST['quotes']];

	$APP->catalog->items($_POST['catalog'])->import($_FILES['document']['tmp_name'], $options, $_POST['field']);

}
catch (Exception $e)
{
	http_response_code(400);
	echo $e->getMessage();
}


