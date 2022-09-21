<?php

if (rex::isBackend() && is_object(rex::getUser())) {

    // Addonrechte (permissions) registieren
    rex_perm::register('xoutputfilter[abbrev]');

    // Sprachen als Subnavigation einfügen
    rex_extension::register('PAGES_PREPARED', function () {

        $clang_id = str_replace('clang', '', rex_be_controller::getCurrentPagePart(3, ''));
        $page = rex_be_controller::getPageObject('xoutputfilter/abbrev');

        if (is_object($page) and rex_clang::count() > 1) {
            foreach (rex_clang::getAll() as $id => $clang) {
                if (rex::getUser()->getComplexPerm('clang')->hasPerm($id)) {
                    $page->addSubpage((new rex_be_page('clang' . $id, $clang->getName()))
                        ->setSubPath(rex_path::addon('xoutputfilter/plugins/abbrev', 'pages/index.php'))
                        ->setIsActive($id == $clang_id)
                    );
                }
            }
        }
    });

}
