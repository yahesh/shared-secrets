<?php
# prevent direct access
if (!defined("SHARED_SECRETS")) { die(""); }

function check_bool($string) {
  $result = null;

  if (is_string($string) && (0 < strlen($string))) {
    $result = filter_var($string, FILTER_VALIDATE_BOOLEAN);
  }

  return $result;
}

function check_int($string) {
  $result = null;

  if (is_string($string) && (0 < strlen($string))) {
    $result = intval($string);
  }

  return $result;
}

# only define $name if it is not yet defined
function config($name, $value) {
  $result = false;

  if (!defined($name)) {
    $result = define($name, $value);
  }

  return $result;
}

# only define $name if it is not yet defined and $value is not null
function config_env($name, $value) {
  $result = false;

  if ((!defined($name)) && (null !== $value)) {
    $result = define($name, $value);
  }

  return $result;
}

function env($name, $default = null) {
  $result = getenv($name);

  # set the default if the environment variable isn't set
  if ((false === $result) || (0 === strlen($result))) {
    $result = $default;
  }

  return $result;
}

function load_dot_env($filename) {
  # read the .env file
  $dotenv = parse_ini_file($filename);
  if (false !== $dotenv) {
    foreach ($dotenv as $key => $value) {
      # only set environment variables that are not already set
      if (false === getenv($key)) {
        putenv($key."=".$value);
      }
    }
  }
}

function split_rsa_keys($string) {
  $result = null;

  if (is_string($string) && (0 < strlen($string))) {
    $result = [];

    if (false !== preg_match_all(REGEX_RSA_FULL_KEY, $string, $matches)) {
      if (array_key_exists("rsakeys", $matches)) {
        # cleanup strings
        foreach ($matches["rsakeys"] as $match_key => $match_value) {
          $lines = explode("\n", $match_value);
          foreach ($lines as $line_key => $line_value) {
            $lines[$line_key] = trim($line_value);
          }
          $matches["rsakeys"][$match_key] = implode("\n", $lines);
        }

        $result = $matches["rsakeys"];
      }
    }
  }

  return $result;
}

function prepare_configuration() {
  if (is_file(ROOT_DIR."/config/config.php")) {
    require_once(ROOT_DIR."/config/config.php");
  }
}

function prepare_defaults() {
  config("DEBUG_MODE",       false);
  config("DEFAULT_TIMEZONE", "Europe/Berlin");
  config("IMPRINT_TEXT",     "Who provides this service?");
  config("IMPRINT_URL",      "http://127.0.0.1/");
  config("JUMBO_SECRETS" ,   false);
  config("MAX_PARAM_SIZE",   (JUMBO_SECRETS) ? 16384 : 1024);
  config("MYSQL_DB",         null);
  config("MYSQL_HOST",       "localhost");
  config("MYSQL_PASS",       null);
  config("MYSQL_PORT",       3306);
  config("MYSQL_USER",       null);
  config("READ_ONLY",        false);
  config("RSA_PRIVATE_KEYS", []);
  config("SERVICE_TITLE",    "Shared-Secrets");
  config("SERVICE_URL",      "http://127.0.0.1/");
  config("SHARE_ONLY",       false);
  config("SQLITE_PATH",      ROOT_DIR."/db/db.sqlite");
}

function prepare_environment() {
  if (is_file(ROOT_DIR."/config/.env")) {
    load_dot_env(ROOT_DIR."/config/.env");
  }
  if (is_file(ROOT_DIR."/.env")) {
    load_dot_env(ROOT_DIR."/.env");
  }
  if (is_file("/.env")) {
    load_dot_env("/.env");
  }

  config_env("DEBUG_MODE",       check_bool(env("DEBUG_MODE")));
  config_env("DEFAULT_TIMEZONE", env("DEFAULT_TIMEZONE"));
  config_env("IMPRINT_TEXT",     env("IMPRINT_TEXT"));
  config_env("IMPRINT_URL",      env("IMPRINT_URL"));
  config_env("JUMBO_SECRETS" ,   check_bool(env("JUMBO_SECRETS")));
  config_env("MAX_PARAM_SIZE",   check_int(env("MAX_PARAM_SIZE")));
  config_env("MYSQL_DB",         env("MYSQL_DB"));
  config_env("MYSQL_HOST",       env("MYSQL_HOST"));
  config_env("MYSQL_PASS",       env("MYSQL_PASS"));
  config_env("MYSQL_PORT",       check_int(env("MYSQL_PORT")));
  config_env("MYSQL_USER",       env("MYSQL_USER"));
  config_env("READ_ONLY",        check_bool(env("READ_ONLY")));
  config_env("RSA_PRIVATE_KEYS", split_rsa_keys(env("RSA_PRIVATE_KEYS")));
  config_env("SERVICE_TITLE",    env("SERVICE_TITLE"));
  config_env("SERVICE_URL",      env("SERVICE_URL"));
  config_env("SHARE_ONLY",       check_bool(env("SHARE_ONLY")));
  config_env("SQLITE_PATH",      env("SQLITE_PATH"));
}

function prepare_migration() {
  config_env("SECRET_SHARING_URL", env("SECRET_SHARING_URL"));
  if (defined("SECRET_SHARING_URL")) {
    config("SERVICE_URL", "SECRET_SHARING_URL");
  }
}

function configure() {
  # define configuration values if they are set in the environment
  prepare_environment();

  # define configuration values if a config file exists
  prepare_configuration();

  # ensure that old values are supported
  prepare_migration(); 

  # set defaults for everything else
  prepare_defaults();
}
