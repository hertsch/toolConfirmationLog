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

// use LEPTON 2.x I18n for access to language files
if (!class_exists('LEPTON_Helper_I18n')) require_once LEPTON_PATH.'/modules/'.basename(dirname(__FILE__)).'/framework/LEPTON/Helper/I18n.php';

global $I18n;
if (!is_object($I18n)) {
  $I18n = new LEPTON_Helper_I18n();
}
else {
  $I18n->addFile('DE.php', LEPTON_PATH.'/modules/'.basename(dirname(__FILE__)).'/languages/');
}

// initialize Dwoo
global $parser;

if (!class_exists('Dwoo')) {
  require_once WB_PATH.'/modules/dwoo/include.php';
}

$cache_path = WB_PATH.'/temp/cache';
if (!file_exists($cache_path)) mkdir($cache_path, 0755, true);
$compiled_path = WB_PATH.'/temp/compiled';
if (!file_exists($compiled_path)) mkdir($compiled_path, 0755, true);

global $parser;
if (!is_object($parser)) $parser = new Dwoo($compiled_path, $cache_path);


class confirmationBackend {

  const REQUEST_ACTION = 'act';

  const ACTION_ABOUT = 'abt';
  const ACTION_DEFAULT = 'def';
  const ACTION_PROTOCOL = 'pro';

  private $page_link = '';
  private $img_url = '';
  private $template_path = '';
  private $error = '';
  private $message = '';
  protected $lang = null;

  /**
   * Constructor for the backend
   */
  public function __construct() {
    global $I18n;
    $this->page_link = ADMIN_URL . '/admintools/tool.php?tool=tool_confirmation_log';
    $this->template_path = LEPTON_PATH . '/modules/' . basename(dirname(__FILE__)) . '/templates/backend/';
    $this->img_url = LEPTON_URL . '/modules/' . basename(dirname(__FILE__)) . '/images/';
    date_default_timezone_set(CFG_TIME_ZONE);
    $this->lang = $I18n;
  } // __construct()

  /**
   * Set $this->error to $error
   *
   * @param $error STR
   */
  protected function setError($error) {
    $this->error = $error;
  } // setError()

  /**
   * Get Error from $this->error;
   *
   * @return STR $this->error
   */
  public function getError() {
    return $this->error;
  } // getError()

  /**
   * Check if $this->error is empty
   *
   * @return BOOL
   */
  public function isError() {
    return (bool) !empty($this->error);
  } // isError

  /**
   * Set $this->message to $message
   *
   * @param $message STR
   */
  protected function setMessage($message) {
    $this->message = $message;
  } // setMessage()

  /**
   * Get Message from $this->message;
   *
   * @return STR $this->message
   */
  public function getMessage() {
    return $this->message;
  } // getMessage()

  /**
   * Check if $this->message is empty
   *
   * @return BOOL
   */
  public function isMessage() {
    return (bool) !empty($this->message);
  } // isMessage

  /**
   * Return Version of Module
   *
   * @return FLOAT
   */
  public function getVersion() {
    // read info.php into array
    $info_text = file(LEPTON_PATH . '/modules/' . basename(dirname(__FILE__)) . '/info.php');
    if ($info_text == false) {
      return -1;
    }
    // walk through array
    foreach ($info_text as $item) {
      if (strpos($item, '$module_version') !== false) {
        // split string $module_version
        $value = explode('=', $item);
        // return floatval
        return floatval(preg_replace('([\'";,\(\)[:space:][:alpha:]])', '', $value[1]));
      }
    }
    return -1;
  } // getVersion()

