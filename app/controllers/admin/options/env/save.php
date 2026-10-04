<?php



	//Статика сообщений — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$M   = $APP->l10n->translate(array_replace_recursive([], (array) $cfg['view']))['messages'];

	if ($_POST['code'])
	{
		//Запись только внутри разрешённых директорий
		$fn = $APP->files->jailPath($_POST['code']['filename'] ?? '');
		echo ($fn and file_put_contents($fn, $_POST['code']['body']) !== false) ? $M['saved']['text'] : $M['savefail']['text'];
	}


	if ($_POST['config'])
	{
		$fn = $APP->files->jailPath($_POST['config']['filename'] ?? '');
		echo ($fn and file_put_contents($fn, $_POST['config']['body']) !== false) ? $M['saved']['text'] : $M['savefail']['text'];
	}


	/*

	if ($_POST['config'])
	{
		echo file_put_contents($_POST['config']['filename'], $_POST['config']['body']) ? 'Установленные объекты: ' : exit('Не удалось сохранить файл');

		$config = $APP->config->get($_POST['config']['filename']);
		foreach ($config as $section => $objects)
			foreach ($objects as $name => $object)
			{
				echo "<br>📦 ".$section.'	🔖 '.$name.''; // ➤ ❱ ↦ ➤ › 🠺 🡂 🢧 ⮞ 🡆 💾 🔰 📦 🔶 📚 🔘
				//~ echo $section.'|'.$name.'<br>';
				$APP->object->collection($section)->set($name, $object);
			}
	}

	*/
