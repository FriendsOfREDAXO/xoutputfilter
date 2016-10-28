<?php

// Für größere Exports den Speicher für PHP erhöhen.
if (rex_ini_get('memory_limit') < 67108864) {
    @ini_set('memory_limit', '64M');
}
@set_time_limit(0);

$success = '';
$error = '';

$Values = array();

if (rex_request('func', 'string', '') == '') {
    $Values['exporttyp'] = '.sql';
    $Values['languages'] = '1';
    $Values['abbrev'] = '1';
    $Values['frontend'] = '1';
    $Values['backend'] = '1';
    $Values['exportdl'] = 'server';
    $Values['hide'] = ' style="display:none;" ';
    $Values['filename'] = 'xoutputfilter_'.date('Ymd_Hi');
} else {
    $Values['exporttyp'] = rex_request('exporttyp', 'string', '');
    if ($Values['exporttyp'] == '' or $Values['exporttyp'] == '.sql') {
        $Values['hide'] = ' style="display:none;" ';
    } else {
        $Values['hide'] = ' ';
    }

    $Values['languages'] = rex_request('languages', 'string', '');
    $Values['abbrev'] = rex_request('abbrev', 'string', '');
    $Values['frontend'] = rex_request('frontend', 'string', '');
    $Values['backend'] = rex_request('backend', 'string', '');

    $Values['exportdl'] = rex_request('exportdl', 'string', '');
    if ($Values['exportdl'] == '') {
        $Values['exportdl'] = 'server';
    }
    $Values['filename'] = rex_request('filename', 'string', '');
    if ($Values['filename'] == '') {
        $Values['filename'] = 'xoutputfilter_'.date('Ymd_Hi');
    }
}

$Values['filename'] = preg_replace('@[^\.A-Za-z0-9_\-]@', '', $Values['filename']);
$Values['filename'] = strtolower($Values['filename']);

// Export
if (rex_request('func', 'string', '') == 'export') {

    $export_path = $this->getPath('plugins/import_export/backup/');
    $outfile = $Values['filename'];

    if ($Values['exportdl'] == 'file') {
        $outfile = '.temp_' . $outfile;
    }
    $hasContent = false;

    if (file_exists($export_path . $outfile . $Values['exporttyp'])) {
        $i = 1;
        while (file_exists($export_path . $outfile . '_' . $i . $Values['exporttyp'])) {
            ++$i;
        }
        $outfile = $outfile . '_' . $i;
    }

    // SQL-Export auf Server ausgeben
    if ($Values['exporttyp'] == '.sql') {
        $hasContent = xoutputfilter_util::exportSql($export_path . $outfile . $Values['exporttyp']);
    }

    // CSV-Export auf Server ausgeben
    if ($Values['exporttyp'] == '.csv') {
        $params = array();
        $params['abbrev'] = $Values['abbrev'];
        $params['frontend'] = $Values['frontend'];
        $params['backend'] = $Values['backend'];
        $params['languages'] = $Values['languages'];
        $hasContent = xoutputfilter_util::exportCsv($export_path . $outfile . $Values['exporttyp'], $params);
    }

    // Download Export
    if ($hasContent and $Values['exportdl'] == 'file') {
        $ctype = 'plain/text';
        xoutputfilter_util::sendFile($export_path . $outfile . $Values['exporttyp'], $ctype, $Values['filename'] . $Values['exporttyp'], 'attachment');
        unlink($export_path . $outfile . $Values['exporttyp']);
        exit;
    }

    if ($hasContent) {
        $success = $this->i18n('backup_file_generated_in') . ' ' . $outfile . $Values['exporttyp'];
    } else {
        $error = $this->i18n('backup_file_error') . ' ' . $export_path . $outfile . $Values['exporttyp'];
    }

    if ($success != '') {
        echo rex_view::success($success);
    }
    if ($error != '') {
        echo rex_view::error($error);
    }
}

// Ausgabe Formular
$content = '';

$content .= '<fieldset><legend>' . $this->i18n('xoutputfilter_export_title_type') . '</legend>';

// Export Typ .sql
$formElements = [];
$n = [];
$n['label'] = '<label for="export_sql">' . $this->i18n('xoutputfilter_export_typsql') . '</label>';
$n['field'] = '<input type="radio" id="export_sql" name="exporttyp"' . (!empty($Values['exporttyp']) && $Values['exporttyp'] == '.sql' ? ' checked="checked"' : '') . ' value=".sql" onclick="jQuery(\'.exportselectcsv\').hide(\'fast\');" />';
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/radio.php');

