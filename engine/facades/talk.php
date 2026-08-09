<?php
namespace App\Facade;

// ----------------------------------------------------------------
//  Talk — иерархический фасад: blog → post → message
// ----------------------------------------------------------------
/*
 *
	// Все сообщения поста
	$APP->talk->blog('support')->post('ticket-abc123')->message()->select()

	// Конкретное сообщение
	$APP->talk->blog('support')->post('ticket-abc123')->message(42)->select()

	// Обновить конкретное сообщение
	$APP->talk->blog('support')->post('ticket-abc123')->message(42)->update([
		'text' => 'Исправленный текст',
	])

	// Удалить конкретное сообщение
	$APP->talk->blog('support')->post('ticket-abc123')->message(42)->delete()

	// Архивировать конкретное сообщение
	$APP->talk->blog('support')->post('ticket-abc123')->message(42)->archive()

	// create() — всегда без ID (создаёт новое)
	$APP->talk->blog('support')->post('ticket-abc123')->message()->create([...])

*/



class Talk
{
    private $orm;
    public $config;

	public function __construct($orm, $config)
	{
		$this->orm    = $orm;
		$this->config = $config;

		$t = $config['table'] ?? [];

		$blogs    = $t['blogs']    ?? 'talk_blogs';
		$posts    = $t['posts']    ?? 'talk_posts';
		$messages = $t['messages'] ?? 'talk_messages';

		// Блог — верхний уровень иерархии. Логический контейнер для постов.
		// Примеры: канал чата, раздел поддержки, форма заявок с сайта, блог.
		$orm->SQL("CREATE TABLE IF NOT EXISTS $blogs (
			id       INTEGER PRIMARY KEY AUTOINCREMENT, -- Внутренний числовой идентификатор
			name     TEXT UNIQUE NOT NULL,              -- Slug-идентификатор блога (уникальный, напр. 'support', 'leads', 'general')
			title    TEXT,                              -- Человекочитаемое название ('Техническая поддержка')
			tags     TEXT,                              -- JSON-массив меток для группировки и фильтрации блогов
			meta     TEXT,                              -- JSON-объект для произвольных расширений без ALTER TABLE
			created  INTEGER,                           -- Unix timestamp создания
			updated  INTEGER,                           -- Unix timestamp последнего изменения (поста или самого блога)
			archived INTEGER                            -- 0 = активен, 1 = в архиве (скрыт из основных списков)
		)");

		// Пост — средний уровень иерархии. Тема, тикет, заявка, статья, задача.
		// Примеры: обращение в поддержку, заявка с сайта, статья блога, задача в трекере.
		$orm->SQL("CREATE TABLE IF NOT EXISTS $posts (
			id       INTEGER PRIMARY KEY AUTOINCREMENT,                        -- Внутренний числовой идентификатор
			blog_id  INTEGER NOT NULL REFERENCES $blogs(id) ON DELETE CASCADE, -- FK на блог; при удалении блога посты удаляются автоматически
			name     TEXT NOT NULL,                                            -- Slug поста, уникальный в пределах блога (напр. 'ticket-abc123')
			title    TEXT,                                                     -- Заголовок поста / темы обращения
			author   TEXT,                                                     -- Автор: email, логин или имя пользователя
			status   TEXT,                                                     -- Статус жизненного цикла: 'open', 'in_progress', 'closed', 'resolved'
			files    TEXT,                                                     -- JSON-массив путей к прикреплённым файлам (скриншоты, документы)
			tags     TEXT,                                                     -- JSON-массив меток для фильтрации и категоризации
			meta     TEXT,                                                     -- JSON-объект для расширений: priority, assignee, deadline и т.д.
			created  INTEGER,                                                  -- Unix timestamp создания поста
			updated  INTEGER,                                                  -- Unix timestamp последнего изменения (обновляется при добавлении сообщения)
			archived INTEGER,                                                  -- 0 = активен, 1 = в архиве (закрытое обращение, устаревшая статья)
			UNIQUE(blog_id, name)                                              -- Slug уникален в пределах блога, но может повторяться в разных блогах
		)");

		// Сообщение — нижний уровень иерархии. Комментарий, ответ, реплика в чате.
		// Примеры: ответ оператора поддержки, комментарий к статье, сообщение в чате.
		$orm->SQL("CREATE TABLE IF NOT EXISTS $messages (
			id       INTEGER PRIMARY KEY AUTOINCREMENT,                        -- Внутренний числовой идентификатор
			post_id  INTEGER NOT NULL REFERENCES $posts(id) ON DELETE CASCADE, -- FK на пост; при удалении поста сообщения удаляются автоматически
			text     TEXT,                                                     -- Текст сообщения (может быть пустым, если есть только вложения)
			author   TEXT,                                                     -- Автор: email, логин или имя пользователя
			files    TEXT,                                                     -- JSON-массив путей к прикреплённым файлам (скриншоты, документы)
			tags     TEXT,                                                     -- JSON-массив меток (напр. 'system', 'internal' для служебных сообщений)
			meta     TEXT,                                                     -- JSON-объект для расширений: reply_to, is_pinned, reaction и т.д.
			created  INTEGER,                                                  -- Unix timestamp создания сообщения
			updated  INTEGER,                                                  -- Unix timestamp последнего редактирования сообщения
			archived INTEGER                                                   -- 0 = активно, 1 = удалено/скрыто (мягкое удаление без потери истории)
		)");
	}

