<?php
# prevent direct access
if (!defined("SHARED_SECRETS")) { die(""); }

function read_secret($secret, &$error = null) {
  $result = null;
  $error  = false;

 # only proceed when the share-only mode is not enabled
  if (!SHARE_ONLY) {
    # parse the secret sharing link
    $secret = parse_secret_sharing_link($secret);

    # only proceed when the secret is not empty
    if (!empty($secret)) {
      $keys       = array_keys(RSA_PRIVATE_KEYS);
      $recipients = [];
      foreach ($keys as $key) {
        if (is_privkey(RSA_PRIVATE_KEYS[$key])) {
          # open the private key
          $privkey = open_privkey(RSA_PRIVATE_KEYS[$key]);
          if (null !== $privkey) {
            $recipients[] = $privkey;
          }
        }
      }

      if (0 < count($recipients)) {
        try {
          $decrypted_secret = decrypt_v01($secret, $recipients, $decrypt_error, $keyid, $fingerprint);
        } finally {
          # prevent deprecation notice in PHP 8.0 and above
          if (0 > version_compare(PHP_VERSION, "8.0.0")) {
            $keys = array_keys($recipients);
            foreach ($keys as $key) {
              openssl_pkey_free($recipients[$key]);
            }
          }

          zeroize_array($recipients);
        }

        if (null !== $decrypted_secret) {
          if (db_write(bin2hex($keyid), bin2hex($fingerprint), $error)) {
            $result = $decrypted_secret;
          }
        } else {
          if (DEBUG_MODE) {
            $error = "Decryption failed: $decrypt_error";
          }
        }
      } else {
        if (DEBUG_MODE) {
          $error = "Private key could not be read.";
        }
      }
    } else {
      if (DEBUG_MODE) {
        $error = "The secret must not be empty.";
      }
    }
  } else {
    $error = "The retrieval of secret sharing links is disabled.";
  }

  # set default error if non is given
  if ((null === $result) && (false === $error)) {
    $error = "An unknown error occured.";
  }

  return $result;
}
