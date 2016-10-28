<?php

if (rex::isBackend() && is_object(rex::getUser())) {

    // Addonrechte (permissions) registieren
    rex_perm::register('xoutputfilter[import_export]');

    // Subpages in Addon-Navigation einfügen
    rex_extension::register('PAGES_PREPARED', function() {

        if (rex::getUser()->hasPerm('xoutputfilter[import_export]')) {

            $page = rex_be_controller::getPageObject('xoutputfilter/import_export');

            if (is_object($page)) {
                $page->addSubpage((new rex_be_page('export', $this->i18n('xoutputfilter_export')))
                    ->setSubPath(rex_path::addon('xoutputfilter', 'plugins/import_export/pages/export.php'))
                );

                $page->addSubpage((new rex_be_page('import', $this->i18n('xoutputfilter_import')))
                    ->setSubPath(rex_path::addon('xoutputfilter', 'plugins/import_export/pages/import.php'))
                );
            }
        }

    });

}
