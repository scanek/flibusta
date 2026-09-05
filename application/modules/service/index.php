
<?php
$service_password = getenv('SERVICE_PASSWORD');
if (!empty($service_password)) {
	if (!isset($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_PW'] !== $service_password) {
		header('WWW-Authenticate: Basic realm="Flibusta Service Area"');
		header('HTTP/1.0 401 Unauthorized');
		echo '<div class="alert alert-danger m-3">Доступ ограничен. Требуется авторизация в сервисной панели.</div>';
		return;
	}
}
?>
<div class='row'>
<div class="col-sm-6">
<div class='card'>
<h4 class="rounded-top p-1" style="background: #d0d0d0;">Статистика</h4>
<div class='card-body'>
<?php
$status_file = '/application/sql/status';
$pid_file = '/application/sql/sync.pid';

$status_import = false;

// 1. Проверка файла PID
if (file_exists($pid_file)) {
	$pid = trim(@file_get_contents($pid_file));
	if (!empty($pid) && file_exists("/proc/$pid")) {
		$status_import = true;
	}
}

// 2. Проверка списка процессов
if (!$status_import) {
	$ps = trim(shell_exec('ps aux | grep -E "docker_sync|app_import|app_topg|app_reindex|wget|curl|psql|app_db_converter" | grep -v grep') ?? '');
	if (!empty($ps)) {
		$status_import = true;
	}
}

// 3. Проверка актуальности файла статуса
if (!$status_import && file_exists($status_file)) {
	$stat_text = trim(@file_get_contents($status_file));
	if (!empty($stat_text) && (time() - filemtime($status_file) < 60)) {
		$status_import = true;
	}
}

function get_ds($path){
	$io = popen ( '/usr/bin/du -sk ' . $path, 'r' );
	$size = fgets ( $io, 4096);
	$size = substr ( $size, 0, strpos ( $size, "\t" ) );
	pclose ( $io );
	return round($size / 1024, 1);
}

if (!$status_import) {
	$cache_size = get_ds("/application/cache/covers") + get_ds("/application/cache/authors");
	$books_size = round(get_ds(rtrim(BOOKS_PATH, '/\\')) / 1024, 1);
	$qtotal = $dbh->query("SELECT (SELECT MAX(time) FROM libbook) mmod, (SELECT COUNT(*) FROM libbook) bcnt, (SELECT COUNT(*) FROM libbook WHERE deleted='0') bdcnt");
	$qtotal->execute();
	$total = $qtotal->fetch();
	echo "<table class='table'><tbody>";
	echo "<tr><td>Актуальность базы:</td><td>$total->mmod</td></tr>";
	echo "<tr><td>Всего произведений:</td><td>$total->bcnt</td></tr>";
	echo "<tr><td>Размер архива:</td><td>$books_size Gb</td></tr>";
	echo "<tr><td>Размер кэша:</td><td>$cache_size Mb</td></tr>";
	echo "</tbody></table>";
} else {
	echo "<div class='text-primary fw-bold'><div class='spinner-border spinner-border-sm me-2' role='status'></div>Идёт процесс импорта или синхронизации...</div>";
}
?>
</div>
</div>
</div>

<div class="col-sm-6">
<div class='card'>
<h4 class="rounded-top p-1" style="background: #d0d0d0;">Операции</h4>
<div class='card-body'>
<?php


if (isset($_GET['empty'])) {
	shell_exec('rm /application/cache/authors/*');
	shell_exec('rm /application/cache/covers/*');
	header("location:$webroot/service/");
	exit;
}

if (!$status_import) {
	if (isset($_GET['import'])) {
		@file_put_contents($status_file, "Запуск процесса синхронизации базы данных...");
		shell_exec('/bin/sh /application/tools/docker_sync_sql.sh > /application/sql/sync.log 2>&1 &');
		header("location:$webroot/service/");
		exit;
	}
	if (isset($_GET['reindex'])) {
		@file_put_contents($status_file, "Запуск сканирования и создания индекса ZIP-файлов...");
		shell_exec('/bin/sh /application/tools/app_reindex.sh > /application/sql/sync.log 2>&1 &');
		header("location:$webroot/service/");
		exit;
	}
}

if ($status_import) {
	$status = 'disabled';
} else {
	$status = '';
}
echo "<div class='d-flex justify-content-between'>";
echo "<a class='btn btn-primary m-1 $status' href='?import=sql'>Обновить базу</a> ";
echo "<a class='btn btn-warning m-1' href='?empty=cache'>Очистить кэш</a> ";
echo "<a class='btn btn-warning m-1 $status' href='?reindex'>Сканирование ZIP</a> ";
echo "</div>";

if ($status_import) {
	$op = file_exists($status_file) ? trim(file_get_contents($status_file)) : '';
	if (empty($op)) {
		$op = 'Идёт обработка данных (скачивание или импорт)...';
	}
	echo "<div class='alert alert-info d-flex align-items-center mt-3 mb-2'>";
	echo "<div class='spinner-border spinner-border-sm me-2' role='status' aria-hidden='true'></div>";
	echo "<div><strong>Текущее действие:</strong> " . nl2br(htmlspecialchars($op)) . "</div>";
	echo "</div>";

	if (file_exists('/application/sql/sync.log')) {
		$log_tail = shell_exec('tail -n 10 /application/sql/sync.log 2>/dev/null');
		if (!empty($log_tail)) {
			echo "<div class='mt-2'><small class='text-muted fw-bold'>Лог выполнения (последние строки):</small>";
			echo "<pre class='bg-dark text-light p-2 rounded' style='font-size: 11px; max-height: 160px; overflow-y: auto; white-space: pre-wrap;'>" . htmlspecialchars($log_tail) . "</pre></div>";
		}
	}
	header("Refresh:3");
} else if (file_exists('/application/sql/sync.log') && file_exists($status_file) && trim(file_get_contents($status_file)) === '') {
	if (time() - filemtime($status_file) < 120) {
		echo "<div class='alert alert-success mt-3'>Обновление базы успешно завершено!</div>";
	}
}

?>
</div>
</div>
</div>

</div>

<div class='row'>
<div class="col-sm-12 mt-3">
<div class='card'>
<div class='card-body'>
<p>
Для выполнения обновления необходимо разместить фалы дампа Флибусты (*.sql) в каталог FlibustaSQL. Процесс занимает до 30 минут, в зависимости от быстродействия сервера (SSD значительно увеличивает скорость импорта)
</p>
<p>
Чтобы отображались фото авторов и обложек для форматов, отличных от FB2, необходимо разместить в каталоге cache файлы архивов lib.a.attached.zip и lib.b.attached.zip соответственно.
В кэше хранятся распакованные фото авторов и обложек для FB2, а также их уменьшенные версии.</p>
<p>Файлы архивов Флибусты (*.zip) размещаются в каталоге книги (по умолчанию <code>Flibusta.Net</code> на хосте, настраивается через <code>BOOKS_DIR</code> в <code>.env</code>). Обрабатываются также файлы ежедневных обновлений, но обязательно необходимо подгружать свежие SQL файлы.</p>
<?php echo "<p>Доступен также OPDS-каталог для читалок: <a href='$webroot/opds/'>/opds/</a></p>"; ?>
<p><b>Каталоги FlibustaSQL, cache и их подкаталоги должны иметь права на запись для контейнера. Скрипты в каталоге /application/tools/ должны иметь права на выполнение.</b></p>
</div></div></div></div>

