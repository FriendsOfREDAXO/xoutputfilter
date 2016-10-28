<?php

if (rex::isBackend() && is_object(rex::getUser())) {

    // Addonrechte (permissions) registieren
    rex_perm::register('xoutputfilter[backend]');

}
