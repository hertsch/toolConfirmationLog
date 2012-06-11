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

// load language depending onfiguration
if (!file_exists(LEPTON_PATH.'/modules/'.basename(dirname(__FILE__)).'/languages/'.LANGUAGE.'.cfg.php')) {
  require_once(LEPTON_PATH.'/modules/'.basename(dirname(__FILE__)).'/languages/EN.cfg.php');
}
else {
  require_once(LEPTON_PATH.'/modules/'.basename(dirname(__FILE__)).'/languages/'.LANGUAGE.'.cfg.php');
}

class dbConfirmationLog {

  private $table_name = null;
  private $error = '';

  /**
   * Constructor for bitlyConfig.
   */
  public function __construct() {
    date_default_timezone_set(CFG_TIME_ZONE);
    $this->table_name = TABLE_PREFIX.'mod_confirmation_log';
  } // __construct()

  /**
   * Create the database table
   * Set error message at any SQL problem.
   *
   * @return boolean
   */
  public function createTable() {
    global $database;
    $SQL = "CREATE TABLE IF NOT EXISTS `".$this->getTableName()."` ( ".
        "`id` INT(11) NOT NULL AUTO_INCREMENT, ".
        "`page_id` INT(11) NOT NULL DEFAULT '-1', ".
        "`second_id` INT(11) NOT NULL DEFAULT '-1', ".
        "`page_type` ENUM('PAGE','NEWS','TOPICS') NOT NULL DEFAULT 'PAGE', ".
        "`page_title` VARCHAR(255) NOT NULL DEFAULT '', ".
        "`page_link` VARCHAR(255) NOT NULL DEFAULT '', ".
        "`installation_name` VARCHAR(255) NOT NULL DEFAULT '', ".
        "`user_name` VARCHAR(255) NOT NULL DEFAULT '', ".
        "`user_email` VARCHAR(255) NOT NULL DEFAULT '', ".
        "`typed_name` VARCHAR(255) NOT NULL DEFAULT '', ".
        "`typed_email` VARCHAR(255) NOT NULL DEFAULT '', ".
        "`confirmed_at` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', ".
        "`status` ENUM('PENDING','TRANSMITTED'), ".
        "`transmitted_at` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00', ".
        "`timestamp` TIMESTAMP, ".
        "PRIMARY KEY (`id`)".
        " ) ENGINE=MyIsam AUTO_INCREMENT=1 DEFAULT CHARSET utf8 COLLATE utf8_general_ci";
    $database->query($SQL);
    if ($database->is_error()) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
      return false;
    }
    return true;
  } // createTable()

  /**
   * Delete the database table
   * Set error message at any SQL problem.
   *
   * @return boolean
   */
  public function deleteTable() {
    global $database;
    $database->query('DROP TABLE IF EXISTS `'.$this->getTableName().'`');
    if ($database->is_error()) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
      return false;
    }
    return true;
  } // deleteTable()

  /**
   * Return the complete table name with the table prefix
   *
   * @param string $table_name
   */
  public function getTableName() {
    return $this->table_name;
  } // getTableName();

  /**
   * @return string $error
   */
  public function getError() {
    return $this->error;
  }

  /**
   * @param string $error
   */
  protected function setError($error='') {
    $this->error = $error;
  }

  /**
   * Check if $this->message is empty
   *
   * @return boolean
   */
  public function isError() {
    return (bool) !empty($this->error);
  } // isMessage

} // class dbConfirmationLog

