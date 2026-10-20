<?php

	/*
	 * Bash-автодополнение для ./console.
	 *
	 * 	./console completion            — печатает скрипт
	 * 	source <(./console completion)  — подключает в текущую сессию
	 * 	./console completion install    — ставит навсегда
	 * 		(в ~/.local/share/bash-completion/completions/console —
	 * 		подхватывается лениво при первом TAB, .bashrc не трогаем)
	 *
	 * Скрипт на каждый TAB вызывает `console complete <cword> <слова>`,
	 * тот печатает кандидатов, фильтрует compgen по текущему слову.
	 */

	$script = <<<'BASH'
# Quark console autocompletion
_quark_console() {
	local cur out
	cur="${COMP_WORDS[COMP_CWORD]}"
	out=$("${COMP_WORDS[0]}" complete "$COMP_CWORD" "${COMP_WORDS[@]:1}" 2>/dev/null)
	COMPREPLY=($(compgen -W "$out" -- "$cur"))
}
complete -F _quark_console console ./console

BASH;

	//install — пишем в ленивый каталог bash-completion, подхват без правок .bashrc
	if (($args[0] ?? null) === 'install')
	{
		$dir = ($_SERVER['HOME'] ?? getenv('HOME')).'/.local/share/bash-completion/completions';
		if (!is_dir($dir)) mkdir($dir, 0755, true);
		file_put_contents("$dir/console", $script);
		echo "Установлено: $dir/console\n";
		echo "Заработает в новой сессии (или: source ~/.local/share/bash-completion/completions/console)\n";
		exit;
	}

	echo $script;
