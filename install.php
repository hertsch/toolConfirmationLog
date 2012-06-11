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

// wb2lepton compatibility
if (!defined('LEPTON_PATH')) require_once WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/wb2lepton.php';

require_once LEPTON_PATH.'/modules/'.basename(dirname(__FILE__)).'/class.confirmation.php';
require_once LEPTON_PATH.'/modules/droplets/functions.inc.php';

global $admin;

if (!file_exists(LEPTON_PATH.'/temp/unzip/')) @mkdir(LEPTON_PATH-'/temp/unzip/');
$result = wb_unpack_and_import(LEPTON_PATH.'/modules/'.basename(dirname(__FILE__)).'/droplets/droplet_confirmation_log.zip', LEPTON_PATH.'/temp/unzip/');
print_r($result);

$log = new dbConfirmationLog();
if (!$log->createTable()) {
  // Prompt Errors
  $admin->print_error($log->getError());
}


