<?php

/**
 * Class xoutputfilter_util - Utils for xoutputfilter
 */
class xoutputfilter_util
{

    // Zeichen die für den CSV-Export maskiert werden
    // Beim CSV-Import werden die Maskierungen wieder zurückgesetzt
    private static $csv_must_mask = array('"', "\n", "\r", "\t", ';');
    private static $csv_masked = array("\~q~", "\~n~", "\~r~", "\~t~", "\~d~");

    /**
     * Export CSV-File
     *
     * @param string $filename Filename with Path
     * @param array $params
     *
     * @return bool TRUE on success, FALSE on failure
     */
    public static function exportCsv($filename, $params)
    {
        $table = rex::getTable('xoutputfilter');
        $content = '';

        $where = ' WHERE `typ` = \'0\' ';

        if (isset($params['abbrev']) and $params['abbrev'] == '1') {
            $where .= ' OR `typ` = \'2\' ';
        }
        if (isset($params['backend']) and $params['backend'] == '1') {
            $where .= ' OR `typ` = \'5\' ';
        }
        if (isset($params['frontend']) and $params['frontend'] == '1') {
            $where .= ' OR `typ` = \'4\' ';
        }
        if (isset($params['languages']) and $params['languages'] == '1') {
            $where .= ' OR `typ` = \'1\' ';
        }

        $query = 'SELECT * FROM `' . $table .'`' . $where . ' ORDER BY `typ` ASC, `name` ASC, `marker` ASC, `lang` ASC ';

        $sql = rex_sql::factory();
        $sql->debugsql = 0;
        $sql->setQuery($query);

        // Daten für Ausgabe aufbereiten
        $srch = self::$csv_must_mask;
        $repl = self::$csv_masked;
        foreach ($sql->getArray() as $d)
        {
            unset($d['id']);
            if ($content == '')
            {
                foreach ($d as $a => $b)
                {
                    $fields[] = '"' . $a . '"';
                }
                $content = implode(';', $fields);
            }

            foreach ($d as $a => $b)
            {
                $d[$a] = '"' . str_replace($srch, $repl, $b) . '"';
            }
            $content .= "\n" . implode(';', $d);
        }

        $hasContent = rex_file::put($filename, $content);

        return $hasContent;
    }

    /**
     * Import CSV-File
     *
     * @param string $filename Filename with Path
     *
     * @return array
     */
    public static function importCsv($filename)
    {
        $table = rex::getTable('xoutputfilter');
        $content = '';

        $state = array();
        $state['state'] = false;
        $state['message'] = '';

        $icounter = 0;
        $ucounter = 0;
        $ecounter = 0;
        $fieldnames = array();
        $sqlinsert = array();

        $srch = self::$csv_masked;
        $repl = self::$csv_must_mask;

        $fp = @fopen($filename, 'r');
        if (!$fp) {
            $state['message'] = rex_i18n::msg('xoutputfilter_error_import_csv', $filename);
            return $state;
        }

        $sql = rex_sql::factory();
        $sql->debugsql = 0;

        while (($line_array = fgetcsv($fp, 30384, ';')) !== FALSE )
        {
            if (count($fieldnames) == 0) // erste Zeile, Feldnamen merken
            {
                $fieldnames = $line_array;
            }
            else // SQL-Insert aufbauen
            {
                $sqlinsert[$icounter] = "INSERT INTO `" . $table . "` ( `id`";

                // Feldnamen
                foreach($fieldnames as $key => $val)
                {
                    $sqlinsert[$icounter] .= ', `'.$val. '` ';
                }
                $sqlinsert[$icounter] .= ' ) VALUES ( NULL';

                // Werte
                foreach($line_array as $key => $val)
                {
                    $val = str_replace($srch, $repl, $val);
                    $sqlinsert[$icounter] .= ', ' . $sql->escape($val) . ' ';
                }

                $sqlinsert[$icounter] .= ' ) ';
                $icounter++;
            }
        }

        $errormsg = '';

        foreach($sqlinsert as $key => $val)
        {
            try {
                $sql->setQuery($val);
                $ucounter++;
            } catch (rex_sql_exception $e) {
                $errormsg .= 'Line '.($key+2). ': '.$e->getMessage().'<br>';
                $ecounter++;
            }
        }

        if ($errormsg == '') {
            $msg = rex_i18n::msg('xoutputfilter_ok_import_csv', $ucounter);
            self::syslog(0, 'XOutputFilter: ' . $msg, __FILE__, __LINE__);
            $syncret = self::synchronize();
            $state['state'] = true;
            $state['message'] = rex_i18n::msg('xoutputfilter_ok_import_csv', $ucounter) . '<br>' . $syncret['message'];
        } else {
            $state['message'] = $errormsg;
        }

        return $state;
    }

