/* interface functions */

// find encrypt button
var encrypt_button = document.getElementById("encrypt");
if (null != encrypt_button) {
  // attach onClick event
  encrypt_button.addEventListener("click", function(){encrypt();});
}

// find encrypt-locally checkbox
var encrypt_locally_checkbox = document.getElementById("encrypt-locally");
if (null != encrypt_locally_checkbox) {
  // attach onClick event
  encrypt_locally_checkbox.addEventListener("click", function(){encrypt_locally();});
}

// find password textfield
var password_textfield = document.getElementById("password");
if (null != password_textfield) {
  // attach keyPress event
  password_textfield.addEventListener("keypress", function(event){encrypt_on_return(event);});
}

// find secret textarea
var secret_textarea = document.getElementById("secret");
if (null != secret_textarea) {
  // attach key events
  secret_textarea.addEventListener("input",    function(){update_counter();});
  secret_textarea.addEventListener("keydown",  function(){update_counter();});
  secret_textarea.addEventListener("keypress", function(){update_counter();});
  secret_textarea.addEventListener("keyup",    function(){update_counter();});
}

// action happening on local encryption
async function encrypt() {
  var maxLimit = document.getElementById("counter").dataset.maxParamSize;
  var result   = null;

  // check the length of the input
  if ((0 < document.getElementById("secret").value.length) &&
      (0 < document.getElementById("password").value.length)) {
    result = await encrypt_v00(document.getElementById("secret").value,
                               document.getElementById("password").value);
  }

  // check the length of the output
  if ((null != result) && (maxLimit >= result.length)) {
    document.getElementById("secret").value = result;

    document.getElementById("share-secret-btn").disabled = false;

    document.getElementById("encrypt").disabled         = true;
    document.getElementById("encrypt-locally").disabled = true;

    document.getElementById("password").readOnly = "readonly";
    document.getElementById("secret").readOnly   = "readonly";

    document.getElementById("counter").style.display       = "none";
    document.getElementById("encrypt-error").style.display = "none";
  } else {
    document.getElementById("encrypt-error").style.display = "block";
  }
}

// show/hide local encryption
function encrypt_locally() {
  if (document.getElementById("encrypt-locally").checked) {
    document.getElementById("share-secret-btn").disabled = true;

    document.getElementById("encrypt").style.visibility  = "visible";
    document.getElementById("password").style.visibility = "visible";
  } else {
    document.getElementById("share-secret-btn").disabled = false;

    document.getElementById("encrypt").style.visibility  = "hidden";
    document.getElementById("password").style.visibility = "hidden";
  }
}

// encrypt if return is pressed
function encrypt_on_return(event) {
  if (event.key === "Enter") {
    // prevent the default handler
    event.preventDefault();

    // execute the encryption
    encrypt();
  }
}

// update the character counter
// we have to count the bytes and line breaks have to be counted as two characters
function update_counter() {
  // only do all this if the textbox is not already set to read-only
  if (!document.getElementById("secret").readOnly) {
    var maxLimit  = document.getElementById("counter").dataset.maxParamSize;
    var softLimit = Math.ceil(maxLimit-((maxLimit/4)*3-81)); // based on Base64-encoded v00 message length

    var length     = new TextEncoder("utf-8").encode(document.getElementById("secret").value).length;
    var linebreaks = (document.getElementById("secret").value.match(/\n/g) || []).length;
    var counter    = maxLimit-length-linebreaks;

    // set the counter
    document.getElementById("counter").innerHTML   = counter.toString();
    document.getElementById("counter").style.color = document.defaultView.getComputedStyle(document.body, null).color;

    // check if the secret is short enough for local encryption
    document.getElementById("encrypt").disabled = (softLimit > counter);
    if (document.getElementById("encrypt").disabled) {
      // change text colour to yellow
      document.getElementById("counter").style.color = "#FFAA1D";
    }

    // disable the submit button if the secret is too long
    document.getElementById("share-secret-btn").disabled = (0 > counter);
    if (document.getElementById("share-secret-btn").disabled) {
      // change text colour to red
      document.getElementById("counter").style.color = "#C40233";
    }
  }
}

/* encryption library */

// encrypt a message with a key and a nonce via AES with the Web Cryptography API
async function aesctr_encrypt(message, key, nonce) {
  return await crypto.subtle.importKey(
    "raw",
    key,
    {
      name : "AES-CTR"
    },
    false,
    [
      "encrypt"
    ]
  ).then(
    function (key) {
      return crypto.subtle.encrypt(
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

// encrypt a message with a password
async function encrypt_v00(message, password) {
  var result = null;

  try {
    // convert strings to array
    message  = new TextEncoder("utf-8").encode(message);
    password = new TextEncoder("utf-8").encode(password);

    var version = hex2array("00");
    var nonce   = hex2array("00000000"+(Math.floor(Date.now() / 1000).toString(16))+"0000000000000000");
    var salt    = crypto.getRandomValues(new Uint8Array(32));
    var key     = await pbkdf2(password, salt);

    if (null != key) {
      var encKey = await hmac(new TextEncoder("utf-8").encode("enc"), key);
      var macKey = await hmac(new TextEncoder("utf-8").encode("mac"), key);

      if ((null != encKey) && (null != macKey)) {
        var encMessage = await aesctr_encrypt(message, encKey, nonce);

        if (null != encMessage) {
          // convert to correct type
          encMessage = new Uint8Array(encMessage);

          var macMessage = new Uint8Array(version.length + salt.length + nonce.length + encMessage.length);
          macMessage.set(version,    0);
          macMessage.set(salt,       version.length);
          macMessage.set(nonce,      version.length + salt.length);
          macMessage.set(encMessage, version.length + salt.length + nonce.length);

          var mac = await hmac(macMessage, macKey);

          if (null != mac) {
            // convert to correct type
            mac = new Uint8Array(mac);

            var fullMessage = new Uint8Array(macMessage.length + mac.length);
            fullMessage.set(macMessage, 0);
            fullMessage.set(mac,        macMessage.length);

            result = btoa(String.fromCharCode.apply(null, fullMessage));
          }
        }
      }
    }
  } catch (error) {}

  return result;
}

// convert a hex string to an array
function hex2array(hex) {
  var result = new Uint8Array(Math.ceil(hex.length / 2));
  for (var i = 0; i < hex.length; i++) {
    result[i] = parseInt(hex.substr(i*2, 2), 16);
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
