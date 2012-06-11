<?php

/**
 * toolConfirmationLog
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link http://phpmanufaktur.de
 * @copyright 2012
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

// include class.secure.php to protect this file and the whole CMS!
if (defined('WB_PATH')) {
  if (defined('LEPTON_VERSION'))
    include(WB_PATH.'/framework/class.secure.php');
}
else {
  $oneback = "../";
  $root = $oneback;
  $level = 1;
  while (($level < 10) && (!file_exists($root.'/framework/class.secure.php'))) {
    $root .= $oneback;
    $level += 1;
  }
  if (file_exists($root.'/framework/class.secure.php')) {
    include($root.'/framework/class.secure.php');
  }
  else {
    trigger_error(sprintf("[ <b>%s</b> ] Can't include class.secure.php!", $_SERVER['SCRIPT_NAME']), E_USER_ERROR);
  }
}
// end include class.secure.php

if ('á' != "\xc3\xa1") {
	// important: language files must be saved as UTF-8 (without BOM)
	trigger_error('The language file <b>'.basename(__FILE__).'</b> is damaged, it must be saved <b>UTF-8</b> encoded!', E_USER_ERROR);
}

$LANG = array(
    '- none -'
      => 'k.A.',
    '- unknown error -'
      => '- unbekannter Fehler -',
    'Error executing template <b>{{ template }}</b>:<br />{{ error }}'
      => 'Fehler bei der Ausführung des Templates <b>{{ template }}</b>:<br />{{ error }}',
    'Error: got no content'
      => 'Kein Inhalt empfangen (leere Antwort)',
    'Error: php.ini does not allow to open the target URL!'
      => 'Fehler: die php.ini erlaubt nicht das Öffnen entfernter URL\'s!',
    'Error: received data are invalid'
      => 'Fehler: die empfangenen Daten sind unvollständig oder ungültig.',
    'Error: The template {{ template }} does not exists in any of the possible paths!'
      => 'Das Template {{ template }} existiert in keinem der möglichen Suchpfade!',
    'NEWS'
      => 'NEWS',
    'PAGE'
      => 'PAGE',
    'PENDING'
      => 'WARTET',
    'Please type in a valid email address!'
      => 'Bitte geben Sie eine gültige E-Mail Adresse an!',
    'Please type in your name!'
      => 'Bitte geben Sie Ihren Namen an!',
    'The preset directory <b>{{ directory }}</b> does not exists, can\'t load any template!'
      => 'Das PRESET Verzeichnis <b>{{ directory }}</b> existiert nicht, kann kein Template laden!',
    'There are no pending log datas to transmit.'
      => 'Es sind keine LOG Daten zu übermitteln!',
    'TOPICS'
      => 'TOPICS',
    'TRANSMITTED'
      => 'Übermittelt',
    'Transmitted and updated {{ count }} log entries.'
      => 'Es wurden {{ count }} LOG Einträge übermittelt und aktualisiert.'

);