  /**
   * Load the desired template, execute the template engine and returns the
   * resulting template
   *
   * @param $template STRING the file name of the template
   * @param $template_data ARRAY the data for the template
   * @return mixed BOOLEAN false or the template
   */
  protected function getTemplate($template, $template_data) {
    global $parser;
    $result = '';
    try {
      $result = $parser->get($this->template_path . $template, $template_data);
    } catch (Exception $e) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
          $this->lang->translate('<p>Error executing template <b>{{ template }}</b>:</p><p>{{ error }}</p>',
              array(
                  'template' => $template,
                  'error' => $e->getMessage())
              )));
      return false;
    }
    return $result;
  } // getTemplate()

  /**
   * Verhindert XSS Cross Site Scripting
   *
   * @param $request REFERENCE ARRAY
   * @return $request
   */
  protected function xssPrevent(&$request) {
    if (is_string($request)) {
      $request = html_entity_decode($request);
      $request = strip_tags($request);
      $request = trim($request);
      $request = stripslashes($request);
    }
    return $request;
  } // xssPrevent()

  /**
   * The action handler of the class formBackend
   *
   * @return string dialog or error message
   */
  public function action() {

    $html_allowed = array();
    foreach ($_REQUEST as $key => $value) {
      if (!in_array($key, $html_allowed)) {
        $_REQUEST[$key] = $this->xssPrevent($value);
      }
    }

    isset($_REQUEST[self::REQUEST_ACTION]) ? $action = $_REQUEST[self::REQUEST_ACTION] : $action = self::ACTION_DEFAULT;

    switch ($action):
    case self::ACTION_ABOUT:
      $result = $this->show(self::ACTION_ABOUT, $this->dlgAbout());
      break;
    default :
      $result = $this->show(self::ACTION_PROTOCOL, $this->dlgProtocolList());
      break;
    endswitch;

    echo $result;
  } // action

  /**
   * Ausgabe des formatierten Ergebnis mit Navigationsleiste
   *
   * @param $action aktives Navigationselement
   * @param $content Inhalt
   * @return STRING $result
   */
  protected function show($action, $content) {
    $tab_navigation_array = array(
        self::ACTION_PROTOCOL => $this->lang->translate('Protocol'),
        self::ACTION_ABOUT => $this->lang->translate('About')
    );

    $navigation = array();
    foreach ($tab_navigation_array as $key => $value) {
      $navigation[] = array(
          'active' => ($key == $action) ? 1 : 0,
          'url' => sprintf('%s&%s=%s', $this->page_link, self::REQUEST_ACTION, $key),
          'text' => $value
      );
    }
    $data = array(
        'navigation' => $navigation,
        'error' => ($this->isError()) ? 1 : 0,
        'content' => ($this->isError()) ? $this->getError() : $content
    );
    return $this->getTemplate('backend.body.htt', $data);
  } // show()

  /**
   * Shows an about dialog
   *
   * @return string dialog
   */
  protected function dlgAbout() {
    $data = array(
        'version' => sprintf('%01.2f', $this->getVersion()),
        //'img_url' => $this->img_url . '/kit_form_logo_400_267.jpg',
        'release_notes' => file_get_contents(LEPTON_PATH . '/modules/' . basename(dirname(__FILE__)) . '/CHANGELOG')
    );
    return $this->getTemplate('backend.about.htt', $data);
  } // dlgAbout()

  protected function dlgProtocolList() {
    global $database;
    $SQL = "SELECT * FROM ".TABLE_PREFIX."mod_confirmation_log ORDER BY `timestamp` DESC";
    if (false === ($query = $database->query($SQL))) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
      return false;
    }
    $fields = array();
    while (false !== ($log = $query->fetchRow(MYSQL_ASSOC))) {
      $fields[$log['id']] = array(
          'id' => $log['id'],
          'page_id' => $log['page_id'],
          'second_id' => $log['second_id'],
          'page_type' => $this->lang->translate($log['page_type']),
          'page_title' => $log['page_title'],
          'page_link' => $log['page_link'],
          'page_url' => LEPTON_URL.$log['page_link'],
          'installation_name' => $log['installation_name'],
          'user_name' => (!empty($log['user_name'])) ? $log['user_name'] : $this->lang->translate('- none -'),
          'user_email' => (!empty($log['user_email'])) ? $log['user_email'] : $this->lang->translate('- none -'),
          'typed_name' => (!empty($log['typed_name'])) ? $log['typed_name'] : $this->lang->translate('- none -'),
          'typed_email' => (!empty($log['typed_email'])) ? $log['typed_email'] : $this->lang->translate('- none -'),
          'confirmed_at' => $log['confirmed_at'],
          'status' => $this->lang->translate($log['status']),
          'transmitted_at' => $log['transmitted_at'],
          'timestamp' => $log['timestamp']
          );
    }
    $data = array(
        'fields' => $fields
        );
    return $this->getTemplate('backend.log.htt', $data);
  } // dlgProtocolList()

}