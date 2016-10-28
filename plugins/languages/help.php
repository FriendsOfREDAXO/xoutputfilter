<h4>XOutputFilter-Plugin languages</h4>

<p>Mit diesem Plugin können auf der Webseite (Frontend) Platzhalter/Konstanten in der jeweiligen Sprache ersetzt werden.</p>

<p>Die Platzhalter/Konstanten werden beim anlegen automatisch in alle Sprachen synchronisiert.</p>

<p>
Die Platzhalter werden ohne öffnendes/schließendes Tag (siehe <a href="?page=xoutputfilter/config">Konfiguration</a>)<br>
mit den entsprechenden Sprachersetzungen erfasst.
</p>
<p>
In den Artikel-Inhalten, Modulen oder Templates werden die Platzhallter mit
öffnendem und schließendem Tag erfasst. z.B. <code>{{copyright}}</code><br>
und dann automatisch durch den entsprechenden Text in der aktuellen Sprache ersetzt.
</p>
<hr>

<h4>Verwendung der Sprachersetzungen in Modulen oder Addons</h4>

<?php
$code = '<?php
// Beispiele:
//   $x->get(PLATZHALTER, [Sprache]);
//   xoutputfilter::get(PLATZHALTER, [Sprache]);

$x = new xoutputfilter();
echo $x->get(\'copyright\');
echo $x->get(\'copyright\', 1);
echo $x->get(\'copyright\', rex_clang::getCurrentId());

echo xoutputfilter::get(\'copyright\', rex_clang::getCurrentId());

// Sprachersetzungen auf eigenen HTML-Code anwenden:
$x = new xoutputfilter();
echo $x->replace($my_content, rex_clang::getCurrentId());

echo xoutputfilter::replace($my_content, rex_clang::getCurrentId());
?>';

echo rex_string::highlight($code);
?>