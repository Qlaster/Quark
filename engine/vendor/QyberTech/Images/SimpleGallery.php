<?php

	namespace QyberTech\Images;

	class SimpleGallery
	{
		static function Albums($directory)
		{
			//Проверяем директорию на валидность
			if ( !is_dir($directory)) return false;
			//загружаем список директорий
			$dir_list = scandir($directory);
			//Убираем ссылки на другие каталоги
			unset($dir_list[0]);
			unset($dir_list[1]);

			//
			foreach ($dir_list as $node)
			{

				//Если это директория - создаем альбом для нее
				if (is_dir($directory.'/'.$node))
				{
					unset($album);
					//даем имя альбому
					$album['name'] = $node;
					$album['head'] = $node;
					//открываем и сканируем файлы внутри
					$album_list = scandir($directory.'/'.$node);
					//Убираем ссылки на другие каталоги
					unset($album_list[0]);
					unset($album_list[1]);


					foreach ($album_list as $anode)
					{
						if (! is_file($directory.'/'.$node.'/'.$anode)) continue;
						$photo['name'] = $anode;
						$photo['image'] = $directory.'/'.$node.'/'.$anode;
						$album['list'][] = $photo;
					}
					$gallery[$node] = $album;
				}

				//Если это файл
				if (is_file($directory.'/'.$node))
				{
					$photo['name'] = $node;
					$photo['image'] = $directory.'/'.$node;
					$gallery['list'][] = $photo;
				}
			}

			return $gallery ?? null;
		}
	}




