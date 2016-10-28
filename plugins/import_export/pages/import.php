<?php
$table = rex::getTable('xoutputfilter');

$page = rex_request('page', 'string', '');
$func = rex_request('func', 'string', '');
$filename = rex_request('filename', 'string', '');

$export_path = $this->getPath('plugins/import_export/backup/');

// Für größere Exports den Speicher für PHP erhöhen.
if (rex_ini_get('memory_limit') < 67108864) {
    @ini_set('memory_limit', '64M');
}
@set_time_limit(0);

$success = '';
$error = '';

// Datei löschen
if ($func == 'delete') {
    if (rex_file::delete($export_path . $filename)) {
        $success = $this->i18n('file_deleted') . ' - ' . $filename;
    } else {
        $error = $this->i18n('error_file_delete') . ' - ' . $filename;
    }
    $func = '';
}

// Dateidownload vom Server
if ($func == 'download') {
    if (is_readable($export_path . $filename)) {
        rex_response::sendFile($export_path . $filename, 'plain/test', 'attachment');
        exit;
    }
}

// Dateiimport vom Server
if ($func == 'import') {
    if (is_readable($export_path . $filename)) {
        // Import SQL
        if (substr($filename, -4, 4) == '.sql') {
            $state = xoutputfilter_util::importSql($export_path . $filename);
            if ($state['state']) {
                $success = $state['message'];
            } else {
                $error = $state['message'];
            }
        }
        // Import CSV
        if (substr($filename, -4, 4) == '.csv') {
            $state = xoutputfilter_util::importCsv($export_path . $filename);
            if ($state['state']) {
                $success = $state['message'];
            } else {
                $error = $state['message'];
            }
        }
    }
}

// Dateiimport über Upload
if ($func == 'upload') {
    if (isset($_FILES['FORM'])) {
        if ($_FILES['FORM']['size']['importfile'] < 1) {
            $error = $this->i18n('no_import_file');
        } else {
            $filename = strtolower($_FILES['FORM']['name']['importfile']);
            $file_temp = $export_path . '.temp_' . $filename;
            if (move_uploaded_file($_FILES['FORM']['tmp_name']['importfile'], $file_temp)) {
                if (substr($filename, -4, 4) == '.sql') {
                    $state = rex_backup::importDb($file_temp);
                    if ($state['state']) {
                        $success = $state['message'];
                    } else {
                        $error = $state['message'];
                    }
                }
                if (substr($filename, -4, 4) == '.csv') {
                    $state = xoutputfilter_util::importCsv($file_temp);
                    if ($state['state']) {
                        $success = $state['message'];
                    } else {
                        $error = $state['message'];
                    }
                }
                rex_file::delete($file_temp);
            } else {
                rex_file::delete($file_temp);
                $error = $this->i18n('error_upload_file', $filename);
            }
        }
    }
}

// Nachricht ausgeben
if ($success != '') {
    echo rex_view::success($success);
}
if ($error != '') {
    echo rex_view::error($error);
}

// Hinweis ausgeben
$content = '';

$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('title_note'), false);
$fragment->setVar('class', 'info', false);
$fragment->setVar('body', '<p>' . $this->i18n('intro_import') . '</p>', false);
$content .= $fragment->parse('core/page/section.php');

echo $content;

$content = '';

// Dateiupload
$formElements = [];
$n = [];
$n['label'] = '<label for="rex-form-importdbfile">' . $this->i18n('backup_file') . '</label>';
$n['field'] = '<input type="file" id="rex-form-importdbfile" name="FORM[importfile]" size="18" />';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$content .= $fragment->parse('core/form/form.php');

// Import-Button
$formElements = [];
$n = [];
$n['field'] = '<button class="btn btn-send rex-form-aligned" type="submit" name="import" value="' . $this->i18n('btnimport') . '"><i class="rex-icon rex-icon-import"></i> ' . $this->i18n('btnimport') . '</button>';
$formElements[] = $n;

$fragment = new rex_fragment();
$fragment->setVar('elements', $formElements, false);
$buttons = $fragment->parse('core/form/submit.php');

// Ausgabe Section
$fragment = new rex_fragment();
$fragment->setVar('title', $this->i18n('title_import'), false);
$fragment->setVar('class', 'edit', false);
$fragment->setVar('body', $content, false);
$fragment->setVar('buttons', $buttons, false);
$content = $fragment->parse('core/page/section.php');

$content = '
<form action="' . rex_url::currentBackendPage() . '" enctype="multipart/form-data" method="post" data-confirm="' . $this->i18n('confirm_upload') . '">
<input type="hidden" name="func" value="upload" />
    ' . $content . '
</form>
';

echo $content;

