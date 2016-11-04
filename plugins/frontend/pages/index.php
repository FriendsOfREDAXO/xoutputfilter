<?php
function xoutputfilterFrontendCheckDup($value, $lang = '')
{
    $table = rex::getTable('xoutputfilter');
    $oid = rex_request('oid', 'int', '');

    $clang = (int)str_replace('clang', '', rex_be_controller::getCurrentPagePart(3));
    $clang = rex_clang::exists($clang) ? $clang : rex_clang::getStartId();
    if ($lang <> '') {
        $clang = $lang;
    }

    $sql = rex_sql::factory();

    $whereParams = ['typ' => '4', 'name' => $value, 'lang' => $clang, 'id' => $oid];

    $sql->setWhere('`typ` = :typ AND `name` = :name AND `lang` = :lang AND `id` <> :id', $whereParams);
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
    $_SESSION['xoutputfilter']['frontend']['filter1'] = trim(rex_request('filter1', 'string', ''));
    $_SESSION['xoutputfilter']['frontend']['filter2'] = trim(rex_request('filter2', 'string', ''));
}
if (!isset($_SESSION['xoutputfilter']['frontend']['filter1']))
{
    $_SESSION['xoutputfilter']['frontend']['filter1'] = '';
    $_SESSION['xoutputfilter']['frontend']['filter2'] = '';
}
?>

