<?php
function xoutputfilterAbbrevCheckDup($value, $lang = '')
{
    $table = rex::getTable('xoutputfilter');
    $oid = rex_request('oid', 'int', '');

    $clang = (int)str_replace('clang', '', rex_be_controller::getCurrentPagePart(3));
    $clang = rex_clang::exists($clang) ? $clang : rex_clang::getStartId();
    if ($lang <> '') {
        $clang = $lang;
    }

    $sql = rex_sql::factory();

    $whereParams = ['typ' => '2', 'marker' => $value, 'lang' => $clang, 'id' => $oid];

    $sql->setWhere('`typ` = :typ AND `marker` = :marker AND `lang` = :lang AND `id` <> :id', $whereParams);
    $sql->setTable($table);
    $sql->select('id');
    if ($sql->getRows() == 0) {
        return true;
    }

    return false;
}

$info = '';
$error = '';
$message = '';

$table = rex::getTable('xoutputfilter');

$page = rex_request('page', 'string', '');
$func = rex_request('func', 'string', '');
$xfunc = rex_request('xfunc', 'string', '');
$oid = rex_request('oid', 'int', -1);
$filter = rex_request('filter', 'int', 0);
$clang = (int)str_replace('clang', '', rex_be_controller::getCurrentPagePart(3));
$clang = rex_clang::exists($clang) ? $clang : rex_clang::getStartId();
rex_clang::setCurrentId($clang);
$formsubmit = rex_request('formsubmit', 'string', '');

$Values = array();

// Filter setzen
if ($filter === 1)
{
    $_SESSION['xoutputfilter']['abbrev']['filter1'] = trim(rex_request('filter1', 'string', ''));
    $_SESSION['xoutputfilter']['abbrev']['filter2'] = trim(rex_request('filter2', 'string', ''));
}
if (!isset($_SESSION['xoutputfilter']['abbrev']['filter1']))
{
    $_SESSION['xoutputfilter']['abbrev']['filter1'] = '';
    $_SESSION['xoutputfilter']['abbrev']['filter2'] = '';
}
?>

<div class="panel panel-default">
<div class="panel-body xoutputfilter-filter">
    <form action="<?php echo rex_url::currentBackendPage(); ?>" method="post" class="form-inline">
    <input type="hidden" name="filter" value="1" />
    <input type="hidden" name="clang" value="<?php echo $clang; ?>" />
    <div class="form-group">
        <label for="rex_420_xoutputfilter_filter1"><?php echo $this->i18n('xoutputfilter_abbrev_label_filter1'); ?></label>
        <input id="rex_420_xoutputfilter_filter1" class="rex-form-text form-control filter-text" type="text" name="filter1" value="<?php echo $_SESSION['xoutputfilter']['abbrev']['filter1']; ?>" />
    </div>
    <div class="form-group">
        <label for="rex_420_xoutputfilter_filter2"><?php echo $this->i18n('xoutputfilter_abbrev_label_filter2'); ?></label>
        <input id="rex_420_xoutputfilter_filter2" class="rex-form-text form-control filter-text" type="text" name="filter2" value="<?php echo $_SESSION['xoutputfilter']['abbrev']['filter2']; ?>" />
    </div>
    <button type="submit" class="btn btn-default " name="rex_420_filter"><i class="rex-icon fa-search"></i>&nbsp;<?php echo $this->i18n('xoutputfilter_abbrev_button_filter'); ?></button>
    </form>
</div>
</div>

