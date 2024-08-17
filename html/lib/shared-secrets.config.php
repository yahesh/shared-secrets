<?php
# prevent direct access
if (!defined("SHARED_SECRETS")) { die(""); }

function check_null($string) {
  $result = false;

  if (is_string($string) && (0 < strlen($string))) {
    $result = (0 === strcasecmp($string, ENV_NULL));
  }

  return $result;
}

# only define $name if it is not yet defined
function config($name, $value) {
  $result = false;

  # handle placeholders in string
  $tmp = handle_placeholders($value);
  if (null !== $tmp) {
    $value = $tmp;
  }

  if (!defined($name)) {
    $result = define($name, $value);
  }

  return $result;
}

# only define $name if it is not yet defined and the environment variable exists,
# identify booleans, integers and RSA key arrays automatically
function config_env($name) {
  $result = false;

  # get environment variable
  $value = getenv($name);

  # only proceed if the environment variable exists
  if (false !== $value) {
    # check if this is the specific null string
    if (check_null($value)) {
      $value = null;
    } else {
      # check if this is an integer string,
      # do this first so that "0" and "1" are not handled as booleans
      $tmp = get_int($value);
      if (null !== $tmp) {
        $value = $tmp;
      } else {
        # check if this is a boolean string
        $tmp = get_bool($value);
        if (null !== $tmp) {
          $value = $tmp;
        } else {
          # check if this a string containing RSA keys
          $tmp = get_rsa_keys($value);
          if (null !== $tmp) {
            $value = $tmp;
          }
        }
      }
    }

    # handle placeholders in string
    $tmp = handle_placeholders($value);
    if (null !== $tmp) {
      $value = $tmp;
    }

    if (!defined($name)) {
      $result = define($name, $value);
    }
  }

  return $result;
}

function get_bool($string) {
  $result = null;

  if (is_string($string) && (0 < strlen($string))) {
    $result = filter_var($string, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
  }

  return $result;
}

function get_int($string) {
  $result = null;

  if (is_string($string) && (0 < strlen($string))) {
    $result = filter_var($string, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
  }

  return $result;
}

function get_rsa_keys($string) {
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

    # reset result
    if (0 >= count($result)) {
      $result = null;
    }
  }

  return $result;
}

function handle_placeholders($string) {
  $result = null;

  if (is_string($string) && (0 < strlen($string))) {
    $result = $string;

    if (false !== preg_match_all(REGEX_ENV_PLACEHOLDERS, $result, $matches)) {
      $search  = [];
      $replace = [];

      if (count($matches["constants"]) === count($matches["placeholders"])) {
        # prepare replacement arrays
        foreach ($matches["placeholders"] as $key => $value) {
          # defined configuration takes precedence over other environment variables
          if (defined($matches["constants"][$key])) {
            $search[]  = $matches["placeholders"][$key];
            $replace[] = constant($matches["constants"][$key]);
          } else {
            # only replace with an environment variable if it is defined
            $tmp = getenv($matches["constants"][$key]);
            if (false !== $tmp) {
              $search[]  = $matches["placeholders"][$key];
              $replace[] = $tmp;
            }
          }
        }
      }

      $result = str_replace($search, $replace, $result);
    }
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
        putenv("{$key}={$value}");
      }
    }
  }
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

  config_env("DEBUG_MODE");
  config_env("DEFAULT_TIMEZONE");
  config_env("IMPRINT_TEXT");
  config_env("IMPRINT_URL");
  config_env("JUMBO_SECRETS");
  config_env("MAX_PARAM_SIZE");
  config_env("MYSQL_DB");
  config_env("MYSQL_HOST");
  config_env("MYSQL_PASS");
  config_env("MYSQL_PORT");
  config_env("MYSQL_USER");
  config_env("READ_ONLY");
  config_env("RSA_PRIVATE_KEYS");
  config_env("SERVICE_TITLE");
  config_env("SERVICE_URL");
  config_env("SHARE_ONLY");
  config_env("SQLITE_PATH");
}

function prepare_migration() {
  config_env("SECRET_SHARING_URL");
  if (defined("SECRET_SHARING_URL")) {
    config("SERVICE_URL", SECRET_SHARING_URL);
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
