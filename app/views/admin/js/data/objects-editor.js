	function object_test_create()
	{
		var object = [];

		for (var i = 0; i < 10; i++)
		{

			object['tag'+i+i] = Math.random();

			//~ object['tag'+i+i] = new Object;
			//~ object['tag'+i+i].tag ='tag'+i;
			//~ object['tag'+i+i].value = Math.random();
		}
		//~ console.log(object);
		return object;
	}

	function object_test_tree_create()
	{
		var object = [];

		object['head'] = 'Корень';
		object['info'] = 'Информация корня';
		object['list'] = [];

		object['list'][0] = [];
		object['list'][0]['head'] = 'Заголовок подъэлемента1';
		object['list'][0]['info'] = 'Информация подъэлемента1';


		object['list'][1] = [];
		object['list'][1]['head'] = 'Заголовок подъэлемента2';
		object['list'][1]['info'] = 'Информация подъэлемента2';

		object['list']['lol'] = [];
		object['list']['lol']['head'] = 'Заголовок подъэлемента lol';
		object['list']['lol']['info'] = 'Информация подъэлемента lol';

		object['list']['lol']['list'] = [];

		object['list']['lol']['list'][1] = [];
		object['list']['lol']['list'][1]['head'] = 'Информация подъэлемента lol > 1';
		object['list']['lol']['list'][2] = [];
		object['list']['lol']['list'][2]['head'] = 'Информация подъэлемента lol > 2';

		return object;
	}




	//Добавить свойство к объекту
	//~ function objects_add_element(object, tag, value)
	//~ {
		//~ if (object == undefined) object = new Object;

		//~ item = new Object;
		//~ item.tag = tag;
		//~ item.value = value;

		//~ object.push(item);
		//~ return object;
	//~ }

	function objects_tree_clear(html_div_id)
	{
		while (html_div_id.firstChild)
				html_div_id.removeChild(html_div_id.firstChild);
	}

    function objects_tree_add_item_blank()
	{
		var element = [];
		element.head = 'Новый элемент';
		element.link = '';

		var html_div_id = document.getElementById('nestable');
		html_div_id = html_div_id.childNodes[0].childNodes[0];

		var li = objects_tree_add_item(html_div_id, element);
		html_div_id.appendChild(li);
	}

	function objects_tree_add_item(html_div_id, element, item=undefined)
	{
		var li = document.createElement('li');
		li.classList.add('dd-item');

		//Объявляем новое поле с данными у li (в котором будет храниться информация о свойствах)
		li.treedata = [];
		li.treename = item;
		//Прикрепляем
		li.treedata = element;


		//==================================================
		//Рисуем кнопочки и описываем их события
		//====================================================
		//Кнопочка редактирования
		var div_handle = document.createElement('div');
		$(div_handle).addClass('infobtn btn btn-circle btn-outline btn-primary pull-right fa fa-pencil');
		div_handle.onclick = function()
							{
								//~ console.log(this.parentNode);
								//Рисуем свойства
								objects_table_paint('object_info', element);
								//Даем ссылку на объект
								window.currentNode = element;
								//Даем ссылку на элемент дерева
								window.currentDOM = $(this).parent().find('.dd-handle');
								//TODO:поправить этот костылик с заголовком.
								//~ document.getElementById('element-name').innerHTML = this.parentNode.treename;

							}
		li.appendChild(div_handle);

		//Кнопочка удаления
		var div_handle = document.createElement('div');
		$(div_handle).addClass('infobtn btn btn-circle btn-outline btn-warning pull-right fa fa-remove');
		div_handle.onclick = function()
							{
								$(this).parent().remove();
							}
		li.appendChild(div_handle);


		//========================================================
		//Заголовок элемента
		//========================================================
		var div_handle = document.createElement('div');
		div_handle.classList.add('dd-handle');

		var head = element.head;
		if (head == undefined) head = '[ '+item+' ]';

		div_handle.innerHTML = head;
		li.appendChild(div_handle);

		return li;
	}



	//Рисует древовидную структуру объекта
	//html_div_id - DOM  в котором рисовать, object - сам объект, list - воспринимать как список элементов или как 1 элемент с полями
	function objects_tree_paint(html_div_id, object, href)
	{
		//~ var html_div_id = document.getElementById(div_id);

		//Удаляем содержимое
		//~ if (html_div_id.firstChild != undefined)

		//~ alert(href);
		//Создаем групповой элемент под список
		var ol = document.createElement('ol');
		ol.classList.add('dd-list');
		//~ $(ol).attr('', '');

		//Если хотят повесить ссылку при клике на все меню
		if (href != undefined) html_div_id.href = href;

		//~ //Создаем копию, с которой будем работать
		//~ var buffer_item = object[item];
		//~ //var copy = Object.assign({}, obj);
		//~ console.log(buffer_item);
		//~ //Удаляем список - он на не нужен
		//~ //buffer_item.splice('list', 1);
		//~ delete buffer_item['list'];
		//~ //Объявляем новое поле с данными у ol
		//~ ol.data = [];
		//~ //Записываем в него значения item
		//~ ol.data = item;


		//Проходимся по коллекци объектов
		for (var item in object)
		{
			item = String(item);
			var head = '';
			var list = undefined;
			//~ var list = [];
			if (object == undefined) continue;

			head = object[item].head;
			if (object[item].head == undefined) head = '[ '+item+' ]';
			if (object[item].list != undefined) list = object[item].list;
			//Если у нас пустой элемент сразу со списком
			if (item == 'list') list = item;


			var li = objects_tree_add_item(html_div_id, object[item], item);


			if (list != undefined)
			{
				objects_tree_paint(li, list);
			}
			else
			{
				//Суть кода ниже в том, что если элемент не содержит list, но имеет подмассив - то нужно все равно вывести
				//Аналог конструкции на php : $arr['e1']['e2']
				//TODO: Временное решение до принятия финальной спецификации
				if (typeof object[item] === 'object' && object[item].head == undefined)
				{
					objects_tree_paint(li, object[item]);
				}

				//~ console.log();
			}

			ol.appendChild(li);
		}

		html_div_id.appendChild(ol);


		//console.log(html_div_id);
		return html_div_id;
	}


	//Собирает из html дерева объект
	function object_tree_compilation(html_div_id)
	{

		$('.dd-list').contents().unwrap();


		var obj = [];
		//~ var obj = {};
		//Собираем весь список элементов
		var elems = html_div_id.childNodes;

		//~ console.log(elems);
		//~ console.log($('.dd-list').contents());


		//Проходимся по нему
		elems.forEach
		(
			function(elem)
			{
				if (elem == undefined) return ;

				//~ console.log(elem.treedata);
				//~ return false;
				//~ alert(elem.tagName);
				if ((elem.tagName == 'LI'))
				{

					//~ if  elems.removeChild('OL');
					//~ if (elem.treename != undefined)
					//Удалим у объекта список свойство list, которое пришло от сервера.
					//Есть вероятность, что порядок элементов изменил пользователь и он больше не актуален (дальше мы его перестроим)
					delete elem.treedata['list'];

					//Если строка не именная, то увеличиваем счетчик
					if (elem.treename == undefined ||  ! isNaN(elem.treename)) elem.treename = obj.length+1;
					//Крепим к объекту
					obj[elem.treename] = elem.treedata;


					//Если этот элемент содержит вложения
					if (elem.getElementsByTagName('li').length != 0)
					{
						//Если элемент не имеет имени. добавляем по порядковому номеру
						if (elem.treename == undefined)
						{
							var bufelem = [];
							bufelem['list'] = object_tree_compilation(elem);
							obj.push(bufelem);
						}
						else
						{
							obj[elem.treename]['list'] = object_tree_compilation(elem);
						}
					}
				}


				//~ alert( elem.tagName ); // HEAD, текст, BODY

			}
		);
		return obj;
	}

	//Собирает из html дерева json
	function json_tree_compilation(html_div_id)
	{

		$('.dd-list').contents().unwrap();

		//Собираем весь список элементов
		var elems = html_div_id.childNodes;
		var json = '';

		//Проходимся по нему
		elems.forEach
		(
			function(elem)
			{
				if (elem == undefined) return ;
				if ((elem.tagName == 'LI'))
				{
					//Удалим у объекта список свойство list, которое пришло от сервера.
					//Есть вероятность, что порядок элементов изменил пользователь и он больше не актуален (дальше мы его перестроим)
					delete elem.treedata['list'];

					//~ json += `"${elem.treename}":"${elem.treedata}",`;
					json += `"${elem.treename}":{`+JSON.stringify(elem.treedata).slice(1, -1);

					//Если этот элемент содержит вложения
					if (elem.getElementsByTagName('li').length != 0)
					{
						if (JSON.stringify(elem.treedata).slice(1, -1)) json += ',';
						json += `"list":`+json_tree_compilation(elem);
					}
					json +='},';
				}
				//~ alert( elem.tagName ); // HEAD, текст, BODY

			}
		);

		//~ return json;
		return '{'+json.slice(0, -1)+'}';
	}