<?php
// Update Value (ajax bei contenteditable=true)
if ($func == 'setvalue')
{
    while (ob_get_level())
        ob_end_clean();

    $rc = array();
    $rc['id'] = rex_request('id', 'int', 0);
    $rc['field'] = trim(rex_request('field', 'string', ''));
    $rc['lang'] = rex_request('lang', 'int', 0);
    $rc['value'] = trim(rex_request('value', 'string', ''));
    $rc['value'] = trim(trim($rc['value'], chr(0xC2).chr(0xA0)));
    $rc['oldvalue'] = trim(rex_request('oldvalue', 'string', ''));

    if ($rc['value'] == '') {
        $rc['value'] = $rc['oldvalue'];
    }

    if ($rc['value'] <> $rc['oldvalue']) {
        if ($rc['field'] == 'marker') {
            if ($rc['value'] <> $rc['oldvalue']) {
                if (!xoutputfilterAbbrevCheckDup($rc['value'], $rc['lang'])) {
                    $rc['error'] = 1;
                    $rc['msg'] = '[' . $rc['value'] . '] - ' . $this->i18n('xoutputfilter_abbrev_error_exists');
                    echo json_encode($rc);
                    exit;
                }
            }
        }

        $sql = rex_sql::factory();

        if ($rc['field'] == 'marker' and rex_addon::get('xoutputfilter')->getConfig('syncabbrev')) {
            $whereParams = ['typ' => '2', 'oldmarker' => $rc['oldvalue']];
            $sql->setWhere('`typ` = :typ AND `marker` = :oldmarker', $whereParams);
        } else {
            $whereParams = ['typ' => '2', 'id' => $rc['id'], 'lang' => $rc['lang']];
            $sql->setWhere('`typ` = :typ AND `id` = :id AND `lang` = :lang', $whereParams);
        }

        try {
            $sql->setValue($rc['field'], $rc['value']);
            $sql->setTable($table);
            $sql->update();
            if ($sql->getRows() >= 1) {
                $rc['error'] = 0;
                $rc['msg'] = '';
            } else {
                $rc['error'] = 1;
                $rc['msg'] = '[' . $rc['value'] . '] - ' . $this->i18n('xoutputfilter_abbrev_error_update');
            }
        } catch (rex_sql_exception $e) {
            $rc['error'] = 1;
            $rc['msg'] = $e->getMessage();
        }
    } else {
        $rc['error'] = 0;
        $rc['msg'] = '';
    }

    header('Content-Type: application/json');
    echo json_encode($rc);

    exit;
}

// aktivieren/deaktivieren per Ajax
if ($func == 'togglestatus')
{
    while (ob_get_level())
        ob_end_clean();

    $sql = rex_sql::factory();

    $rc = array();
    $rc['error'] = 0;
    $rc['value'] = '';
    $rc['msg'] = '';
    $rc['href'] = trim(rex_request('href', 'string', ''));
    $rc['oldvalue'] = trim(rex_request('oldvalue', 'string', ''));

    $oldstatus = rex_request('oldstatus', 'int');
    $status = (rex_request('oldstatus', 'int') + 1) % 2;

    if ($status == 0) {
        $rc['value'] = '<span class=rex-offline><i class="rex-icon rex-icon-active-false"></i> '.$this->i18n('xoutputfilter_deactivated').'</span>';
    } else {
        $rc['value'] = '<span class=rex-online><i class="rex-icon rex-icon-active-true"></i> '.$this->i18n('xoutputfilter_activated').'</span>';
    }
    $rc['href'] = str_replace('&oldstatus='.$oldstatus, '&oldstatus='.$status, $rc['href']);

    try {
        if (!rex_addon::get('xoutputfilter')->getConfig('syncabbrev')) {
            $sql->setValue('active', $status);
            $sql->setWhere(['typ' => '2', 'id' => $oid]);
            $sql->setTable($table);
            $sql->update();
        } else {
            $sql->setWhere(['typ' => '2', 'id' => $oid]);
            $sql->setTable($table);
            $sql->select('`marker`');
            if ($sql->getRows() == 1) {
                $marker = $sql->getValue('marker');
                $sql->setValue('active', $status);
                $sql->setWhere(['typ' => '2', 'marker' => $marker]);
                $sql->setTable($table);
                $sql->update();
            }
        }
    } catch (rex_sql_exception $e) {
        $rc['error'] = 1;
        $rc['msg'] = $e->getMessage();
    }

    header('Content-Type: application/json');
    echo json_encode($rc);

    exit;
}

