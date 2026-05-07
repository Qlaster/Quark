<?php

// Получаем метаданные полей каталога.
// fields() уже разобрал source и заполнил $field['relation']['db/table/column'].
$fields = $APP->catalog->fields($_GET['catalog']);
$field  = $fields[$_GET['field']];

// Если поле не найдено или не является relation — возвращаем пустой массив
if (empty($field['relation']))
{
    header('Content-Type: application/json');
    echo json_encode([]);
    exit;
}

$relation = $field['relation'];

$orm = $APP->db->connect($relation['db'])->table($relation['table']);

// Два режима:
// ?id=N    — загрузить лейбл для конкретного ID (восстановление состояния при открытии формы)
// ?term=.. — поиск по подстроке (автокомплит)
if ($_GET['id'])
    $rows = $orm->where(['id' => (int) $_GET['id']])->select(['id', $relation['column']]);
else
    $rows = $orm->like($_GET['term'])->limit(20)->select(['id', $relation['column']]);

// Формируем ответ в формате jQuery UI Autocomplete: [{value: id, label: text}, ...]
$result = [];
foreach ((array) $rows as $row)
    $result[] = ['value' => $row['id'], 'label' => $row[$relation['column']]];

header('Content-Type: application/json');
echo json_encode($result);