/**
	//Собирает из html дерева объект
	function json_tree_compilation_bad_v1(html_div_id)
	{
		$('.dd-list').contents().unwrap();


		var json_buffer = '';
		//~ var obj = {};
		//Собираем весь список элементов
		var elems = html_div_id.childNodes;

		//~ console.log(elems);
		//~ console.log($('.dd-list').contents());

		var iterator = 0;

		//Проходимся по нему
		elems.forEach
		(
			function(elem)
			{
				if (elem == undefined) return ;

				if ((elem.tagName == 'LI'))
				{

					//~ if  elems.removeChild('OL');
					//~ if (elem.treename != undefined)
					//Удалим у объекта список свойство list, которое пришло от сервера.
					//Есть вероятность, что порядок элементов изменил пользователь и он больше не актуален (дальше мы его перестроим)
					delete elem.treedata['list'];


					//Если строка не именная, то увеличиваем счетчик
					if (elem.treename == undefined ||  isNaN(elem.treename)) elem.treename = 'item'+iterator++;
					//Крепим к объекту
					//~ json_buffer += '"' + elem.treename + '":{';

					//~ Json += '"' + property + '":[';
					//~ for(prop in value) json += '"' +value[prop]+ '],';
						//~ json = json.substr(0, json.length-1)+"],";

					//~ json_buffer = json_buffer.substr(0, json_buffer.length-1)+'';
					//~ json_buffer += '}';

					//~ for(prop in elem.treedata) console.log(elem.treedata[prop]);

					//~ obj[elem.treename] = elem.treedata;
					//~ console.log(elem.treedata);
					//~ console.log(property.join(','));

					var property = '';

					//~ for(prop in elem.treedata) property.push('"'+prop+'":"'+elem.treedata[prop]+'"');

					for(prop in elem.treedata) property += '"'+prop+'":"'+elem.treedata[prop]+'",';
					//~ json_buffer += property.slice(0, -1);

					property += '"<'+elem.treename+'>":'+ property.slice(0, -1)+',';

					//~ json_buffer += property;

					//~ console.log(property);

					//~ var property = '';

					//Если этот элемент содержит вложения
					if (elem.getElementsByTagName('li').length != 0)
					//~ if (elem.getElementsByTagName('li').length == 99)
					{
						//Если элемент не имеет имени. добавляем по порядковому номеру
						if (elem.treename == undefined) elem.treename = 'item'+iterator++;
						//~ if (elem.treename == undefined)
						//~ {
							//~ var bufelem = [];
							//~ bufelem['list'] = object_tree_compilation(elem);
							//~ obj.push(bufelem);
						//~ }
						//~ else
						{
							//~ property['list'] = '"' + elem.treename + '":{"list":'+json_tree_compilation(elem)+'}';

							 //~ property['list'] = '"' + prop + '":{"list":'+json_tree_compilation(elem)+'}';

							 property += '"list":'+json_tree_compilation(elem)+',';
							 //~ json_buffer += '"' + elem.treename + '":{"list":'+json_tree_compilation(elem)+'}';

							//~ json_buffer = json_buffer.substr(0, json_buffer.length-1)+"},";
							//~ obj[elem.treename]['list'] = object_tree_compilation(elem);
						}
					}



					json_buffer += property.slice(0, -1);


				}
			}
		);
		//~ return "{"+json_buffer+ "}".replace(',}', '}');
		return "{"+json_buffer+ "}";
	}
	*
**/

	//Перерисовка таблицы из объекта
	function objects_table_paint(div_id, object)
	{
		object_table_clear(div_id);

		//Проходимся по полям объекта
		for (var item in object)
		{
			//Если нам подсунули что то не внятное - лучше мы пропустим этот элемент
			if (object[item] == undefined) continue;
			object_table_additem(div_id, item, object[item])
		}

	}

	//Добавляет элемент в таблицу
	function object_table_additem(div_id, name='', value='')
	{
		var html_div_id = document.getElementById(div_id);
		var html_tr = document.createElement('tr');
		html_tr.innerHTML = 	'<td><input onchange="objects_corrent_node_edit()" value="'+name+'" placeholder="Имя свойства" class="form-control"></td> \
								 <td><input onchange="objects_corrent_node_edit()" value="'+value+'" placeholder="Значение свойства" class="form-control"></td>\
								 <td class="text-center" style="width:1em"><div onclick="$(this).parent().parent().remove()" class="infobtn btn btn-circle btn-outline btn-warning fa fa-remove"></div></td>';
		html_div_id.appendChild(html_tr);
	}

	//Очищает таблицу с элементами
	function object_table_clear(div_id)
	{
		var html_div_id = document.getElementById(div_id);

		//Удаляем содержимое
		while (html_div_id.firstChild)
			html_div_id.removeChild(html_div_id.firstChild);
	}



	//При изменении свойств элемента, забирает его значения и записываетв дерево
	function objects_corrent_node_edit()
	{
		//Очищаем страрые свойства
		for (var prop in window.currentNode) { if (window.currentNode.hasOwnProperty(prop)) { delete window.currentNode[prop]; } }

		//Собираем ноые свойства элемента
		$('#object_info').children().each(function(i, elem)
		{
			var name  = $(elem).children().children().eq(0).val();
			var value = $(elem).children().children().eq(1).val();

			if (name == '' && value == '') return;
			//Меняем свойства элемента
			window.currentNode[name] = value;
			//Меняем заголовок в дереве
			$(window.currentDOM).html(window.currentNode['head']);
		});
	}


	//Забрать элементы из таблицы
	function objects_table_getting(div_id)
	{
		var childNodes = document.getElementById(div_id).childNodes;
		var object = [];

		for (var i = 0; i < childNodes.length; i++)
		{
			// отфильтровать не-элементы
			if (childNodes[i].nodeType != 1) continue;

			//Забираем значения элементов
			var tag = childNodes[i].childNodes[0].childNodes[0].value;
			var value = childNodes[i].childNodes[2].childNodes[0].value;

			object[i] = new Object;
			object[i].tag = tag;
			object[i].value = value;
		}

		//~ console.log(object);

		return object;
	}



	//Функция для пребразования объекта js в такой объект, который бы мог понимать php.
	//Дело в том, что по умолчанию, сериализовать объекта в js по нормальному нельзя. А еще я ненавижу js за такое.
	function jsObj2phpObj(object)
	{
		var json = '{';
		for(property in object){
			var value = object[property];
			if(typeof(value) == 'string'){
				json += '"' + property + '":"' + value + '",'
			} else {
				if(!value[0]){
					json += '"' + property + '":' + jsObj2phpObj(value) + ',';
				} else {
					json += '"' + property + '":[';
					for(prop in value) json += '"' +value[prop]+ '],';
						json = json.substr(0, json.length-1)+"],";
				}
			}
		}
		return json.substr(0, json.length-1)+"}";
	}


	//Получение парамеров адресной строки
	function parseGetParams()
	{
		var $_GET = {};
		var __GET = window.location.search.substring(1).split("&");
		for(var i=0; i<__GET.length; i++)
		{
			var getVar = __GET[i].split("=");
			$_GET[getVar[0]] = typeof(getVar[1])=="undefined" ? "" : getVar[1];
		}
		return $_GET;
	}














