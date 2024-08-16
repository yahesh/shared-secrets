<?php
# prevent direct access
if (!defined("SHARED_SECRETS")) { die(""); }

# get result and error
define("RESULT", read_secret(SECRET_URI, $error));
define("ERROR",  $error);

# set the correct response code on error
if ((null === RESULT) || (false !== ERROR)) {
  http_response_code(403);
}

if (PLAIN_OUTPUT) {
  # include plain response
  require_once(ROOT_DIR."/pages/".SECRET_ACTION."/".REQUEST_METHOD.".plain.php");
} else {
  # include HTML reponse
  require_once(ROOT_DIR."/pages/".SECRET_ACTION."/".REQUEST_METHOD.".html.php");
}
