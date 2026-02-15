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
		if ( (is_dir($path)) and ( is_file($path.DIRECTORY_SEPARATOR.$this->config['handler']) ) ) //and (substr($controller, -1) == '/')
		{
			//Случай №1. Нужно просто вызвать дефолтный метод контроллера
			return $path.DIRECTORY_SEPARATOR.$this->config['handler'];
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
	public function all($path='', $ext='*.php')
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
return new Controller($this->config->get(__file__), ['files'=>$this->utils->files]);

