<?php



	if ($_POST['code'])
	{
		echo file_put_contents($_POST['code']['filename'], $_POST['code']['body']) ? 'Сохранение успешно' : 'Не удалось сохранить файл';
	}


	if ($_POST['config'])
	{
		echo file_put_contents($_POST['config']['filename'], $_POST['config']['body']) ? 'Сохранение успешно' : 'Не удалось сохранить файл';
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