/***
	function json_from_DOM(html_div_id)
	{
		$('.dd-list').contents().unwrap();

		//Собираем весь список элементов
		var elems = html_div_id.childNodes;

		function makeJSON(elems)
		{
			var json = '{';

			//Проходимся по нему
			elems.forEach(
				function(elem) {
					if (elem == undefined) return;

					if ((elem.tagName == 'LI')) {
						//Удалим у объекта список свойство list, которое пришло от сервера.
						//Есть вероятность, что порядок элементов изменил пользователь и он больше не актуален (дальше мы его перестроим)
						delete elem.treedata['list'];
						json += `"${elem.treename}":`;
						//Если этот элемент содержит вложения
						if (elem.getElementsByTagName('li').length != 0) {
							json += `"list":${makeJSON(elem.childNodes)},`;
						}
						if (elem.treedata && Object.keys(elem.treedata).length !== 0) {
							var arr = [];
							$.each(elem.treedata, function(k, v) {
								arr.push(`"${k}":"${v}"`);
							})
							json += `{${arr.join(',')}}`;
						}
					}
				}
			);
			// json = json.replace(/.$/,"");
			json += "}"
			return json;
		}
		json = makeJSON(elems);
		// json = json.replace(/.$/,"");
		json += '}';
		return json;
	}
***/
