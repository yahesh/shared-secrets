<?php
# Shared-Secrets v0.40b2
#
# Copyright (c) 2023-2024, Yahe
# Copyright (c) 2016-2023, SysEleven GmbH
# All rights reserved.
#
# This page allows you to share a secret through a secret sharing link.
# The secret is stored in the secret sharing link and not on the server.
# A secret sharing link can only be used once.

# prevent direct access
define("SHARED_SECRETS", true);

# store the __DIR__ constant in an additional constant
# so that is does not change between script files
define("ROOT_DIR", __DIR__);

# include configuration, static definitions and functions
require_once(ROOT_DIR."/lib/shared-secrets.db.php");
require_once(ROOT_DIR."/lib/shared-secrets.config.php");
require_once(ROOT_DIR."/lib/shared-secrets.crypto.php");
require_once(ROOT_DIR."/lib/shared-secrets.main.php");
require_once(ROOT_DIR."/lib/shared-secrets.static.php");
require_once(ROOT_DIR."/lib/shared-secrets.url.php");

# execute
configure();
main();