    /**
     * Export SQL-File
     *
     * @param string $filename Filename with Path
     *
     * @return array
     */
    public static function exportSql($filename)
    {
        $EXPTABLES = array();
        $EXPTABLES[] = rex::getTable('xoutputfilter');

        return rex_backup::exportDb($filename, $EXPTABLES);
    }

    /**
     * Import SQL-File
     *
     * @param string $filename Filename with Path
     *
     * @return array
     */
    public static function importSql($filename)
    {
        $return = [];
        $return['state'] = false;
        $return['message'] = '';

        $msg = '';
        $error = '';

        if ($filename == '' || substr($filename, -4, 4) != '.sql') {
            $return['message'] = rex_i18n::msg('xoutputfilter_error_no_import_file');
            return $return;
        }

        $conts = rex_file::get($filename);

        // Versionsstempel prüfen
        // ## Redaxo Database Dump Version x.x
        $mainVersion = rex::getVersion('%s');
        $version = strpos($conts, '## Redaxo Database Dump Version ' . $mainVersion);
        if ($version === false) {
            $return['message'] = rex_i18n::msg('xoutputfilter_no_valid_import_file') . '. [## Redaxo Database Dump Version ' . $mainVersion . '] is missing';
            return $return;
        }
        // Versionsstempel entfernen
        $conts = trim(str_replace('## Redaxo Database Dump Version ' . $mainVersion, '', $conts));

        // Prefix prüfen
        // ## Prefix xxx_
        if (preg_match('/^## Prefix ([a-zA-Z0-9\_]*)/', $conts, $matches) && isset($matches[1])) {
            // prefix entfernen
            $prefix = $matches[1];
            $conts = trim(str_replace('## Prefix ' . $prefix, '', $conts));
        } else {
            // Prefix wurde nicht gefunden
            $return['message'] = rex_i18n::msg('xoutputfilter_no_valid_import_file') . '. [## Prefix ' . rex::getTablePrefix() . '] is missing';
            return $return;
        }

        // Charset prüfen
        // ## charset xxx_
        if (preg_match('/^## charset ([a-zA-Z0-9\_\-]*)/', $conts, $matches) && isset($matches[1])) {
            // charset entfernen
            $charset = $matches[1];
            $conts = trim(str_replace('## charset ' . $charset, '', $conts));

            // $rexCharset = rex_i18n::msg('htmlcharset');
            $rexCharset = 'utf-8';
            if ($rexCharset != $charset) {
                $return['message'] = rex_i18n::msg('xoutputfilter_no_valid_charset') . '. ' . $rexCharset . ' != ' . $charset;
                return $return;
            }
        }

        // Prefix im export mit dem der installation angleichen
        if (rex::getTablePrefix() != $prefix) {
            // Hier case-insensitiv ersetzen, damit alle möglich Schreibweisen (TABLE TablE, tAblE,..) ersetzt werden
            // Dies ist wichtig, da auch SQLs innerhalb von Ein/Ausgabe der Module vom rex-admin verwendet werden
            $conts = preg_replace('/(TABLES? `?)' . preg_quote($prefix, '/') . '/i', '$1' . rex::getTablePrefix(), $conts);
            $conts = preg_replace('/(INTO `?)'  . preg_quote($prefix, '/') . '/i', '$1' . rex::getTablePrefix(), $conts);
            $conts = preg_replace('/(EXISTS `?)' . preg_quote($prefix, '/') . '/i', '$1' . rex::getTablePrefix(), $conts);
        }

        // Datei aufteilen
        $lines = [];
        rex_sql_util::splitSqlFile($lines, $conts, 0);

        $sql = rex_sql::factory();
        foreach ($lines as $line) {
            try {
                $sql->setQuery($line['query']);
            } catch (rex_sql_exception $e) {
                $error .= "\n" . $e->getMessage();
            }
        }

        if ($error != '') {
            $return['message'] = trim($error);
            return $return;
        }

        $msg .= rex_i18n::msg('xoutputfilter_database_imported') . ' ' . rex_i18n::msg('xoutputfilter_entry_count', count($lines));
        unset($lines);

        self::syslog(0, 'XOutputFilter: ' . $msg, __FILE__, __LINE__);

        $syncret = self::synchronize();

        $return['state'] = true;
        $return['message'] = $msg . '<br>' . $syncret['message'];

        return $return;
    }

