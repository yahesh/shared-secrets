<?php

  # prevent direct access
  if (!defined("SYS11_SECRETS")) { die(""); }

  # prevents cache hits with wrong CSS
  $cache_value = md5_file(__FILE__);

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title><?php print(htmlentities(SERVICE_TITLE)); ?></title>

    <link href="/vendors/bootstrap/css/bootstrap.min.css?<?php print($cache_value); ?>" rel="stylesheet" type="text/css" />
  </head>

<body>

  <header class="p-3 text-bg-dark">
    <div class="container">
      <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
        <a href="/" class="d-flex align-items-center mb-2 mb-lg-0 text-white text-decoration-none fs-4 me-2">
          <?php print(htmlentities(SERVICE_TITLE)); ?>
        </a>

        <ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
        <li><a class="nav-link px-2 <?php if (empty(SECRET_URI)) { ?> text-white <?php } else { ?> text-secondary <?php } ?>" href="/">Share a secret.</a></li>
          <li><a class="nav-link px-2 <?php if (0 === strcmp(SECRET_URI, HOW_PAGE_NAME)) { ?> text-white <?php } else { ?> text-secondary <?php } ?>" href="/how">How does this service work?</a></li>
          <li><a class="nav-link px-2 <?php if (0 === strcmp(SECRET_URI, PUB_PAGE_NAME)) { ?> text-white <?php } else { ?> text-secondary <?php } ?>" href="/pub">Download the public key.</a></li>
          <li><a class="nav-link px-2 <?php if (0 === strcmp(SECRET_URI, IMPRINT_PAGE_NAME)) { ?> text-white <?php } else { ?> text-secondary <?php } ?>" href="/imprint"><?= (defined("IMPRINT_TEXT") && (null !== IMPRINT_TEXT)) ? html(IMPRINT_TEXT) : "Who provides this service?" ?></a></li>
        </ul>
      </div>
    </div>
  </header>

  <div class="jumbotron text-center mt-3">
    <h1><?php print(htmlentities(PAGE_TITLE)); ?></h1>
    <p>This page allows you to share a secret through a secret sharing link.<br />
       The secret is stored in the secret sharing link and not on the server.<br />
       A secret sharing link can only be used once.</p>
  </div>

  <div class="container">
    <!-- header -->
