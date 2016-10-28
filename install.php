<?php

// Defaultwerte setzen
if (!$this->hasConfig() or ($this->getConfig('tagopen') == '' and $this->getConfig('tagclose') == '')) {
    $this->setConfig('tagopen', '{{');
    $this->setConfig('tagclose', '}}');
    $this->setConfig('showalllangs', '1');
}
