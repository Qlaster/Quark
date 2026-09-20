<?php

namespace App\Facade;


/**
 * Алгоритм работы механизма поддержки контроллеров.
 *
 * Описание последовательности обработки URL:
 * 1) Путь сначала рассматривается как путь до папки с контроллерами, при этом вызывается метод по умолчанию (обычно index.php).
 *    Если есть папка и файл с одинаковым именем — приоритет у папки. Но если в папке нет index — она игнорируется.
 *
 * 2) Если папки нет, то "откусываем" последний элемент пути и ищем его.
 *    Сначала добавляем расширение .php, если его нет, ищем по имени как есть.
 *
 * 3) Если файл не найден — проверяем наличие контроллера в базовом пути.
 *    Если он есть — вызываем дефолтный обработчик контроллера, указанный в конфиге.
 *
 * Конфигурационные параметры:
 * @property array $config - массив настроек:
 *   - 'folder'  => папка с контроллерами,
 *   - 'runlock' => запрет вызова методов вне папки,
 *   - 'handler' => дефолтный обработчик (например, index.php),
 *   - 'cascade' => каскадная обработка контроллеров.
 */

# ---------------------------------------------------------------- #
#                 ОБЪЯВЛЕНИЕ	ИНТЕРФЕЙСА                         #
# ---------------------------------------------------------------- #
interface QControllerInterface
{
	/**
	* Запуск контроллера
	* @param string $controller Имя контроллера
	* @return void
	*/
	public function run($controller);
}

# ---------------------------------------------------------------- #
#                 РЕАЛИЗАЦИЯ	ИНТЕРФЕЙСА                         #
# ---------------------------------------------------------------- #
class Controller implements QControllerInterface
{
	/**
	 * Конфигурация контроллера
	 * @var array
	 */
	public $config;

    /**
     * Конструктор
     * @param array $config Настройки конфигурации
     * @param array $interfaces Внешние интерфейсы для работы класса
     */
	function __construct($config, $interfaces=['files'=>null])
	{
		//Корректируем конфиг. Если опции нет, то устанавливаем по дефолту.
		$this->config = $this->correctConfig($config);
		$this->interfaces = (object) $interfaces;
	}

	/**
     * Преобразует адресный путь в путь к контроллеру с учетом настроек
     * @param string $controller Путь к контроллеру
     * @return string Полный путь к файлу контроллера
     */
	public function realpath($controller='')
	{
		//Корректируем конфиг. Если опции нет, то устанавливаем по дефолту.
		$this->config = $this->correctConfig($this->config);

		if ($this->config['runlock'])
		{
			//раскрывает все символические ссылки, переходы типа '/./', '/../' и лишние символы '/' в пути path, возвращая канонизированный путь к файлу.
			$path = $this->filtering($controller);
			//Строим полный путь
			$path = $this->config['folder'] .DIRECTORY_SEPARATOR. $path;
		}
		else
		{
			//Строим полный путь
			$path = $this->config['folder'] .DIRECTORY_SEPARATOR. $controller;
			//раскрывает все символические ссылки, переходы типа '/./', '/../' и лишние символы '/' в пути path, возвращая канонизированный путь к файлу.
			$path = $this->filtering($path);
		}

		//Вызываем с php.
		if ( is_file($path.'.php')	) return $path.'.php';
		//Вызываем без php (обратились по имени, не указав расширение)
		if ( is_file($path) 		) return $path;

		//Если обратились к папке, в которой лежит обработчик по умолчанию
		//Путь без конечного '/' — иначе получим 'dir//index.php', который
		//не совпадёт со строкой в ACL и обойдёт проверки доступа
		$dirpath = rtrim($path, DIRECTORY_SEPARATOR);
		if ( (is_dir($dirpath)) and ( is_file($dirpath.DIRECTORY_SEPARATOR.$this->config['handler']) ) ) //and (substr($controller, -1) == '/')
		{
			//Случай №1. Нужно просто вызвать дефолтный метод контроллера
			return $dirpath.DIRECTORY_SEPARATOR.$this->config['handler'];
		}
		return $path;
	}

    /**
     * Проверяет существование контроллера
     * @param string $controller Имя контроллера
     * @return bool
     */
	public function exists($controller)
	{
		//Запросим реальный путь до контроллера
		$filename = $this->realpath($controller);

		//Проверим, есть ли файл и доступен ли он
		return is_file($filename) && is_readable($filename);
	}

