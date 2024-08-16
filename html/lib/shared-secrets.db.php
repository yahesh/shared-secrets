<?php
# prevent direct access
if (!defined("SHARED_SECRETS")) { die(""); }

function db_write($keyid, $fingerprint, &$error) {
  $result = false;

  # check which database we should use
  if (mysql_check()) {
    $result = mysql_write($keyid, $fingerprint, $error);
  } else {
    # create the SQLite database if it does not exist
    if (!is_file(SQLITE_PATH)) {
      sqlite_create();
    }
    $result = sqlite_write($keyid, $fingerprint, $error);
  }

  return $result;
}

# check if all values for a proper MySQL connection are set
function mysql_check() {
  return (is_string(MYSQL_DB) &&
          is_string(MYSQL_HOST) &&
          is_int(MYSQL_PORT) &&
          is_string(MYSQL_PASS) &&
          is_string(MYSQL_USER));
}

function mysql_write($keyid, $fingerprint, &$error) {
  $result = false;

  if ($link = mysqli_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS, MYSQL_DB, MYSQL_PORT)) {
    try {
      if ($statement = mysqli_prepare($link, MYSQL_WRITE)) {
        if (mysqli_stmt_bind_param($statement, "ss", $keyid, $fingerprint)) {
          if (mysqli_stmt_execute($statement)) {
            if (1 === mysqli_affected_rows($link)) {
              $result = true;
            } else {
              $error = "Secret has already been retrieved.";
            }
          } else {
            if (DEBUG_MODE) {
              $error = "Insert statement could not be executed";
            }
          }
        } else {
          if (DEBUG_MODE) {
            $error = "Insert statement parameters could not be bound.";
          }
        }
      } else {
        if (DEBUG_MODE) {
          $error = "Insert statement could not be prepared.";
        }
      }
    } finally {
      mysqli_close($link);
    }
  } else {
    if (DEBUG_MODE) {
      $error = "Database connection could not be established.";
    }
  }

  return $result;
}

function sqlite_create() {
  $result = false;

  if ($link = new SQLite3(SQLITE_PATH, SQLITE3_OPEN_CREATE | SQLITE3_OPEN_READWRITE)) {
    try {
      $result = (false !== $link->query(SQLITE_CREATE));
    } finally {
      $link->close();
    }
  }

  return $result;
}

function sqlite_write($keyid, $fingerprint, &$error) {
  $result = false;

  if ($link = new SQLite3(SQLITE_PATH, SQLITE3_OPEN_READWRITE)) {
    try {
      if ($statement = $link->prepare(SQLITE_WRITE)) {
        if ($statement->bindValue(SQLITE_KEYID,       $keyid) &&
            $statement->bindValue(SQLITE_FINGERPRINT, $fingerprint)) {
          if ($execution = $statement->execute()) {
            try {
              if (0 !== $link->lastInsertRowID()) {
                $result = true;
              } else {
                $error = "Secret has already been retrieved.";
              }
            } finally {
              $execution->finalize();
            }
          } else {
            if (DEBUG_MODE) {
              $error = "Insert statement could not be executed";
            }
          }
        } else {
          if (DEBUG_MODE) {
            $error = "Insert statement parameters could not be bound.";
          }
        }
      } else {
        if (DEBUG_MODE) {
          $error = "Insert statement could not be prepared.";
        }
      }      
    } finally {
      $link->close();
    }
  } else {
    if (DEBUG_MODE) {
      $error = "Database connection could not be established.";
    }
  }

  return $result;
}
