<?php

	/*
	 * Фасад консоли — CLI-рантайм Quark.
	 *
	 * Скрипт ./console — тонкий бутстрап (env + APP), вся механика здесь:
	 *
	 * 	dispatch($tokens)
	 * 	│
	 * 	├─ 1. Команда-файл: самый длинный существующий префикс
	 * 	│     engine/console/**.php → include в скоупе фасада.
	 * 	│     Команда видит $this (args/flags/vars + приватные
	 * 	│     хелперы фасада) и $APP. Сегмент '_*' — не команда.
	 * 	│
	 * 	├─ 2. Фасад-фолбэк: $APP->{a} + метод {b} → вызов с хвостом
	 * 	│     токенов. Аргументы '{'/'[' — json_decode.
	 * 	│     Печать: scalar → строка, array → JSON, null → тишина.
	 * 	│
	 * 	└─ 3. Ничего не нашлось → команда help (console help).
	 *
	 * Входные каналы команды — публичные свойства:
	 *
	 * 	$this->args   — позиционные токены хвоста
	 * 	$this->flags  — --flag → true, --k=v → [k=>v]
	 * 	$this->vars   — k=v   → [k=>v]
	 *
	 * 	Терминатор '--' выключает разбор: всё после — только args.
	 * 	Провал = только брошенное исключение → stderr + exit≠0
	 * 	(печать исключения — в скрипте ./console, ASCII-кот).
	 *
	 * Цепочки команд — публичный dispatch: $this->dispatch(['service','stop',$name]).
	 */

	namespace App\Facade;


	class QConsole
	{
		public $config;

		//Входные каналы команды — public: чтение и подделка для цепочек
		public $args  = [];    // позиционные токены хвоста
		public $flags = [];    // --flag → true, --k=v → [k=>v]
		public $vars  = [];    // k=v    → [k=>v]

		private $app;

		function __construct($config, $app = null)
		{
			$this->config = (array) $config;
			$this->app    = $app;
		}


		//~ ------------------------------------------------------------
		//~ Пайплайн
		//~ ------------------------------------------------------------

		//Главная точка входа: tokens → резолв → файл | фасад | help.
		//Публична — команды могут редиспетчить вызовы синтаксисом консоли
		public function dispatch(array $tokens)
		{
			//1. команда-файл: самый длинный префикс дерева консоли
			list($file, $skip) = $this->resolve($tokens);
			if ($file)
			{
				$this->parse(array_slice($tokens, $skip));
				return $this->invoke($file);
			}

			//2. фасад-фолбэк: console <facade> <method> [args]
			if ($this->fallback($tokens)) return;

			//3. ничего не нашлось — справка (обычная команда-файл)
			$this->dispatch(['help']);
		}

		//Самый длинный существующий префикс engine/console/**.php.
		//→ [файл|null, число токенов пути] — один проход дерева
		private function resolve(array $tokens)
		{
			$file = null; $skip = 0;
			$dir  = $this->config['console']['path'] ?? 'engine/console';

			foreach ($tokens as $i => $token)
			{
				if ($token === '' or $token[0] === '_') break;
				$cand = $dir.'/'.$token;
				if (is_file("$cand.php")) { $file = "$cand.php"; $skip = $i + 1; }
				if (is_dir($cand))        { $dir = $cand; continue; }
				break;
			}
			return [$file, $skip];
		}

		//Разбор хвоста на три канала → $this->args/flags/vars.
		//'--' — терминатор: дальше только позиционные
		private function parse(array $tail)
		{
			$args = $flags = $vars = [];
			$positionalOnly = false;

			foreach ($tail as $t)
			{
				if ($positionalOnly) { $args[] = $t; continue; }
				if ($t === '--')     { $positionalOnly = true; continue; }

				if (strpos($t, '--') === 0)
				{
					$t = substr($t, 2);
					//сплит по первому '=' — значение с '=' не ломается
					if (strpos($t, '=') !== false) list($k, $v) = explode('=', $t, 2);
					else { $k = $t; $v = true; }
					$flags[$k] = $v;
				}
				elseif (strpos($t, '=') !== false)
				{
					list($k, $v) = explode('=', $t, 2);
					$vars[$k] = $v;
				}
				else $args[] = $t;
			}

			$this->args  = $args;
			$this->flags = $flags;
			$this->vars  = $vars;
		}

		//Выполнение команды в скоупе фасада: файл видит $this и $APP.
		//Локалки $args/$flags/$vars — совместимость с командами на старом контракте
		private function invoke($file)
		{
			$APP   = $this->app;
			$args  = $this->args;
			$flags = $this->flags;
			$vars  = $this->vars;
			return include $file;   //команда печатает сама; return игнорируем
		}


		//~ ------------------------------------------------------------
		//~ Фолбэк на фасады
		//~ ------------------------------------------------------------

		//$APP->{a}->{b}(хвост): '{'/'[' — json_decode, печать результата
		private function fallback(array $tokens)
		{
			$facades = $this->app->__facades();
			$fname   = $tokens[0] ?? '';

			if (!isset($facades[$fname])) return false;

			$facade = $this->app->$fname;
			$method = $tokens[1] ?? '';

			//метода нет — оглавление публичных методов фасада
			if (!$method or !method_exists($facade, $method))
				return $this->contents($facade);

			$args = array_map(function ($a) {
				return $a !== '' and ($a[0] === '{' or $a[0] === '[') ? json_decode($a, true) : $a;
			}, array_slice($tokens, 2));

			$result = call_user_func_array([$facade, $method], $args);
			if ($result !== null)
				echo is_scalar($result)
					? $result."\n"
					: json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)."\n";
			return true;
		}

		//Оглавление public-методов фасада (метода не нашлось)
		private function contents($facade)
		{
			$ref = new \ReflectionClass($facade);
			echo "Фасад '".$ref->getShortName()."' — методы:\n";
			foreach (get_class_methods($facade) as $m)
				if ($m[0] !== '_') echo "\t$m()\n";
			return true;
		}


		//~ ------------------------------------------------------------
		//~ Сахар
		//~ ------------------------------------------------------------

		//Прокси к фасадам приложения: в команде $this->service->start()
		public function __get($name) { return $this->app->$name; }
	}



	# ---------------------------------------------------------------- #
	# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
	# ---------------------------------------------------------------- #

	return new QConsole($this->config->get(__FILE__), $this);
