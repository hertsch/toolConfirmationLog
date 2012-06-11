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

// initialize Dwoo
global $parser;

if (!class_exists('Dwoo')) {
  require_once LEPTON_PATH.'/modules/dwoo/include.php';
}

$cache_path = LEPTON_PATH.'/temp/cache';
if (!file_exists($cache_path)) mkdir($cache_path, 0755, true);
$compiled_path = LEPTON_PATH.'/temp/compiled';
if (!file_exists($compiled_path)) mkdir($compiled_path, 0755, true);

global $parser;
if (!is_object($parser)) $parser = new Dwoo($compiled_path, $cache_path);

// use LEPTON 2.x I18n for access to language files
if (!class_exists('LEPTON_Helper_I18n')) require_once LEPTON_PATH.'/modules/'.basename(dirname(__FILE__)).'/framework/LEPTON/Helper/I18n.php';

global $I18n;
if (!is_object($I18n)) {
  $I18n = new LEPTON_Helper_I18n();
}
else {
  $I18n->addFile('DE.php', LEPTON_PATH.'/modules/'.basename(dirname(__FILE__)).'/languages/');
}

class frontendConfirmation {

  const REQUEST_ACTION = 'act';

  const ACTION_DEFAULT = 'def';
  const ACTION_CHECK = 'chk';

  private $page_link = '';
  private $img_url = '';
  private $template_path = '';
  private $error = '';
  private $message = '';

  const PARAM_PRESET = 'log_preset';
  const PARAM_FALLBACK_PRESET = 'fallback_preset';
  const PARAM_FALLBACK_LANGUAGE = 'fallback_language';
  const PARAM_LANGUAGE = 'language';
  const PARAM_DEBUG = 'debug';
  const PARAM_USE_EMAIL = 'use_email';
  const PARAM_USE_NAME = 'use_name';

  const FORM_ANCHOR = 'clog';

  private $params = array(
      self::PARAM_PRESET => 1,
      self::PARAM_LANGUAGE => LANGUAGE,
      self::PARAM_FALLBACK_LANGUAGE => 'DE',
      self::PARAM_FALLBACK_PRESET => 1,
      self::PARAM_DEBUG => false,
      self::PARAM_USE_EMAIL => true,
      self::PARAM_USE_NAME => true
      );

  protected $lang;

  /**
   * Constructor
  */
  public function __construct() {
    global $I18n;
    $url = '';
    $_SESSION['FRONTEND'] = true;
    $this->getUrlByPageID(PAGE_ID, $url);
    $this->page_link = $url;
    $this->template_path = LEPTON_PATH.'/modules/'.basename(dirname(__FILE__)).'/templates/frontend/';
    $this->img_url = LEPTON_URL.'/modules/'.basename(dirname(__FILE__)).'/images/';
    date_default_timezone_set(CFG_TIME_ZONE);
    $this->lang = $I18n;
  } // __construct()


  /**
   * Get the parameters - this function is important for the droplet usage.
   *
   * @return array $params
   */
  public function getParams() {
    return $this->params;
  } // getParams()

