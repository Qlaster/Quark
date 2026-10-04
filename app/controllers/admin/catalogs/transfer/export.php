<?php

/*
 * export.php
 *
 * Экспорт каталога в CSV файл
 *
 */

//Статика сообщений — компаньон-ini [view] + перевод
$cfg = $APP->config->get();
$M   = $APP->l10n->translate(array_replace_recursive([], (array) $cfg['view']))['messages'];

try
{
    $catalogName = $_GET['catalog'];

    if (!$catalogName) throw new Exception($M['nocatalog']['text']);
    if (!$APP->catalog->listing()[$catalogName]) throw new Exception($M['nocatalogexists']['text']);

    $delimiter = isset($_GET['delimiter']) ? $_GET['delimiter'] : ';';
    $quotes    = isset($_GET['quotes'])    ? $_GET['quotes']    : '"';

    $filename = $catalogName . '_' . date('Y-m-d') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, no-store, must-revalidate');

    $options = ['delimiter' => $delimiter, 'quotes' => $quotes];
    $APP->catalog->items($catalogName)->export('php://output', $options);

}
catch (Exception $e)
{
    http_response_code(400);
    echo $e->getMessage();
}
