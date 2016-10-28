<?php
$content = '';
$buttons = '';

$func = rex_request('func', 'string');

// Konfiguration speichern
if ($func == 'update') {

    $this->setConfig(rex_post('settings', [
        ['active', 'string'],
        ['runtimeinfo', 'string'],
        ['syslog', 'string'],
        ['codemirror', 'string'],
        ['sessioncache', 'string'],
        ['altmenuentry', 'string'],
        ['excludecats', 'array'],
        ['excludeids', 'string'],
        ['excludepages', 'string'],
        ['showalllangs', 'string'],
        ['tagopen', 'string'],
        ['tagclose', 'string'],
        ['syncabbrev', 'string'],
        ['syncfrontend', 'string'],
        ['scanmarker', 'string']
    ]));

    echo rex_view::success($this->i18n('xoutputfilter_config_saved'));
}

// Config-Werte bereitstellen
$Values = array();
$Values['active'] = $this->getConfig('active');
$Values['runtimeinfo'] = $this->getConfig('runtimeinfo');
$Values['syslog'] = $this->getConfig('syslog');
$Values['codemirror'] = $this->getConfig('codemirror');
$Values['sessioncache'] = $this->getConfig('sessioncache');
$Values['altmenuentry'] = $this->getConfig('altmenuentry');
$Values['excludecats'] = $this->getConfig('excludecats');
$Values['excludeids'] = $this->getConfig('excludeids');
$Values['excludepages'] = $this->getConfig('excludepages');
$Values['showalllangs'] = $this->getConfig('showalllangs');
$Values['tagopen'] = $this->getConfig('tagopen');
$Values['tagclose'] = $this->getConfig('tagclose');
$Values['syncabbrev'] = $this->getConfig('syncabbrev');
$Values['syncfrontend'] = $this->getConfig('syncfrontend');
$Values['scanmarker'] = $this->getConfig('scanmarker');

// Defaultwerte für öffnendes und schliessendes Tag
if ($Values['tagopen'] == '' and $Values['tagclose'] == '') {
    $Values['tagopen'] = '{{';
    $Values['tagclose'] = '}}';
    $this->setConfig('tagopen', '{{');
    $this->setConfig('tagclose', '}}');
}

$content .= '<fieldset><legend>' . $this->i18n('xoutputfilter_config_title') . '</legend>';

// Checkbox XOutputFilter aktiv
$formElements = [];
$n = [];
$n['label'] = '<label for="active">' . htmlspecialchars_decode($this->i18n('xoutputfilter_config_active')) . '</label>';
$n['field'] = '<input type="checkbox" id="active" name="settings[active]"' . (!empty($Values['active']) && $Values['active'] == '1' ? ' checked="checked"' : '') . ' value="1" />';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/checkbox.php');

// Checkbox Ausgabe Laufzeitinformationen
$formElements = [];
$n = [];
$n['label'] = '<label for="runtimeinfo">' . htmlspecialchars_decode($this->i18n('xoutputfilter_config_runtimeinfo')) . '</label>';
$n['field'] = '<input type="checkbox" id="runtimeinfo" name="settings[runtimeinfo]"' . (!empty($Values['runtimeinfo']) && $Values['runtimeinfo'] == '1' ? ' checked="checked"' : '') . ' value="1" />';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/checkbox.php');

// Checkbox Ausgabe Syslog
$formElements = [];
$n = [];
$n['label'] = '<label for="syslog">' . htmlspecialchars_decode($this->i18n('xoutputfilter_config_syslog')) . '</label>';
$n['field'] = '<input type="checkbox" id="syslog" name="settings[syslog]"' . (!empty($Values['syslog']) && $Values['syslog'] == '1' ? ' checked="checked"' : '') . ' value="1" />';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/checkbox.php');

// Checkbox Codemirror
if (rex_plugin::get('be_style', 'customizer')->isAvailable() and rex_plugin::get('be_style', 'customizer')->getConfig('codemirror')) {
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="codemirror">' . htmlspecialchars_decode($this->i18n('xoutputfilter_config_codemirror')) . '</label>';
    $n['field'] = '<input type="checkbox" id="codemirror" name="settings[codemirror]"' . (!empty($Values['codemirror']) && $Values['codemirror'] == '1' ? ' checked="checked"' : '') . ' value="1" />';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/checkbox.php');
}

// Checkbox Sessioncache
$formElements = [];
$n = [];
$n['label'] = '<label for="sessioncache">' . htmlspecialchars_decode($this->i18n('xoutputfilter_config_sessioncache')) . '</label>';
$n['field'] = '<input type="checkbox" id="sessioncache" name="settings[sessioncache]"' . (!empty($Values['sessioncache']) && $Values['sessioncache'] == '1' ? ' checked="checked"' : '') . ' value="1" />';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/checkbox.php');

// Alternativer Menüeintrag
$formElements = [];
$n = [];
$n['label'] = '<label for="altmenuentry">' . htmlspecialchars_decode($this->i18n('config_altmenuentry')) . '</label>';
$n['field'] = '<input class="form-control" type="text" id="altmenuentry" name="settings[altmenuentry]" value="' . $Values['altmenuentry'] . '" />';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

$content .= '</fieldset>';

