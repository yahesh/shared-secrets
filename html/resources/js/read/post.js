/* interface functions */

// prevent reposting via the history
if (window.history.replaceState) {
  window.history.replaceState(null, null, window.location.href);
}

// find copy-to-clipboard button
var copy_to_clipboard_button = document.getElementById("copy-to-clipboard");
if (null != copy_to_clipboard_button) {
  // attach onClick event
  copy_to_clipboard_button.addEventListener("click", function(){copy_to_clipboard(document.getElementById("secret").textContent);});
}

// find decrypt button
var decrypt_button = document.getElementById("decrypt");
if (null != decrypt_button) {
  // attach onClick event
  decrypt_button.addEventListener("click", function(){decrypt();});
}

// find decrypt-locally checkbox
var decrypt_locally_checkbox = document.getElementById("decrypt-locally");
if (null != decrypt_locally_checkbox) {
  // attach onClick event
  decrypt_locally_checkbox.addEventListener("click", function(){decrypt_locally();});
}

// find password textfield
var password_textfield = document.getElementById("password");
if (null != password_textfield) {
  // attach keyPress event
  password_textfield.addEventListener("keypress", function(event){decrypt_on_return(event);});
}

// action happening on copy to clipboard
async function copy_to_clipboard(text) {
  try {
    await navigator.clipboard.writeText(text);

    document.getElementById("copy-to-clipboard").value = "✔ Copied to Clipboard!";
  } catch (error) {
    console.error(error.message);
  }
}

// action happening on local decryption
async function decrypt() {
  var result = null;

  // check the length of the input
  if ((0 < document.getElementById("secret").innerHTML.length) &&
      (0 < document.getElementById("password").value.length)) {
    result = await decrypt_v00(document.getElementById("secret").innerHTML,
                               document.getElementById("password").value);
  }

  if (null != result) {
    document.getElementById("secret").innerHTML = html_entities(result);

    document.getElementById("decrypt").disabled         = true;
    document.getElementById("decrypt-locally").disabled = true;

    document.getElementById("password").readOnly = "readonly";

    document.getElementById("decrypt-error").style.display = "none";
  } else {
    document.getElementById("decrypt-error").style.display = "block";
  }
}

// show/hide local decryption
function decrypt_locally() {
  if (document.getElementById("decrypt-locally").checked) {
    document.getElementById("decrypt").style.visibility  = "visible";
    document.getElementById("password").style.visibility = "visible";
  } else {
    document.getElementById("decrypt").style.visibility  = "hidden";
    document.getElementById("password").style.visibility = "hidden";
  }
}

// decrypt if return is pressed
function decrypt_on_return(event) {
  if (event.key === "Enter") {
    // prevent the default handler
    event.preventDefault();

    // execute the decryption
    decrypt();
  }
}

/* decryption library */

// decrypt a message with a key and a nonce via AES with the Web Cryptography API
async function aesctr_decrypt(message, key, nonce) {
  return await crypto.subtle.importKey(
    "raw",
    key,
    {
      name : "AES-CTR"
    },
    false,
    [
      "decrypt"
    ]
  ).then(
    function (key) {
      return crypto.subtle.decrypt(
        {
          "name"    : "AES-CTR",
          "counter" : nonce,
          "length"  : 128
        },
        key,
        message
      );
    }
  ).catch(
    function (error) {
      return null;
    }
  );
}

// compare two arrays
function compare(arraya, arrayb) {
  result = (arraya.length == arrayb.length);

  if (result) {
    for (i = 0; i < arraya.length; i++) {
      result = (result && (arraya[i] == arrayb[i]));
    }
  }

  return result;
}

// decrypt a message with a password
async function decrypt_v00(message, password) {
  var result = null;

  try {
    // convert string to array
    password = new TextEncoder("utf-8").encode(password);

    var fullMessage = string2array(atob(message));

    if (82 <= fullMessage.length) {
      var macMessage = fullMessage.slice(  0, -32);
      var mac        = fullMessage.slice(-32);

      var version    = macMessage.slice( 0,  1);
      var salt       = macMessage.slice( 1, 33);
      var nonce      = macMessage.slice(33, 49);
      var encMessage = macMessage.slice(49);

      if (0 == version[0]) {
        var key = await pbkdf2(password, salt);

        if (null != key) {
          var encKey = await hmac(new TextEncoder("utf-8").encode("enc"), key);
          var macKey = await hmac(new TextEncoder("utf-8").encode("mac"), key);

          if ((null != encKey) && (null != macKey)) {
            var checkMac = await hmac(macMessage, macKey);

            if (null != checkMac) {
              // convert to correct type
              checkMac = new Uint8Array(checkMac);

              if (compare(checkMac, mac)) {
                var content = await aesctr_decrypt(encMessage, encKey, nonce);

                if (null != content) {
                  result = new TextDecoder().decode(content);
                }
              }
            }
          }
        }
      }
    }
  } catch (error) {}

  return result;
}

// replace HTML entities
function html_entities(content) {
  return content.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
}

// convert a string to an array
function string2array(string) {
  var result = new Uint8Array(string.length);
  for(i = 0; i < string.length; i++) {
    result[i] = string.charCodeAt(i);
  }

  return result;
}

// calculate the HMAC of a message over a key with the Web Cryptography API
async function hmac(message, key) {
  return await crypto.subtle.importKey("raw",
    key,
    {
      name   : "HMAC",
      "hash" : "SHA-256"
    },
    false,
    [
      "sign"
    ]
  ).then(
    function (key) {
      return crypto.subtle.sign(
        "HMAC",
        key,
        message
      );
    }
  ).catch(
    function (error) {
      return null;
    }
  );
}

// calculate the PBKDF2 of a password over a salt with the Web Cryptography API
async function pbkdf2(password, salt) {
  return await crypto.subtle.importKey(
    "raw",
    password,
    {
      "name" : "PBKDF2"
    },
    false,
    [
      "deriveKey"
    ]
  ).then(
    function (key) {
      return crypto.subtle.deriveKey(
        {
          "name"       : "PBKDF2",
          "salt"       : salt,
          "iterations" : 512000,
          "hash"       : "SHA-256"
        },
        key,
        {
          "name"   : "AES-CTR",
          "length" : 256
        },
        true,
        [
          "encrypt",
          "decrypt"
        ]
      );
    }
  ).then(
    function (key) {
      return crypto.subtle.exportKey(
        "raw",
        key
      );
    }
  ).catch(
    function (error) {
      return null;
    }
  );
}
