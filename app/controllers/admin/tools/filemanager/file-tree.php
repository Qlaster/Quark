<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);



	//print_r($APP->utils->files->tree('/home'));

	//рисуем иерархию каталогов

	$home = $APP->url->home();
	$cwd  =  getcwd();

	$tree = $APP->files->tree($cwd);

	//~ print_r($tree); die;


	tree_node($tree, $cwd); die;






	function file_type($filename)
	{
		$path_info = pathinfo($filename);
		$ext = $path_info['extension'];
		return $ext;
	}

	function tree_node($node, $path=null)
	{
		echo "<ul>";

			//Сначала каталоги, потом файлы; внутри групп — по алфавиту
			$dirs  = array_filter($node, 'is_array');
			$files = array_diff_key($node, $dirs);
			uksort($dirs,  'strnatcasecmp');
			uksort($files, 'strnatcasecmp');

			foreach ($dirs + $files as $key => $name)
			{
				if (is_array($name))
				{
					echo "<li placeholder='$path/$key/' >$key";
					tree_node($name, "$path/$key");
					echo "</li>";
				}
				else
				{
					$ext = file_type($key);
					if ($ext == 'php')
					{
						echo "<li class=\"text-navy\" data-jstree='{\"type\":\"html\"}' placeholder='$path/$key' >$key</li>";
					}
					else
					{
						echo "<li data-jstree='{\"type\":\"html\"}' placeholder='$path/$key'>$key</li>";
					}
				}
			}


		echo "</ul>";
	}

?>
