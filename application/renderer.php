<!doctype html>
<html lang="ru" data-bs-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<meta name='wmail-verification' content='7404cc552213a233445be2fe20acca8c' />

<?php
if (!empty($url->description)) {
    echo "<meta name='description' content='" . htmlspecialchars($url->description) . "' />\n";
}

$title = !empty($url->title) ? $url->title : 'Библиотека Флибуста';
echo "<title>" . htmlspecialchars($title) . "</title>\n";
include_once(ROOT_PATH . 'webroot.php');
?>

<link rel="icon" href="<?php echo $webroot; ?>/favicon.svg" sizes="any" type="image/svg+xml">
<link href="<?php echo $webroot; ?>/bootstrap/css/bootstrap.min.css" rel="stylesheet">
<link href="<?php echo $webroot; ?>/css/all.min.css" rel="stylesheet">
<link href="<?php echo $webroot; ?>/css/style.css?v=2" rel="stylesheet">

<script>
// Apply saved theme immediately to avoid flash of white/dark
(function() {
  var t = localStorage.getItem('flibusta_theme') || ((window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light');
  document.documentElement.setAttribute('data-bs-theme', t);
  document.documentElement.setAttribute('data-theme', t);
})();
</script>

<script src="<?php echo $webroot; ?>/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $webroot; ?>/js/theme.js?v=2"></script>
</head>

<?php
$c1 = $c2 = $c3 = $c4 = $c5 = $c6 = '';
switch ($url->mod) {
    case '':
        $c1 = 'active';
        break;
    case 'genres':
        $c2 = 'active';
        break;
    case 'series':
        $c3 = 'active';
        break;
    case 'authors':
        $c4 = 'active';
        break;
    case 'fav':
        $c5 = 'active';
        break;
    case 'service':
        $c6 = 'active';
        break;
    default:
        $c1 = 'active';
}
?>

<body>

<!-- Верхняя навигационная панель -->
<nav class="navbar navbar-expand-lg site-navbar sticky-top">
  <div class="container">
    <a class="navbar-brand me-4" href="<?php echo $webroot; ?>/" title="Локальное зеркало библиотеки">
      <span class="brand-icon"><i class="fas fa-book-open"></i></span>
      <span>Флибуста</span>
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Навигация">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarMain">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item <?php echo $c1; ?>">
          <a class="nav-link" href="<?php echo $webroot; ?>/"><i class="fas fa-book me-1"></i> Книги</a>
        </li>
        <li class="nav-item <?php echo $c2; ?>">
          <a class="nav-link" href="<?php echo $webroot; ?>/genres/"><i class="fas fa-tags me-1"></i> Жанры</a>
        </li>
        <li class="nav-item <?php echo $c4; ?>">
          <a class="nav-link" href="<?php echo $webroot; ?>/authors/"><i class="fas fa-user-edit me-1"></i> Авторы</a>
        </li>
        <li class="nav-item <?php echo $c3; ?>">
          <a class="nav-link" href="<?php echo $webroot; ?>/series/"><i class="fas fa-layer-group me-1"></i> Серии</a>
        </li>
        <li class="nav-item <?php echo $c5; ?>">
          <a class="nav-link" href="<?php echo $webroot; ?>/fav/"><i class="fas fa-bookmark me-1"></i> Полка</a>
        </li>
        <li class="nav-item <?php echo $c6; ?>">
          <a class="nav-link" href="<?php echo $webroot; ?>/service/"><i class="fas fa-sliders-h me-1"></i> Сервис</a>
        </li>
      </ul>

      <div class="d-flex align-items-center gap-2 mt-2 mt-lg-0">
        <!-- Переключатель темы (Dark/Light) -->
        <button id="theme-toggle-btn" class="theme-btn" type="button" title="Переключить тему оформления">
          <i id="theme-toggle-icon" class="fas fa-moon text-primary"></i>
        </button>

        <!-- Кнопка читателя / полки -->
        <a href="<?php echo $webroot; ?>/favlist/" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1 text-truncate" style="max-width: 180px;" title="Управление книжными полками">
          <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($user_name); ?>
        </a>
      </div>
    </div>
  </div>
</nav>

<!-- Основной контент страницы -->
<main class="container my-4 main-content-wrapper">
<?php
if (file_exists($url->module)) {
    include($url->module);
} else {
    echo "<div class='card text-center p-5 border-0 shadow-sm'>
            <div class='mb-3 text-muted'><i class='fas fa-compass fa-3x'></i></div>
            <h3 class='fw-bold'>Раздел не найден</h3>
            <p class='text-muted'>Вы ввели неверный адрес либо данный раздел ещё находится в разработке.</p>
            <div><a href='$webroot/' class='btn btn-primary rounded-pill px-4'>Вернуться на главную</a></div>
          </div>";
    header("HTTP/1.0 404 Not Found");
}
?>
</main>

<!-- Подвал -->
<footer class="site-footer">
  <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
    <div>
      <strong>Локальное зеркало Flibusta</strong> &bull; Домашняя цифровая библиотека
    </div>
    <div class="d-flex align-items-center gap-3">
      <a href="<?php echo $webroot; ?>/opds/" class="badge bg-primary-subtle text-primary border border-primary-subtle text-decoration-none px-3 py-2 rounded-pill" title="Каталог для FBReader, Moon+ Reader, KOReader">
        <i class="fas fa-rss me-1"></i> OPDS-каталог: <?php echo $webroot; ?>/opds/
      </a>
      <a href="<?php echo $webroot; ?>/service/" class="text-muted">Сервис</a>
    </div>
  </div>
</footer>

</body>
</html>