$content .= '<fieldset><legend>' . $this->i18n('xoutputfilter_config_titleexclude') . '</legend>';

// Exclude Kategorien
$formElements = [];
$n = [];
$n['label'] = '<label for="excludecats">' . htmlspecialchars_decode($this->i18n('xoutputfilter_config_excludecats')) . '</label>';

$category_select = new rex_category_select(false, false, false, true);
$category_select->setName('settings[excludecats][]');
$category_select->setId('excludecats');
$category_select->setSize('10');
$category_select->setMultiple(true);
$category_select->setAttribute('style', 'width:100%');
$category_select->setSelected($Values['excludecats']);

$n['field'] = $category_select->get();
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');	

// Exclude Seiten-Id's
$formElements = [];
$n = [];
$n['label'] = '<label for="REX_LINKLIST_SELECT_1">' . htmlspecialchars_decode($this->i18n('xoutputfilter_config_excludeids')) . '</label>';
$n['field'] = rex_var_linklist::getWidget(1, 'settings[excludeids]', $Values['excludeids']);
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

// Exclude Backend-Pages
$formElements = [];
$n = [];
$n['label'] = '<label for="excludepages">' . htmlspecialchars_decode($this->i18n('xoutputfilter_config_excludepages')) . '</label>';
$n['field'] = '<input class="form-control" type="text" id="excludepages" name="settings[excludepages]" value="' . $Values['excludepages'] . '" />';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

$content .= '</fieldset>';

$content .= '<fieldset><legend>' . $this->i18n('xoutputfilter_config_titlemarker') . '</legend>';

// Checkbox alle Sprachen anzeigen
$formElements = [];
$n = [];
$n['label'] = '<label for="showalllangs">' . htmlspecialchars_decode($this->i18n('xoutputfilter_config_showalllangs')) . '</label>';
$n['field'] = '<input type="checkbox" id="showalllangs" name="settings[showalllangs]"' . (!empty($Values['showalllangs']) && $Values['showalllangs'] == '1' ? ' checked="checked"' : '') . ' value="1" />';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/checkbox.php');

// Open/Close-Tag
$panelElements = '';
$formElements = [];
$n = [];
$n['header'] = '<div class="row"><div class="col-md-5">';
$n['footer'] = '</div></div>';
$n['label'] = '<label for="tagopen">' . htmlspecialchars_decode($this->i18n('xoutputfilter_config_tags')) . '</label>';
$n['field'] = '
    <div class="input-group">
        <input class="form-control text-right" type="text" id="tagopen" name="settings[tagopen]" value="' . $Values['tagopen'] . '" style="width:4.0em;" />
        <span class="input-group-addon">' . $this->i18n('xoutputfilter_config_placeholder') . '</span>
        <input class="form-control" type="text" id="tagclose" name="settings[tagclose]" value="' . $Values['tagclose'] . '" style="width:4.0em;" />
    </div>';
$formElements[] = $n;
$fragment = new \rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/container.php');

$content .= '</fieldset>';

$content .= '<fieldset><legend>' . $this->i18n('xoutputfilter_config_titlesync') . '</legend>';

// Checkbox Abkürzungen synchronisieren
if (rex_plugin::get('xoutputfilter', 'abbrev')->isAvailable()) {
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="syncabbrev">' . htmlspecialchars_decode($this->i18n('xoutputfilter_config_syncabbrev')) . '</label>';
    $n['field'] = '<input type="checkbox" id="syncabbrev" name="settings[syncabbrev]"' . (!empty($Values['syncabbrev']) && $Values['syncabbrev'] == '1' ? ' checked="checked"' : '') . ' value="1" />';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/checkbox.php');
}

// Checkbox Frontend synchronisieren
if (rex_plugin::get('xoutputfilter', 'frontend')->isAvailable()) {
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="syncfrontend">' . htmlspecialchars_decode($this->i18n('xoutputfilter_config_syncfrontend')) . '</label>';
    $n['field'] = '<input type="checkbox" id="syncfrontend" name="settings[syncfrontend]"' . (!empty($Values['syncfrontend']) && $Values['syncfrontend'] == '1' ? ' checked="checked"' : '') . ' value="1" />';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/checkbox.php');
}

// Checkbox Marker suchen
$formElements = [];
$n = [];
$n['label'] = '<label for="scanmarker">' . htmlspecialchars_decode($this->i18n('xoutputfilter_config_scanmarker')) . '</label>';
$n['field'] = '<input type="checkbox" id="scanmarker" name="settings[scanmarker]"' . (!empty($Values['scanmarker']) && $Values['scanmarker'] == '1' ? ' checked="checked"' : '') . ' value="1" />';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/checkbox.php');

$content .= '</fieldset>';

// Save-Button
$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="save" value="' . $this->i18n('save') . '">' . $this->i18n('save') . '</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');

// Ausgabe Section
$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('title_config'), false);
$fragment->setVar('class', 'edit', false);
$fragment->setVar('body', $content, false);
$fragment->setVar('buttons', $buttons, false);
$content = $fragment->parse('core/page/section.php');

$content = '
<form action="' . rex_url::currentBackendPage() . '" method="post">
<input type="hidden" name="func" value="update" />
    ' . $content . '
</form>
';

echo $content;