// Abbruch
if (trim(rex_request('abort', 'string', '')) == '1') {
    $xfunc = '';
    $func = '';
    $info = $this->i18n('xoutputfilter_msg_cancel');
}

// Löschen
if (trim(rex_request('delete', 'string', '')) == '1') {
    $xfunc = '';
    $func = 'delete';
}

// Formular abgeschickt, Speichern, übernehmen oder löschen
if ($xfunc <> '') {
    $func = $xfunc;

    $sql = rex_sql::factory();

    // Formularwerte übernehmen
    $Values['marker'] = trim(rex_request('marker', 'string', ''));
    $Values['html'] = rex_request('html', 'string', '');
    $Values['active'] = rex_request('active', 'string', '');
    $Values['excludeids'] = rex_request('excludeids', 'string', '');
    $oldmarker = htmlspecialchars_decode(rex_request('oldmarker', 'string', ''));

    // Feldprüfungen
    if ($Values['marker'] <> $oldmarker) {
        if (!xoutputfilterAbbrevCheckDup($Values['marker'])) {
            $error .= $this->i18n('xoutputfilter_abbrev_error_exists') . '<br>';
        }
    }
    if ($Values['marker'] == '') {
        $error .= $this->i18n('xoutputfilter_abbrev_error_no_name'). '<br>';
    }
    if ($Values['html'] == '') {
        $error .= $this->i18n('xoutputfilter_abbrev_error_no_html'). '<br>';
    }

    if (!$error) {

        // speichern / insert
        if ((trim(rex_request('save', 'string', '')) == '1') and ($oid == -1)) {
            try {
                $sql->setValue('id', null);
                $sql->setValue('typ', '2');
                $sql->setValue('lang', $clang);
                foreach ($Values as $fieldname => $value) {
                    $sql->setValue($fieldname, $value);
                }
                $sql->setTable($table);
                $sql->insert();
                $formsubmit = '';
                $func = '';
                $message = $this->i18n('xoutputfilter_msg_saved');
            } catch (rex_sql_exception $e) {
                $error = $this->i18n('xoutputfilter_abbrev_error_update');
            }
        // übernehmen / update
        } else {
            try {
                foreach ($Values as $fieldname => $value) {
                    $sql->setValue($fieldname, $value);
                }
                $sql->setWhere( ['typ' => '2', 'lang' => $clang, 'id' => $oid] );
                $sql->setTable($table);
                $sql->update();

                if (rex_addon::get('xoutputfilter')->getConfig('syncabbrev')) {
                    unset($Values['html']);
                    foreach ($Values as $fieldname => $value) {
                        $sql->setValue($fieldname, $value);
                    }
                    $whereParams = ['typ' => '2', 'oldmarker' => $oldmarker];
                    $sql->setWhere('`typ` = :typ AND `marker` = :oldmarker', $whereParams);
                    $sql->setTable($table);
                    $sql->update();
                }

                $formsubmit = '';
                if ((trim(rex_request('save', 'string', '')) == '1') or ($oid == -1)) {
                    $func = '';
                    $message = $this->i18n('xoutputfilter_msg_saved');
                } else {
                    $message = $this->i18n('xoutputfilter_msg_update');
                }
            } catch (rex_sql_exception $e) {
                $error = $this->i18n('xoutputfilter_abbrev_error_update');
            }
        }

    }
}

