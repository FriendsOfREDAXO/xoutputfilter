# Plugin `frontend` - Frontend-Ersetzungen

## PHP mit Parameter

### Beispiel

Wähle PHP mit Parametern als Ersetzungstyp
- Verwende date als Marker (es ist nur ein Wert möglich)
- als Ersetzung / PHP-Code: <?php echo date($params['format']); ?>
- Als Marker kann man nun verwenden [[date format="d.m.Y"]]
- Freuen, wenn das aktuelle Datum richtig formatiert ausgegeben wird.
Achtung: Manchmal werden die Anführungszeichen automatisch in andere Zeichen umgewandelt und die Parameter werde nnicht erkannt. In solch einem Fall kann auch param=|wert| verwendet werden.


---

&raquo; Weiter zum **[Plugin import_export](plugin_import_export.md)**
