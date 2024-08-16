<?php
# prevent direct access
if (!defined("SHARED_SECRETS")) { die(""); }

function decrypt_v00($message, $password, &$error = null, &$fingerprint = null) {
  $result      = null;
  $error       = false;
  $fingerprint = null;

  if (is_string($message) && is_string($password)) {
    $data = [];
    try {
      # check message format
      $data[OPENSSL_FULLMESSAGE] = $message;

      if (82 <= strlen($data[OPENSSL_FULLMESSAGE])) {
        # parse message
        $data[OPENSSL_MACMESSAGE] = substr($data[OPENSSL_FULLMESSAGE],   0, -32);
        $data[OPENSSL_MAC]        = substr($data[OPENSSL_FULLMESSAGE], -32);

        $data[OPENSSL_VERSION]    = substr($data[OPENSSL_MACMESSAGE],  0,  1);
        $data[OPENSSL_SALT]       = substr($data[OPENSSL_MACMESSAGE],  1, 32);
        $data[OPENSSL_NONCE]      = substr($data[OPENSSL_MACMESSAGE], 33, 16);
        $data[OPENSSL_ENCMESSAGE] = substr($data[OPENSSL_MACMESSAGE], 49);

        if ("\x00" === $data[OPENSSL_VERSION]) {
          # derive secure key from password and salt
          $data[OPENSSL_KEY] = hash_pbkdf2("sha256", $password, $data[OPENSSL_SALT], 512000, 0, true);

          if (false !== $data[OPENSSL_KEY]) {
            $data[OPENSSL_ENCKEY] = hash_hmac("sha256", "enc", $data[OPENSSL_KEY], true); // generate enc key
            $data[OPENSSL_MACKEY] = hash_hmac("sha256", "mac", $data[OPENSSL_KEY], true); // generate mac key

            if ((false !== $data[OPENSSL_ENCKEY]) && (false !== $data[OPENSSL_MACKEY])) {
              # calculate MAC with mac key
              $data[OPENSSL_CHECKMAC] = hash_hmac("sha256", $data[OPENSSL_MACMESSAGE], $data[OPENSSL_MACKEY], true);

              if (false !== $data[OPENSSL_CHECKMAC]) {
                if (hash_equals($data[OPENSSL_CHECKMAC], $data[OPENSSL_MAC])) {
                  # decrypt message with enc key
                  $data[OPENSSL_MESSAGE] = openssl_decrypt($data[OPENSSL_ENCMESSAGE], "aes-256-ctr", $data[OPENSSL_ENCKEY], OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $data[OPENSSL_NONCE]);

                  if (false !== $data[OPENSSL_MESSAGE]) {
                    # set result value
                    $result      = $data[OPENSSL_MESSAGE];
                    $fingerprint = $data[OPENSSL_CHECKMAC];
                  } else {
                    $error = "message decryption failed";
                  }
                } else {
                  $error = "mac comparison mismatch";
                }
              } else {
                $error = "mac calculation failed";
              }
            } else {
              $error = "key expansion failed";
            }
          } else {
            $error = "key derivation failed";
          }
        } else {
          $error = "message has wrong version";
        }
      } else {
        $error = "message has wrong length";
      }
    } finally {
      zeroize_array($data);
    }
  } else {
    $error = "insufficient arguments given";
  }

  return $result;
}