// Hinzufügen / Bearbeiten
if ($func == 'add' || $func == 'edit')
{
    $oldmarker = htmlspecialchars_decode(rex_request('oldmarker', 'string', ''));

    if ($formsubmit <> 'true') {
        if ($func == 'add' and $oid == -1) {
            $Values['marker'] = '';
            $Values['html'] = '';
            $Values['active'] = '';
            $Values['excludeids'] = '';
        }

        if ($func == 'edit' and $oid <> -1) {
            $sql = rex_sql::factory();

            $sql->setWhere(['typ' => '2', 'id' => $oid]);
            $sql->setTable($table);
            $sql->select('`marker`, `html`, `active`, `excludeids`');

            if ($sql->getRows() == 1) {
                $marker = $sql->getValue('marker');
                $oldmarker = $sql->getValue('marker');
                $Values['marker'] = $sql->getValue('marker');
                $Values['html'] = $sql->getValue('html');
                $Values['active'] = $sql->getValue('active');
                $Values['excludeids'] = $sql->getValue('excludeids');
            }
        }
    }

    $content = '';

    $fieldset = $func == 'edit' ? $this->i18n('xoutputfilter_abbrev_title_edit') : $this->i18n('xoutputfilter_abbrev_title_add');

    // Abkürzung
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="marker">' . htmlspecialchars_decode($this->i18n('xoutputfilter_abbrev_label_marker')) . '</label>';
    $n['field'] = '<input class="form-control" type="text" id="marker" name="marker" value="' . htmlspecialchars($Values['marker']) . '" />';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/container.php');

    // Hinweistext
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="html">' . htmlspecialchars_decode($this->i18n('xoutputfilter_abbrev_label_html')) . '</label>';
    $n['field'] = '<textarea class="form-control" type="text" id="html" name="html" rows="3">'.htmlspecialchars($Values['html']).'</textarea>';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/container.php');

    // aktiviert
    $formElements = [];
    $n = [];

    $attributes = array();
    $attributes['id'] = 'active';
    $attributes['name'] = 'active';
    $attributes['class'] = 'form-control';
    $field = new rex_form_select_element('select', null, $attributes);

    $field->setLabel(htmlspecialchars_decode($this->i18n('xoutputfilter_abbrev_label_active')));
    $select = $field->getSelect();
    $select->addOption($this->i18n('yes'), 1);
    $select->addOption($this->i18n('no'), 0);
    $select->setSize(1);
    $select->setSelected($Values['active']);
    $n['field'] = $field->get();
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/container.php');

    // Exclude Seiten-Id's
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="REX_LINKLIST_SELECT_1">' . htmlspecialchars_decode($this->i18n('xoutputfilter_abbrev_label_excludeids')) . '</label>';
    $n['field'] = rex_var_linklist::getWidget(1, 'excludeids', $Values['excludeids']);
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/container.php');

    // Buttons
    $formElements = [];

    $n = [];
    $n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="save" value="1">' . $this->i18n('xoutputfilter_button_save') . '</button>';
    $formElements[] = $n;

    if ($func == 'edit') {
        $n = [];
        $n['field'] = '<button class="btn btn-apply" type="submit" name="apply" value="1">' . $this->i18n('xoutputfilter_button_apply') . '</button>';
        $formElements[] = $n;

        $n = [];
        $n['field'] = '<button class="btn btn-delete" type="submit" name="delete" value="1" data-confirm="[' . htmlspecialchars($Values['marker']) . '] - ' . $this->i18n('xoutputfilter_abbrev_really_delete'). '">' . $this->i18n('xoutputfilter_button_delete') . '</button>';
        $formElements[] = $n;
    }

    $n = [];
    $n['field'] = '<button class="btn btn-abort" type="submit" name="abort" value="1">' . $this->i18n('xoutputfilter_button_abort') . '</button>';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $buttons = $fragment->parse('core/form/submit.php');

    if ($message) {
        echo rex_view::success($message);
    }
    if ($error) {
        echo rex_view::error($error);
    }

    // Forumular
    $fragment = new rex_fragment();
    $fragment->setVar('class', 'edit', false);
    $fragment->setVar('title', $fieldset);
    $fragment->setVar('body', $content, false);
    $fragment->setVar('buttons', $buttons, false);
    $content = $fragment->parse('core/page/section.php');


$content = '
<form action="' . rex_url::currentBackendPage() . '" method="post">
<input type="hidden" name="xfunc" value="' . $func . '" />
<input type="hidden" name="oid" value="' . $oid . '" />
<input type="hidden" name="oldmarker" value="' . htmlspecialchars($oldmarker) . '" />
<input type="hidden" name="func" value="" />
<input type="hidden" name="formsubmit" value="true" />
    ' . $content . '
</form>
';

    echo $content;
}

