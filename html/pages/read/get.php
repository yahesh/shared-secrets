<?php
# prevent direct access
if (!defined("SHARED_SECRETS")) { die(""); }

# define page title
define("HEADING_TITLE", "Read a Secret.");

# include header
require_once(ROOT_DIR."/template/header.php");
?>

  <noscript>
    <div class="alert alert-warning">
      <strong>Warning!</strong> You don't have JavaScript enabled. You will not be able to read password-protected secrets.
    </div>
  </noscript>

  <h2>Read a Secret:</h2>
  <pre class="bg-light border rounded" id="secret"><?= html(trail(SERVICE_URL, "/").SECRET_URI) ?><?= (PLAIN_OUTPUT) ? "?plain" : "" ?></pre>

  <form role="form" action="/<?= html(SECRET_URI) ?><?= (PLAIN_OUTPUT) ? "?plain" : "" ?>" method="post">
    <button type="submit" class="btn btn-primary float-end" id="read-secret-btn" name="read-secret-btn">Read the Secret!</button>
  </form>

  <div id="spacer" />

  <link href="<?= html(cache_bust_url("/resources/css/".SECRET_ACTION."/".REQUEST_METHOD.".css")) ?>" integrity="<?= html(subresource_integrity("/resources/css/".SECRET_ACTION."/".REQUEST_METHOD.".css")) ?>" rel="stylesheet" type="text/css" />
  <script src="<?= html(cache_bust_url("/resources/js/".SECRET_ACTION."/".REQUEST_METHOD.".js")) ?>" integrity="<?= html(subresource_integrity("/resources/js/".SECRET_ACTION."/".REQUEST_METHOD.".js")) ?>" type="text/javascript"></script>

<?php
# include footer
require_once(ROOT_DIR."/template/footer.php");
