<?php
# prevent direct access
if (!defined("SHARED_SECRETS")) { die(""); }

# convert the URL to something that Apache supports
function apache_bugfix_encode($url) {
  return implode("/", str_split($url, APACHE_BUGFIX_LENGTH));
}

# undo the conversion to something that Apache supports
function apache_bugfix_decode($url) {
  return implode("", explode("/", $url));
}

# create a cache-busting URL
function cache_bust_url($file, $algo = "sha256") {
  return $file."?".url_base64_encode(base64_encode(hash_file($algo, ROOT_DIR."/".nolead($file, "/"), true)));
}

# create the secret sharing link
function get_secret_sharing_link($secret) {
  return trail(SERVICE_URL, "/").apache_bugfix_encode(url_base64_encode(base64_encode($secret)));
}

# escape HTML in the given $string
function html($string) {
  return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, "UTF-8", false);
}

# checks if $string starts with $lead
# if not then $lead is prepended to $string
function lead($string, $lead) {
  $result = ($string ?? "");

  if ($lead !== substr($result, 0, strlen($lead))) {
    $result = $lead.$result;
  }

  return $result;
}

# checks if $string starts with $lead
# if it does then $lead is removed from $string
function nolead($string, $lead) {
  $result = ($string ?? "");

  # repeat until there's no match
  while (0 === strpos($result, $lead)) {
    $result = substr($result, strlen($lead));
  }

  return $result;
}

# checks if $string ends with $trail
# if it does then $trail is removed from $string
function notrail($string, $trail) {
  $result = $string;

  # repeat until there's no match
  while ($trail === substr($result, -strlen($trail))) {
    $result = substr($result, 0, -strlen($trail));
  }

  return $result;
}

# read the secret from the secret sharing link
function parse_secret_sharing_link($url) {
  return base64_decode(url_base64_decode(apache_bugfix_decode(nolead($url, trail(SERVICE_URL, "/")))), true);
}

# generate the subresource integrity string
function subresource_integrity($file, $algo = "sha256") {
  return $algo."-".base64_encode(hash_file($algo, ROOT_DIR."/".nolead($file, "/"), true));
}

# checks if $string ends with $trail
# if not then $trail is appended to $string
function trail($string, $trail) {
  $result = $string;

  if ($trail !== substr($result, -strlen($trail))) {
    $result = $result.$trail;
  }

  return $result;
}

# convert URL-safe Base64 encoding to standard Base64 encoding
function url_base64_decode($url_base64_content) {
  $result = null;

  if (is_string($url_base64_content)) {
    $result = str_replace([MARKER_URL_BASE64_A, MARKER_URL_BASE64_B], [MARKER_BASE64_A, MARKER_BASE64_B],
                          $url_base64_content);

    # fill up with end markers as necessary
    while (0 !== (strlen($result) % 4)) {
      $result .= MARKER_BASE64_END;
    }
  }

  return $result;
}

# convert standard Base64 encoding to URL-safe Base64 encoding
function url_base64_encode($base64_content) {
  $result = null;

  if (is_string($base64_content)) {
    $result = str_replace([MARKER_BASE64_A, MARKER_BASE64_B], [MARKER_URL_BASE64_A, MARKER_URL_BASE64_B],
                          rtrim($base64_content, MARKER_BASE64_END));
  }

  return $result;
}
