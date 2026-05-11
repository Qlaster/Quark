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

    // ИЗМЕНЕНО: $name стал необязательным — blog() без аргумента вернёт контекст всех блогов
    public function blog($name = null): BlogContext
    {
        return new BlogContext($this->orm, $this->config, $name);
    }
}

// ----------------------------------------------------------------
//  BlogContext
// ----------------------------------------------------------------

class BlogContext
{
    private $orm;
    private $config;
    private $name;

    private function table(): string
    {
        return $this->config['table']['blogs'] ?? 'talk_blogs';
    }

    // : $name стал nullable
    public function __construct($orm, $config, $name = null)
    {
        $this->orm     = $orm;
        $this->config  = $config;
        $this->name    = $name;
    }

    public function create(array $data = []): self
    {
        // : guard — нельзя создать блог без имени
        if ($this->name === null)
            throw new \RuntimeException("blog() requires a name to create");

        $now  = time();
        $data = array_merge(['title' => null, 'tags' => null, 'meta' => null], $data);

        if (isset($data['tags']) && is_array($data['tags']))
            $data['tags'] = json_encode($data['tags']);
        if (isset($data['meta']) && is_array($data['meta']))
            $data['meta'] = json_encode($data['meta']);

        $data['name']    = $this->name;
        $data['created'] = $now;
        $data['updated'] = $now;

        $this->orm->table($this->table())->insert($data);
        return $this;
    }

    // : если $name === null — возвращает все блоги без фильтра по имени
    public function select(...$where): array
    {
        $query = $this->orm->table($this->table());

        if ($this->name !== null)
            $query = $query->where(['name' => $this->name]);

        $rows = $query->wheres(...$where)->select();
        return array_map([$this, 'decode'], (array) $rows);
    }

    public function update(array $data): self
    {
        // : guard
        if ($this->name === null)
            throw new \RuntimeException("blog() requires a name to update");

        $data['updated'] = time();

        if (isset($data['tags']) && is_array($data['tags']))
            $data['tags'] = json_encode($data['tags']);
        if (isset($data['meta']) && is_array($data['meta']))
            $data['meta'] = json_encode($data['meta']);

        $this->orm->table($this->table())->where(['name' => $this->name])->update($data);
        return $this;
    }

    public function delete(): self
    {
        // : guard
        if ($this->name === null)
            throw new \RuntimeException("blog() requires a name to delete");

        $this->orm->table($this->table())->where(['name' => $this->name])->delete();
        return $this;
    }

    public function archive(bool $state = true): self
    {
        // : guard — update() уже бросит исключение, но явный guard нагляднее
        if ($this->name === null)
            throw new \RuntimeException("blog() requires a name to archive");

        return $this->update(['archived' => (int) $state]);
    }

    public function post($name = null): PostContext
    {
		if ($this->name === null)
			throw new \RuntimeException("blog() requires a name to access posts");

        return new PostContext($this->orm, $this->config, $this->name, $name);
    }

    private function decode(array $row): array
    {
        if ($row['tags']) $row['tags'] = json_decode($row['tags'], true);
        if ($row['meta']) $row['meta'] = json_decode($row['meta'], true);
        return $row;
    }
}

// ----------------------------------------------------------------
//  PostContext
// ----------------------------------------------------------------

class PostContext
{
    private $orm;
    private $config;
    private $blogName;
    private $postName; // может быть null — тогда select() вернёт все посты блога

    private function blogsTable(): string { return $this->config['table']['blogs'] ?? 'talk_blogs'; }
    private function postsTable(): string { return $this->config['table']['posts'] ?? 'talk_posts'; }

    public function __construct($orm, $config, string $blogName, $postName = null)
    {
        $this->orm      = $orm;
        $this->config   = $config;
        $this->blogName = $blogName;
        $this->postName = $postName;
    }

    private function blogId()
    {
        $row = $this->orm->table($this->blogsTable())->where(['name' => $this->blogName])->select(['id']);
        return $row[0]['id'] ?? null;
    }