// Export Typ .csv
$formElements = [];
$n = [];
$n['label'] = '<label for="export_csv">' . $this->i18n('xoutputfilter_export_typcsv') . '</label>';
$n['field'] = '<input type="radio" id="export_csv" name="exporttyp"' . (!empty($Values['exporttyp']) && $Values['exporttyp'] == '.csv' ? ' checked="checked"' : '') . ' value=".csv" onclick="jQuery(\'.exportselectcsv\').show(\'fast\');" />';
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/radio.php');

$content .= '<div class="exportselectcsv" '.$Values['hide'].'>';

// Checkboxen Export csv
$formElements = [];
$n = [];
$n['label'] = '<label for="languages">' . $this->i18n('xoutputfilter_csv_languages') . '</label>';
$n['field'] = '<input type="checkbox" id="languages" name="languages"' . (!empty($Values['languages']) && $Values['languages'] == '1' ? ' checked="checked"' : '') . ' value="1" />';
$formElements[] = $n;
$n = [];
$n['label'] = '<label for="abbrev">' . $this->i18n('xoutputfilter_csv_abbrev') . '</label>';
$n['field'] = '<input type="checkbox" id="abbrev" name="abbrev"' . (!empty($Values['abbrev']) && $Values['abbrev'] == '1' ? ' checked="checked"' : '') . ' value="1" />';
$formElements[] = $n;
$n = [];
$n['label'] = '<label for="frontend">' . $this->i18n('xoutputfilter_csv_frontend') . '</label>';
$n['field'] = '<input type="checkbox" id="frontend" name="frontend"' . (!empty($Values['frontend']) && $Values['frontend'] == '1' ? ' checked="checked"' : '') . ' value="1" />';
$formElements[] = $n;
$n = [];
$n['label'] = '<label for="backend">' . $this->i18n('xoutputfilter_csv_backend') . '</label>';
$n['field'] = '<input type="checkbox" id="backend" name="backend"' . (!empty($Values['backend']) && $Values['backend'] == '1' ? ' checked="checked"' : '') . ' value="1" />';
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/checkbox.php');

$content .= '</div>';
$content .= '</fieldset>';

$content .= '<fieldset><legend>' . $this->i18n('xoutputfilter_export_title_file') . '</legend>';

// Export Server
$formElements = [];
$n = [];
$n['label'] = '<label for="exportdls">' . $this->i18n('xoutputfilter_export_server') . '</label>';
$n['field'] = '<input type="radio" id="exportdls" name="exportdl"' . (!empty($Values['exportdl']) && $Values['exportdl'] == 'server' ? ' checked="checked"' : '') . ' value="server" />';
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/radio.php');

// Export File
$formElements = [];
$n = [];
$n['label'] = '<label for="exportdlf">' . $this->i18n('xoutputfilter_export_file') . '</label>';
$n['field'] = '<input type="radio" id="exportdlf" name="exportdl"' . (!empty($Values['exportdl']) && $Values['exportdl'] == 'file' ? ' checked="checked"' : '') . ' value="file" />';
$formElements[] = $n;
$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/radio.php');

// Dateiname
$formElements = [];
$n = [];
$n['label'] = '<label for="filename">' . $this->i18n('xoutputfilter_export_filename') . '</label>';
$n['field'] = '<input class="form-control" type="text" id="filename" name="filename" value="' . $Values['filename'] . '" />';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/form.php');

$content .= '</fieldset>';

// Export-Button
$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-save rex-form-aligned" type="submit" name="export" value="' . $this->i18n('btnexport') . '"><i class="rex-icon rex-icon-download"></i> ' . $this->i18n('btnexport') . '</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');

// Ausgabe Section
$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('title_export'), false);
$fragment->setVar('class', 'edit', false);
$fragment->setVar('body', $content, false);
$fragment->setVar('buttons', $buttons, false);
$content = $fragment->parse('core/page/section.php');

$content = '
<form action="' . rex_url::currentBackendPage() . '" method="post">
<input type="hidden" name="func" value="export" />
    ' . $content . '
</form>
';

echo $content;