  /**
   * Set the parameters - this function will be called by droplets.
   *
   * @param $params array
   * @return boolean true on success
   */
  public function setParams($params = array()) {
    $this->params = $params;
    // check only the preset path but not the subdirectories with the languages!
    if (!file_exists($this->template_path.$this->params[self::PARAM_PRESET])) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__,
          $this->lang->translate('The preset directory <b>{{ directory }}</b> does not exists, can\'t load any template!', array(
          'directory' => '/modules/tool_confirmation_log/templates/frontend/'.$this->params[self::PARAM_PRESET].'/'))));
      return false;
    }
    return true;
  } // setParams()

  /**
   * Set $this->error to $error
   *
   * @param $error string
   */
  public function setError($error) {
    $this->error = $error;
  } // setError()

  /**
   * Get Error from $this->error;
   *
   * @return string $this->error
   */
  public function getError() {
    return $this->error;
  } // getError()

  /**
   * Check if $this->error is empty
   *
   * @return boolean
   */
  public function isError() {
    return (bool) !empty($this->error);
  } // isError

  /**
   * Set $this->message to $message
   *
   * @param $message string
   */
  public function setMessage($message) {
    $this->message = $message;
  } // setMessage()

  /**
   * Get Message from $this->message;
   *
   * @return string $this->message
   */
  public function getMessage() {
    return $this->message;
  } // getMessage()

  /**
   * Check if $this->message is empty
   *
   * @return boolean
   */
  public function isMessage() {
    return (bool) !empty($this->message);
  } // isMessage

  /**
   * Execute the desired template and return the completed template
   *
   * @param $template STRING the filename of the template without path
   * @param $template_data ARRAY the template data
   * @return STRING template or boolean false on error
   */
  protected function getTemplate($template, $template_data) {
    global $parser;
    $template_path = $this->template_path.$this->params[self::PARAM_PRESET].'/'.$this->params[self::PARAM_LANGUAGE].'/'.$template;
    if (!file_exists($template_path)) {
      // template does not exist - fallback to default language!
      $template_path = $this->template_path.$this->params[self::PARAM_PRESET].'/'.$this->params[self::PARAM_FALLBACK_LANGUAGE].'/'.$template;
      if (!file_exists($template_path)) {
        // template does not exists - fallback to the default preset!
        $template_path = $this->template_path.$this->params[self::PARAM_FALLBACK_PRESET].'/'.$this->params[self::PARAM_LANGUAGE].'/'.$template;
        if (!file_exists($template_path)) {
          // template does not exists - fallback to the default preset and the default language
          $template_path = $this->template_path.$this->params[self::PARAM_FALLBACK_PRESET].'/'.$this->params[self::PARAM_FALLBACK_LANGUAGE].'/'.$template;
          if (!file_exists($template_path)) {
            // template does not exists in any possible path - give up!
            $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Error: The template {{ template }} does not exists in any of the possible paths!', array(
                'template',
                $template))));
            return false;
          }
        }
      }
    }

    // add the template_path to the $template_data (for debugging purposes)
    if (!isset($template_data['template_path']))
      $template_data['template_path'] = $template_path;
    // add the debug flag to the $template_data
    if (!isset($template_data['DEBUG']))
      $template_data['DEBUG'] = (int) $this->params[self::PARAM_DEBUG];

    try {
      // try to execute the template with Dwoo
      $result = $parser->get($template_path, $template_data);
    }
    catch (Exception $e) {
      // prompt the Dwoo error
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $this->lang->translate('Error executing template <b>{{ template }}</b>:<br />{{ error }}', array(
          'template' => $template,
          'error' => $e->getMessage()))));
      return false;
    }
    return $result;
  } // getTemplate()

  /**
   * Verhindert XSS Cross Site Scripting
   *
   * @param REFERENCE ARRAY $request
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
   * The action handler of kitForm - call this function after creating a new
   * instance of kitForm!
   *
   * @return STRING result
   */
  public function action() {

    if ($this->isError())
      return sprintf('<a name="%s"></a><div class="error">%s</div>', self::FORM_ANCHOR, $this->getError());

    /**
     * to prevent cross site scripting XSS it is important to look also to
     * $_REQUESTs which are needed by other KIT addons. Addons which need
     * a $_REQUEST with HTML should set a key in $_SESSION['KIT_HTML_REQUEST']
    */
    $html_allowed = array();
    if (isset($_SESSION['KIT_HTML_REQUEST']))
      $html_allowed = $_SESSION['KIT_HTML_REQUEST'];
    $html = array();
    foreach ($html as $key)
      $html_allowed[] = $key;
    $_SESSION['KIT_HTML_REQUEST'] = $html_allowed;
    foreach ($_REQUEST as $key => $value) {
      if (stripos($key, 'amp;') == 0) {
        $key = substr($key, 4);
        $_REQUEST[$key] = $value;
        unset($_REQUEST['amp;'.$key]);
      }
      if (!in_array($key, $html_allowed)) {
        $_REQUEST[$key] = $this->xssPrevent($value);
      }
    }

    isset($_REQUEST[self::REQUEST_ACTION]) ? $action = $_REQUEST[self::REQUEST_ACTION] : $action = self::ACTION_DEFAULT;

    switch ($action) :
    case self::ACTION_CHECK:
      $result = $this->checkConfirmation();
      break;
    case self::ACTION_DEFAULT:
    default:
      $result = $this->showConfirmationDlg();
      break;
    endswitch;

    if ($this->isError())
      $result = sprintf('<a name="%s"></a><div class="error">%s</div>', self::FORM_ANCHOR, $this->getError());
    return $result;
  } // action

  protected function showConfirmationDlg() {
    $data = array(
        'form' => array(
            'name' => 'confirm_dlg',
            'action' => array(
                'link' => $this->page_link,
                'name' => self::REQUEST_ACTION,
                'value' => self::ACTION_CHECK
                ),
            'response' => ($this->isMessage()) ? $this->getMessage() : null
            ),
        'fields' => array(
            'name' => array(
                'active' => $this->params[self::PARAM_USE_NAME] ? 1 : 0,
                'name' => 'confirm_name',
                'value' => isset($_REQUEST['confirm_name']) ? $_REQUEST['confirm_name'] : ''
                ),
            'email' => array(
                'active' => $this->params[self::PARAM_USE_EMAIL] ? 1 : 0,
                'name' => 'confirm_email',
                'value' => isset($_REQUEST['confirm_email']) ? $_REQUEST['confirm_email'] : ''
                )
            )
        );
    return $this->getTemplate('confirm.htt', $data);
  } // showConfirmationDlg()

  protected function checkConfirmation() {
    global $database;
    $email = '';
    $name = '';
    if ($this->params[self::PARAM_USE_EMAIL]) {
      if (!isset($_REQUEST['confirm_email']) || empty($_REQUEST['confirm_email']) ||
          !$this->checkEMail($_REQUEST['confirm_email'])) {
        $this->setMessage($this->lang->translate('Please type in a valid email address!'));
        return $this->showConfirmationDlg();
      }
      $email = $_REQUEST['confirm_email'];
    }
    if ($this->params[self::PARAM_USE_NAME]) {
      if (!isset($_REQUEST['confirm_name']) || empty($_REQUEST['confirm_name'])) {
        $this->setMessage($this->lang->translate('Please type in your name!'));
        return $this->showConfirmationDlg();
      }
      $name = $_REQUEST['confirm_name'];
    }
    $user_name = (isset($_SESSION['DISPLAY_NAME'])) ? $_SESSION['DISPLAY_NAME'] : '';
    $user_email = (isset($_SESSION['EMAIL'])) ? $_SESSION['EMAIL'] : '';

    if (defined('TOPIC_ID')) {
      $second_id = TOPIC_ID;
      $page_type = 'TOPICS';
    }
    elseif (defined('POST_ID')) {
      $second_id = POST_ID;
      $page_type = 'NEWS';
    }
    else {
      $second_id = 0;
      $page_type = 'PAGE';
    }

    $page_title = $this->getPageTitle();
    $installation_name = defined('INSTALLATION_NAME') ? INSTALLATION_NAME : '';
    $page_link = substr($this->page_link, strlen(LEPTON_URL));

    $SQL = "INSERT INTO ".TABLE_PREFIX."mod_confirmation_log SET `page_id`='".PAGE_ID."', ".
        "`second_id`='$second_id', `page_type`='$page_type', `page_title`='$page_title', ".
        "`installation_name`='$installation_name', `user_name`='$user_name', `user_email`='$user_email', ".
        "`typed_name`='$name', `typed_email`='$email', `confirmed_at`='".date('Y-m-d H:i:s')."', ".
        "`status`='PENDING', `page_link`='$page_link'";
    if (!$database->query($SQL)) {
      $this->setError(sprintf('[%s - %s] %s', __METHOD__, __LINE__, $database->get_error()));
      return false;
    }
    $data = array();
    return $this->getTemplate('success.htt', $data);
  } // checkConfirmation()

  public static function getPageTitle() {
    global $database;
    if (defined('TOPIC_ID')) {
      // get title from TOPICS
      return $database->get_one("SELECT `title` FROM ".TABLE_PREFIX."mod_topics WHERE `topic_id`='".TOPIC_ID."'", MYSQL_ASSOC);
    }
    elseif (defined('POST_ID')) {
      // get title from NEWS
      return $database->get_one("SELECT `title` FROM ".TABLE_PREFIX."mod_news_posts WHERE `post_id`='".POST_ID."'", MYSQL_ASSOC);
    }
    else {
      // get regular page title
      return $database->get_one("SELECT `page_title` FROM ".TABLE_PREFIX."pages WHERE `page_id`='".PAGE_ID."'", MYSQL_ASSOC);
    }
  } // getPageTitle()

  public static function checkEMail($email) {
    if (preg_match("/^([0-9a-zA-Z]+[-._+&])*[0-9a-zA-Z]+@([-0-9a-zA-Z]+[.])+[a-zA-Z]{2,6}$/i", $email)) {
      return true;
    }
    return false;
  }

  /**
   * Get the realname link to page by PAGE_ID
   *
   * @param INT $pageID
   * @param STR REFERENCE &$fileName
   * @return BOOL
   */
  public function getLinkByPageID($pageID, &$fileName) {
    global $database;
    global $sql_result;

    $fileName = 'ERROR';
    if (false === ($link = $database->get_one("SELECT `link` FROM ".TABLE_PREFIX."pages WHERE `page_id`='$pageID'"))) {
      $this->setError(sprintf('[%s - %s] PAGES: %s', __METHOD__, __LINE__, $database->get_error()));
      return false;
    }
    if (is_file(LEPTON_PATH.PAGES_DIRECTORY.$link.PAGE_EXTENSION)) {
      $fileName = $link.PAGE_EXTENSION;
      return true;
    }
    return false;
  } // getFileNameByPageID

  /**
   * Get the URL of a page by the PAGE_ID
   *
   * @param INT $pageID
   * @param STR reference $url
   * @return BOOL
   */
  public function getUrlByPageID($pageID, &$url, $ignore_topics = false) {
    global $database;
    if (defined('TOPIC_ID') && !$ignore_topics) {
      // it's a TOPICS page
      $SQL = sprintf("SELECT `link` FROM %smod_topics WHERE `topic_id`='%d'", TABLE_PREFIX, TOPIC_ID);
      if (false !== ($link = $database->get_one($SQL, MYSQL_ASSOC))) {
        // include TOPICS settings
        global $topics_directory;
        include LEPTON_PATH . '/modules/topics/module_settings.php';
        $url = LEPTON_URL . $topics_directory . $link . PAGE_EXTENSION;
        return true;
      }
    }
    elseif (defined('POST_ID')) {
      // it's a NEWS page
      $SQL = sprintf("SELECT `link` FROM %smod_news_posts WHERE `post_id`='%d'", TABLE_PREFIX, POST_ID);
      if (false !== ($link = $database->get_one($SQL, MYSQL_ASSOC))) {
        $url = LEPTON_URL.PAGES_DIRECTORY.$link.PAGE_EXTENSION;
        return true;
      }
    }
    elseif ($this->getLinkByPageID($pageID, $url)) {
      $url = LEPTON_URL.PAGES_DIRECTORY.$url;
      return true;
    }
    return false;
  } // getUrlByPageID()

  } // class frontendConfirmation