    /**
     * Запускает контроллер
     * @param string $controller Имя контроллера
     * @param array $_VARS Переменные окружения для передачи в контроллер
     * @return mixed Результат выполнения или null
     */
	public function run($controller='', array $_VARS=[])
	{
		//Создадим переменные окружения
		foreach ($_VARS as $_enviroment_var_name => $_enviroment_var_value) $$_enviroment_var_name = $_enviroment_var_value;
		unset($_enviroment_var_name, $_enviroment_var_value);

		//Проверяем существование и доступность файла
		if (!is_readable($ctrl = $this->realpath($controller)) or !is_file($ctrl)) return null;

		//Выполняем контроллер
		$result = include $ctrl;
		return ($result === null) ? true : $result;
	}

    /**
     * Проверяет синтаксис контроллера
     * @param string $controller Имя контроллера
     * @return bool|null
     */
	public function check($controller)
	{
		if (!function_exists('exec')) return null;
		try
		{
			$controller = escapeshellarg($controller);
			$res = exec("php -l '$controller'");
			return (bool) (mb_strcut($res, 0, 16) == 'No syntax errors');
		}
		catch (Error $e)
		{
			return false;
		}
	}


	/**
	 * Разбирает файл через token_get_all() и возвращает имена
	 * классов/интерфейсов/трейтов/enum, которые он объявляет — БЕЗ выполнения файла.
	 * Это безопасная альтернатива include() для диагностики (см. probe()),
	 * не рискующая коллизией в глобальной таблице классов процесса.
	 *
	 * @param string     $filename Путь к файлу, который нужно просканировать
	 * @param array|null $types    Какие секции включать в результат.
	 *                             null трактуется как "все секции" (эквивалент дефолта).
	 * @param bool       $flat     true — вернуть единый список имён без группировки по типу;
	 *                             false (по умолчанию) — вернуть массив, сгруппированный по типу конструкции.
	 * @return array Ассоциативный массив вида ['class' => ['Имя' => 'Имя', ...], 'interface' => [...], ...]
	 *               либо плоский список имён, если $flat = true.
	 */
	public function scan($filename, array $types = ['class', 'interface', 'trait', 'enum'], $flat = false)
	{
		//Разбиваем исходник на токены компилятора PHP. Файл при этом не выполняется —
		//это чисто лексический анализ, поэтому include()-подобных побочных эффектов не будет
		$tokens = token_get_all(file_get_contents($filename));

		//Заготовка результата: фиксируем секции заранее, чтобы порядок и набор ключей
		//были предсказуемы даже если в файле не нашлось, например, ни одного trait
		$result = ['class' => [], 'interface' => [], 'trait' => [], 'enum' => []];

		//Текущий namespace файла — обновляется по ходу разбора, т.к. namespace
		//может встретиться несколько раз (или отсутствовать вовсе — тогда имена глобальные)
		$namespace = '';

		//Сопоставление токена конструкции с ключом секции результата
		$typeMap = [
			T_CLASS     => 'class',
			T_INTERFACE => 'interface',
			T_TRAIT     => 'trait',
		];
		//T_ENUM появился только в PHP 8.1 — подключаем токен только если он определён,
		//иначе на старых версиях PHP будет "Undefined constant"
		if (defined('T_ENUM')) $typeMap[T_ENUM] = 'enum';

		//Проходим по всем токенам файла
		for ($i = 0; $i < count($tokens); $i++)
		{
			$token = $tokens[$i];

			//Встретили объявление namespace — вычитываем его полное имя
			//до символа ";" или "{" (границы блока namespace)
			if (is_array($token) && $token[0] === T_NAMESPACE)
			{
				$namespace = '';
				$j = $i + 1;
				while (isset($tokens[$j]) && $tokens[$j] !== ';' && $tokens[$j] !== '{')
				{
					//T_NAME_QUALIFIED/T_NAME_FULLY_QUALIFIED — токены PHP 8+,
					//раньше "App\Facade" разбивалось на T_STRING + T_NS_SEPARATOR по частям
					if (is_array($tokens[$j]) && in_array($tokens[$j][0], [T_STRING, T_NS_SEPARATOR, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED]))
						$namespace .= $tokens[$j][1];
					$j++;
				}
				//Перепрыгиваем уже разобранные токены namespace, чтобы не разбирать их повторно
				$i = $j;
				continue;
			}

			//Встретили class/interface/trait/enum
			if (is_array($token) && isset($typeMap[$token[0]]))
			{
				//Пропускаем анонимные классы вида "new class { ... }" —
				//у них нет объявляемого имени и они не создают риска коллизии по имени
				$prev = $tokens[$i-1] ?? null;
				if (is_array($prev) && $prev[0] === T_NEW) continue;

				//Ищем следующий значимый токен — это и есть имя класса/интерфейса/трейта
				//(пропускаем пробелы между словом "class" и именем)
				$j = $i + 1;
				while (isset($tokens[$j]) && (!is_array($tokens[$j]) || $tokens[$j][0] === T_WHITESPACE)) $j++;

				if (isset($tokens[$j]) && is_array($tokens[$j]) && $tokens[$j][0] === T_STRING)
				{
					//Собираем полное имя (FQCN) — с namespace, если он был объявлен
					$fqcn = $namespace ? $namespace.'\\'.$tokens[$j][1] : $tokens[$j][1];

					//Кладём имя одновременно как ключ и как значение —
					//это даёт дедупликацию "бесплатно" (повторная запись не создаст дубль)
					//и быстрый O(1) поиск через isset() вместо in_array()
					$result[$typeMap[$token[0]]][$fqcn] = $fqcn;
				}
			}
		}

		//Если запрошены не все секции — отфильтровываем результат,
		//оставляя только те ключи, что перечислены в $types.
		//null трактуем как "все секции" — сохраняем поведение по умолчанию
		if ($types !== null)
			$result = array_intersect_key($result, array_flip($types));

		//Если группировка не нужна — сворачиваем все секции в один список.
		//Ключи (имена) сохраняются осознанно — это даёт тот же O(1)-поиск через isset(),
		//что и в сгруппированном виде, а не просто "список для отображения"
		if ($flat)
			return array_merge(...array_values($result));

		//По умолчанию — сгруппированный по типу конструкции результат
		return $result;
	}