    /**
     *  Sends a file to client
     *
     * @param string $filename Filename with Path
     * @param string $contentType eg. plain/text
     * @param string $outfilename Filename for Browserdownload
     * @param string $contentDisposition inline/attachment
     */
    public static function sendFile($file, $contentType, $outfilename, $contentDisposition = 'attachment')
    {
        while (ob_get_length()) {
            ob_end_clean();
        }

        if (!file_exists($file)) {
            header('HTTP/1.1 404 Not Found');
            exit;
        }

        header('Content-Type: ' . $contentType);
        header('Content-Disposition: ' . $contentDisposition . '; filename="' . basename($outfilename) . '"');
        header('HTTP/1.1 200 OK');

        header('Content-Length: ' . filesize($file));
        self::readfileChunked($file);

        exit;
    }

    /**
     * Datei chunked senden
     *
     * @param string $filename Filename with Path
     *
     * @return bool TRUE on success, FALSE on failure
     */
    public static function readfileChunked($filename)
    {
        $chunksize = 1*(1024*1024); // how many bytes per chunk
        $buffer = '';
        $handle = fopen($filename, 'rb');
        if ($handle === false)
        {
            return false;
        }
        while (!feof($handle))
        {
            $buffer = fread($handle, $chunksize);
            print $buffer;
        }
        return fclose($handle);
    }

    /**
     * Synchronisierung
     *
     * @return array
     */
    public static function synchronize()
    {
        $return = [];
        $return['state'] = true;
        $return['message'] = '';

        // Abkürzungen synchronisieren
        $rc = self::syncAbbrev();
        if (!$rc['state']) {
            $return['state'] = false;
        }
        if ($rc['message']) {
            $return['message'] .= $rc['message'] . '<br>';
        }

        // Frontend-Ersetzungen synchronisieren
        $rc = self::syncFrontend();
        if (!$rc['state']) {
            $return['state'] = false;
        }
        if ($rc['message']) {
            $return['message'] .= $rc['message'] . '<br>';
        }

        // Sprachen synchronisieren
        $rc = self::syncLanguages();
        if (!$rc['state']) {
            $return['state'] = false;
        }
        if ($rc['message']) {
            $return['message'] .= $rc['message'] . '<br>';
        }

        // Templates+Module nach Markern durchsuchen
        $rc = self::syncTemplatesModules();
        if (!$rc['state']) {
            $return['state'] = false;
        }
        if ($rc['message']) {
            $return['message'] .= $rc['message'] . '<br>';
        }

        return $return;
    }

