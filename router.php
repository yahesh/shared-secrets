<?php
// Usage: `php -S localhost:8080 ./router.php`

// this script shall only be called from the CLI server
if ("cli-server" !== PHP_SAPI) { die(""); }

function preg_match_array($pattern, $subject) {
  $result = false;

  if (is_array($pattern) && is_string($subject)) {
    foreach ($pattern as $pattern_item) {
      $result = (1 === preg_match($pattern_item, $subject));

      // it's enough to have one match
      if ($result) {
        break;
      }
    }
  }

  return $result;
}

// do some URL handling
$result = false;
$path   = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
if (preg_match_array(["@^\/\.dockerignore$@",
                      "@^\/\.env(\.default)?$@",
                      "@^\/\.git(\/.*)?$@",
                      "@^\/\.gitattributes$@",
                      "@^\/\.gitignore$@",
                      "@^\/\.htaccess$@",
                      "@^\/CHANGELOG(\.md)?$@",
                      "@^\/ENCRYPTION(\.md)?$@",
                      "@^\/LICENSE(\.md)?$@",
                      "@^\/README(\.md)?$@",
                      "@^\/SECURITY(\.md)?$@",
                      "@^\/Dockerfile$@",
                      "@^\/router\.php$@",
                      "@^\/defaults(\/.*)?$@",
                      "@^\/(html\/)?actions(\/.*)?$@",
                      "@^\/(html\/)?config(\/.*)?$@",
                      "@^\/(html\/)?db(\/.*)?$@",
                      "@^\/(html\/)?lib(\/.*)?$@",
                      "@^\/(html\/)?pages(\/.*)?$@",
                      "@^\/(html\/)?template(\/.*)?$@"],
                     $path)) {
  // single entrypoint
  require_once(__DIR__."/index.php");
  $result = true;
} elseif (preg_match_array(["@^\/resources(\/.*)?$@",
                            "@^\/vendors(\/.*)?$@"],
                           $path) &&
          is_file(__DIR__."/html{$path}")) {
  // redirect direct accesses
  http_response_code(302);
  header("Location: /html{$path}");
  $result = true;
} elseif (!is_file(__DIR__.$path)) {
  // single entrypoint
  require_once(__DIR__."/index.php");
  $result = true;
}
return $result;