function decrypt_v01($message, $recipients, &$error = null, &$keyid = null, &$fingerprint = null) {
  $result      = null;
  $error       = false;
  $fingerprint = null;
  $keyid       = null;

  if (is_string($message) && is_array($recipients)) {
    $data = [];
    try {
      # check message format
      $data[OPENSSL_FULLMESSAGE] = $message;

      if (118 <= strlen($data[OPENSSL_FULLMESSAGE])) {
        # parse message
        $data[OPENSSL_MACMESSAGE] = substr($data[OPENSSL_FULLMESSAGE],   0, -32);
        $data[OPENSSL_MAC]        = substr($data[OPENSSL_FULLMESSAGE], -32);

        $data[OPENSSL_VERSION] = substr($data[OPENSSL_MACMESSAGE], 0, 1);

        $data[OPENSSL_RSAKEYCOUNT]   = hexdec(bin2hex(substr($data[OPENSSL_MACMESSAGE], 1, 2)));
        $data[OPENSSL_RSAKEYIDS]     = [];
        $data[OPENSSL_RSAKEYLENGTHS] = [];
        $data[OPENSSL_RSAKEYS]       = [];

        # iterate through the rsa keys
        $position = 3;
        for ($i = 0; $i < $data[OPENSSL_RSAKEYCOUNT]; $i++) {
          $data[OPENSSL_RSAKEYIDS][$i]     = substr($data[OPENSSL_MACMESSAGE], $position+0, 32);
          $data[OPENSSL_RSAKEYLENGTHS][$i] = hexdec(bin2hex(substr($data[OPENSSL_MACMESSAGE], $position+32, 2)));
          $data[OPENSSL_RSAKEYS][$i]       = substr($data[OPENSSL_MACMESSAGE], $position+34, $data[OPENSSL_RSAKEYLENGTHS][$i]);

          # update position of next entry
          $position = $position+34+$data[OPENSSL_RSAKEYLENGTHS][$i];
        }

        $data[OPENSSL_NONCE]      = substr($data[OPENSSL_MACMESSAGE], $position, 16);
        $data[OPENSSL_ENCMESSAGE] = substr($data[OPENSSL_MACMESSAGE], $position+16);

        if ("\x01" === $data[OPENSSL_VERSION]) {
          # for shared-secrets we only support encryption with one key
          if (1 === $data[OPENSSL_RSAKEYCOUNT]) {
            # set default key value
            $data[OPENSSL_KEY] = false;

            # iterate through the recipients and see whether we find a fitting key id
            $keys     = array_keys($recipients);
            $rsakeyid = null;
            foreach ($keys as $key) {
              $rsakeyid = get_keyid($recipients[$key]);
              if (null !== $rsakeyid) {
                for ($i = 0; $i < count($data[OPENSSL_RSAKEYIDS]); $i++) {
                  if (hash_equals($rsakeyid, $data[OPENSSL_RSAKEYIDS][$i])) {
                    if (openssl_private_decrypt($data[OPENSSL_RSAKEYS][$i], $rsakey, $recipients[$key], OPENSSL_PKCS1_OAEP_PADDING)) {
                      $data[OPENSSL_KEY] = $rsakey;

                      # break after the first decrypted key
                      break;
                    }
                  }
                }

                # break after the first decrypted key
                if (false !== $data[OPENSSL_KEY]) {
                  break;
                }
              }
            }

            if (false !== $data[OPENSSL_KEY]) {
              $data[OPENSSL_ENCKEY] = hash_hmac("sha256", "enc", $data[OPENSSL_KEY], true); // generate enc key
              $data[OPENSSL_MACKEY] = hash_hmac("sha256", "mac", $data[OPENSSL_KEY], true); // generate mac key

              if ((false !== $data[OPENSSL_ENCKEY]) && (false !== $data[OPENSSL_MACKEY])) {
                # calculate MAC with mac key
                $data[OPENSSL_CHECKMAC] = hash_hmac("sha256", $data[OPENSSL_MACMESSAGE], $data[OPENSSL_MACKEY], true);

                if (false !== $data[OPENSSL_CHECKMAC]) {
                  if (hash_equals($data[OPENSSL_CHECKMAC], $data[OPENSSL_MAC])) {
                    # decrypt message with enc key
                    $data[OPENSSL_MESSAGE] = openssl_decrypt($data[OPENSSL_ENCMESSAGE], "aes-256-ctr", $data[OPENSSL_ENCKEY], OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $data[OPENSSL_NONCE]);

                    if (false !== $data[OPENSSL_MESSAGE]) {
                      # set result value
                      $result      = $data[OPENSSL_MESSAGE];
                      $fingerprint = $data[OPENSSL_CHECKMAC];
                      $keyid       = $rsakeyid;
                    } else {
                      $error = "message decryption failed";
                    }
                  } else {
                    $error = "mac comparison mismatch";
                  }
                } else {
                  $error = "mac calculation failed";
                }
              } else {
                $error = "key expansion failed";
              }
            } else {
              $error = "key decryption failed";
            }
          } else {
            $error = "message is encrypted for more than one recipient";
          }
        } else {
          $error = "message has wrong version";
        }
      } else {
        $error = "message has wrong length";
      }
    } finally {
      zeroize_array($data);
    }
  } else {
    $error = "insufficient arguments given";
  }

  return $result;
}