    /**
     * Abkürzungen synchronisieren
     *
     * @return array
     */
    public static function syncAbbrev()
    {
        $table = rex::getTable('xoutputfilter');

        $return = [];
        $return['state'] = true;
        $return['message'] = '';

        if (!rex_plugin::get('xoutputfilter', 'abbrev')->isAvailable() or !rex_addon::get('xoutputfilter')->getConfig('syncabbrev')) {
            return $return;
        }

        $sql = rex_sql::factory();
        $sqli = rex_sql::factory();

        // ungültige Sprachen löschen
        $langs = array();
        foreach (rex_clang::getAll() as $id => $lang) {
            $langs[] = $id;
        }
        $langs = trim(implode(', ', $langs));

        $query = "DELETE FROM `$table` WHERE `typ` = '2' AND `lang` NOT IN ($langs) ";
        $sql->setQuery($query);
        if ($sql->getRows() > 0 and rex_addon::get('xoutputfilter')->getConfig('syslog')) {
            self::syslog(0, 'XOutputFilter: syncAbbrev - ' . $sql->getRows() . ' records deleted', __FILE__, __LINE__);
        }

        // Abkürzungen synchronisieren
        $updcounter = 0;
        $langcount = count(rex_clang::getAll());
        $query = "SELECT `marker`, `html`, `active`, `excludeids`, `lang` FROM `$table` WHERE `typ` = '2' GROUP BY `marker` HAVING COUNT(`id`) < '$langcount' ORDER BY `html` ASC, `lang` ASC ";
        $sql->setQuery($query);

        $fields = $sql->getFieldnames();

        for ($i = 0; $i < $sql->getRows(); $i++) {
            foreach (rex_clang::getAll() as $id => $lang) {
                if ($id <> $sql->getValue('lang')) {
                    $sqli->setValue('id', null);
                    $sqli->setValue('typ', '2');
                    foreach ($fields as $key => $value) {
                        if ($value == 'lang') {
                            $sqli->setValue('lang', $id);
                        } else {
                            $sqli->setValue($value, $sql->getValue($value));
                        }
                    }
                    $sqli->setTable($table)->insert();
                    if ($sql->getRows() > 0) {
                        $updcounter++;
                    }
                }
            }
            $sql->next();
        }

        if ($updcounter > 0) {
            $return['message'] = 'XOutputFilter Sync: Abbrev - ' . $updcounter . ' records inserted';
            if (rex_addon::get('xoutputfilter')->getConfig('syslog')) {
                self::syslog(0, $return['message'], __FILE__, __LINE__);
            }
        }

        return $return;
    }

    /**
     * Frontend-Ersetzungen synchronisieren
     *
     * @return array
     */
    public static function syncFrontend()
    {
        $table = rex::getTable('xoutputfilter');

        $return = [];
        $return['state'] = true;
        $return['message'] = '';

        if (!rex_plugin::get('xoutputfilter', 'frontend')->isAvailable() or !rex_addon::get('xoutputfilter')->getConfig('syncfrontend')) {
            return $return;
        }

        $sql = rex_sql::factory();
        $sqli = rex_sql::factory();

        // ungültige Sprachen löschen
        $langs = array();
        foreach (rex_clang::getAll() as $id => $lang) {
            $langs[] = $id;
        }
        $langs = trim(implode(', ', $langs));

        $query = "DELETE FROM `$table` WHERE `typ` = '4' AND `lang` NOT IN ($langs) ";
        $sql->setQuery($query);
        if ($sql->getRows() > 0 and rex_addon::get('xoutputfilter')->getConfig('syslog')) {
            self::syslog(0, 'XOutputFilter: syncFrontend - ' . $sql->getRows() . ' records deleted', __FILE__, __LINE__);
        }

        // Frontend-Ersetzungen synchronisieren
        $updcounter = 0;
        $langcount = count(rex_clang::getAll());
        $query = "SELECT `name` , `description` , `active`, `insertbefore`, `marker`, `html`, `categories`, `subcats`, `allcats`, `once`, `excludeids`, `lang` FROM `$table` WHERE `typ` = '4' GROUP BY `name` HAVING COUNT(`id`) < '$langcount' ORDER BY `name` ASC, `lang` ASC ";
        $sql->setQuery($query);

        $fields = $sql->getFieldnames();

        for ($i = 0; $i < $sql->getRows(); $i++) {
            foreach (rex_clang::getAll() as $id => $lang) {
                if ($id <> $sql->getValue('lang')) {
                    $sqli->setValue('id', null);
                    $sqli->setValue('typ', '4');
                    foreach ($fields as $key => $value) {
                        if ($value == 'lang') {
                            $sqli->setValue('lang', $id);
                        } else {
                            $sqli->setValue($value, $sql->getValue($value));
                        }
                    }
                    $sqli->setTable($table)->insert();
                    if ($sql->getRows() > 0) {
                        $updcounter++;
                    }
                }
            }
            $sql->next();
        }

        if ($updcounter > 0) {
            $return['message'] = 'XOutputFilter Sync: Frontend - ' . $updcounter . ' records inserted';
            if (rex_addon::get('xoutputfilter')->getConfig('syslog')) {
                self::syslog(0, $return['message'], __FILE__, __LINE__);
            }
        }

        return $return;
    }