    public function create(array $data = []): self
    {
        if ($this->postName === null)
            throw new \RuntimeException("post() requires a name to create");

        $blogId = $this->blogId();
        if (!$blogId) throw new \RuntimeException("Blog '{$this->blogName}' not found");

        $now  = time();
        $data = array_merge(['title' => null, 'author' => null, 'status' => 'open',
                             'files' => null, 'tags' => null, 'meta' => null], $data);

        if (isset($data['files']) && is_array($data['files']))
            $data['files'] = json_encode($data['files']);
        if (isset($data['tags']) && is_array($data['tags']))
            $data['tags'] = json_encode($data['tags']);
        if (isset($data['meta']) && is_array($data['meta']))
            $data['meta'] = json_encode($data['meta']);

        $data['blog_id'] = $blogId;
        $data['name']    = $this->postName;
        $data['created'] = $now;
        $data['updated'] = $now;

        $this->orm->table($this->postsTable())->insert($data);
        return $this;
    }

    public function select(...$where): array
    {
        $blogId = $this->blogId();
        if (!$blogId) return [];

        $query = $this->orm->table($this->postsTable())
            ->where(['blog_id' => $blogId]);

        if ($this->postName !== null)
            $query = $query->where(['name' => $this->postName]);

        $rows = $query->wheres(...$where)->select();
        return array_map([$this, 'decode'], (array) $rows);
    }

    public function update(array $data): self
    {
        if ($this->postName === null)
            throw new \RuntimeException("post() requires a name to update");

        $blogId = $this->blogId();
        if (!$blogId) throw new \RuntimeException("Blog '{$this->blogName}' not found");

        $data['updated'] = time();

        if (isset($data['files']) && is_array($data['files']))
            $data['files'] = json_encode($data['files']);
        if (isset($data['tags']) && is_array($data['tags']))
            $data['tags'] = json_encode($data['tags']);
        if (isset($data['meta']) && is_array($data['meta']))
            $data['meta'] = json_encode($data['meta']);

        $this->orm->table($this->postsTable())
            ->where(['blog_id' => $blogId, 'name' => $this->postName])
            ->update($data);
        return $this;
    }

    public function delete(): self
    {
        if ($this->postName === null)
            throw new \RuntimeException("post() requires a name to delete");

        $blogId = $this->blogId();
        if (!$blogId) return $this;

        $this->orm->table($this->postsTable())
            ->where(['blog_id' => $blogId, 'name' => $this->postName])
            ->delete();
        return $this;
    }

    public function archive(bool $state = true): self
    {
        return $this->update(['archived' => (int) $state]);
    }

    // Изменено: принимает необязательный int $id
    public function message($id = null): MessageContext
    {
        if ($this->postName === null)
            throw new \RuntimeException("post() requires a name to access messages");

        return new MessageContext($this->orm, $this->config, $this->blogName, $this->postName, $id);
    }

    private function decode(array $row): array
    {
        if ($row['files']) $row['files'] = json_decode($row['files'], true);
        if ($row['tags'])  $row['tags']  = json_decode($row['tags'],  true);
        if ($row['meta'])  $row['meta']  = json_decode($row['meta'],  true);
        return $row;
    }
}

// ----------------------------------------------------------------
//  MessageContext
// ----------------------------------------------------------------

class MessageContext
{
    private $orm;
    private $config;
    private $blogName;
    private $postName;
    private $messageId; // null — контекст всех сообщений; int — контекст конкретного сообщения

    private function blogsTable():    string { return $this->config['table']['blogs']    ?? 'talk_blogs'; }
    private function postsTable():    string { return $this->config['table']['posts']    ?? 'talk_posts'; }
    private function messagesTable(): string { return $this->config['table']['messages'] ?? 'talk_messages'; }

    // Изменено: добавлен необязательный параметр $messageId
    public function __construct($orm, $config, string $blogName, string $postName, $messageId = null)
    {
        $this->orm       = $orm;
        $this->config    = $config;
        $this->blogName  = $blogName;
        $this->postName  = $postName;
        $this->messageId = $messageId;
    }

