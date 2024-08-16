<?php
# prevent direct access
if (!defined("SHARED_SECRETS")) { die(""); }

# set correct content type
header("Content-Type: text/plain");

if ((null !== RESULT) && (false === ERROR)) {
  print(RESULT);
}