// Löschen
if ($func == 'delete')
{
    $sql = rex_sql::factory();

    if (!rex_addon::get('xoutputfilter')->getConfig('syncabbrev')) {
        $sql->setWhere(['typ' => '2', 'id' => $oid]);
        $sql->setTable($table);
        $sql->delete();
    } else {
        $sql->setWhere(['typ' => '2', 'id' => $oid]);
        $sql->setTable($table);
        $sql->select('`marker`');
        if ($sql->getRows() == 1) {
            $marker = $sql->getValue('marker');
            $sql->setWhere(['typ' => '2', 'marker' => $marker]);
            $sql->setTable($table);
            $sql->delete();
        }
    }
    echo rex_view::success($this->i18n('xoutputfilter_abbrev_deleted'));
    $func = '';
}

// aktivieren/deaktivieren
if ($func == 'setstatus')
{
    $sql = rex_sql::factory();

    $status = (rex_request('oldstatus', 'int') + 1) % 2;

    if (!rex_addon::get('xoutputfilter')->getConfig('syncabbrev')) {
        $sql->setValue('active', $status);
        $sql->setWhere(['typ' => '2', 'id' => $oid]);
        $sql->setTable($table);
        $sql->update();
    } else {
        $sql->setWhere(['typ' => '2', 'id' => $oid]);
        $sql->setTable($table);
        $sql->select('`marker`');
        if ($sql->getRows() == 1) {
            $marker = $sql->getValue('marker');
            $sql->setValue('active', $status);
            $sql->setWhere(['typ' => '2', 'marker' => $marker]);
            $sql->setTable($table);
            $sql->update();
        }
    }
    $msg = $status == 1 ? 'xoutputfilter_abbrev_status_activated' : 'xoutputfilter_abbrev_status_deactivated';
    echo rex_view::success($this->i18n($msg));
    $func = '';
}

