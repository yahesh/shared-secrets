<?php
# prevent direct access
if (!defined("SHARED_SECRETS")) { die(""); }

# define page title
define("HEADING_TITLE", "Share a Secret.");

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
      <strong>Warning!</strong> You don't have JavaScript enabled. You will not be able to share password-protected secrets.
    </div>
  </noscript>

  <h2>Share a Secret:</h2>
  <pre class="bg-light border rounded" id="secret"><?= $result ?></pre>
  <input type="button" class="btn btn-primary float-end" id="copy-to-clipboard" value="Copy to Clipboard!" />

  <div id="spacer" />

  <link href="<?= html(cache_bust_url("/resources/css/".SECRET_ACTION."/".REQUEST_METHOD.".css")) ?>" integrity="<?= html(subresource_integrity("/resources/css/".SECRET_ACTION."/".REQUEST_METHOD.".css")) ?>" rel="stylesheet" type="text/css" />
  <script src="<?= html(cache_bust_url("/resources/js/".SECRET_ACTION."/".REQUEST_METHOD.".js")) ?>" integrity="<?= html(subresource_integrity("/resources/js/".SECRET_ACTION."/".REQUEST_METHOD.".js")) ?>" type="text/javascript"></script>

<?php
# include footer
require_once(ROOT_DIR."/template/footer.php");
