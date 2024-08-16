<?php
# prevent direct access
if (!defined("SHARED_SECRETS")) { die(""); }

# just reuse the GET version
require_once(ROOT_DIR."/pages/".SECRET_ACTION."/get.php");