// Ausgabe Dateiliste CSV-Dateien
$content = '
	<table class="table table-striped table-hover">
		<thead>
			<tr>
				<th class="rex-table-icon"></th>
				<th>' . $this->i18n('filename') . '</th>
				<th class="rex-table-width-5">' . $this->i18n('filesize') . '</th>
				<th class="rex-table-width-5">' . $this->i18n('createdate') . '</th>
				<th class="rex-table-action" colspan="3">' . $this->i18n('functions') . '</th>
			</tr>
		</thead>
		<tbody>'
;

$export_path = $this->getPath('plugins/import_export/backup/');
$folder = glob($export_path . '*.csv');

foreach ($folder as $file)
{
    $filename = basename($file);
    $filec = date('d.m.Y H:i', filemtime($file));
    $filesize = rex_file::formattedSize($file);

    $content .= '
			<tr>
				<td class="rex-table-icon"><i class="rex-icon rex-icon-table"></i></td>
				<td>' . $filename . '</td>
				<td>' . $filesize . '</td>
				<td>' . $filec . '</td>
				<td class="rex-table-action"><a href="' . rex_url::currentBackendPage(['func' => 'import', 'filename' => $filename]) . '" title="' . $this->i18n('import_file') . '" data-confirm="[' . $filename . '] - ' . $this->i18n('proceed_file_import_csv') . '"><i class="rex-icon rex-icon-import"></i> ' . $this->i18n('file_import') . '</a></td>
				<td class="rex-table-action"><a href="' . rex_url::currentBackendPage(['func' => 'download', 'filename' => $filename]) . '" title="' . $this->i18n('download_file') . '"><i class="rex-icon rex-icon-download"></i> ' . $this->i18n('file_download') . '</a></td>
				<td class="rex-table-action"><a href="' . rex_url::currentBackendPage(['func' => 'delete', 'filename' => $filename]) . '" title="' . $this->i18n('delete_file') . '" data-confirm="[' . $filename . '] - ' . $this->i18n('proceed_file_delete') . ' ?"><i class="rex-icon rex-icon-delete"></i> ' . $this->i18n('file_delete') . '</a></td>
			</tr>'
			;
}

$content .= '
		</tbody>
	</table>'
;

$fragment = new rex_fragment();
$fragment->setVar('title', htmlspecialchars_decode($this->i18n('csv_title')), false);
$fragment->setVar('content', $content, false);
$content = $fragment->parse('core/page/section.php');

echo $content;

// Ausgabe Dateiliste SQL-Dateien
$content = '
	<table class="table table-striped table-hover">
		<thead>
			<tr>
				<th class="rex-table-icon"></th>
				<th>' . $this->i18n('filename') . '</th>
				<th class="rex-table-width-5">' . $this->i18n('filesize') . '</th>
				<th class="rex-table-width-5">' . $this->i18n('createdate') . '</th>
				<th class="rex-table-action" colspan="3">' . $this->i18n('functions') . '</th>
			</tr>
		</thead>
		<tbody>'
;

$export_path = $this->getPath('plugins/import_export/backup/');
$folder = glob($export_path . '*.sql');

foreach ($folder as $file)
{
    $filename = basename($file);
    $filec = date('d.m.Y H:i', filemtime($file));
    $filesize = rex_file::formattedSize($file);

    $content .= '
			<tr>
				<td class="rex-table-icon"><i class="rex-icon rex-icon-database"></i></td>
				<td>' . $filename . '</td>
				<td>' . $filesize . '</td>
				<td>' . $filec . '</td>
				<td class="rex-table-action"><a href="' . rex_url::currentBackendPage(['func' => 'import', 'filename' => $filename]) . '" title="' . $this->i18n('import_file') . '" data-confirm="[' . $filename . '] - ' . $this->i18n('proceed_file_import_sql') . '"><i class="rex-icon rex-icon-import"></i> ' . $this->i18n('file_import') . '</a></td>
				<td class="rex-table-action"><a href="' . rex_url::currentBackendPage(['func' => 'download', 'filename' => $filename]) . '" title="' . $this->i18n('download_file') . '"><i class="rex-icon rex-icon-download"></i> ' . $this->i18n('file_download') . '</a></td>
				<td class="rex-table-action"><a href="' . rex_url::currentBackendPage(['func' => 'delete', 'filename' => $filename]) . '" title="' . $this->i18n('delete_file') . '" data-confirm="[' . $filename . '] - ' . $this->i18n('proceed_file_delete') . ' ?"><i class="rex-icon rex-icon-delete"></i> ' . $this->i18n('file_delete') . '</a></td>
			</tr>'
			;
}

$content .= '
		</tbody>
	</table>'
;

$fragment = new rex_fragment();
$fragment->setVar('title', htmlspecialchars_decode($this->i18n('sql_title')), false);
$fragment->setVar('content', $content, false);
$content = $fragment->parse('core/page/section.php');

echo $content;