    private function postId(): int
    {
        $blogs = $this->blogsTable();
        $posts = $this->postsTable();

        $blog = $this->orm->table($blogs)->where(['name' => $this->blogName])->select(['id']);
        $blogId = $blog[0]['id'] ?? null;
        if (!$blogId) return null;

        $post = $this->orm->table($posts)->where(['blog_id' => $blogId, 'name' => $this->postName])->select(['id']);
        return $post[0]['id'] ?? null;
    }

    // create() не требует ID — всегда создаёт новое сообщение
    public function create(array $data): self
    {
        $postId = $this->postId();
        if (!$postId) throw new \RuntimeException("Post '{$this->postName}' not found");

        $now  = time();
        $data = array_merge(['text' => null, 'author' => null,
                             'files' => null, 'tags' => null, 'meta' => null], $data);

        if (isset($data['files']) && is_array($data['files']))
            $data['files'] = json_encode($data['files']);
        if (isset($data['tags']) && is_array($data['tags']))
            $data['tags'] = json_encode($data['tags']);
        if (isset($data['meta']) && is_array($data['meta']))
            $data['meta'] = json_encode($data['meta']);

        $data['post_id'] = $postId;
        $data['created'] = $now;
        $data['updated'] = $now;

        $this->orm->table($this->messagesTable())->insert($data);

        // Обновляем updated у поста при добавлении нового сообщения
        $this->orm->table($this->postsTable())
            ->where(['id' => $postId])
            ->update(['updated' => $now]);

        return $this;
    }

    // Изменено: если $messageId задан — фильтрует по нему
    public function select(...$where): array
    {
        $postId = $this->postId();
        if (!$postId) return [];

        $query = $this->orm->table($this->messagesTable())
            ->where(['post_id' => $postId]);

        if ($this->messageId !== null)
            $query = $query->where(['id' => $this->messageId]);

        $rows = $query->wheres(...$where)->select();
        return array_map([$this, 'decode'], (array) $rows);
    }

    // Изменено: ID берётся из $this->messageId, не из параметра
    public function update(array $data): self
    {
        if ($this->messageId === null)
            throw new \RuntimeException("message() requires an id to update");

        $data['updated'] = time();

        if (isset($data['files']) && is_array($data['files']))
            $data['files'] = json_encode($data['files']);
        if (isset($data['tags']) && is_array($data['tags']))
            $data['tags'] = json_encode($data['tags']);
        if (isset($data['meta']) && is_array($data['meta']))
            $data['meta'] = json_encode($data['meta']);

        $this->orm->table($this->messagesTable())
            ->where(['id' => $this->messageId, 'post_id' => $this->postId()])
            ->update($data);

        return $this;
    }

    // Изменено: ID берётся из $this->messageId
    public function delete(): self
    {
        if ($this->messageId === null)
            throw new \RuntimeException("message() requires an id to delete");

        $this->orm->table($this->messagesTable())
            ->where(['id' => $this->messageId, 'post_id' => $this->postId()])
            ->delete();

        return $this;
    }

    // Изменено: ID берётся из $this->messageId
    public function archive(bool $state = true): self
    {
        if ($this->messageId === null)
            throw new \RuntimeException("message() requires an id to archive");

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

        $rows = $this->orm->table($this->messagesTable())
            ->where(['post_id' => $postId])
            ->where("created > ?", $since)
            ->OrderBy('created ASC')
            ->select();

        return array_map([$this, 'decode'], (array) $rows);
    }

    private function decode(array $row): array
    {
        if ($row['files']) $row['files'] = json_decode($row['files'], true);
        if ($row['tags'])  $row['tags']  = json_decode($row['tags'],  true);
        if ($row['meta'])  $row['meta']  = json_decode($row['meta'],  true);
        return $row;
    }
}

// ----------------------------------------------------------------
//  Подключение фасада
// ----------------------------------------------------------------
$config = $this->config->get(__file__);
return new Talk($this->db->connect($config['db']['name']), $config);
