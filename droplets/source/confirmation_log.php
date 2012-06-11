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

return $log->action();
