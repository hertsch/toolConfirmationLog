<?php

/**
 * toolConfirmationLog
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link http://phpmanufaktur.de
 * @copyright 2012
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

if (!file_exists(WB_PATH.'/modules/tool_confirmation_log/class.frontend.php')) {
  return "toolConfirmationLog is not installed!";
}

require_once WB_PATH.'/modules/tool_confirmation_log/class.frontend.php';

$log = new frontendConfirmation();
$params = $log->getParams();
$params[frontendConfirmation::PARAM_USE_EMAIL] = (isset($use_email) && (strtolower($use_email) == 'false')) ? false : true;
$params[frontendConfirmation::PARAM_USE_NAME] = (isset($use_name) && (strtolower($use_name) == 'false')) ? false : true;
if (!$log->setParams($params)) return $log->getError();
return $log->action();
