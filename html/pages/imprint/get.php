<?php
# prevent direct access
if (!defined("SHARED_SECRETS")) { die(""); }

# redirect to configured imprint URL
redirect_page(IMPRINT_URL);
