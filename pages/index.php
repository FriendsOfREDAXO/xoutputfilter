<?php

// Titel ausgeben
// evtl. alternativer Menüeintrag
if (!rex::getUser()->isAdmin() and rex::getUser()->hasPerm('xoutputfilter[altmenuentry]') and $this->getConfig('altmenuentry')) {
    echo rex_view::title($this->getConfig('altmenuentry'));
}
else {
    echo rex_view::title($this->i18n('xoutputfilter_title'));
}

// Hinweis ausgeben wenn kein Plugin installiert ist
if (
    !rex_plugin::get('xoutputfilter', 'languages')->isAvailable() and
    !rex_plugin::get('xoutputfilter', 'abbrev')->isAvailable() and
    !rex_plugin::get('xoutputfilter', 'frontend')->isAvailable() and
    !rex_plugin::get('xoutputfilter', 'backen')->isAvailable()
    ) {
    echo rex_view::warning($this->i18n('xoutputfilter_no_plugin'));
}

// Session-Cache löschen
unset($_SESSION['xoutputfilter']['@backend']['items']);
unset($_SESSION['xoutputfilter']['@frontend']['items']);

// Include Page
include rex_be_controller::getCurrentPageObject()->getSubPath();
