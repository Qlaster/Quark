<?php

	$content = $APP->controller->run('admin/autoinclude', $APP);
	
	$name = $_GET['name'];	
	$content['nav']['path']['head'] = "Галерея: <b>$name</b>";
	
	
	//Загрузим галлерею
	$gallery = $APP->object->collection('gallery')->get($name);
	
	
	
	//Если нам передали несколько файликов на добавление
	if ($_FILES['files']) 
	{
		//переформатирование структуры файлов в более пригодный вид
		$_FILES['files'] = reArrayFiles($_FILES['files']);

		//~ print_r($_FILES); die;
		
		//Что бы создать директорию под галлерею, надо заменить служебные символы
		$name = str_replace('/', '_', $name);
		$name = str_replace('*', '_', $name);
		$name = str_replace('<', '_', $name);
		$name = str_replace('>', '_', $name);
		$name = str_replace('#', '_', $name);
		$name = str_replace('&', '_', $name);
		$name = str_replace('%', '_', $name);
		$name = str_replace('?', '_', $name);
		
		
		foreach	($_FILES['files'] as $_key => $_file)
		{
			
			$path = "public/gallery/$name/";
			if (!file_exists($path)) 
			{
				mkdir($path, 0777, true);
				chmod($path, 0777);
			}
			
			$new_filename = $path.$_file['name'];
			
			
			move_uploaded_file($_file['tmp_name'], $new_filename); //$_SERVER['CONTEXT_DOCUMENT_ROOT']
			chmod($new_filename, 0776);	
			
			$record['image']    = $new_filename;
			$record['selected'] = 'panel-primary has-success';
			$gallery['list'][]  = $record;
		}
	}


	//Если передали на сохранение
	if ($_POST)
	{
		$APP->object->collection('gallery')->set($name, $_POST);
		
		//Перезагрузим галлерею еще раз, что бы убедиться, что все сохранилось
		$gallery = $APP->object->collection('gallery')->get($name);
	}
	


	$content['gallery'] = $gallery;
	
	$APP->template->file('admin/constructor/gallery/gallery_edit.html')->display($content);
	
	
	
	
	
	
	
	
	
	
	/*
	 *	Меняет структуру $_FILES на более удобную 
	 * 
	 */
	function reArrayFiles(&$file_post) 
	{

		$file_ary = array();
		$file_count = count((array) $file_post['name']);
		$file_keys = array_keys($file_post);

		for ($i=0; $i<$file_count; $i++) 
		{
			foreach ($file_keys as $key) 
			{
				$file_ary[$i][$key] = $file_post[$key][$i];
			}
		}

		return $file_ary;
	}
