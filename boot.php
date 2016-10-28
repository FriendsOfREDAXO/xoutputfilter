<?php

if (rex::isBackend() && is_object(rex::getUser())) {
    
    $addondir = basename(__DIR__);

    // Addon CSS / JavaScript
    if (rex_be_controller::getCurrentPagePart(1) == 'xoutputfilter') {
        if ($this->getConfig('active') == '1') {
            rex_view::addCssFile($this->getAssetsUrl('css/xoutputfilter.css'));
            rex_view::addJsFile($this->getAssetsUrl('js/xoutputfilter.js'));
        }
    }

    // Addonrechte (permissions) registieren
    rex_perm::register('xoutputfilter[]');
    rex_perm::register('xoutputfilter[config]');
    rex_perm::register('xoutputfilter[altmenuentry]');

    // Synchronisierung
    rex_extension::register('CLANG_ADDED', 'xoutputfilter_util::synchronize');
    rex_extension::register('CLANG_DELETED', 'xoutputfilter_util::synchronize');

    // Alternativer Menüeintrag
    if (!rex::getUser()->isAdmin() and rex::getUser()->hasPerm('xoutputfilter[altmenuentry]') and $this->getConfig('altmenuentry')) {
        $page = $this->getProperty('page');
        $page['title'] = $this->getConfig('altmenuentry');
        $this->setProperty('page', $page);
    }

    // Installierte und aktivierte Plugins in Addon-Navigation einfügen
    rex_extension::register('PAGES_PREPARED', function() {

        $page = rex_be_controller::getPageObject('xoutputfilter');
        if (!is_object($page)) {
            return;
        }

        // Sprachersetzungen
        if (rex::getUser()->hasPerm('xoutputfilter[languages]')) {
            if (rex_plugin::get('xoutputfilter', 'languages')->isAvailable()) {
                $page->addSubpage((new rex_be_page('languages', $this->i18n('xoutputfilter_languages')))
                    ->setSubPath(rex_path::addon('xoutputfilter', 'plugins/languages/pages/index.php'))
                    ->setIcon('rex-icon fa-flag-o')
                );
            }
        }

        // Abkürzungen
        if (rex::getUser()->hasPerm('xoutputfilter[abbrev]')) {
            if (rex_plugin::get('xoutputfilter', 'abbrev')->isAvailable()) {
                $page->addSubpage((new rex_be_page('abbrev', $this->i18n('xoutputfilter_abbrev')))
                    ->setSubPath(rex_path::addon('xoutputfilter', 'plugins/abbrev/pages/index.php'))
                    ->setIcon('rex-icon fa-commenting-o')
                );
            }
        }

        // Frontend-Ersetzungen
        if (rex::getUser()->hasPerm('xoutputfilter[frontend]')) {
            if (rex_plugin::get('xoutputfilter', 'frontend')->isAvailable()) {
                $page->addSubpage((new rex_be_page('frontend', $this->i18n('xoutputfilter_frontend')))
                    ->setSubPath(rex_path::addon('xoutputfilter', 'plugins/frontend/pages/index.php'))
                    ->setIcon('rex-icon fa-desktop')
                );
            }
        }

        // Backend-Ersetzungen
        if (rex::getUser()->hasPerm('xoutputfilter[backend]')) {
            if (rex_plugin::get('xoutputfilter', 'backend')->isAvailable()) {
                $page->addSubpage((new rex_be_page('backend', $this->i18n('xoutputfilter_backend')))
                    ->setSubPath(rex_path::addon('xoutputfilter', 'plugins/backend/pages/index.php'))
                    ->setIcon('rex-icon fa-bookmark-o')
                );
            }
        }

        // Import/Export
        if (rex::getUser()->hasPerm('xoutputfilter[import_export]')) {
            if (rex_plugin::get('xoutputfilter', 'import_export')->isAvailable()) {
                $page->addSubpage((new rex_be_page('import_export', $this->i18n('xoutputfilter_import_export')))
                    ->setSubPath(rex_path::addon('xoutputfilter', 'plugins/import_export/pages/export.php'))
                    ->setPjax(false)
                    ->setIcon('rex-icon fa-database')
                );
            }
        }

        // Konfiguration
        if (rex::getUser()->hasPerm('xoutputfilter[config]')) {
            $page->addSubpage((new rex_be_page('config', $this->i18n('xoutputfilter_config')))
                ->setSubPath(rex_path::addon('xoutputfilter', 'pages/config.php'))
                ->setIcon('rex-icon fa-wrench')
            );
        }

        // Dokumentation
        if (rex::getUser()->isAdmin()) {
            if (rex_plugin::get('xoutputfilter', 'documentation')->isAvailable()) {
                $page->addSubpage((new rex_be_page('documentation', $this->i18n('xoutputfilter_documentation')))
                    ->setSubPath(rex_path::addon('xoutputfilter', 'plugins/documentation/pages/index.php'))
                    ->setPjax(false)
                    ->setIcon('rex-icon fa-book')
                );
            }
        }

        // Info mit Subpages
        $page->addSubpage((new rex_be_page('info', $this->i18n('xoutputfilter_info')))
            ->setSubPath(rex_path::addon('xoutputfilter', 'pages/info.hilfe.php'))
            ->addItemClass('pull-right')
            ->setIcon('rex-icon fa-info')
        );

        $page = rex_be_controller::getPageObject('xoutputfilter/info');
        if (is_object($page)) {
            $page->addSubpage((new rex_be_page('hilfe', $this->i18n('xoutputfilter_hilfe')))
                ->setSubPath(rex_path::addon('xoutputfilter', 'pages/info.hilfe.php'))
            );

            $page->addSubpage((new rex_be_page('changelog', $this->i18n('xoutputfilter_changelog')))
                ->setSubPath(rex_path::addon('xoutputfilter', 'pages/info.changelog.php'))
            );

            $page->addSubpage((new rex_be_page('lizenz', $this->i18n('xoutputfilter_lizenz')))
                ->setSubPath(rex_path::addon('xoutputfilter', 'pages/info.lizenz.php'))
            );
        }

    });

}