function encrypt_v00($message, $password, &$error = null) {
  $result = null;
  $error  = false;

  if (is_string($message) && is_string($password)) {
    $data = [];
    try {
      # set and generate values
      $data[OPENSSL_VERSION] = "\x00";
      $data[OPENSSL_SALT]    = openssl_random_pseudo_bytes(32, $strong_crypto); // generate random salt
      $data[OPENSSL_MESSAGE] = $message;

      if ((false !== $data[OPENSSL_SALT]) && $strong_crypto) {
        # derive secure key from password and salt
        $data[OPENSSL_KEY] = hash_pbkdf2("sha256", $password, $data[OPENSSL_SALT], 512000, 0, true);

        if (false !== $data[OPENSSL_KEY]) {
          $data[OPENSSL_ENCKEY] = hash_hmac("sha256", "enc", $data[OPENSSL_KEY], true); // generate enc key
          $data[OPENSSL_MACKEY] = hash_hmac("sha256", "mac", $data[OPENSSL_KEY], true); // generate mac key

          if ((false !== $data[OPENSSL_ENCKEY]) && (false !== $data[OPENSSL_MACKEY])) {
            $data[OPENSSL_NONCE] = hex2bin(sprintf("%016x0000000000000000", time())); // generate nonce

            if (false !== $data[OPENSSL_NONCE]) {
              # encrypt message with enc key
              $data[OPENSSL_ENCMESSAGE] = openssl_encrypt($data[OPENSSL_MESSAGE], "aes-256-ctr", $data[OPENSSL_ENCKEY], OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $data[OPENSSL_NONCE]);

              if (false !== $data[OPENSSL_ENCMESSAGE]) {
                # concatenate the mac message
                $data[OPENSSL_MACMESSAGE]  = $data[OPENSSL_VERSION];
                $data[OPENSSL_MACMESSAGE] .= $data[OPENSSL_SALT];
                $data[OPENSSL_MACMESSAGE] .= $data[OPENSSL_NONCE];
                $data[OPENSSL_MACMESSAGE] .= $data[OPENSSL_ENCMESSAGE];

                # calculate MAC with mac key
                $data[OPENSSL_MAC] = hash_hmac("sha256", $data[OPENSSL_MACMESSAGE], $data[OPENSSL_MACKEY], true);

                if (false !== $data[OPENSSL_MAC]) {
                  # concatenate the full message
                  $data[OPENSSL_FULLMESSAGE]  = $data[OPENSSL_MACMESSAGE];
                  $data[OPENSSL_FULLMESSAGE] .= $data[OPENSSL_MAC];

                  # set result value
                  $result = $data[OPENSSL_FULLMESSAGE];
                } else {
                  $error = "mac calculation failed";
                }
              } else {
                $error = "message encryption failed";
              }
            } else {
              $error = "nonce generation failed";
            }
          } else {
            $error = "key expansion failed";
          }
        } else {
          $error = "key derivation failed";
        }
      } else {
        $error = "salt generation failed";
      }
    } finally {
      zeroize_array($data);
    }
  } else {
    $error = "insufficient arguments given";
  }

  return $result;
}

