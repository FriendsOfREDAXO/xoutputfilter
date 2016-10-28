<h4>XOutputFilter-Addon für REDAXO 5</h4>

<p>Mit dem Addon XOutputFilter hat man die Möglichkeit über den Extension-Point OUTPUT_FILTER die Ausgabe der REDAXO-Seite zu beeinflussen sowohl im Frontend als auch im Backend.</p>

<p>Die Hauptaufgabe dieses Addons ist die Ersetzung von Platzhaltern/Konstanten in der jeweiligen Sprache und die Kennzeichnung von Abkürzungen und Akronymen.</p>

<p>Über eine Programmschnittstelle kann in Modulen und Addons auf die Sprachersetzungen zugegriffen werden.</p>

<p>Zusätzlich können für das Frontend und das Backend verschiedene "Inserts" mit Code-Fragmenten, sonstigem HTML-Code oder auch PHP-Code angelegt werden. Diese Einträge können dann bestimmten Markern und Kategorien/Unterkategorien zugeordnet werden. Der Code wird - je nach Auswahl - entweder vor, hinter oder statt dem vorhandenen Marker im Quelltext ausgegeben beziehungsweise ausgeführt.</p>

<p>Die gewünschten Funktionen des Addons können über Plugins aktiviert und den Benutzern zugeordnet werden.</p>

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

<hr>

<h4>Credits</h4>

<ul>
<li>Andreas Eberhard, <a href="http://aesoft.de">http://aesoft.de</a></li>
<li>Peter Bickel, <a href="http://polarpixel.de">http://polarpixel.de</a></li>
<li><a href="https://github.com/FriendsOfREDAXO">[Friends Of REDAXO]</a> Gemeinsame REDAXO-Entwicklung!</li>
</ul>

<hr>

Idee und Realisierung der ersten Version: <a href="http://aesoft.de">Andreas Eberhard / aesoft.de</a> und <a href="http://polarpixel.de">Peter Bickel / polarpixel.de</a>