    public function blog($name = null): BlogContext
    {
        return new BlogContext($this->orm, $this->config, $name);
    }
}

// ----------------------------------------------------------------
//  Базовый контекст: ORM-ссылка, таблицы, JSON-упаковка, slug-имена
// ----------------------------------------------------------------

abstract class TalkContext
{
    protected $orm;
    protected $config;

    // JSON-колонки конкретного контекста (переопределяется в наследниках)
    protected $jsonFields = [];

    public function __construct($orm, $config)
    {
        $this->orm    = $orm;
        $this->config = $config;
    }

    protected function table(string $key): string
    {
        return $this->config['table'][$key] ?? "talk_$key";
    }

    // Slug-валидация имени. Имя уходит и в БД, и в файловые пути аплоадов —
    // разделители путей и навигационные сегменты запрещены.
    // null — это "контекст всего уровня", кидает RuntimeException только на write-операциях.
    protected static function checkName($name, string $what)
    {
        if ($name === null)
            throw new \RuntimeException("$what() requires a name");

        if (!is_string($name) or $name === '' or $name === '.' or $name === '..'
            or strpbrk($name, "/\\") !== false or strpos($name, "\0") !== false)
            throw new \InvalidArgumentException("Invalid $what name");
    }

    // JSON-колонки: массивы -> строки перед записью
    protected function encodeJson(array $data): array
    {
        foreach ($this->jsonFields as $field)
            if (isset($data[$field]) && is_array($data[$field]))
                $data[$field] = json_encode($data[$field]);
        return $data;
    }

    // JSON-колонки: строки -> массивы после чтения
    protected function decodeRow(array $row): array
    {
        foreach ($this->jsonFields as $field)
            if (!empty($row[$field])) $row[$field] = json_decode($row[$field], true);
        return $row;
    }

    // Поля, которые нельзя перезаписать через update()
    protected function sanitizeUpdate(array $data, array $protected): array
    {
        foreach ($protected as $field) unset($data[$field]);
        return $data;
    }
}

// ----------------------------------------------------------------
//  BlogContext
// ----------------------------------------------------------------

class BlogContext extends TalkContext
{
    protected $jsonFields = ['tags', 'meta'];

    private $name;

    public function __construct($orm, $config, $name = null)
    {
        parent::__construct($orm, $config);
        // null = контекст всех блогов; имя валидируем сразу — slug должен быть честным
        if ($name !== null) self::checkName($name, 'blog');
        $this->name = $name;
    }

    public function create(array $data = []): self
    {
        self::checkName($this->name, 'blog');

        $now  = time();
        $data = $this->encodeJson(array_merge(['title' => null, 'tags' => null, 'meta' => null], $data));

        $data['name']    = $this->name;
        $data['created'] = $now;
        $data['updated'] = $now;

        $this->orm->table($this->table('blogs'))->insert($data);
        return $this;
    }

    // $name === null — все блоги без фильтра по имени
    public function select(...$where): array
    {
        $query = $this->orm->table($this->table('blogs'));

        if ($this->name !== null)
            $query = $query->where(['name' => $this->name]);

        $rows = $query->wheres(...$where)->select();
        return array_map([$this, 'decodeRow'], (array) $rows);
    }

