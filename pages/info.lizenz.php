<?php

$file = rex_file::get(rex_path::addon('xoutputfilter', 'LICENSE.md'));

if (class_exists('Parsedown')) {
    $Parsedown = new Parsedown();
    $text = $Parsedown->text($file);
} else {
    $text = nl2br($file);
}

$content =  '<div id="xoutputfilter">' . $text . '</div>';

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('xoutputfilter_title_lizenz'));
$fragment->setVar('body', $content, false);
echo $fragment->parse('core/page/section.php');
