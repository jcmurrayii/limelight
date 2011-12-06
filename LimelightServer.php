<?php

require_once 'lib/restPHP/Server.php';
require_once 'lib/CachedRequest/HTTP/FileCachedRequest.php';
require_once 'LimelightConfig.php';

/**
 * This is the LimelightServer class.
 */
class LimelightServer extends restPHP_Server {

  /**
   * Constructor
   */
  function __construct() {

    // Call the constructor.
    parent::_construct();

    // Override the config based on the Limelight configuration.
    $this->config = array_merge($this->config, LimelightConfig::getConfig());

    // Add the organization ID to the base_url.
    $this->base_url .= ('/' . $this->config['organization_id']);
  }

  /**
   * Add params to the request.
   *
   * @param type $request
   * @param type $params
   */
  protected function addParams(&$request, $params) {

    // Iterate through the params and add them to the request.
    if ($params) {
      foreach ($params as $key => $value) {
        if ($key == 'media_file') {
          $request->addUpload($key, $value);
        }
        else {
          $request->addPostParameter($key, $value);
        }
      }
    }
  }

  /**
   * Perform an authentication on this request.
   */
  protected function authenticate(&$request) {

    // Only authenticate under certain conditions.
    if ($this->config['authenticate'] && $this->config['access_key'] && $this->config['secret']) {
      $parsed_url = parse_url($request->url->getURL());
      $str_to_sign = strtolower($request->method . '|' . $parsed_url['host'] . '|' . $parsed_url['path']) . '|';

      // Get the query variables, and make sure the required ones are set for authentication.
      $params = $request->url->getQueryVariables();
      if (!isset($params['access_key'])) {
        $params['access_key'] = $this->config['access_key'];
        $request->url->setQueryVariable('access_key', $params['access_key']);
      }
      if (!isset($params['expires'])) {
        $params['expires'] = time() + 300;
        $request->url->setQueryVariable('expires', $params['expires']);
      }

      // Sort them in alphabetical order.
      $keys = array_keys($params);
      sort($keys);

      // Iterate through the keys.
      foreach ($keys as $key) {
        $str_to_sign .= $key . '=' .$params[$key] . '&';
      }

      // Remove the last & from the path.
      $str_to_sign = rtrim($str_to_sign,'&');
      $signature = base64_encode(hash_hmac('sha256', $str_to_sign, $this->config['secret'], true));
      $request->url->setQueryVariable('signature', $signature);
    }
  }

  /**
   * Performs a save call.
   */
  public function save($entity, $endpoint) {
    $this->config['authenticate'] = TRUE;
    $ret = parent::save($entity, $endpoint);
    $this->config['authenticate'] = FALSE;
    return $ret;
  }

  /**
   * Deletes an entity.
   */
  public function delete($endpoint) {
    $this->config['authenticate'] = TRUE;
    $ret = parent::delete($endpoint);
    $this->config['authenticate'] = FALSE;
    return $ret;
  }
}
?>