<div class="panel panel-default">
<div class="panel-body xoutputfilter-filter">
    <form action="<?php echo rex_url::currentBackendPage(); ?>" method="post" class="form-inline">
    <input type="hidden" name="filter" value="1" />
    <input type="hidden" name="clang" value="<?php echo $clang; ?>" />
    <div class="form-group">
        <label for="rex_420_xoutputfilter_filter1"><?php echo $this->i18n('xoutputfilter_frontend_label_filter1'); ?></label>
        <input id="rex_420_xoutputfilter_filter1" class="rex-form-text form-control filter-text" type="text" name="filter1" value="<?php echo $_SESSION['xoutputfilter']['frontend']['filter1']; ?>" />
    </div>    
    <div class="form-group">
        <label for="rex_420_xoutputfilter_filter2"><?php echo $this->i18n('xoutputfilter_frontend_label_filter2'); ?></label>
        <input id="rex_420_xoutputfilter_filter2" class="rex-form-text form-control filter-text" type="text" name="filter2" value="<?php echo $_SESSION['xoutputfilter']['frontend']['filter2']; ?>" />
    </div>    
    <button type="submit" class="btn btn-default" name="rex_420_filter"><i class="rex-icon fa-search"></i>&nbsp;<?php echo $this->i18n('xoutputfilter_frontend_button_filter'); ?></button>
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
        if ($rc['field'] == 'name') {
            if ($rc['value'] <> $rc['oldvalue']) {
                if (!xoutputfilterFrontendCheckDup($rc['value'], $rc['lang'])) {
                    $rc['error'] = 1;
                    $rc['msg'] = '[' . $rc['value'] . '] - ' . $this->i18n('xoutputfilter_frontend_error_exists');
                    echo json_encode($rc);
                    exit;
                }
            }
        }

        $sql = rex_sql::factory();

        if ($rc['field'] == 'name' and rex_addon::get('xoutputfilter')->getConfig('syncfrontend')) {
            $whereParams = ['typ' => '4', 'oldmarker' => $rc['oldvalue']];
            $sql->setWhere('`typ` = :typ AND `name` = :oldmarker', $whereParams);
        } else {
            $whereParams = ['typ' => '4', 'id' => $rc['id'], 'lang' => $rc['lang']];
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
                $rc['msg'] = '[' . $rc['value'] . '] - ' . $this->i18n('xoutputfilter_frontend_error_update');
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
        $status = (rex_request('oldstatus', 'int') + 1) % 2;

        if (!rex_addon::get('xoutputfilter')->getConfig('syncfrontend')) {
            $sql->setValue('active', $status);
            $sql->setWhere(['typ' => '4', 'id' => $oid]);
            $sql->setTable($table);
            $sql->update();
        } else {
            $sql->setWhere(['typ' => '4', 'id' => $oid]);
            $sql->setTable($table);
            $sql->select('`name`');
            if ($sql->getRows() == 1) {
                $name = $sql->getValue('name');
                $sql->setValue('active', $status);
                $sql->setWhere(['typ' => '4', 'name' => $name]);
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
    $Values['name'] = trim(rex_request('name', 'string', ''));
    $Values['description'] = trim(rex_request('description', 'string', ''));
    $Values['active'] = trim(rex_request('active', 'string', ''));
    $Values['insertbefore'] = trim(rex_request('insertbefore', 'string', ''));
    $Values['marker'] = trim(rex_request('marker', 'string', ''));
    $Values['html'] = rex_request('html', 'string', '');
    $Values['categories'] = implode(',', rex_request('categories', 'array', ''));
    $Values['subcats'] = rex_request('subcats', 'string', '');
    $Values['allcats'] = rex_request('allcats', 'string', '');
    $Values['once'] = rex_request('once', 'string', '');
    $Values['excludeids'] = rex_request('excludeids', 'string', '');
    $oldmarker = htmlspecialchars_decode(rex_request('oldmarker', 'string', ''));

    // Feldprüfungen
    if ($Values['name'] <> $oldmarker) {
        if (!xoutputfilterFrontendCheckDup($Values['name'])) {
            $error .= $this->i18n('xoutputfilter_frontend_error_exists') . '<br>';
        }
    }
    if ($Values['name'] == '') {
        $error .= $this->i18n('xoutputfilter_frontend_error_no_name'). '<br>';
    }
    if ($Values['description'] == '') {
        $error .= $this->i18n('xoutputfilter_frontend_error_no_description'). '<br>';
    }
    if ($Values['marker'] == '') {
        $error .= $this->i18n('xoutputfilter_frontend_error_no_marker'). '<br>';
    }

    if (!$error) {

         // speichern / insert
        if ((trim(rex_request('save', 'string', '')) == '1') and ($oid == -1)) {
            try {
                $sql->setValue('id', null);
                $sql->setValue('typ', '4');
                $sql->setValue('lang', $clang);
                foreach ($Values as $fieldname => $value) {
                    $sql->setValue($fieldname, $value);
                }
                $sql->setTable($table)->insert();
                $formsubmit = '';
                $func = '';
                $message = $this->i18n('xoutputfilter_msg_saved');
            } catch (rex_sql_exception $e) {
                $error = $this->i18n('xoutputfilter_frontend_error_update');
            }
        // übernehmen / update
        } else {
            try {
                foreach ($Values as $fieldname => $value) {
                    $sql->setValue($fieldname, $value);
                }
                $sql->setWhere( ['typ' => '4', 'lang' => $clang, 'id' => $oid] );
                $sql->setTable($table);
                $sql->update();

                if (rex_addon::get('xoutputfilter')->getConfig('syncfrontend')) {
                    $sql->setValue('name', $Values['name']);
                    $sql->setValue('active', $Values['active']);
                    $whereParams = ['typ' => '4', 'oldmarker' => $oldmarker];
                    $sql->setWhere('`typ` = :typ AND `name` = :oldmarker', $whereParams);
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
                $error = $this->i18n('xoutputfilter_frontend_error_update');
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
            $Values['name'] = '';
            $Values['description'] = '';
            $Values['active'] = '';
            $Values['insertbefore'] = '';
            $Values['marker'] = '';
            $Values['html'] = '';
            $Values['categories'] = '';
            $Values['subcats'] = '';
            $Values['allcats'] = '';
            $Values['once'] = '';
            $Values['excludeids'] = '';
        }

        if ($func == 'edit' and $oid <> -1) {
            $sql = rex_sql::factory();

            $sql->setWhere(['typ' => '4', 'id' => $oid]);
            $sql->setTable($table);
            $sql->select('`name`, `description`, `active`, `insertbefore`, `marker`, `html`, `active`, `categories`, `subcats`, `allcats`, `once`, `excludeids`');

            if ($sql->getRows() == 1) {
                $name = $sql->getValue('name');
                $oldmarker = $sql->getValue('name');
                $Values['name'] = $sql->getValue('name');
                $Values['description'] = $sql->getValue('description');
                $Values['active'] = $sql->getValue('active');
                $Values['insertbefore'] = $sql->getValue('insertbefore');
                $Values['marker'] = $sql->getValue('marker');
                $Values['html'] = $sql->getValue('html');
                $Values['categories'] = $sql->getValue('categories');
                $Values['subcats'] = $sql->getValue('subcats');
                $Values['allcats'] = $sql->getValue('allcats');
                $Values['once'] = $sql->getValue('once');
                $Values['excludeids'] = $sql->getValue('excludeids');
            }
        }
    }

    $content = '';

    $fieldset = $func == 'edit' ? $this->i18n('xoutputfilter_frontend_title_edit') : $this->i18n('xoutputfilter_frontend_title_add');

    // Name
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="name">' . htmlspecialchars_decode($this->i18n('xoutputfilter_frontend_label_name')) . '</label>';
    $n['field'] = '<input class="form-control" type="text" id="name" name="name" value="' . htmlspecialchars($Values['name']) . '" />';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/container.php');

    // Beschreibung
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="description">' . htmlspecialchars_decode($this->i18n('xoutputfilter_frontend_label_description')) . '</label>';
    $n['field'] = '<textarea class="form-control" type="text" id="description" name="description" rows="3">'.htmlspecialchars($Values['description']).'</textarea>';
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

    $field->setLabel(htmlspecialchars_decode($this->i18n('xoutputfilter_frontend_label_active')));
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

    // Ersetzungstyp
    $formElements = [];
    $n = [];

    $attributes = array();
    $attributes['id'] = 'insertbefore';
    $attributes['name'] = 'insertbefore';
    $attributes['class'] = 'form-control';
    $field = new rex_form_select_element('select', null, $attributes);

    $field->setLabel(htmlspecialchars_decode($this->i18n('xoutputfilter_frontend_insertbefore')));
    $select = $field->getSelect();
    $select->addOption($this->i18n('xoutputfilter_frontend_insertbefore0'), 0);
    $select->addOption($this->i18n('xoutputfilter_frontend_insertbefore1'), 1);
    $select->addOption($this->i18n('xoutputfilter_frontend_insertbefore2'), 2);
    $select->addOption($this->i18n('xoutputfilter_frontend_insertbefore3'), 3);
    $select->addOption($this->i18n('xoutputfilter_frontend_insertbefore4'), 4);
    $select->setSize(1);
    $select->setSelected($Values['insertbefore']);
    $n['field'] = $field->get();
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/container.php');

    // Marker
    $formElements = [];
    $n = [];
    $class = 'form-control';
    $mode = '';
    if (rex_plugin::get('be_style', 'customizer')->isAvailable() and rex_plugin::get('be_style', 'customizer')->getConfig('codemirror')) {
        if (rex_addon::get('xoutputfilter')->getConfig('codemirror')) {
            $class = 'form-control codemirror';
            $mode = 'codemirror-mode="php/htmlmixed"';
        }
    }
    $n['label'] = '<label for="marker">' . htmlspecialchars_decode($this->i18n('xoutputfilter_frontend_label_marker')) . '</label>';
    $n['field'] = '<textarea class="'.$class.'" '.$mode.' type="text" id="marker" name="marker" rows="8">'.htmlspecialchars($Values['marker']).'</textarea>';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/container.php');

    // Ersetzung
    $formElements = [];
    $n = [];
    $class = 'form-control';
    $mode = '';
    if (rex_plugin::get('be_style', 'customizer')->isAvailable() and rex_plugin::get('be_style', 'customizer')->getConfig('codemirror')) {
        if (rex_addon::get('xoutputfilter')->getConfig('codemirror')) {
            $class = 'form-control codemirror';
            $mode = 'codemirror-mode="php/htmlmixed"';
        }
    }
    $n['label'] = '<label for="html">' . htmlspecialchars_decode($this->i18n('xoutputfilter_frontend_label_html')) . '</label>';
    $n['field'] = '<textarea class="'.$class.'" '.$mode.' type="text" id="html" name="html" rows="8">'.htmlspecialchars($Values['html']).'</textarea>';
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/container.php');

    // aktive Kategorien
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="categories">' . htmlspecialchars_decode($this->i18n('xoutputfilter_frontend_categories')) . '</label>';

    $category_select = new rex_category_select(false, false, false, true);
    $category_select->setName('categories[]');
    $category_select->setId('categories');
    $category_select->setSize('10');
    $category_select->setMultiple(true);
    $category_select->setAttribute('style', 'width:100%');
    $category_select->setSelected(explode(',', $Values['categories']));

    $n['field'] = $category_select->get();
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/container.php');	

    // inklusive Subkategorien
    $formElements = [];
    $n = [];

    $attributes = array();
    $attributes['id'] = 'subcats';
    $attributes['name'] = 'subcats';
    $attributes['class'] = 'form-control';
    $field = new rex_form_select_element('select', null, $attributes);

    $field->setLabel(htmlspecialchars_decode($this->i18n('xoutputfilter_frontend_subcategories')));
    $select = $field->getSelect();
    $select->addOption($this->i18n('yes'), 1);
    $select->addOption($this->i18n('no'), 0);
    $select->setSize(1);
    $select->setSelected($Values['subcats']);
    $n['field'] = $field->get();
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/container.php');

    // alle Kategorien
    $formElements = [];
    $n = [];

    $attributes = array();
    $attributes['id'] = 'allcats';
    $attributes['name'] = 'allcats';
    $attributes['class'] = 'form-control';
    $field = new rex_form_select_element('select', null, $attributes);

    $field->setLabel(htmlspecialchars_decode($this->i18n('xoutputfilter_frontend_allcats')));
    $select = $field->getSelect();
    $select->addOption($this->i18n('yes'), 1);
    $select->addOption($this->i18n('no'), 0);
    $select->setSize(1);
    $select->setSelected($Values['allcats']);
    $n['field'] = $field->get();
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/container.php');

    // nur einmal einfügen
    $formElements = [];
    $n = [];

    $attributes = array();
    $attributes['id'] = 'once';
    $attributes['name'] = 'once';
    $attributes['class'] = 'form-control';
    $field = new rex_form_select_element('select', null, $attributes);

    $field->setLabel(htmlspecialchars_decode($this->i18n('xoutputfilter_frontend_once')));
    $select = $field->getSelect();
    $select->addOption($this->i18n('yes'), 1);
    $select->addOption($this->i18n('no'), 0);
    $select->setSize(1);
    $select->setSelected($Values['once']);
    $n['field'] = $field->get();
    $formElements[] = $n;

    $fragment = new rex_fragment();
    $fragment->setVar('elements', $formElements, false);
    $content .= $fragment->parse('core/form/container.php');

    // Exclude Seiten-Id's
    $formElements = [];
    $n = [];
    $n['label'] = '<label for="REX_LINKLIST_SELECT_1">' . htmlspecialchars_decode($this->i18n('xoutputfilter_frontend_excludeids')) . '</label>';
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
        $n['field'] = '<button class="btn btn-delete" type="submit" name="delete" value="1" data-confirm="[' . htmlspecialchars($Values['name']) . '] - ' . $this->i18n('xoutputfilter_frontend_really_delete'). '">' . $this->i18n('xoutputfilter_button_delete') . '</button>';
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
<input type="hidden" name="xfunc" value="'.$func.'" />
<input type="hidden" name="oid" value="'.$oid.'" />
<input type="hidden" name="oldmarker" value="'.htmlspecialchars($oldmarker).'" />
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
    $sql->debugsql = 0;

    if (!rex_addon::get('xoutputfilter')->getConfig('syncfrontend')) {
        $sql->setWhere(['typ' => '4', 'id' => $oid]);
        $sql->setTable($table);
        $sql->delete();
    } else {
        $sql->setWhere(['typ' => '4', 'id' => $oid]);
        $sql->setTable($table);
        $sql->select('`name`');
        if ($sql->getRows() == 1) {
            $name = $sql->getValue('name');
            $sql->setWhere(['typ' => '4', 'name' => $name]);
            $sql->setTable($table);
            $sql->delete();
        }
    }
    echo rex_view::success($this->i18n('xoutputfilter_frontend_deleted'));
    $func = '';
}

// aktivieren/deaktivieren
if ($func == 'setstatus')
{
    $sql = rex_sql::factory();

    $status = (rex_request('oldstatus', 'int') + 1) % 2;

    if (!rex_addon::get('xoutputfilter')->getConfig('syncfrontend')) {
        $sql->setValue('active', $status);
        $sql->setWhere(['typ' => '4', 'id' => $oid]);
        $sql->setTable($table);
        $sql->update();
    } else {
        $sql->setWhere(['typ' => '4', 'id' => $oid]);
        $sql->setTable($table);
        $sql->select('`name`');
        if ($sql->getRows() == 1) {
            $name = $sql->getValue('name');
            $sql->setValue('active', $status);
            $sql->setWhere(['typ' => '4', 'name' => $name]);
            $sql->setTable($table);
            $sql->update();
        }
    }
    $msg = $status == 1 ? 'xoutputfilter_frontend_status_activated' : 'xoutputfilter_frontend_status_deactivated';
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

    xoutputfilter_util::syncFrontend();

    // SQL inkl. Filter
    $sqlfilter = '';
    if (isset($_SESSION['xoutputfilter']['frontend']['filter1']) and $_SESSION['xoutputfilter']['frontend']['filter1'] <> '')
    {
        $sqlfilter .= ' AND ( `name` like \'%'.$_SESSION['xoutputfilter']['frontend']['filter1'].'%\' ';
        $sqlfilter .= ' OR `description` like \'%'.$_SESSION['xoutputfilter']['frontend']['filter1'].'%\') ';
    }
    if (isset($_SESSION['xoutputfilter']['frontend']['filter2']) and $_SESSION['xoutputfilter']['frontend']['filter2'] <> '')
    {
        $sqlfilter .= ' AND `marker` like \'%'.$_SESSION['xoutputfilter']['frontend']['filter2'].'%\' ';
    }
    $sql = 'SELECT `id`, `name`, `description`, `marker`, `active`, `lang`, `excludeids`, `insertbefore`, `categories`, `subcats`, `allcats`, `once` FROM ' . $table . ' WHERE `typ` = \'4\' AND `lang` = ' . $clang . $sqlfilter . ' ORDER BY `name` ASC ';

    $list = rex_list::factory($sql, 30, 'frontend', 0);

    $list->setNoRowsMessage($this->i18n('xoutputfilter_frontend_nodata'));

    $list->addTableAttribute('class', 'table-striped table-hover xoutputfilter');

    $list->addParam('clang', $clang);

    $list->addTableColumnGroup(array(40, 150, '*', 300, 20, 20, 20, 20));

    $tdIcon = '<i class="rex-icon rex-icon-edit" title="' . $this->i18n('edit') . '"></i>';
    $thIcon = '<a href="' . $list->getUrl(['func' => 'add']) . '" ' . rex::getAccesskey($this->i18n('frontend_add'), 'add') . ' title="' . $this->i18n('languages_add') . '"><i class="rex-icon rex-icon-add"></i></a>';
    $list->addColumn($thIcon, $tdIcon, 0, ['<th class="rex-table-icon">###VALUE###</th>', '<td class="rex-table-icon">###VALUE###</td>']);
    $list->setColumnParams($thIcon, ['func' => 'edit', 'oid' => '###id###']);

    $list->removeColumn('id');
    $list->removeColumn('lang');
    $list->removeColumn('active');
    $list->removeColumn('excludeids');
    $list->removeColumn('insertbefore');
    $list->removeColumn('categories');
    $list->removeColumn('subcats');
    $list->removeColumn('allcats');
    $list->removeColumn('once');

    $list->setColumnLabel('name', $this->i18n('xoutputfilter_frontend_name_header'));
    $list->setColumnLayout('name', ['<th>###VALUE###</th>', '<td contenteditable="true" data-title="###LABEL###" data-id="###id###" data-field="name" data-lang="###lang###" data-oldvalue="">###VALUE###</td>']);

    $list->setColumnLabel('description', $this->i18n('xoutputfilter_frontend_description_header'));
    $list->setColumnLayout('description', ['<th>###VALUE###</th>', '<td contenteditable="true" data-title="###LABEL###" data-id="###id###" data-field="description" data-lang="###lang###" data-oldvalue="">###VALUE###</td>']);

    $list->setColumnLabel('marker', $this->i18n('xoutputfilter_frontend_marker_header'));
    $list->setColumnLayout('marker', ['<th>###VALUE###</th>', '<td contenteditable="true" data-title="###LABEL###" data-id="###id###" data-field="marker" data-lang="###lang###" data-oldvalue="">###VALUE###</td>']);

    $list->addColumn('info', '', -1, ['<th>###VALUE###</th>', '<td>###VALUE###</td>']);
    $list->setColumnLabel('info', $this->i18n('xoutputfilter_info_header'));
    $list->setColumnFormat('info', 'custom', function($params) {
        $list = $params['list'];
        $title = '';
        $title .= $this->i18n('xoutputfilter_frontend_insertbefore'.$list->getValue('insertbefore')) . ' | ';
        if ($list->getValue('categories') <> '') {
            $title .= $this->i18n('xoutputfilter_info_tx_categories') . ' ' . $list->getValue('categories') . ' | ';
            if ($list->getValue('subcats') == '1') {
                $title .= $this->i18n('xoutputfilter_info_tx_subcats') . ' | ';
            }
        }
        if ($list->getValue('allcats') == '1') {
            $title .= $this->i18n('xoutputfilter_info_tx_allcats') . ' | ';
        }
        if ($list->getValue('once') == '1') {
            $title .= $this->i18n('xoutputfilter_info_tx_once') . ' | ';
        }
        if ($list->getValue('excludeids') <> '') {
            $title .= $this->i18n('xoutputfilter_info_tx_excludeids') . ' ' . $list->getValue('excludeids') . ' | ';
        }
        $title = str_replace(' | ', ' &#10;', htmlspecialchars($title));
        $str = '<i class="rex-icon fa-cogs" data-toggle="tooltip" data-placement="left" title="' . $title . '"></i>';
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
    $fragment->setVar('title', $this->i18n('xoutputfilter_frontend_table_header'), false);
    $fragment->setVar('content', $content, false);
    echo $fragment->parse('core/page/section.php');
}
