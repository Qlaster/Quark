<?php

/**
 * PHP CSV parser
 * - Валидность CSV: проверяет наличие разделителя, корректную строку заголовков, и одинаковое число полей во всех строках
 * - Возвращает первые 12 записей (после заголовка) как массив
 * - Возвращает массив результата и список ошибок/валидных сообщений
 */



	//~ print_r($_POST);
	//~ print_r($_FILES);


	$file = current($APP->utils->files->uploadList()['document']);

	// Пример использования:
	//~ $filename = 'path/to/your/file.csv';
	//~ $parsed = parse_csv_first12($file['tmp_name'], $_POST['delimiter'], false);

	//Запросим данные из csv файла
	$content['table']['rows'] = parse_csv_2($file['tmp_name'], $_POST['delimiter'], $_POST['quotes']);

	//Запросим поля каталога, которые необходимо заполнить
	$content['table']['cols'] = $APP->catalog->fields($_POST['catalog']);

	//~ print_r($content);

	//Отрисуем
	$APP->template->file('admin/catalogs/transfer/frame.preview.html')->display($content);





	//~ echo "Ошибки:\n";
	//~ foreach ($parsed['errors'] as $err) {
		//~ echo "- " . $err . PHP_EOL;
	//~ }

	//~ echo "\nПервые 12 записей:\n";
	//~ foreach ($parsed['rows'] as $idx => $row) {
		//~ echo ($idx + 1) . ") " . print_r($row, true) . PHP_EOL;
	//~ }





function parse_csv_2($file, $delimiter, $quote)
{
    // Открываем файл для чтения
    $handle = fopen($file, 'r');
    if ($handle === false)
        die('Не удалось открыть файл');

    // Читаем первые 3 байта, чтобы проверить BOM
    $bom = fread($handle, 3);
    $bomBytes = [0xEF, 0xBB, 0xBF];

    // Проверяем наличие BOM
    if (ord($bom[0]) === $bomBytes[0] && ord($bom[1]) === $bomBytes[1] && ord($bom[2]) === $bomBytes[2]) {
        // BOM есть, продолжим чтение файла, пропуская BOM
        // Он уже прочитан, ничего делать не нужно
    } else {
        // BOM отсутствует, возвращаем указатель файла назад
        rewind($handle);
    }

    $records = [];
    $maxRecords = 12;
    $recordCount = 0;

    // Читаем файл построчно
    while (($row = fgetcsv($handle, 1000, $delimiter, $quote)) !== false)
    {
        $records[] = $row;
        $recordCount++;
        if ($recordCount >= $maxRecords)
            break;
    }

    fclose($handle);

    return $records;
}


/**
 * Парсит CSV файл и возвращает массив первых 12 записей
 * @param string $filename Путь к CSV файлу
 * @param string $delimiter Разделитель полей (по умолчанию ',')
 * @param

 bool   $hasHeader Нужен ли заголовок (по умолчанию true)
 * @return array Массив с ключами 'rows' => массив записей, 'errors' => массив ошибок
 */
function parse_csv_first12(string $filename, string $delimiter = ',', bool $hasHeader = true): array
{
    $result = [
        'rows' => [],
        'errors' => []
    ];

    if (!file_exists($filename)) {
        $result['errors'][] = "Файл не найден: $filename";
        return $result;
    }

    if (!is_readable($filename)) {
        $result['errors'][] = "Файл недоступен для чтения: $filename";
        return $result;
    }

    $handle = fopen($filename, 'r');
    if ($handle === false) {
        $result['errors'][] = "Не удалось открыть файл: $filename";
        return $result;
    }

    $lineNumber = 0;
    $headerFields = null;
    $expectedCount = null;

    // Если нужен заголовок, читаем первую строку как заголовки
    if ($hasHeader)
    {
        $headerLine = fgetcsv($handle, 0, $delimiter);
        $lineNumber++;
        if ($headerLine === false) {
            $result['errors'][] = "Пустой или некорректный заголовок на строке $lineNumber in $filename";
            fclose($handle);
            return $result;
        }
        $headerFields = $headerLine;
        $expectedCount = count($headerFields);
        // Валидируем: заголовок не пустой
        if ($expectedCount === 0) {
            $result['errors'][] = "Заголовок не содержит полей на строке $lineNumber";
        }
    }

    // Читаем последующие строки и валидируем
    while (($row = fgetcsv($handle, 0, $delimiter)) !== false)
    {
        $lineNumber++;

        // Пропуск пустых строк
        if ($row === null || (count($row) === 1 && $row[0] === '')) {
            continue;
        }

        // Проверка на одинаковое число полей
        if ($expectedCount !== null && count($row) !== $expectedCount) {
            $result['errors'][] = "Неверное количество полей на строке $lineNumber: ожидается $expectedCount, найдено " . count($row);
            // Можно продолжать сбор данных, либо пропускать — здесь добавим строку как есть без учета валидации
        }

        // Сохранение записи: если нет заголовка, можно использовать числовые ключи
        // Здесь мы возвращаем как ассоциативный массив, если есть заголовок
        if ($hasHeader && $headerFields !== null) {
            $record = array_combine($headerFields, $row);
            if ($record === false) {
                // Если количество полей не совпало, пытаемся слепить с частичным заполнением
                $record = [];
                for ($i = 0; $i < max(count($headerFields), count($row)); $i++) {
                    $key = $headerFields[$i] ?? "field_$i";
                    $val = $row[$i] ?? null;
                    $record[$key] = $val;
                }
            }
        } else {
            // Без заголовка — используем числовые ключи
            $record = $row;
        }

        $result['rows'][] = $record;

        // Ограничение: первые 12 записей
        if (count($result['rows']) >= 12) {
            break;
        }
    }

    fclose($handle);
    return $result;
}