    /**
     * Корректирует конфигурацию, заполняя недостающие параметры по умолчанию
     * @param array $config Входящая конфигурация
     * @return array Окончательная конфигурация
     */
	public static function correctConfig($config = array())
	{
		$result = $config;

		if ( !isset($config['folder']) 	) 	$result['folder'] 	= './controllers';
		if ( !isset($config['handler'])	)	$result['handler'] 	= 'index.php';

		$result['runlock'] = (isset($config['runlock'])) ? filter_var($config['runlock'], FILTER_VALIDATE_BOOLEAN) : true;
		$result['cascade'] = (isset($config['cascade'])) ? filter_var($config['cascade'], FILTER_VALIDATE_BOOLEAN) : true;

		return $result;
	}



    /**
     * Обеспечивает безопасность пути, раскрывая символические ссылки и удаляя опасные переходы
     * @param string $path Входящий путь
     * @return string Безопасный путь
     */
	private function filtering($path)
	{
		//Удалим возможность возврата за корневую директорию.
		//Для этого, разберем путь на составные части:
		$dir = explode(DIRECTORY_SEPARATOR, $path);

		//Удаляем пустые и текущие директории
		$dir = array_diff($dir, ['', '.']);

		//Раскрываем ..
		foreach ($dir as $key => $value)
			if ($value=='..') unset($dir[$key-1], $dir[$key]);


		//Собираем готовый путь
		$res_path = implode(DIRECTORY_SEPARATOR, $dir);

		//Если первоначальный вариант пути заканчивался на /, то мы должны поправить эту ситуацию, вернув его на место.
		if ( substr($path, -1) == DIRECTORY_SEPARATOR ) $res_path .= DIRECTORY_SEPARATOR;

		//Вернем готовый путь
		return $res_path;
	}



    /**
     * Получает список всех контроллеров
     * @return array
     */
	public function fetch($path='', $ext='*.php')
	{
		$pathCtl = rtrim($this->config['folder'], '/').DIRECTORY_SEPARATOR.$path;
		return $this->interfaces->files->listing($pathCtl, $ext);
	}



	//Каскадный запуск контроллеров до первого сработавшего
	public function cascade($URL, $Q=null)
	{

	}


	//Получить конфиг по умолчанию
	public function getDefaultConfig($config = array())
	{

	}

}


# ---------------------------------------------------------------- #
# --------------[ СОЗДАЕМ И ПОДКЛЮЧАЕМ ИНТЕРФЕЙС ]---------------- #
# ---------------------------------------------------------------- #

//Создаем класс управления контроллерами
return new Controller($this->config->get(__file__), ['files'=>$this->files]);

