<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns:atom="http://www.w3.org/2005/Atom"
  xmlns:dc="http://purl.org/dc/terms/"
  xmlns:os="http://a9.com/-/spec/opensearch/1.1/"
  xmlns:opds="http://opds-spec.org/2010/catalog"
  exclude-result-prefixes="atom dc os opds">

  <xsl:output method="html" encoding="utf-8" indent="yes" doctype-system="about:legacy-compat" />

  <xsl:template match="/">
    <html lang="ru">
      <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <title><xsl:value-of select="atom:feed/atom:title"/> — OPDS Каталог Flibusta</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml" />
        <link rel="stylesheet" href="/bootstrap/css/bootstrap.min.css" />
        <link rel="stylesheet" href="/css/all.min.css" />
        <link rel="stylesheet" href="/css/style.css?v=2" />
        <style>
          .opds-header {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: var(--shadow-sm);
          }
          .opds-nav-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
          }
          .opds-nav-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1rem 1.25rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            text-decoration: none !important;
            color: var(--text-main);
            transition: var(--transition);
            box-shadow: var(--shadow-sm);
          }
          .opds-nav-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--accent-primary);
            color: var(--accent-primary);
          }
          .opds-nav-icon {
            width: 42px;
            height: 42px;
            border-radius: var(--radius-md);
            background: var(--bg-subtle);
            color: var(--accent-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            margin-right: 1rem;
            flex-shrink: 0;
          }
          .opds-books-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 2rem;
          }
          .opds-book-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            display: flex;
            gap: 1.25rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
          }
          .opds-book-card:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--accent-primary);
          }
          .opds-book-cover {
            width: 100px;
            height: 150px;
            flex-shrink: 0;
            border-radius: var(--radius-sm);
            overflow: hidden;
            background: var(--bg-subtle);
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
          }
          .opds-book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
          }
          .opds-book-info {
            flex-grow: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
          }
          .opds-book-title {
            font-size: 1.15rem;
            font-weight: 700;
            color: var(--text-main);
            text-decoration: none;
            margin-bottom: 0.25rem;
          }
          .opds-book-title:hover {
            color: var(--accent-primary);
          }
          .opds-book-author {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
          }
          .opds-book-summary {
            font-size: 0.85rem;
            color: var(--text-muted);
            line-height: 1.5;
            margin-bottom: 0.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
          }
          .opds-download-btn {
            padding: 0.35rem 0.85rem;
            border-radius: var(--radius-xl);
            font-size: 0.8rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            transition: var(--transition);
          }
          @media (max-width: 576px) {
            .opds-book-card {
              padding: 0.85rem;
              gap: 0.85rem;
            }
            .opds-book-cover {
              width: 75px;
              height: 112px;
            }
          }
        </style>
      </head>
      <body>
        <!-- Верхний навбар -->
        <nav class="navbar navbar-expand-lg site-navbar sticky-top">
          <div class="container">
            <a class="navbar-brand me-4" href="/" title="Локальное зеркало библиотеки">
              <span class="brand-icon"><i class="fas fa-book-open"></i></span>
              <span>Флибуста</span>
            </a>
            <div class="d-flex align-items-center gap-2 ms-auto">
              <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-2 rounded-pill">
                <i class="fas fa-rss me-1"></i> OPDS Режим
              </span>
              <a href="/" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1">
                <i class="fas fa-arrow-left me-1"></i> На сайт
              </a>
            </div>
          </div>
        </nav>

        <main class="container my-4">
          <!-- Шапка раздела OPDS -->
          <div class="opds-header">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
              <div>
                <div class="d-flex align-items-center gap-2 mb-1">
                  <a href="/opds/" class="badge bg-secondary-subtle text-secondary text-decoration-none rounded-pill px-2 py-1">
                    <i class="fas fa-home me-1"></i> Корень каталога
                  </a>
                  <xsl:if test="atom:feed/atom:link[@rel='start' and @href != '/opds/']">
                    <a href="{atom:feed/atom:link[@rel='start']/@href}" class="badge bg-secondary-subtle text-secondary text-decoration-none rounded-pill px-2 py-1">
                      <i class="fas fa-level-up-alt me-1"></i> Наверх
                    </a>
                  </xsl:if>
                </div>
                <h2 class="fw-bold mb-1" style="font-family: var(--font-serif);"><xsl:value-of select="atom:feed/atom:title"/></h2>
                <div class="text-muted small">
                  Каталог формата OPDS (Atom/XML) для электронных книг и читалок
                </div>
              </div>

              <!-- Форма поиска OPDS -->
              <form action="/opds/search" method="get" class="d-flex align-items-center gap-2 ms-md-auto" style="min-width: 280px; max-width: 400px; width: 100%;">
                <div class="input-group">
                  <input type="search" name="q" class="form-control form-control-sm rounded-start-pill" placeholder="Поиск в OPDS..." required="required" />
                  <select name="by" class="form-select form-select-sm" style="max-width: 110px;">
                    <option value="book">Книги</option>
                    <option value="author">Авторы</option>
                  </select>
                  <button type="submit" class="btn btn-primary btn-sm rounded-end-pill">
                    <i class="fas fa-search"></i>
                  </button>
                </div>
              </form>
            </div>
          </div>

          <!-- Разделение: Навигационные записи (Категории, Разделы, Авторы) -->
          <xsl:if test="atom:feed/atom:entry[not(atom:link[starts-with(@rel, 'http://opds-spec.org/acquisition')]) and not(atom:link[contains(@type, 'fb2') or contains(@type, 'epub') or contains(@type, 'pdf') or contains(@type, 'mobi')])]">
            <div class="opds-nav-grid">
              <xsl:for-each select="atom:feed/atom:entry[not(atom:link[starts-with(@rel, 'http://opds-spec.org/acquisition')]) and not(atom:link[contains(@type, 'fb2') or contains(@type, 'epub') or contains(@type, 'pdf') or contains(@type, 'mobi')])]">
                <xsl:variable name="linkUrl" select="atom:link[not(@rel) or @rel='subsection' or @rel='alternate' or contains(@type, 'opds-catalog')]/@href" />
                <a href="{$linkUrl}" class="opds-nav-card">
                  <div class="d-flex align-items-center">
                    <div class="opds-nav-icon">
                      <xsl:choose>
                        <xsl:when test="contains(atom:title, 'Новинки')"><i class="fas fa-star"></i></xsl:when>
                        <xsl:when test="contains(atom:title, 'полк') or contains(atom:title, 'Полк')"><i class="fas fa-bookmark"></i></xsl:when>
                        <xsl:when test="contains(atom:title, 'жанр') or contains(atom:title, 'Жанр')"><i class="fas fa-tags"></i></xsl:when>
                        <xsl:when test="contains(atom:title, 'автор') or contains(atom:title, 'Автор')"><i class="fas fa-user-edit"></i></xsl:when>
                        <xsl:when test="contains(atom:title, 'сери') or contains(atom:title, 'Сери') or contains(atom:title, 'сборник')"><i class="fas fa-layer-group"></i></xsl:when>
                        <xsl:otherwise><i class="fas fa-folder"></i></xsl:otherwise>
                      </xsl:choose>
                    </div>
                    <div>
                      <div class="fw-bold"><xsl:value-of select="atom:title"/></div>
                      <xsl:if test="atom:content != ''">
                        <div class="text-muted small"><xsl:value-of select="atom:content"/></div>
                      </xsl:if>
                    </div>
                  </div>
                  <div class="text-muted ms-2"><i class="fas fa-chevron-right"></i></div>
                </a>
              </xsl:for-each>
            </div>
          </xsl:if>

          <!-- Разделение: Книги (Acquisition Entries) -->
          <xsl:if test="atom:feed/atom:entry[atom:link[starts-with(@rel, 'http://opds-spec.org/acquisition')] or atom:link[contains(@type, 'fb2') or contains(@type, 'epub') or contains(@type, 'pdf') or contains(@type, 'mobi')]]">
            <div class="opds-books-list">
              <xsl:for-each select="atom:feed/atom:entry[atom:link[starts-with(@rel, 'http://opds-spec.org/acquisition')] or atom:link[contains(@type, 'fb2') or contains(@type, 'epub') or contains(@type, 'pdf') or contains(@type, 'mobi')]]">
                <div class="opds-book-card">
                  <!-- Обложка -->
                  <div class="opds-book-cover">
                    <xsl:variable name="coverUrl" select="atom:link[@rel='http://opds-spec.org/image/thumbnail' or @rel='http://opds-spec.org/image']/@href" />
                    <xsl:choose>
                      <xsl:when test="$coverUrl != ''">
                        <img src="{$coverUrl}" alt="{atom:title}" loading="lazy" />
                      </xsl:when>
                      <xsl:otherwise>
                        <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                          <i class="fas fa-book fa-2x"></i>
                        </div>
                      </xsl:otherwise>
                    </xsl:choose>
                  </div>

                  <!-- Информация о книге -->
                  <div class="opds-book-info">
                    <xsl:variable name="viewUrl" select="atom:link[@rel='alternate']/@href" />
                    <xsl:choose>
                      <xsl:when test="$viewUrl != ''">
                        <a href="{$viewUrl}" class="opds-book-title"><xsl:value-of select="atom:title"/></a>
                      </xsl:when>
                      <xsl:otherwise>
                        <span class="opds-book-title"><xsl:value-of select="atom:title"/></span>
                      </xsl:otherwise>
                    </xsl:choose>

                    <!-- Автор -->
                    <div class="opds-book-author">
                      <i class="fas fa-user-edit me-1"></i>
                      <xsl:for-each select="atom:author/atom:name">
                        <xsl:value-of select="."/><xsl:if test="position() != last()">, </xsl:if>
                      </xsl:for-each>
                    </div>

                    <!-- Жанры и мета-теги -->
                    <div class="d-flex flex-wrap gap-1 mb-2">
                      <xsl:for-each select="atom:category">
                        <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill">
                          <xsl:value-of select="@label"/>
                        </span>
                      </xsl:for-each>
                      <xsl:if test="dc:language != ''">
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill">
                          <xsl:value-of select="dc:language"/>
                        </span>
                      </xsl:if>
                      <xsl:if test="dc:issued != ''">
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill">
                          <xsl:value-of select="dc:issued"/> г.
                        </span>
                      </xsl:if>
                    </div>

                    <!-- Аннотация -->
                    <xsl:if test="atom:summary != ''">
                      <div class="opds-book-summary">
                        <xsl:value-of select="atom:summary"/>
                      </div>
                    </xsl:if>

                    <!-- Кнопки скачивания и действий -->
                    <div class="d-flex flex-wrap align-items-center gap-2 mt-auto pt-2 border-top">
                      <xsl:for-each select="atom:link[starts-with(@rel, 'http://opds-spec.org/acquisition')]">
                        <a href="{@href}" class="opds-download-btn btn btn-primary btn-sm rounded-pill">
                          <i class="fas fa-download"></i>
                          <span>
                            <xsl:choose>
                              <xsl:when test="@title != ''"><xsl:value-of select="@title"/></xsl:when>
                              <xsl:when test="contains(@type, 'fb2')">Скачать FB2</xsl:when>
                              <xsl:when test="contains(@type, 'epub')">Скачать EPUB</xsl:when>
                              <xsl:when test="contains(@type, 'mobi')">Скачать MOBI</xsl:when>
                              <xsl:when test="contains(@type, 'pdf')">Скачать PDF</xsl:when>
                              <xsl:otherwise>Скачать</xsl:otherwise>
                            </xsl:choose>
                          </span>
                        </a>
                      </xsl:for-each>

                      <xsl:if test="$viewUrl != ''">
                        <a href="{$viewUrl}" class="btn btn-outline-secondary btn-sm rounded-pill ms-auto">
                          <i class="fas fa-book-reader me-1"></i> Читать на сайте
                        </a>
                      </xsl:if>
                    </div>
                  </div>
                </div>
              </xsl:for-each>
            </div>
          </xsl:if>

          <!-- Постраничная навигация (Pagination) -->
          <xsl:if test="atom:feed/atom:link[@rel='previous' or @rel='prev'] or atom:feed/atom:link[@rel='next']">
            <div class="d-flex justify-content-center align-items-center gap-3 my-4">
              <xsl:if test="atom:feed/atom:link[@rel='previous' or @rel='prev']">
                <a href="{atom:feed/atom:link[@rel='previous' or @rel='prev']/@href}" class="btn btn-outline-primary rounded-pill px-4">
                  <i class="fas fa-chevron-left me-1"></i> Предыдущая страница
                </a>
              </xsl:if>
              <xsl:if test="atom:feed/atom:link[@rel='next']">
                <a href="{atom:feed/atom:link[@rel='next']/@href}" class="btn btn-primary rounded-pill px-4">
                  Следующая страница <i class="fas fa-chevron-right ms-1"></i>
                </a>
              </xsl:if>
            </div>
          </xsl:if>

        </main>

        <!-- Подвал -->
        <footer class="site-footer">
          <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
            <div>
              <strong>OPDS Каталог Flibusta</strong> &bull; Домашняя электронная библиотека
            </div>
            <div class="text-muted small">
              Совместимо со всеми читалками: FBReader, Moon+ Reader, KOReader, PocketBook
            </div>
          </div>
        </footer>
      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>
