<?php
# prevent direct access
if (!defined("SHARED_SECRETS")) { die(""); }

# define page title
define("HEADING_TITLE", "Share a Secret.");

# include header
require_once(ROOT_DIR."/template/header.php");
?>

  <noscript>
    <div class="alert alert-warning">
      <strong>Warning!</strong> You don't have JavaScript enabled. You will not be able to share password-protected secrets.
    </div>
  </noscript>
  <div class="alert alert-danger" id="encrypt-error">
    <strong>Error!</strong> Local encryption failed.
  </div>

  <form role="form" action="/<?= html(SECRET_URI) ?><?= (PLAIN_OUTPUT) ? "?plain" : "" ?>" method="post">
    <h2>Share a Secret:</h2>
    <div id="secret-div">
      <textarea autocomplete="off" class="bg-light border form-control rounded" id="secret" name="secret" rows="5" required="required"></textarea>
      <div id="counter" data-max-param-size="<?= MAX_PARAM_SIZE ?>"><?= MAX_PARAM_SIZE ?></div>
    </div>
    <button type="submit" class="btn btn-primary float-end" id="share-secret-btn" name="share-secret-btn">Share the Secret!</button>
  </form>

  <div class="float-start form-check-inline" id="checkbox-div">
    <input class="form-check-input" type="checkbox" autocomplete="off" id="encrypt-locally" value="" />
    <label class="form-check-label" for="encrypt-locally">Password-protected:</label>
  </div>
  <div id="password-div">
    <input type="password" autocomplete="off" class="bg-light border float-start form-control rounded" id="password" />
    <input type="button" class="btn btn-primary float-end" id="encrypt" value="Protect!" />
  </div>

  <div id="spacer" />

  <link href="<?= html(cache_bust_url("/resources/css/".SECRET_ACTION."/".REQUEST_METHOD.".css")) ?>" integrity="<?= html(subresource_integrity("/resources/css/".SECRET_ACTION."/".REQUEST_METHOD.".css")) ?>" rel="stylesheet" type="text/css" />
  <script src="<?= html(cache_bust_url("/resources/js/".SECRET_ACTION."/".REQUEST_METHOD.".js")) ?>" integrity="<?= html(subresource_integrity("/resources/js/".SECRET_ACTION."/".REQUEST_METHOD.".js")) ?>" type="text/javascript"></script>

<?php
# include footer
require_once(ROOT_DIR."/template/footer.php");
