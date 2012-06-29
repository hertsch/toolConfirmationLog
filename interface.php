<?php

/**
 * toolConfirmationLog
 *
 * @author Ralf Hertsch <ralf.hertsch@phpmanufaktur.de>
 * @link http://phpmanufaktur.de
 * @copyright 2012
 * @license MIT License (MIT) http://www.opensource.org/licenses/MIT
 */

if (!defined('WB_PATH')) require '../../config.php';

// wb2lepton compatibility
if (!defined('LEPTON_PATH')) require_once WB_PATH.'/modules/'.basename(dirname(__FILE__)).'/wb2lepton.php';

// use LEPTON 2.x I18n for access to language files
if (!class_exists('LEPTON_Helper_I18n')) require_once LEPTON_PATH.'/modules/'.basename(dirname(__FILE__)).'/framework/LEPTON/Helper/I18n.php';

global $I18n;
if (!is_object($I18n)) {
  $I18n = new LEPTON_Helper_I18n();
}
else {
  $I18n->addFile('DE.php', LEPTON_PATH.'/modules/'.basename(dirname(__FILE__)).'/languages/');
}

function transmit($target_url, &$status='') {
  global $database;
  global $I18n;
  $SQL = "SELECT * FROM ".TABLE_PREFIX."mod_confirmation_log WHERE `status`='PENDING'";
  if (false === ($query = $database->query($SQL))) {
    $status = sprintf('[%s - %s] %s', __FUNCTION__, __LINE__, $database->get_error());
    return false;
  }
  $data = array();
  while (false !== ($log = $query->fetchRow(MYSQL_ASSOC))) {
    $data[$log['id']] = $log;
  }
  if (count($data) > 0) {
    // transmit data
    if (ini_get('allow_url_fopen') != 1) {
      $status = sprintf('[%s - %s] %s', __FUNCTION__, __LINE__, $I18n->translate('Error: php.ini does not allow to open the target URL!'));
      return false;
    }
    $url = $target_url.'/modules/tool_confirmation_log/interface.php';
    $post = array(
        'action' => 'receive',
        'data' => json_encode($data)
        );
    $result = http_post($url, $post);
    $result  = json_decode($result, true);
    if (is_array($result)) {
      // walk through the responses
      $count = 0;
      foreach ($result as $log) {
        if (!isset($log['id']) || !isset($log['status']) || !isset($log['transmitted_at'])) {
          $status = sprintf('[%s - %s] %s', __FUNCTION__, __LINE__, $I18n->translate('Error: received data are invalid'));
          return false;
        }
        $SQL = sprintf("UPDATE %smod_confirmation_log SET `status`='%s', `transmitted_at`='%s' WHERE `id`='%s'",
            TABLE_PREFIX, $log['status'], $log['transmitted_at'], $log['id']);
        if (!$database->query($SQL)) {
          $status = sprintf('[%s - %s] %s', __FUNCTION__, __LINE__, $database->get_error());
          return false;
        }
        $count++;
      }
      $status = $I18n->translate('Transmitted and updated {{ count }} log entries.', array('count' => $count));
      return true;
    }
    else {
      $status = $result;
      return false;
    }
  }
  else {
    // nothing to do
    $status = $I18n->translate('There are no pending log datas to transmit.');
    return true;
  }
} // transmit()

function http_post($url, $data) {
  $data_url = http_build_query ($data);
  $command = $url.'?'.$data_url;
  $ch = curl_init();
  $options = array(
      CURLOPT_SSL_VERIFYPEER => false,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_USERAGENT => 'toolConfirmationLog',
      CURLOPT_URL => $command,
      CURLOPT_POST => true
      );
  curl_setopt_array($ch, $options);
  $result = curl_exec($ch);
  $status = curl_getinfo($ch);
  curl_close($ch);
  return $result;
} // http_post()

function receive() {
  global $database;
  if (isset($_REQUEST['data'])) {
    $data = json_decode($_REQUEST['data'], true);
    if (is_array($data)) {
      $received = array();
      $transmitted_at = date('Y-m-d H:i:s');
      foreach ($data as $log) {
        $set = '';
        foreach ($log as $key => $value) {
          if (($key == 'id') || ($key == 'timestamp')) continue;
          if (!empty($set)) $set .= ', ';
          if ($key == 'status') $value = 'TRANSMITTED';
          if ($key == 'transmitted_at') $value = $transmitted_at;
          $set .= sprintf("`%s`='%s'", $key, $value);
        }
        $SQL = sprintf("INSERT INTO %smod_confirmation_log SET %s", TABLE_PREFIX, $set);
        if (!$database->query($SQL)) {
          exit(sprintf('[%s - %s] %s', __FUNCTION__, __LINE__, $database->get_error()));
        }
        $received[$log['id']] = array(
            'id' => $log['id'],
            'status' => 'TRANSMITTED',
            'transmitted_at' => $transmitted_at
            );
      }
      exit(json_encode($received));
    }
  }
  else {
    exit('no data received');
  }
} // receive()

if (isset($_REQUEST['action']) && ($_REQUEST['action'] == 'receive')) {
  receive();
}