<?php

  # prevent direct access
  if (!defined("SYS11_SECRETS")) { die(""); }

  # prevents cache hits with wrong CSS
  $cache_value = md5_file(__FILE__);

?>
      <!-- footer -->
    </div>

    <script src="/vendors/bootstrap/js/bootstrap.bundle.min.js?<?php print($cache_value); ?>" type="text/javascript"></script>
  </body>
</html>
