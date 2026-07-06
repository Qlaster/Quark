<?php



	if ($_POST['code'])
	{
		//Запись только внутри разрешённых директорий
		$fn = $APP->files->jailPath($_POST['code']['filename'] ?? '');
		echo ($fn and file_put_contents($fn, $_POST['code']['body']) !== false) ? 'Сохранение успешно' : 'Не удалось сохранить файл';
	}


	if ($_POST['config'])
	{
		$fn = $APP->files->jailPath($_POST['config']['filename'] ?? '');
		echo ($fn and file_put_contents($fn, $_POST['config']['body']) !== false) ? 'Сохранение успешно' : 'Не удалось сохранить файл';
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