// Wenn aktiv dann über Extensionpoint OUTPUT_FILTER den HTML-Code mit den Ersetzungen manipulieren
if ($this->getConfig('active') == '1') {

    if (rex::isBackend() and rex_plugin::get('xoutputfilter', 'backend')->isAvailable()) {
        rex_extension::register('OUTPUT_FILTER', 'xoutputfilter::backendReplace');
    }

    if (!rex::isBackend()
        and (
            rex_plugin::get('xoutputfilter', 'abbrev')->isAvailable() or
            rex_plugin::get('xoutputfilter', 'languages')->isAvailable() or
            rex_plugin::get('xoutputfilter', 'frontend')->isAvailable()
            )
       ) {
        rex_extension::register('OUTPUT_FILTER', 'xoutputfilter::frontendReplace');
    }

} else {
    // Im Backend einen Hinweis ausgeben dass XOutputFilter nicht aktiv ist
    if (rex::isBackend()) {
        $bp = rex_be_controller::getCurrentPagePart(1);
        if ($bp == 'system' or $bp == 'xoutputfilter') {
            rex_view::addCssFile($this->getAssetsUrl('css/xoutputfilter.css'));
            rex_view::addJsFile($this->getAssetsUrl('js/xoutputfilter.js'));
            rex_extension::register('OUTPUT_FILTER', function(rex_extension_point $ep){
                $suchmuster = '</body>';
                $ersetzen = '<script>displayGrowl("'.$this->i18n('xoutputfilter_notactive').'", 1500, "alert-warning");</script></body>';
                $ep->setSubject(str_replace($suchmuster, $ersetzen, $ep->getSubject()));
            });
        }
	}
}