function encrypt_v01($message, $recipients, &$error = null) {
  $result = null;
  $error  = false;

  if (is_string($message) && is_array($recipients)) {
    $data = [];
    try {
      # set and generate values
      $data[OPENSSL_VERSION] = "\x01";
      $data[OPENSSL_KEY]     = openssl_random_pseudo_bytes(32, $strong_crypto); // generate random key
      $data[OPENSSL_MESSAGE] = $message;

      if ((false !== $data[OPENSSL_KEY]) && $strong_crypto) {
        $data[OPENSSL_ENCKEY] = hash_hmac("sha256", "enc", $data[OPENSSL_KEY], true); // generate enc key
        $data[OPENSSL_MACKEY] = hash_hmac("sha256", "mac", $data[OPENSSL_KEY], true); // generate mac key

        if ((false !== $data[OPENSSL_ENCKEY]) && (false !== $data[OPENSSL_MACKEY])) {
          $data[OPENSSL_NONCE] = hex2bin(sprintf("%016x0000000000000000", time())); // generate nonce

          if (false !== $data[OPENSSL_NONCE]) {
            # iterate through recipients and generate rsa keys
            $data[OPENSSL_RSAKEYCOUNT]   = 0;
            $data[OPENSSL_RSAKEYIDS]     = [];
            $data[OPENSSL_RSAKEYLENGTHS] = [];
            $data[OPENSSL_RSAKEYS]       = [];

            $keys = array_keys($recipients);
            foreach ($keys as $key) {
              $rsakeyid = get_keyid($recipients[$key]);
              if (null !== $rsakeyid) {
                if (openssl_public_encrypt($data[OPENSSL_KEY], $rsakey, $recipients[$key], OPENSSL_PKCS1_OAEP_PADDING)) {
                  $data[OPENSSL_RSAKEYCOUNT]         = $data[OPENSSL_RSAKEYCOUNT]+1;
                  $data[OPENSSL_RSAKEYIDS][$key]     = $rsakeyid;
                  $data[OPENSSL_RSAKEYLENGTHS][$key] = strlen($rsakey);
                  $data[OPENSSL_RSAKEYS][$key]       = $rsakey;
                }
              }
            }

            # check if we were able to encrypt for all recipients
            if (count($recipients) === $data[OPENSSL_RSAKEYCOUNT]) {
              # encrypt message with enc key
              $data[OPENSSL_ENCMESSAGE] = openssl_encrypt($data[OPENSSL_MESSAGE], "aes-256-ctr", $data[OPENSSL_ENCKEY], OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $data[OPENSSL_NONCE]);

              if (false !== $data[OPENSSL_ENCMESSAGE]) {
                # concatenate the mac message
                $data[OPENSSL_MACMESSAGE]  = $data[OPENSSL_VERSION];
                $data[OPENSSL_MACMESSAGE] .= hex2bin(sprintf("%04x", $data[OPENSSL_RSAKEYCOUNT]));

                $keys = array_keys($data[OPENSSL_RSAKEYIDS]);
                foreach ($keys as $key) {
                  $data[OPENSSL_MACMESSAGE] .= $data[OPENSSL_RSAKEYIDS][$key];
                  $data[OPENSSL_MACMESSAGE] .= hex2bin(sprintf("%04x", $data[OPENSSL_RSAKEYLENGTHS][$key]));
                  $data[OPENSSL_MACMESSAGE] .= $data[OPENSSL_RSAKEYS][$key];
                }

                $data[OPENSSL_MACMESSAGE] .= $data[OPENSSL_NONCE];
                $data[OPENSSL_MACMESSAGE] .= $data[OPENSSL_ENCMESSAGE];

                # calculate MAC with mac key
                $data[OPENSSL_MAC] = hash_hmac("sha256", $data[OPENSSL_MACMESSAGE], $data[OPENSSL_MACKEY], true);

                if (false !== $data[OPENSSL_MAC]) {
                  # concatenate the full message
                  $data[OPENSSL_FULLMESSAGE]  = $data[OPENSSL_MACMESSAGE];
                  $data[OPENSSL_FULLMESSAGE] .= $data[OPENSSL_MAC];

                  # set result value
                  $result = $data[OPENSSL_FULLMESSAGE];
                } else {
                  $error = "mac calculation failed";
                }
              } else {
                $error = "message encryption failed";
              }
            } else {
              $error = "key encryption failed";
            }
          } else {
            $error = "nonce generation failed";
          }
        } else {
          $error = "key expansion failed";
        }
      } else {
        $error = "key generation failed";
      }
    } finally {
      zeroize_array($data);
    }
  } else {
    $error = "insufficient arguments given";
  }

  return $result;
}

