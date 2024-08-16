<?php
# prevent direct access
if (!defined("SHARED_SECRETS")) { die(""); }
?>
      <!-- footer -->
    </div>

    <script src="<?= html(cache_bust_url("/vendors/bootstrap/js/bootstrap.bundle.min.js")) ?>" integrity="<?= html(subresource_integrity("/vendors/bootstrap/js/bootstrap.bundle.min.js")) ?>" type="text/javascript"></script>
  </body>
</html>
