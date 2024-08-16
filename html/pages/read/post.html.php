<?php
# prevent direct access
if (!defined("SHARED_SECRETS")) { die(""); }

# define page title
define("HEADING_TITLE", "Read a Secret.");

$result = "";
if ((null !== RESULT) && (false === ERROR)) {
  $result = html(RESULT);
} else {
  $result = "<strong>ERROR:</strong> ".html(ERROR);
}

# include header
require_once(ROOT_DIR."/template/header.php");
?>

  <noscript>
    <div class="alert alert-warning">
      <strong>Warning!</strong> You don't have JavaScript enabled. You will not be able to read password-protected secrets.
    </div>
  </noscript>
  <div class="alert alert-danger" id="decrypt-error">
    <strong>Error!</strong> Local decryption failed.
  </div>

  <h2>Read a Secret:</h2>
  <pre class="bg-light border rounded" id="secret"><?= $result ?></pre>
  <input type="button" class="btn btn-primary float-end" id="copy-to-clipboard" value="Copy to Clipboard!" />

  <div class="float-start form-check-inline" id="checkbox-div">
    <input class="form-check-input" type="checkbox" autocomplete="off" id="decrypt-locally" value="" />
    <label class="form-check-label" for="decrypt-locally">Password-protected:</label>
  </div>
  <div id="password-div">
    <input type="password" autocomplete="off" class="bg-light border float-start form-control rounded" id="password" />
    <input type="button" class="btn btn-primary float-end" id="decrypt" value="Unprotect!" />
  </div>

  <div id="spacer" />

  <link href="<?= html(cache_bust_url("/resources/css/".SECRET_ACTION."/".REQUEST_METHOD.".css")) ?>" integrity="<?= html(subresource_integrity("/resources/css/".SECRET_ACTION."/".REQUEST_METHOD.".css")) ?>" rel="stylesheet" type="text/css" />
  <script src="<?= html(cache_bust_url("/resources/js/".SECRET_ACTION."/".REQUEST_METHOD.".js")) ?>" integrity="<?= html(subresource_integrity("/resources/js/".SECRET_ACTION."/".REQUEST_METHOD.".js")) ?>" type="text/javascript"></script>

<?php
# include footer
require_once(ROOT_DIR."/template/footer.php");
