<?php
# prevent direct access
if (!defined("SHARED_SECRETS")) { die(""); }
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title><?= html(SERVICE_TITLE) ?></title>

    <link href="<?= html(cache_bust_url("/vendors/bootstrap/css/bootstrap.min.css")) ?>" integrity="<?= html(subresource_integrity("/vendors/bootstrap/css/bootstrap.min.css")) ?>" rel="stylesheet" type="text/css" />
  </head>

  <body>
    <nav class="bg-dark navbar-dark navbar navbar-expand-lg">
      <div class="container-fluid justify-content-center">
        <a class="navbar-brand" href="/"><?= html(SERVICE_TITLE) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarColor01" aria-controls="navbarColor01" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse flex-grow-0 navbar-collapse" id="navbarColor01">
          <ul class="mb-2 mb-lg-0 me-auto mx-auto navbar-nav">
            <li class="nav-item">
              <a class="nav-link <?= (empty(SECRET_URI)) ? "active" : "" ?>" href="/">Share a secret.</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= (0 === strcmp(SECRET_URI, PAGE_HOW)) ? "active" : "" ?>" href="/how">How does this service work?</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= (0 === strcmp(SECRET_URI, PAGE_PUB)) ? "active" : "" ?>" href="/pub">Download the public key.</a>
            </li>
            <li class="nav-item">
              <a class="nav-link <?= (0 === strcmp(SECRET_URI, PAGE_IMPRINT)) ? "active" : "" ?>" href="/imprint"><?= html(IMPRINT_TEXT) ?></a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <div class="bg-light p-4 text-center">
      <h1><?= html(HEADING_TITLE) ?></h1>
      <p>This page allows you to share a secret through a secret sharing link.<br />
         The secret is stored in the secret sharing link and not on the server.<br />
         A secret sharing link can only be used once.</p>
    </div>

    <div class="container mt-5">
      <!-- header -->
