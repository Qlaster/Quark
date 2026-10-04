<?php


	//Статика сообщений — компаньон-ini [view] + перевод
	$cfg = $APP->config->get();
	$M   = $APP->l10n->translate(array_replace_recursive([], (array) $cfg['view']))['messages'];

	if ($_POST['config'])
	{
		//Запись только внутри разрешённых директорий
		$fn = $APP->files->jailPath($_POST['config']['filename'] ?? '');
		echo ($fn and file_put_contents($fn, $_POST['config']['body']) !== false) ? $M['installed']['text'] : exit($M['savefail']['text']);

		$config = $APP->config->get($fn);
		foreach ($config as $section => $objects)
			foreach ($objects as $name => $object)
			{
				echo "<br>📦 ".$section.'	🔖 '.$name.''; // ➤ ❱ ↦ ➤ › 🠺 🡂 🢧 ⮞ 🡆 💾 🔰 📦 🔶 📚 🔘
				//~ echo $section.'|'.$name.'<br>';
				$APP->object->collection($section)->set($name, $object);
			}
	}
