<?php

/**
 * Class xoutputfilter
 */
class xoutputfilter
{

    /**
     * Return Language-Replacement
     *
     * @param string $placeholder Platzhalter
     * @param string $lang Sprache
     *
     * @return string
     */
    public static function get($placeholder, $lang = '')
    {
        $output = '';
        return $output;
    }

    /**
     * Replace Language-Placeholders
     *
     * @param string $html HRML-Code/Text
     * @param string $lang Sprache
     *
     * @return string
     */
    public static function replace($html, $lang = '')
    {
        $output = trim($html);
        return $output;
    }

    /**
     * Check exclude backend pages
     *
     * @param array $excludepages
     * @param array $page
     *
     * @return boolean
     */
    public static function excludePage($excludepages, $page)
    {
        if (trim($page) == '') {
            return true;
        }
        foreach ($excludepages as $value) {
            $value = trim(strtolower($value));
            if ($value and strpos($page, $value) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check include backend pages
     *
     * @param array $includepages
     * @param array $page
     *
     * @return boolean
     */
    public static function includePage($includepages, $page)
    {
        if (trim($page) == '') {
            return false;
        }
        foreach ($includepages as $value) {
            $value = trim(strtolower($value));
            if ($value and strpos($page, $value) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check exclude articles
     *
     * @param array $excludeids
     * @param array $id
     *
     * @return boolean
     */
    public static function excludeId($excludeids, $id)
    {
        if (trim($id) == '' or trim($id) == '0') {
            return true;
        }
        if (in_array($id, $excludeids)) {
            return true;
        }
        return false;
    }

    /**
     * Check exclude categories
     *
     * @param array $excludeids
     * @param array $id
     *
     * @return boolean
     */
    public static function excludeCategory($excludecats, $id)
    {
        if (trim($id) == '' or trim($id) == '0') {
            return true;
        }
        if (in_array($id, $excludecats)) {
            return true;
        }
        return false;
    }

    /**
     * Get Backend Replacements from table
     *
     * @return array
     */
    public static function getBackendReplacements()
    {
        $table = rex::getTable('xoutputfilter');
        $sql = rex_sql::factory();

        $query = "SELECT `html`, `marker`, `excludeids`, `categories`, `allcats`, `insertbefore`, `once` FROM $table WHERE `typ` = '5' AND `active` = '1' ORDER BY `name` ASC ";

        return $sql->getArray($query);
    }

    /**
     * Get Frontend Replacements from table
     *
     * @return array
     */
    public static function getFrontendReplacements($clang)
    {
        $table = rex::getTable('xoutputfilter');
        $sql = rex_sql::factory();

        $types = array();
        if (rex_plugin::get('xoutputfilter', 'languages')->isAvailable()) {
            $types[] = "'1'";
        }
        if (rex_plugin::get('xoutputfilter', 'abbrev')->isAvailable()) {
            $types[] = "'2'";
        }
        if (rex_plugin::get('xoutputfilter', 'frontend')->isAvailable()) {
            $types[] = "'4'";
        }
        $types = trim(implode(', ', $types));

        $query = "SELECT `typ`, `html`, `marker`, `excludeids`, `categories`, `allcats`, `insertbefore`, `once` FROM $table WHERE `active` = '1' AND `lang` = '$clang' AND `typ` IN ($types) ORDER BY `typ` ASC, `name` ASC, `marker` ASC ";

        return $sql->getArray($query);
    }

    /**
     * Replace Backend-Replaces
     *
     * @param rex_extension_point $ep
     */
    public static function backendReplace(rex_extension_point $ep)
    {
        $starttime = microtime(true);
        $replcount = 0;

        // aktuelle Backend-Page
        $page = trim(strtolower(rex_be_controller::getCurrentPage()));

        // Excluded-Pages aus Konfiguration übergehen
        $excludepages = xoutputfilter_util::getArrayFromString(',', rex_addon::get('xoutputfilter')->getConfig('excludepages'));
        if (xoutputfilter::excludePage($excludepages, $page)) {
            return;
        }

        // Content zwischenspeichern
        $content = trim($ep->getSubject());

        // aktive Backend-Ersetzungen aus Tabelle bereitstellen
        if (!isset($_SESSION['xoutputfilter']['@backend']['items']) or !rex_addon::get('xoutputfilter')->getConfig('sessioncache')) {
            $items = xoutputfilter::getBackendReplacements();
            $_SESSION['xoutputfilter']['@backend']['items'] = $items;
            $table = rex::getTable('xoutputfilter');
            $infofrom = 'from table '.$table;
        } else {
            $items = $_SESSION['xoutputfilter']['@backend']['items'];
            $infofrom = 'from Session-Cache';
        }

        // Backend-Ersetzungen abarbeiten
        foreach ($items as $item) {
            // Excluded-Pages übergehen
            $excludepages = xoutputfilter_util::getArrayFromString(',', $item['excludeids']);
            if (xoutputfilter::excludePage($excludepages, $page)) {
                continue;
            }

            // Entweder bei allen Seiten oder nur ausgewählte Seiten ...
            $includepages = xoutputfilter_util::getArrayFromString(',', $item['categories']);
            if (!$item['allcats'] and !xoutputfilter::includePage($includepages, $page)) {
                continue;
            }

            // Ersetzungsdaten
            $insertbefore = $item['insertbefore'];
            $once = ($item['once'] == '1') ? 1 : -1;
            $replace = $item['html'];
            $marker = $item['marker'];
            $markers = xoutputfilter_util::getArrayFromString('|', $marker);
            $replcount++;

            // normale Ersetzung
            if (($insertbefore == '0') or ($insertbefore == '1') or ($insertbefore == '2')) {
                $phprc = xoutputfilter_util::evalphp($replace);
                if (!$phprc['error']) {
                    $replace = $phprc['evaloutput'];
                }	

                foreach ($markers as $search) {
                    // Code nur einmal einfügen/ersetzen - dann mit preg_replace
                    if ($once == 1) {
                        $pattern1 = array('#', '[', ']', '?', '.', '^', '$', '*', '+', '|', '{', '}', '(', ')', '<', '>');
                        $pattern2 = array('\#', '\[', '\]', '\?', '\.', '\^', '\$', '\*', '\+', '\|', '\{', '\}', '\(', '\)', '\<', '\>');
                        $pattern = '#' . str_replace($pattern1, $pattern2, $search) . '#';
                        if ($insertbefore == '0') { // nach dem Marker einfügen
                          $content = preg_replace($pattern, $search . $replace, $content, 1);
                        }
                        if ($insertbefore == '1') { // vor dem Marker einfügen
                          $content = preg_replace($pattern, $replace . $search, $content, 1);
                        }
                        if ($insertbefore == '2') { // Marker ersetzen
                          $content = preg_replace($pattern, $replace, $content, 1);
                        }
                    // Code mehrmals einfügen/ersetzen
                    } else {
                        if ($insertbefore == '0') { // nach dem Marker einfügen
                          $content = str_replace($search, $search . $replace, $content);
                        }
                        if ($insertbefore == '1') { // vor dem Marker einfügen
                          $content = str_replace($search, $replace . $search, $content);
                        }
                        if ($insertbefore == '2') { // Marker ersetzen
                          $content = str_replace($search, $replace, $content);
                        }
                    }
                }
            }

            // PREG_REPLACE
            if ($insertbefore == '3') {
                $search = trim(str_replace(array("\n", "\r"), '', $marker));
                $phprc = xoutputfilter_util::evalphp($replace);
                if (!$phprc['error']) {
                    $replace = $phprc['evaloutput'];
                }		
                $content = preg_replace($search, $replace, $content, $once);
            }

            // PHP-Code
            if ($insertbefore == '4') {
                foreach ($markers as $search) {
                    if ((trim($search) <> '') and strstr($content, trim($search))) {
                        $_SESSION['xoutputfilter']['content'] = $content;
                        $phprc = xoutputfilter_util::evalphp($replace);
                        if (!$phprc['error']) {
                            $content = $_SESSION['xoutputfilter']['content'];
                        }
                    }
                }
            }
        }

        // evtl. Info ausgeben
        $info = '';
        $endtime = microtime(true);
        if (rex_addon::get('xoutputfilter')->getConfig('runtimeinfo')) {
            $info =  "\n" . '<!-- XOutputFilter backend: ' . $replcount . ' replacements in ' . number_format($endtime - $starttime, 4, ',', ' ') . ' Sek. (' . $infofrom . ') -->';
        }

        //return $content . $info;
        $ep->setSubject($content . $info);
    }

    /**
     * Replace Language-Placeholders, Abbrev, Frontend-Replaces
     *
     * @param rex_extension_point $ep
     */
    public static function frontendReplace(rex_extension_point $ep)
    {
        $starttime = microtime(true);
        $replcount = 0;
        $categoryid = 0;

        // aktuelle Sprache, Artikel-Id und Category-Id
        $clang = rex_clang::getCurrentId();
        $article = rex_article::getCurrent();
        if ($article) {
            $articleid = rex_article::getCurrentId();
            if (rex_category::getCurrent()) {
                $categoryid = rex_category::getCurrent()->getId();
            }
        } else {
            return;
        }

        // Excluded-Categories aus Konfiguration übergehen
        $excludecats = rex_addon::get('xoutputfilter')->getConfig('excludecats');
        if (xoutputfilter::excludeCategory($excludecats, $categoryid)) {
            return;
        }

        // Excluded-Pages aus Konfiguration übergehen
        $excludeids = xoutputfilter_util::getArrayFromString(',', rex_addon::get('xoutputfilter')->getConfig('excludeids'));
        if (xoutputfilter::excludeId($excludeids, $articleid)) {
            return;
        }

        // Content zwischenspeichern
        $content = trim($ep->getSubject());

        // aktive Frontend-Ersetzungen aus Tabelle bereitstellen
        if (!isset($_SESSION['xoutputfilter']['@frontend']['items'][$clang]) or !rex_addon::get('xoutputfilter')->getConfig('sessioncache')) {
            $items = xoutputfilter::getFrontendReplacements($clang);
            $_SESSION['xoutputfilter']['@frontend']['items'][$clang] = $items;
            $table = rex::getTable('xoutputfilter');
            $infofrom = 'from table '.$table;
        } else {
            $items = $_SESSION['xoutputfilter']['@frontend']['items'][$clang];
            $infofrom = 'from Session';
        }

        // Open/Close-Tags für Sprachersetzungen
        $tagopen = rex_addon::get('xoutputfilter')->getConfig('tagopen');
        $tagclose = rex_addon::get('xoutputfilter')->getConfig('tagclose');

        // Arrays für Ersetzungen initialisieren
        $allsearch = array();
        $allreplace = array();
        $bodysearch = array();
        $bodyreplace = array();

        // Frontend-Ersetzungen abarbeiten
        foreach ($items as $item) {
            // Excluded-Pages übergehen
            $excludeids = xoutputfilter_util::getArrayFromString(',', $item['excludeids']);
            if (xoutputfilter::excludeId($excludeids, $articleid)) {
                continue;
            }

            $marker = $item['marker'];
            $replace = $item['html'];
            $replcount++;

            // Sprachersetzungen
            if ($item['typ'] == '1') {
                $allsearch[] = $tagopen . $marker . $tagclose;
                $allreplace[] = $replace;
            }

            // Abkürzungen (abbrev)
            if ($item['typ'] == '2') {
                $pattern1 = array('#', '[', ']', '?', '.', '^', '$', '*', '+', '|', '{', '}', '(', ')', '<', '>');
                $pattern2 = array('\#', '\[', '\]', '\?', '\.', '\^', '\$', '\*', '\+', '\|', '\{', '\}', '\(', '\)', '\<', '\>');
                $pattern = str_replace($pattern1, $pattern2, $marker);
                $pattern1 = array('"', "\r", "\n", "\\");
                $pattern2 = array('&quot;', '', ' ', '');
                $val = str_replace($pattern1, $pattern2, $replace);
                $bodysearch[] =  "|(?!<[^<>]*?)(?<![?.&])" . $pattern . "(?![^<>]*?>)|msU";
                $bodyreplace[] =  '<abbr title="'.$val.'">'.$marker.'</abbr>';

            }

            // Frontend-Ersetzungen
            if ($item['typ'] == '4') {
                $insertbefore = $item['insertbefore'];
                $once = ($item['once'] == '1') ? 1 : -1;
                $replace = $item['html'];
                $marker = $item['marker'];
                $markers = xoutputfilter_util::getArrayFromString('|', $marker);

                // normale Ersetzung
                if (($insertbefore == '0') or ($insertbefore == '1') or ($insertbefore == '2')) {
                    $phprc = xoutputfilter_util::evalphp($replace);
                    if (!$phprc['error']) {
                        $replace = $phprc['evaloutput'];
                    }	

                    foreach ($markers as $search) {
                        // Code nur einmal einfügen/ersetzen - dann mit preg_replace
                        if ($once == 1) {
                            $pattern1 = array('#', '[', ']', '?', '.', '^', '$', '*', '+', '|', '{', '}', '(', ')', '<', '>');
                            $pattern2 = array('\#', '\[', '\]', '\?', '\.', '\^', '\$', '\*', '\+', '\|', '\{', '\}', '\(', '\)', '\<', '\>');
                            $pattern = '#' . str_replace($pattern1, $pattern2, $search) . '#';
                            if ($insertbefore == '0') { // nach dem Marker einfügen
                              $content = preg_replace($pattern, $search . $replace, $content, 1);
                            }
                            if ($insertbefore == '1') { // vor dem Marker einfügen
                              $content = preg_replace($pattern, $replace . $search, $content, 1);
                            }
                            if ($insertbefore == '2') { // Marker ersetzen
                              $content = preg_replace($pattern, $replace, $content, 1);
                            }
                        // Code mehrmals einfügen/ersetzen
                        } else {
                            if ($insertbefore == '0') { // nach dem Marker einfügen
                              $content = str_replace($search, $search . $replace, $content);
                            }
                            if ($insertbefore == '1') { // vor dem Marker einfügen
                              $content = str_replace($search, $replace . $search, $content);
                            }
                            if ($insertbefore == '2') { // Marker ersetzen
                              $content = str_replace($search, $replace, $content);
                            }
                        }
                    }
                }

                // PREG_REPLACE
                if ($insertbefore == '3') {
                    $search = trim(str_replace(array("\n", "\r"), '', $marker));
                    $phprc = xoutputfilter_util::evalphp($replace);
                    if (!$phprc['error']) {
                        $replace = $phprc['evaloutput'];
                    }		
                    $content = preg_replace($search, $replace, $content, $once);
                }

                // PHP-Code
                if ($insertbefore == '4') {
                    foreach ($markers as $search) {
                        if ((trim($search) <> '') and strstr($content, trim($search))) {
                            $_SESSION['xoutputfilter']['content'] = $content;
                            $phprc = xoutputfilter_util::evalphp($replace);
                            if (!$phprc['error']) {
                                $content = $_SESSION['xoutputfilter']['content'];
                            }
                        }
                    }
                }
                
                // PHP mit Parametern: [[date format="d.m.Y" param2="dadfa"]]
                if ($insertbefore == '5') {
                    // RegEx für das Erkennen von Markern erzeugen...
                    // #  [[   date   (.*)   ]]   #  i
                    // => #\[\[date(.*)\]\]#i
                    $delimiter = '#';
                    $pattern = $delimiter.preg_quote($tagopen, $delimiter)
                            .preg_quote($marker, $delimiter)
                            .'(.*)'
                            .preg_quote($tagclose, $delimiter)
                            .$delimiter.'Uui';
                    
                    // jedes Vorkommen des Markers finden...
                    if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE)) {
                        $result = ''; // neuer content wird in $result aufgebaut
                        $start = 0;   // Hilfs-Index
                        foreach ($matches as &$match) {
                            // Parameter-Teil weiter parsen und $params-Array aufbauen...
                            $paramsPart = trim($match[1][0]);
                            // g{-3} matched das selbe Zeichen was zuvor gematcht wurde ' oder "
                            //$paramsPattern = '#([a-zA-Z0-9_:\\-]+)=(["\']|“)(.*)(\\g{-3}|”)#Uu';
                            //$paramsPattern = '#([a-zA-Z0-9_:\\-]+)=(?|(["\'])(.*)\\g{-2}|(“)(.*)”)#Uu';
                            // |“([^”]*)”
                            $paramsPattern = '#([a-zA-Z0-9_:\\-]+)=(?|"([^"]*)"|\'([^\']*)\'|\|([^\|]*)\|)#Uu';
                            $params = [];
                            if (preg_match_all($paramsPattern, $paramsPart, $paramsMatches, PREG_SET_ORDER)) {
                                foreach ($paramsMatches as &$pmatch) {
                                    //$params[$pmatch[1]] = $pmatch[3];
                                    $params[$pmatch[1]] = $pmatch[2];
                                }
                            }
                            
                            // Ersetzung erzeugen, in dem wir den Code mit den $params ausführen...
                            $phprc = xoutputfilter_util::evalphp($replace, $params);
                            if (!$phprc['error']) {
                                $replacement = $phprc['evaloutput'];
                            } else {
                                $replacement = $match[0][0]; // kann man sicher feiner machen - marker bleibt.
                            }
                            
                            // content zusammen bauen...
                            // ...erst der Teil vor dem Marker...
                            $result .= substr($content, $start, $match[0][1]-$start);
                            // ...dann die Ersetzung...
                            $result .= $replacement;
                            // ...zum Schluss setzen wir den Hilfs-Index auf nach dem Marker, fürs nächste Mal.
                            $start = $match[0][1] + strlen($match[0][0]);
                        }
                        // Den Rest nach dem letzten Marker anhängen...
                        $result .= substr($content, $start);
                        
                        // ... und fertig!
                        $content = $result;
                    }
                } // -- $insertbefore == '5'
                
                
            }
        }

        // Sprachersetzungen auf gesamten Content
        if (count($allsearch) >= 1) {
            $content = str_replace($allsearch, $allreplace, $content);
        }

        // Abkürzungen nur auf den Body-Bereich
        if (count($bodysearch) >= 1) {
            preg_match_all("=<body[^>]*>(.*)</body>=iUms", $content, $output);
            if (isset($output[1][0])) {
                $body = $output[1][0];
                $bodynew = preg_replace($bodysearch, $bodyreplace, $body);
                $content = str_replace($body, $bodynew, $content);
            }
        }

        // evtl. Info ausgeben
        $info = '';
        $endtime = microtime(true);
        if (rex_addon::get('xoutputfilter')->getConfig('runtimeinfo')) {
            $info =  "\n" . '<!-- XOutputFilter frontend: ' . $replcount . ' replacements in ' . number_format($endtime - $starttime, 4, ',', ' ') . ' Sek. (' . $infofrom . ') -->';
        }

        //return $content . $info;
        $ep->setSubject($content . $info);
    }

}