    public function update(array $data): self
    {
        self::checkName($this->name, 'blog');

        $data = $this->encodeJson($this->sanitizeUpdate($data, ['id', 'name', 'created']));
        $data['updated'] = time();

        $this->orm->table($this->table('blogs'))->where(['name' => $this->name])->update($data);
        return $this;
    }

    public function delete(): self
    {
        self::checkName($this->name, 'blog');

        $this->orm->table($this->table('blogs'))->where(['name' => $this->name])->delete();
        return $this;
    }

    public function archive(bool $state = true): self
    {
        return $this->update(['archived' => (int) $state]);
    }

    public function post($name = null): PostContext
    {
        self::checkName($this->name, 'blog');
        return new PostContext($this->orm, $this->config, $this->name, $name);
    }
}

// ----------------------------------------------------------------
//  PostContext
// ----------------------------------------------------------------

class PostContext extends TalkContext
{
    protected $jsonFields = ['files', 'tags', 'meta'];

    private $blogName;
    private $postName; // null — select() вернёт все посты блога
    private $blogId;   // null = не резолвлен, false = блог не найден

    public function __construct($orm, $config, string $blogName, $postName = null)
    {
        parent::__construct($orm, $config);
        if ($postName !== null) self::checkName($postName, 'post');
        $this->blogName = $blogName;
        $this->postName = $postName;
    }

    // id блога резолвится один раз на контекст — объекты живут в пределах запроса
    private function blogId()
    {
        if ($this->blogId === null)
        {
            $row = $this->orm->table($this->table('blogs'))
                ->where(['name' => $this->blogName])->select(['id']);
            $this->blogId = $row[0]['id'] ?? false;
        }
        return $this->blogId;
    }

    public function create(array $data = []): self
    {
        self::checkName($this->postName, 'post');

        $blogId = $this->blogId();
        if (!$blogId) throw new \RuntimeException("Blog '{$this->blogName}' not found");

        $now  = time();
        $data = $this->encodeJson(array_merge(
            ['title' => null, 'author' => null, 'status' => 'open',
             'files' => null, 'tags' => null, 'meta' => null], $data));

        $data['blog_id'] = $blogId;
        $data['name']    = $this->postName;
        $data['created'] = $now;
        $data['updated'] = $now;

        $this->orm->table($this->table('posts'))->insert($data);
        return $this;
    }

    public function select(...$where): array
    {
        $blogId = $this->blogId();
        if (!$blogId) return [];

        $query = $this->orm->table($this->table('posts'))
            ->where(['blog_id' => $blogId]);

        if ($this->postName !== null)
            $query = $query->where(['name' => $this->postName]);

        $rows = $query->wheres(...$where)->select();
        return array_map([$this, 'decodeRow'], (array) $rows);
    }

    public function update(array $data): self
    {
        self::checkName($this->postName, 'post');

        $blogId = $this->blogId();
        if (!$blogId) throw new \RuntimeException("Blog '{$this->blogName}' not found");

        $data = $this->encodeJson($this->sanitizeUpdate($data, ['id', 'blog_id', 'name', 'created']));
        $data['updated'] = time();

        $this->orm->table($this->table('posts'))
            ->where(['blog_id' => $blogId, 'name' => $this->postName])
            ->update($data);
        return $this;
    }

    public function delete(): self
    {
        self::checkName($this->postName, 'post');

        $blogId = $this->blogId();
        if (!$blogId) return $this;

        $this->orm->table($this->table('posts'))
            ->where(['blog_id' => $blogId, 'name' => $this->postName])
            ->delete();
        return $this;
    }

    public function search(string $term): array
	{
		$blogId = $this->blogId();
		if (!$blogId) return [];

		$rows = $this->orm->table($this->table('posts'))
			->where(['blog_id' => $blogId])
			->like($term)
			->select();

		return array_map([$this, 'decodeRow'], (array) $rows);
	}

    public function archive(bool $state = true): self
    {
        return $this->update(['archived' => (int) $state]);
    }

    public function message($id = null): MessageContext
    {
        self::checkName($this->postName, 'post');
        return new MessageContext($this->orm, $this->config, $this->blogName, $this->postName, $id);
    }
}