    /**
     * Sprachersetzungen synchronisieren
     *
     * @return array
     */
    public static function syncLanguages()
    {
        $table = rex::getTable('xoutputfilter');

        $return = [];
        $return['state'] = true;
        $return['message'] = '';

        if (!rex_plugin::get('xoutputfilter', 'languages')->isAvailable()) {
            return $return;
        }

        $sql = rex_sql::factory();
        $sqli = rex_sql::factory();

        // ungültige Sprachen löschen
        $langs = array();
        foreach (rex_clang::getAll() as $id => $lang) {
            $langs[] = $id;
        }
        $langs = trim(implode(', ', $langs));

        $query = "DELETE FROM `$table` WHERE `typ` = '1' AND `lang` NOT IN ($langs) ";
        $sql->setQuery($query);
        if ($sql->getRows() > 0 and rex_addon::get('xoutputfilter')->getConfig('syslog')) {
            self::syslog(0, 'XOutputFilter: syncLanguages - ' . $sql->getRows() . ' records deleted', __FILE__, __LINE__);
        }

        // Sprachen synchronisieren
        $updcounter = 0;
        $langcount = count(rex_clang::getAll());
        $query = "SELECT `active`, `marker`, `html`, `excludeids`, `lang` FROM `$table` WHERE `typ` = '1' GROUP BY `marker` HAVING COUNT(`id`) < '$langcount' ORDER BY `marker` ASC, `lang` ASC ";
        $sql->setQuery($query);

        $fields = $sql->getFieldnames();

        for ($i = 0; $i < $sql->getRows(); $i++) {
            foreach (rex_clang::getAll() as $id => $lang) {
                if ($id <> $sql->getValue('lang')) {
                    $sqli->setValue('id', null);
                    $sqli->setValue('typ', '1');
                    foreach ($fields as $key => $value) {
                        if ($value == 'lang') {
                            $sqli->setValue('lang', $id);
                        } else {
                            $sqli->setValue($value, $sql->getValue($value));
                        }
                    }
                    $sqli->setTable($table)->insert();
                    if ($sql->getRows() > 0) {
                        $updcounter++;
                    }
                }
            }
            $sql->next();
        }

        if ($updcounter > 0) {
            $return['message'] = 'XOutputFilter Sync: Languages - ' . $updcounter . ' records inserted';
            if (rex_addon::get('xoutputfilter')->getConfig('syslog')) {
                self::syslog(0, $return['message'], __FILE__, __LINE__);
            }
        }

        return $return;
    }

    /**
     * Sprachersetzungen synchronisieren
     *
     * @return array
     */
    public static function syncTemplatesModules()
    {
        $table = rex::getTable('xoutputfilter');

        $return = [];
        $return['state'] = true;
        $return['message'] = '';

        $sql = rex_sql::factory();
        $sql->debugsql = 0;
    }

    /**
     * Meldung im Systemlog ausgeben
     *
     * @param string $msg Messge
     * @param string $file Filename
     * @param string $line Linenumber
     */
    public static function syslog($errno, $msg, $file, $line)
    {
        if (rex_addon::get('xoutputfilter')->getConfig('syslog')) {
            $usr = '';
            if (rex::getUser()) {
                $usr = rex::getUser()->getLogin() . ' (' . rex::getUser()->getId() . '): ';
            }
            $err = $errno;
            if ($errno == 0) {
                $err = E_USER_NOTICE;
            }
            rex_logger::logError($err, $usr . $msg, $file, $line);
        }
    }

    /**
     * Array aus String zurückliefern
     *
     * @param string $delimiter
     * @param string $string
     * @return array
     */
    public static function getArrayFromString($delimiter = ',', $string)
    {
        $arr = explode($delimiter, $string);
        foreach ($arr as $key => $val) {
            $arr[$key] = trim($val);
        }
        return $arr;
    }

    /**
     * Eval PHP-Code
     *
     * @param array $code
     *
     * @return array
     */
    public static function evalphp($code)
    {
        $evalresult = array();
        $evalresult['error'] = false;
        $evalresult['phperror'] = '';
        $evalresult['evaloutput'] = $code;

        if (strstr($code, '<?php') and !strstr($code, 'bexit;') and !strstr($code, 'bdie;'))
        {
            ob_start();
            $evalresult['evaloutput'] = eval('?>' . $code );
            if ($evalresult['evaloutput'] === false) {
                ob_end_clean();
                $evalresult['error'] = true;
            } else {
                $evalresult['evaloutput'] = ob_get_contents();
                ob_end_clean();
            }
        }
        return $evalresult;
    }

}
