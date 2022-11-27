<?php


	$content = $APP->controller->run('admin/autoinclude', ['APP'=>$APP]);



	//print_r($APP->utils->files->tree('/home'));

	//рисуем иерархию каталогов

	$home = $APP->url->home();
	$cwd  =  getcwd();

	$tree = $APP->utils->files->tree($cwd);

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

			foreach ($node as $key => $name)
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

<!--
	QLASTER
-->
	<ul>
			<li>css1
				<ul>
					<li  data-jstree='{"type":"css"}'>animate.css</li>
					<li  data-jstree='{"type":"css"}'>bootstrap.css</li>
					<li  data-jstree='{"type":"css"}'>style.css</li>
				</ul>
			</li>
			<li>email-templates
				<ul>
					<li  data-jstree='{"type":"html"}'>action.html</li>
					<li  data-jstree='{"type":"html"}'>alert.html</li>
					<li  data-jstree='{"type":"html"}'>billing.html</li>
				</ul>
			</li>
			<li>fonts
				<ul>
					<li data-jstree='{"type":"svg"}'>glyphicons-halflings-regular.eot</li>
					<li data-jstree='{"type":"svg"}'>glyphicons-halflings-regular.svg</li>
					<li data-jstree='{"type":"svg"}'>glyphicons-halflings-regular.ttf</li>
					<li data-jstree='{"type":"svg"}'>glyphicons-halflings-regular.woff</li>
				</ul>
			</li>
			<li>img
				<ul>
					<li data-jstree='{"type":"img"}'>profile_small.jpg</li>
					<li data-jstree='{"type":"img"}'>angular_logo.png</li>
					<li class="text-navy" data-jstree='{"type":"img"}'>html_logo.png</li>
					<li class="text-navy" data-jstree='{"type":"img"}'>mvc_logo.png</li>
				</ul>
			</li>
			<li class="jstree-open">js
				<ul>
					<li data-jstree='{"type":"js"}'>inspinia.js</li>
					<li data-jstree='{"type":"js"}'>bootstrap.js</li>
					<li data-jstree='{"type":"js"}'>jquery-2.1.1.js</li>
					<li data-jstree='{"type":"js"}'>jquery-ui.custom.min.js</li>
					<li class="text-navy" data-jstree='{"type":"js"}'>jquery-ui-1.10.4.min.js</li>
				</ul>
			</li>
			<li data-jstree='{"type":"html"}'> affix.html</li>
			<li data-jstree='{"type":"html"}'> dashboard.html</li>
			<li data-jstree='{"type":"html"}'> buttons.html</li>
			<li data-jstree='{"type":"html"}'> calendar.html</li>
			<li data-jstree='{"type":"html"}'> contacts.html</li>
			<li data-jstree='{"type":"html"}'> css_animation.html</li>
			<li  class="text-navy" data-jstree='{"type":"html"}'> flot_chart.html</li>
			<li  class="text-navy" data-jstree='{"type":"html"}'> google_maps.html</li>
			<li data-jstree='{"type":"html"}'> icons.html</li>
			<li data-jstree='{"type":"html"}'> inboice.html</li>
			<li data-jstree='{"type":"html"}'> login.html</li>
			<li data-jstree='{"type":"html"}'> mailbox.html</li>
			<li data-jstree='{"type":"html"}'> profile.html</li>
			<li  class="text-navy" data-jstree='{"type":"html"}'> register.html</li>
			<li data-jstree='{"type":"html"}'> timeline.html</li>
			<li data-jstree='{"type":"html"}'> video.html</li>
			<li data-jstree='{"type":"html"}'> widgets.html</li>
	</ul>

<?php die; ?>