// Liste ausgeben
if ($func == '')
{

    if ($error) {
        echo rex_view::error($error);
    }
    if ($info) {
        echo rex_view::info($info);
    }
    if ($message) {
        echo rex_view::success($message);
    }

    xoutputfilter_util::syncAbbrev();

    // SQL inkl. Filter
    $sqlfilter = '';
    if (isset($_SESSION['xoutputfilter']['abbrev']['filter1']) and $_SESSION['xoutputfilter']['abbrev']['filter1'] <> '')
    {
        $sqlfilter .= ' AND `marker` like \'%'.$_SESSION['xoutputfilter']['abbrev']['filter1'].'%\' ';
    }
    if (isset($_SESSION['xoutputfilter']['abbrev']['filter2']) and $_SESSION['xoutputfilter']['abbrev']['filter2'] <> '')
    {
        $sqlfilter .= ' AND `html` like \'%'.$_SESSION['xoutputfilter']['abbrev']['filter2'].'%\' ';
    }
    $sql = 'SELECT `id`, `marker`, `html`, `active`, `lang`, `excludeids` FROM ' . $table . ' WHERE `typ` = \'2\' AND `lang` = ' . $clang . $sqlfilter . ' ORDER BY `marker` ASC ';

    $list = rex_list::factory($sql, 30, 'abbrev', 0);

    $list->setNoRowsMessage($this->i18n('xoutputfilter_abbrev_nodata'));

    $list->addTableAttribute('class', 'table-striped table-hover xoutputfilter');

    $list->addParam('clang', $clang);

    $list->addTableColumnGroup(array(20, 150, '*', 20, 20, 20, 20));

    $tdIcon = '<i class="rex-icon rex-icon-edit" title="' . $this->i18n('edit') . '"></i>';
    $thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '" ' . rex::getAccesskey($this->i18n('abbrev_add'), 'add') . ' title="' . $this->i18n('abbrev_add') . '"><i class="rex-icon rex-icon-add"></i></a>';
    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'oid' => '###id###']);

    $list->removeColumn('id');
    $list->removeColumn('lang');
    $list->removeColumn('active');
    $list->removeColumn('excludeids');

    $list->setColumnLabel('marker', $this->i18n('xoutputfilter_abbrev_id_header'));
    $list->setColumnLayout('marker', ['<th>###VALUE###</th>', '<td contenteditable="true" data-title="###LABEL###" data-id="###id###" data-field="marker" data-lang="###lang###" data-oldvalue="">###VALUE###</td>']);

    $list->setColumnLabel('html', $this->i18n('xoutputfilter_abbrev_html_header'));
    $list->setColumnLayout('html', ['<th>###VALUE###</th>', '<td contenteditable="true" data-title="###LABEL###" data-id="###id###" data-field="html" data-lang="###lang###" data-oldvalue="">###VALUE###</td>']);

    $list->addColumn('info', '', -1, ['<th>###VALUE###</th>', '<td>###VALUE###</td>']);
    $list->setColumnLabel('info', $this->i18n('xoutputfilter_info_header'));
    $list->setColumnFormat('info', 'custom', function($params) {
        $list = $params['list'];
        if ($list->getValue('excludeids') <> '') {
            $title = $this->i18n('xoutputfilter_info_tx_excludeids') . ' ' . $list->getValue('excludeids');
            $str = '<i class="rex-icon fa-cogs" data-toggle="tooltip" data-placement="left" title="' . htmlspecialchars($title) . '"></i>';
        } else {
            $str = '';
        }
        return $str;
    });

    $list->addColumn('func', '', -1, ['<th colspan="3">###VALUE###</th>', '<td nowrap="nowrap">###VALUE###</td >']);
    $list->setColumnLabel('func', $this->i18n('xoutputfilter_func_header'));
    $list->setColumnFormat('func', 'custom', function($params) {
        $list = $params['list'];
        $list->addLinkAttribute('active', 'class', 'toggle');

        if ($list->getValue('active') == 1) {
            $list->setColumnParams('active', ['func' => 'setstatus', 'oldstatus' => '###active###', 'oid' => '###id###']);
            $str = $list->getColumnLink('active', '<span class="rex-online"><i class="rex-icon rex-icon-active-true"></i> ' . $this->i18n('xoutputfilter_activated') . '</span>');
        } else {
            $list->setColumnParams('active', ['func' => 'setstatus', 'oldstatus' => '###active###', 'oid' => '###id###']);
            $str = $list->getColumnLink('active', '<span class="rex-offline"><i class="rex-icon rex-icon-active-false"></i> ' . $this->i18n('xoutputfilter_deactivated') . '</span>');
        }
        $str .= '</td>';

        $str .= '<td nowrap="nowrap">';
        $list->setColumnParams('edit', ['func' => 'edit', 'oid' => '###id###']);
        $str .= $list->getColumnLink('edit', '<i class="rex-icon rex-icon-edit"></i> ' . $this->i18n('edit') . '');
        $str .= '</td>';

        $str .= '<td nowrap="nowrap">';
        $list->setColumnParams('delete', ['func' => 'delete', 'oid' => '###id###']);
        $list->addLinkAttribute('delete', 'data-confirm', '[###marker###] - ' . $this->i18n('xoutputfilter_abbrev_really_delete'));
        $str .= $list->getColumnLink('delete', '<i class="rex-icon rex-icon-delete"></i> ' . $this->i18n('delete') . '');
        return $str;
    });

    $content = $list->get();

    $fragment = new rex_fragment();
    $fragment->setVar('title', $this->i18n('xoutputfilter_abbrev_table_header'), false);
    $fragment->setVar('content', $content, false);
    echo $fragment->parse('core/page/section.php');
}