function is_privkey($string) {
  $result = false;

  if (is_string($string)) {
    // cleanup
    $string = trim($string);

    if (false !== preg_match_all(REGEX_RSA_RAW_PRIVATE_KEY, $string, $matches)) {
      if (array_key_exists("rawkeys", $matches)) {
        # make sure that the block only contains one key
        $result = (1 === count($matches["rawkeys"]));
      }
    }
  }

  return $result;
}

function is_pubkey($string) {
  $result = false;

  if (is_string($string)) {
    // cleanup
    $string = trim($string);
    
    if (false !== preg_match_all(REGEX_RSA_RAW_PUBLIC_KEY, $string, $matches)) {
      if (array_key_exists("rawkeys", $matches)) {
        # make sure that the block only contains one key
        $result = (1 === count($matches["rawkeys"]));
      }          
    }
  }

  return $result;
}

function get_keyid($key) {
  $result = null;

  $keypem = get_keypem($key);
  if (null !== $keypem) {
    if (false !== preg_match_all(REGEX_RSA_RAW_PUBLIC_KEY, $keypem, $matches)) {
      if (array_key_exists("rawkeys", $matches)) {
        # make sure that the block only contains one key
        if (1 === count($matches["rawkeys"])) {
          $keyid = str_replace(["\n", "\r"], "", $matches["rawkeys"][0]);
          $keyid = base64_decode($keyid, true);
          if (false !== $keyid) {
            $result = hash("sha256", $keyid, true);
          }
        }
      }
    }
  }

  return $result;
}

function get_keypem($key) {
  $result = null;

  $details = openssl_pkey_get_details($key);
  if (is_array($details) && array_key_exists("key", $details)) {
    try {
      $result = trim($details["key"]);
    } finally {
      zeroize_array($details);
    }
  }

  return $result;
}

function open_privkey($string) {
  $result = null;

  $privkey = openssl_pkey_get_private($string);
  if (false !== $privkey) {
    $result = $privkey;
  }

  return $result;
}

function open_pubkey($string) {
  $result = null;

  $privkey = open_privkey($string);
  if (null !== $privkey) {
    try {
      $details = openssl_pkey_get_details($privkey);
      if (is_array($details) && array_key_exists("key", $details)) {
        try {
          $pubkey = openssl_pkey_get_public($details["key"]);
          if (false !== $pubkey) {
            $result = $pubkey;
          }
        } finally {
          zeroize_array($details);
        }
      }
    } finally {
      # prevent deprecation notice in PHP 8.0 and above
      if (0 > version_compare(PHP_VERSION, "8.0.0")) {
        openssl_pkey_free($privkey);
      }
    }
  }

  return $result;
}

function zeroize_array(&$array) {
  $result = false;

  if (is_array($array)) {
    $result = true;

    $keys = array_keys($array);
    foreach ($keys as $key) {
      if (is_array($array[$key])) {
        $result = $result && zeroize_array($array[$key]);
      } elseif (is_string($array[$key])) {
        for ($i = 0; $i < strlen($array[$key]); $i++) {
          $array[$key][$i] = "\0";
        }
      }
    }
  }

  return $result;
}
