// Theme and UI Controller for Flibusta Local Mirror
(function () {
    'use strict';

    // 1. Theme Management (Dark / Light)
    const THEME_KEY = 'flibusta_theme';
    const VIEW_KEY = 'flibusta_catalog_view';
    const READER_THEME_KEY = 'flibusta_reader_theme';
    const READER_FONT_SIZE_KEY = 'flibusta_reader_font_size';
    const READER_FONT_FAMILY_KEY = 'flibusta_reader_font_family';

    function getPreferredTheme() {
        const stored = localStorage.getItem(THEME_KEY);
        if (stored) return stored;
        return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        document.documentElement.setAttribute('data-theme', theme);
        const icon = document.getElementById('theme-toggle-icon');
        if (icon) {
            if (theme === 'dark') {
                icon.className = 'fas fa-sun text-warning';
            } else {
                icon.className = 'fas fa-moon text-primary';
            }
        }
    }

    // Initialize immediately to prevent flash
    const initialTheme = getPreferredTheme();
    applyTheme(initialTheme);

    document.addEventListener('DOMContentLoaded', () => {
        applyTheme(getPreferredTheme());

        const toggleBtn = document.getElementById('theme-toggle-btn');
        if (toggleBtn) {
            toggleBtn.addEventListener('click', (e) => {
                e.preventDefault();
                const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
                const next = current === 'dark' ? 'light' : 'dark';
                localStorage.setItem(THEME_KEY, next);
                applyTheme(next);
            });
        }

        // 2. Catalog View Mode (Grid vs List)
        const catalogContainer = document.getElementById('books-catalog-container');
        const gridBtn = document.getElementById('view-mode-grid');
        const listBtn = document.getElementById('view-mode-list');

        function setCatalogView(mode) {
            if (catalogContainer) {
                if (mode === 'list') {
                    catalogContainer.classList.remove('books-grid');
                    catalogContainer.classList.add('books-list');
                } else {
                    catalogContainer.classList.remove('books-list');
                    catalogContainer.classList.add('books-grid');
                }
            }
            if (gridBtn && listBtn) {
                if (mode === 'list') {
                    gridBtn.classList.remove('active', 'btn-primary');
                    gridBtn.classList.add('btn-outline-secondary');
                    listBtn.classList.remove('btn-outline-secondary');
                    listBtn.classList.add('active', 'btn-primary');
                } else {
                    gridBtn.classList.remove('btn-outline-secondary');
                    gridBtn.classList.add('active', 'btn-primary');
                    listBtn.classList.remove('active', 'btn-primary');
                    listBtn.classList.add('btn-outline-secondary');
                }
            }
            localStorage.setItem(VIEW_KEY, mode);
        }

        const savedView = localStorage.getItem(VIEW_KEY) || 'grid';
        setCatalogView(savedView);

        if (gridBtn) {
            gridBtn.addEventListener('click', (e) => {
                e.preventDefault();
                setCatalogView('grid');
            });
        }
        if (listBtn) {
            listBtn.addEventListener('click', (e) => {
                e.preventDefault();
                setCatalogView('list');
            });
        }

        // 3. Reader Controls (if reader is present on page)
        const readerEl = document.getElementById('reader-content');
        if (readerEl) {
            // Apply saved font size
            let currentFontSize = parseInt(localStorage.getItem(READER_FONT_SIZE_KEY) || '18', 10);
            function setFontSize(size) {
                currentFontSize = Math.max(12, Math.min(32, size));
                readerEl.style.fontSize = currentFontSize + 'px';
                localStorage.setItem(READER_FONT_SIZE_KEY, currentFontSize);
                const sizeDisplay = document.getElementById('reader-font-size-val');
                if (sizeDisplay) sizeDisplay.textContent = currentFontSize + 'px';
            }
            setFontSize(currentFontSize);

            const fontDec = document.getElementById('reader-font-dec');
            const fontInc = document.getElementById('reader-font-inc');
            if (fontDec) fontDec.addEventListener('click', () => setFontSize(currentFontSize - 2));
            if (fontInc) fontInc.addEventListener('click', () => setFontSize(currentFontSize + 2));

            // Apply font family
            const fontSelect = document.getElementById('reader-font-select');
            function setFontFamily(family) {
                readerEl.style.fontFamily = family === 'sans' ? 'var(--font-sans)' : 'var(--font-serif)';
                localStorage.setItem(READER_FONT_FAMILY_KEY, family);
                if (fontSelect) fontSelect.value = family;
            }
            const savedFont = localStorage.getItem(READER_FONT_FAMILY_KEY) || 'serif';
            setFontFamily(savedFont);
            if (fontSelect) {
                fontSelect.addEventListener('change', (e) => setFontFamily(e.target.value));
            }

            // Apply reader theme
            const readerBox = document.getElementById('reader-wrapper');
            function setReaderTheme(rTheme) {
                if (!readerBox) return;
                readerBox.classList.remove('reader-paper', 'reader-sepia', 'reader-night', 'reader-oled');
                readerBox.classList.add('reader-' + rTheme);
                localStorage.setItem(READER_THEME_KEY, rTheme);
                document.querySelectorAll('.reader-theme-btn').forEach(btn => {
                    btn.classList.toggle('active', btn.getAttribute('data-rtheme') === rTheme);
                });
            }
            const savedReaderTheme = localStorage.getItem(READER_THEME_KEY) || 'paper';
            setReaderTheme(savedReaderTheme);

            document.querySelectorAll('.reader-theme-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    const t = btn.getAttribute('data-rtheme');
                    setReaderTheme(t);
                });
            });

            // Fullscreen reader toggle
            const fsBtn = document.getElementById('reader-fullscreen-btn');
            if (fsBtn) {
                fsBtn.addEventListener('click', () => {
                    if (!document.fullscreenElement) {
                        if (readerBox.requestFullscreen) {
                            readerBox.requestFullscreen();
                        } else if (readerBox.webkitRequestFullscreen) {
                            readerBox.webkitRequestFullscreen();
                        }
                    } else {
                        if (document.exitFullscreen) {
                            document.exitFullscreen();
                        }
                    }
                });
            }

            // Reading Progress Indicator
            const progressBar = document.getElementById('reader-progress-bar');
            const progressVal = document.getElementById('reader-progress-val');
            window.addEventListener('scroll', () => {
                const totalHeight = document.documentElement.scrollHeight - window.innerHeight;
                if (totalHeight > 0) {
                    const progress = Math.min(100, Math.max(0, Math.round((window.scrollY / totalHeight) * 100)));
                    if (progressBar) progressBar.style.width = progress + '%';
                    if (progressVal) progressVal.textContent = progress + '%';
                }
            }, { passive: true });
        }
    });
})();