// ----------------------------------------------------------------
//  MessageContext
// ----------------------------------------------------------------

class MessageContext extends TalkContext
{
    protected $jsonFields = ['files', 'tags', 'meta'];

    private $blogName;
    private $postName;
    private $messageId; // null — контекст всех сообщений; int — конкретное сообщение
    private $blogId;    // null = не резолвлен, false = не найден
    private $postId;

    public function __construct($orm, $config, string $blogName, string $postName, $messageId = null)
    {
        parent::__construct($orm, $config);
        $this->blogName  = $blogName;
        $this->postName  = $postName;
        $this->messageId = $messageId;
    }

    private function blogId()
    {
        if ($this->blogId === null)
        {
            $row = $this->orm->table($this->table('blogs'))
                ->where(['name' => $this->blogName])->select(['id']);
            $this->blogId = $row[0]['id'] ?? false;
        }
        return $this->blogId;
    }

    // id поста или false — резолвится один раз на контекст
    private function postId()
    {
        if ($this->postId === null)
        {
            $this->postId = false;
            if ($blogId = $this->blogId())
            {
                $row = $this->orm->table($this->table('posts'))
                    ->where(['blog_id' => $blogId, 'name' => $this->postName])->select(['id']);
                $this->postId = $row[0]['id'] ?? false;
            }
        }
        return $this->postId;
    }

    // create() не требует ID — всегда создаёт новое сообщение
    public function create(array $data): self
    {
        $postId = $this->postId();
        if (!$postId) throw new \RuntimeException("Post '{$this->postName}' not found");

        $now  = time();
        $data = $this->encodeJson(array_merge(
            ['text' => null, 'author' => null, 'files' => null, 'tags' => null, 'meta' => null], $data));

        $data['post_id'] = $postId;
        $data['created'] = $now;
        $data['updated'] = $now;

        $this->orm->table($this->table('messages'))->insert($data);

        // Обновляем updated у поста при добавлении нового сообщения
        $this->orm->table($this->table('posts'))
            ->where(['id' => $postId])
            ->update(['updated' => $now]);

        return $this;
    }

    // $messageId задан — фильтрует по нему
    public function select(...$where): array
    {
        $postId = $this->postId();
        if (!$postId) return [];

        $query = $this->orm->table($this->table('messages'))
            ->where(['post_id' => $postId]);

        if ($this->messageId !== null)
            $query = $query->where(['id' => $this->messageId]);

        $rows = $query->wheres(...$where)->select();
        return array_map([$this, 'decodeRow'], (array) $rows);
    }

    public function update(array $data): self
    {
        if ($this->messageId === null)
            throw new \RuntimeException("message() requires an id to update");

        $postId = $this->postId();
        if (!$postId) throw new \RuntimeException("Post '{$this->postName}' not found");

        $data = $this->encodeJson($this->sanitizeUpdate($data, ['id', 'post_id', 'created']));
        $data['updated'] = time();

        $this->orm->table($this->table('messages'))
            ->where(['id' => $this->messageId, 'post_id' => $postId])
            ->update($data);

        return $this;
    }

    public function delete(): self
    {
        if ($this->messageId === null)
            throw new \RuntimeException("message() requires an id to delete");

        $postId = $this->postId();
        if (!$postId) throw new \RuntimeException("Post '{$this->postName}' not found");

        $this->orm->table($this->table('messages'))
            ->where(['id' => $this->messageId, 'post_id' => $postId])
            ->delete();

        return $this;
    }

    public function archive(bool $state = true): self
    {
        return $this->update(['archived' => (int) $state]);
    }

    /**
     * Возвращает сообщения новее указанного unixtime.
     * Используется для polling (проверка новых сообщений).
     */
    public function slice(int $since): array
    {
        $postId = $this->postId();
        if (!$postId) return [];

        $rows = $this->orm->table($this->table('messages'))
            ->where(['post_id' => $postId])
            ->where("created > ?", $since)
            ->OrderBy('created ASC')
            ->select();

        return array_map([$this, 'decodeRow'], (array) $rows);
    }
}

// ----------------------------------------------------------------
//  Подключение фасада
// ----------------------------------------------------------------
$config = $this->config->get(__file__);
return new Talk($this->db->connect($config['db']['name']), $config);
