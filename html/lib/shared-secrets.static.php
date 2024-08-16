<?php
# prevent direct access
if (!defined("SHARED_SECRETS")) { die(""); }

# define Apache Bugfix length
define("APACHE_BUGFIX_LENGTH", 64);

# define DB queries
define("MYSQL_WRITE",        "INSERT IGNORE INTO secrets (keyid, fingerprint, time) VALUES (?, ?, CURRENT_TIMESTAMP)");
define("SQLITE_CREATE",      "CREATE TABLE IF NOT EXISTS secrets (keyid VARCHAR(64), fingerprint VARCHAR(64), time TIMESTAMP, PRIMARY KEY (keyid, fingerprint))");
define("SQLITE_FINGERPRINT", ":fingerprint");
define("SQLITE_KEYID",       ":keyid");
define("SQLITE_WRITE",       "INSERT OR IGNORE INTO secrets (keyid, fingerprint, time) VALUES (:keyid, :fingerprint, CURRENT_TIMESTAMP)");

# define encoding markers
define("MARKER_BASE64_A",     "+");
define("MARKER_BASE64_B",     "/");
define("MARKER_BASE64_END",   "=");
define("MARKER_URL_BASE64_A", "-");
define("MARKER_URL_BASE64_B", "_");
define("MARKER_URL_ENCODE",   "%");

# define method names
define("METHOD_GET",  "get");
define("METHOD_POST", "post");

# define OpenSSL encryption fields
define("OPENSSL_CHECKMAC",      "checkmac");
define("OPENSSL_ENCKEY",        "enckey");
define("OPENSSL_ENCMESSAGE",    "encmessage");
define("OPENSSL_FULLMESSAGE",   "fullmessage");
define("OPENSSL_KEY",           "key");
define("OPENSSL_MAC",           "mac");
define("OPENSSL_MACKEY",        "mackey");
define("OPENSSL_MACMESSAGE",    "macmessage");
define("OPENSSL_MESSAGE",       "message");
define("OPENSSL_NONCE",         "nonce");
define("OPENSSL_RSAKEYCOUNT",   "rsakeycount");
define("OPENSSL_RSAKEYIDS",     "rsakeyids");
define("OPENSSL_RSAKEYLENGTHS", "rsakeylengths");
define("OPENSSL_RSAKEYS",       "rsakeys");
define("OPENSSL_SALT",          "salt");
define("OPENSSL_VERSION",       "version");

# define page names
define("PAGE_HOW",     "how");
define("PAGE_IMPRINT", "imprint");
define("PAGE_PUB",     "pub");
define("PAGE_READ",    "read");
define("PAGE_SHARE",   "share");

# define parameter values
define("PARAM_PLAIN",  "plain");
define("PARAM_SECRET", "secret");

# define RegEx values
define("REGEX_RSA_FULL_KEY",        "@(?<rsakeys>-----BEGIN (?:RSA )?(?:PRIVATE|PUBLIC) KEY-----(?:.*)-----END (?:RSA )?(?:PRIVATE|PUBLIC) KEY-----)@is");
define("REGEX_RSA_RAW_PRIVATE_KEY", "@-----BEGIN (?:RSA )?PRIVATE KEY-----(?<rawkeys>.*)-----END (?:RSA )?PRIVATE KEY-----@is");
define("REGEX_RSA_RAW_PUBLIC_KEY",  "@-----BEGIN (?:RSA )?PUBLIC KEY-----(?<rawkeys>.*)-----END (?:RSA )?PUBLIC KEY-----@is");
