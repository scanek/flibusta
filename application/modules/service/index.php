
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
<div class='row g-4'>
<div class="col-md-6">
<div class='card border-0 shadow-sm rounded-4 h-100'>
<div class='card-header bg-transparent border-bottom py-3'>
<h5 class='fw-bold mb-0'><i class='fas fa-chart-pie text-primary me-2'></i> Статистика библиотеки</h5>
</div>
<div class='card-body p-4'>
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
	echo "<table class='table table-borderless align-middle mb-0'><tbody>";
	echo "<tr><td class='text-muted ps-0'>Актуальность базы:</td><td class='fw-bold text-end pe-0'>" . (!empty($total->mmod) ? $total->mmod : '—') . "</td></tr>";
	echo "<tr><td class='text-muted ps-0'>Всего произведений:</td><td class='fw-bold text-end pe-0'>" . number_format($total->bcnt ?? 0, 0, '.', ' ') . "</td></tr>";
	echo "<tr><td class='text-muted ps-0'>Доступно активных:</td><td class='fw-bold text-end pe-0 text-success'>" . number_format($total->bdcnt ?? 0, 0, '.', ' ') . "</td></tr>";
	echo "<tr><td class='text-muted ps-0'>Размер архива книг:</td><td class='fw-bold text-end pe-0'>$books_size Gb</td></tr>";
	echo "<tr><td class='text-muted ps-0'>Размер кэша:</td><td class='fw-bold text-end pe-0'>$cache_size Mb</td></tr>";
	echo "</tbody></table>";
} else {
	echo "<div class='text-primary fw-bold p-3'><div class='spinner-border spinner-border-sm me-2' role='status'></div>Идёт процесс импорта или синхронизации...</div>";
}
?>
</div>
</div>
</div>

<div class="col-md-6">
<div class='card border-0 shadow-sm rounded-4 h-100'>
<div class='card-header bg-transparent border-bottom py-3'>
<h5 class='fw-bold mb-0'><i class='fas fa-wrench text-primary me-2'></i> Операции и обслуживание</h5>
</div>
<div class='card-body p-4'>
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
echo "<div class='d-flex flex-wrap gap-2'>";
echo "<a class='btn btn-primary rounded-pill px-4 $status' href='?import=sql'><i class='fas fa-sync-alt me-2'></i> Обновить базу</a> ";
echo "<a class='btn btn-outline-warning rounded-pill px-3' href='?empty=cache' onclick='return confirm(\"Очистить кэш обложек и картинок?\")'><i class='fas fa-trash-alt me-1'></i> Очистить кэш</a> ";
echo "<a class='btn btn-outline-secondary rounded-pill px-3 $status' href='?reindex'><i class='fas fa-file-archive me-1'></i> Сканирование ZIP</a> ";
echo "</div>";

if ($status_import) {
	$op = file_exists($status_file) ? trim(file_get_contents($status_file)) : '';
	if (empty($op)) {
		$op = 'Идёт обработка данных (скачивание или импорт)...';
	}
	echo "<div class='alert alert-info d-flex align-items-center rounded-3 mt-3 mb-2'>";
	echo "<div class='spinner-border spinner-border-sm me-2' role='status' aria-hidden='true'></div>";
	echo "<div><strong>Текущее действие:</strong> " . nl2br(htmlspecialchars($op)) . "</div>";
	echo "</div>";

	if (file_exists('/application/sql/sync.log')) {
		$log_tail = shell_exec('tail -n 10 /application/sql/sync.log 2>/dev/null');
		if (!empty($log_tail)) {
			echo "<div class='mt-2'><small class='text-muted fw-bold'>Лог выполнения (последние строки):</small>";
			echo "<pre class='bg-dark text-light p-3 rounded-3' style='font-size: 11px; max-height: 180px; overflow-y: auto; white-space: pre-wrap;'>" . htmlspecialchars($log_tail) . "</pre></div>";
		}
	}
	header("Refresh:3");
} else if (file_exists('/application/sql/sync.log') && file_exists($status_file) && trim(file_get_contents($status_file)) === '') {
	if (time() - filemtime($status_file) < 120) {
		echo "<div class='alert alert-success rounded-3 mt-3'><i class='fas fa-check-circle me-2'></i> Обновление базы успешно завершено!</div>";
	}
}

?>
</div>
</div>
</div>

</div>

<div class='row mt-4'>
<div class="col-sm-12">
<div class='card border-0 shadow-sm rounded-4 p-4'>
<h5 class='fw-bold mb-3'><i class='fas fa-info-circle text-primary me-2'></i> Справка по работе с библиотекой</h5>
<p class='text-body-secondary mb-2'>
1. <strong>База данных:</strong> Скачивание и импорт SQL-дампов выполняются автоматически по кнопке «Обновить базу» выше или по расписанию в Cron.
</p>
<p class='text-body-secondary mb-2'>
2. <strong>Архивы книг:</strong> Файлы архивов (*.zip) размещаются в каталоге книг (по умолчанию <code>Flibusta.Net</code> на хосте, настраивается через <code>BOOKS_DIR</code> в <code>.env</code>).
</p>
<p class='text-body-secondary mb-2'>
3. <strong>OPDS-каталог:</strong> Для подключения внешних читалок (FBReader, Moon+ Reader, KOReader) используйте адрес: <code><?php echo $webroot; ?>/opds/</code>
</p>
</div></div></div>

