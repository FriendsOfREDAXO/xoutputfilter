<?php

// XOutputFilter
$file = rex_file::get(rex_path::addon('xoutputfilter', 'help.php'));
ob_start(); eval('?>'.$file.'<?php;'); $file = ob_get_contents(); ob_end_clean();
$content = '<div id="xoutputfilter">' . $file . '</div>';

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('xoutputfilter_title_hilfe'));
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');

// Plugins
$plugins = array('abbrev', 'backend', 'frontend', 'import_export', 'languages');

foreach ($plugins as $plugin) {
    if (rex_plugin::get('xoutputfilter', $plugin)->isAvailable()) {
        $file = rex_file::get(rex_path::addon('xoutputfilter/plugins/' . $plugin, 'help.php'));
        ob_start(); eval('?>'.$file.'<?php;'); $file = ob_get_contents(); ob_end_clean();
        $content = '<div id="xoutputfilter">' . $file . '</div>';

        $fragment = new rex_fragment();
        $fragment->setVar('title', 'Plugin ' . $plugin);
        $fragment->setVar('body', $content, false);
        echo $fragment->parse('core/page/section.php');
    }
}
