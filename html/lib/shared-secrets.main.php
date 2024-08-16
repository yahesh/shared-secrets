<?php
# prevent direct access
if (!defined("SHARED_SECRETS")) { die(""); }

function prepare_debug_mode() {
  if (DEBUG_MODE) {
    error_reporting(E_ALL | E_STRICT | E_NOTICE);
  } else {
    error_reporting(0);
  }
  ini_set("display_errors",         (DEBUG_MODE) ? 1 : 0);
  ini_set("display_startup_errors", (DEBUG_MODE) ? 1 : 0);
  ini_set("html_errors",            (DEBUG_MODE) ? 1 : 0);
  ini_set("track_errors",           (DEBUG_MODE) ? 1 : 0);
}

function prepare_default_timezone() {
  date_default_timezone_set(DEFAULT_TIMEZONE);
}

function prepare_runtime() {
  # prepare plain param
  define("PLAIN_OUTPUT", (isset($_POST[PARAM_PLAIN]) || isset($_GET[PARAM_PLAIN])));

  # prepare request method
  define("REQUEST_METHOD", strtolower($_SERVER["REQUEST_METHOD"]));

  # prepare secret input
  $input = null;
  if (isset($_POST[PARAM_SECRET])) {
    if (!empty($_POST[PARAM_SECRET])) {
      $input = $_POST[PARAM_SECRET];
    }
  }
  define("SECRET_INPUT", $input);

  # prepare secret URI
  $uri = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
  if (!is_string($uri)) {
    // fall back to unparsed on failure
    $uri = $_SERVER["REQUEST_URI"];
  }
  if (false !== strpos($uri, MARKER_URL_ENCODE)) {
    $uri = urldecode($uri);
  }
  define("SECRET_URI", nolead($uri, "/"));

  # prepare secret action
  $action = PAGE_READ;
  if (empty(SECRET_URI)) {
    # show share page if no URI is given
    $action = PAGE_SHARE;
  } elseif (in_array(SECRET_URI, [PAGE_HOW, PAGE_IMPRINT, PAGE_PUB])) {
    # show pages based on page URI
    $action = SECRET_URI;
  }
  define("SECRET_ACTION", $action);
}

function main() {
  # prepare the debug mode
  prepare_debug_mode();

  # prepare the default timezone
  prepare_default_timezone();

  # prepare the runtime
  prepare_runtime();

  # only proceed when a GET or POST request is encountered
  if (in_array(REQUEST_METHOD, [METHOD_GET, METHOD_POST])) {
    # import action code based on secret action
    require_once(ROOT_DIR."/actions/".SECRET_ACTION.".php");

    # import page code based on secret action and request method
    require_once(ROOT_DIR."/pages/".SECRET_ACTION."/".REQUEST_METHOD.".php");
  } else {
    # return a corresponding result code
    http_response_code(405);
    header("Allow: GET, POST");
  }
}
