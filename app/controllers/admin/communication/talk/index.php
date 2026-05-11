<?php
$content = $APP->controller->run('admin/autoinclude', ['APP' => $APP]);
$content['title'] = 'Коммуникации — Каналы';

$channel = $_GET['channel'] ?? null;
$post    = $_GET['post']    ?? null;
$search  = trim($_GET['search'] ?? '');

// Каналы
$blogs = $APP->talk->blog()->select();

foreach ($blogs as &$_blog)
{
    $_blog['selected'] = ($channel === $_blog['name']);
    $_blog['updated']  = $_blog['updated'] ? date('d.m.Y H:i', $_blog['updated']) : '—';
    $_blog['tags_str'] = implode(', ', (array)($_blog['tags'] ?? []));
}
unset($_blog);

$content['catalog']['channels']['head']     = 'Каналы';
$content['catalog']['channels']['list']     = $blogs;
$content['catalog']['channels']['selected'] = null;

foreach ($blogs as $_b) {
    if ($_b['selected']) { $content['catalog']['channels']['selected'] = $_b; break; }
}

// Посты выбранного канала
$content['catalog']['posts']['head']     = 'Посты';
$content['catalog']['posts']['list']     = [];
$content['catalog']['posts']['selected'] = null;

if ($channel)
{
    $postCtx = $APP->talk->blog($channel)->post();
    $posts   = $search ? $postCtx->search($search) : $postCtx->select();

    foreach ($posts as &$_post) {
        $_post['selected'] = ($post === $_post['name']);
        $_post['updated']  = $_post['updated'] ? date('d.m.Y H:i', $_post['updated']) : '—';
        $_post['tags_str'] = implode(', ', (array)($_post['tags'] ?? []));
        if ($_post['selected']) $content['catalog']['posts']['selected'] = $_post;
    }
    unset($_post);
    $content['catalog']['posts']['list'] = $posts;
}



$content['form']['search']['placeholder'] = $search ? $search : 'Поиск';


$APP->template->file('admin/communication/talk/channels.html')->display($content);
