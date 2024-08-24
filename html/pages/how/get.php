<?php
# prevent direct access
if (!defined("SHARED_SECRETS")) { die(""); }

# define page title
define("HEADING_TITLE", "How does this service work?");

# include header
require_once(ROOT_DIR."/template/header.php");
?>

  <h2>Short description of this service.</h2>
  <p>This secret sharing service is based on AES and RSA encryption. When creating a new secret sharing link, a random key is generated which is used to encrypt the secret using AES. The key itself is then encrypted using RSA. The result of the encryption is URL-safe Base64 encoded and prepended with the URL of this website. When the secret sharing link is called, the URL-safe Base64 encoded message is decrypted and the result of the decryption is displayed on the website. Additionally, the fingerprint of the encrypted message is stored in a database to prevent it from being displayed more than once.</p>
  <p>To create a secret sharing link you have to do certain steps that are decribed here:
    <ol>
      <li>download the correct public key</li>
      <li>derive the required key material</li>
      <li>encrypt the secret via AES-256-CTR</li>
      <li>encrypt the key material via RSA-OAEP</li>
      <li>calculate a MAC of the data via HMAC-SHA-256</li>
      <li>Base64 encode the result</li>
      <li>remove line breaks</li>
      <li>apply URL-safe Base64 encoding:
        <ul>
          <li>remove equation signs</li>
          <li>replace "+" with "-"</li>
          <li>replace "/" with "_"</li>
        </ul>
      </li>
      <li>prepend the service URL</li>
    </ol>
  </p>

  <h3>Shell example.</h3>
  <p>You can use the following shell command to encrypt a message and be compatible with the online encryption:</p>

<pre class="bg-light border rounded" id="asymmetric">MESSAGE="message to encrypt" &amp;&amp;
URLPREFIX="<?= html(notrail(SERVICE_URL, "/")) ?>" &amp;&amp;
RSAKEYCOUNT="0001" &amp;&amp;
RSAKEYFILE="$(curl -s "${URLPREFIX}/pub?plain")" &amp;&amp;
VERSION="01" &amp;&amp;
NONCE=$(printf "%016x0000000000000000" "$(date +%s)") &amp;&amp;
KEY=$(openssl rand -hex 32) &amp;&amp;
ENCKEY=$(echo -n "enc" | openssl dgst -binary -mac "HMAC" -macopt "hexkey:$KEY" -sha256 | xxd -p | tr -d "\n") &amp;&amp;
MACKEY=$(echo -n "mac" | openssl dgst -binary -mac "HMAC" -macopt "hexkey:$KEY" -sha256 | xxd -p | tr -d "\n") &amp;&amp;
RSAKEY=$(echo -n "$KEY" | xxd -r -p | openssl pkeyutl -encrypt -inkey &lt;(echo -n "$RSAKEYFILE") -keyform PEM -pkeyopt "rsa_padding_mode:oaep" -pubin | xxd -p | tr -d "\n") &amp;&amp;
RSAKEYID=$(openssl rsa -in &lt;(echo -n "$RSAKEYFILE") -pubin -pubout -outform DER 2&gt;/dev/null | openssl dgst -binary -sha256 | xxd -p | tr -d "\n") &amp;&amp;
RSAKEYLENGTH=$(echo -n "$RSAKEY" | xxd -p -r | wc -c) &amp;&amp;
RSAKEYLENGTH=$(printf "%04x" "$RSAKEYLENGTH") &amp;&amp;
ENCMESSAGE=$(echo -n "$MESSAGE" | openssl enc -aes-256-ctr -iv "$NONCE" -K "$ENCKEY" -nopad | xxd -p | tr -d "\n") &amp;&amp;
MACMESSAGE="$VERSION$RSAKEYCOUNT$RSAKEYID$RSAKEYLENGTH$RSAKEY$NONCE$ENCMESSAGE" &amp;&amp;
MAC=$(echo -n "$MACMESSAGE" | xxd -p -r | openssl dgst -binary -mac "HMAC" -macopt "hexkey:$MACKEY" -sha256 | xxd -p | tr -d "\n") &amp;&amp;
FULLMESSAGE="$MACMESSAGE$MAC" &amp;&amp;
OUTPUT=$(echo -n "$FULLMESSAGE" | xxd -p -r | openssl base64 | tr "+" "-" | tr "/" "_" | tr "\n" "/" | tr -d "=") &amp;&amp;
OUTPUT="$URLPREFIX/$OUTPUT" &amp;&amp;
echo "$OUTPUT"</pre>
  <input type="button" class="btn btn-primary float-end" id="copy-to-clipboard-asymmetric" value="Copy to Clipboard!" />

  <h2>Short description of the password-protection feature.</h2>
  <p>When using the password-protection feature, the secret is encrypted locally in your browser using AES-256-CTR. The encryption key is derived from the entered password and a dynamically generated salt using the PBKDF2-SHA-256 algorithm. The password-protection feature is implemented using client-side JavaScript. Please beware that a compromised server may serve you JavaScript code that defeats the purpose of the local encryption. If you do not trust the server that provides the secret sharing service, then encrypt your secret with a locally installed application before sharing it.</p>

  <h3>Shell example.</h3>
  <p>You can use the following shell command to encrypt a message and be compatible with the browser-based encryption:</p>

<pre class="bg-light border rounded" id="symmetric">MESSAGE="message to encrypt" &amp;&amp;
PASSWORD="password" &amp;&amp;
VERSION="00" &amp;&amp;
NONCE=$(printf "%016x0000000000000000" "$(date +%s)") &amp;&amp;
SALT=$(openssl rand -hex 32) &amp;&amp;
KEY=$(openssl kdf -binary -kdfopt "digest:SHA256" -kdfopt "hexsalt:$SALT" -kdfopt "iter:512000" -kdfopt "pass:$PASSWORD" -keylen 32 PBKDF2 | xxd -p | tr -d "\n") &amp;&amp;
ENCKEY=$(echo -n "enc" | openssl dgst -binary -mac "HMAC" -macopt "hexkey:$KEY" -sha256 | xxd -p | tr -d "\n") &amp;&amp;
MACKEY=$(echo -n "mac" | openssl dgst -binary -mac "HMAC" -macopt "hexkey:$KEY" -sha256 | xxd -p | tr -d "\n") &amp;&amp;
ENCMESSAGE=$(echo -n "$MESSAGE" | openssl enc -aes-256-ctr -iv "$NONCE" -K "$ENCKEY" -nopad | xxd -p | tr -d "\n") &amp;&amp;
MACMESSAGE="$VERSION$SALT$NONCE$ENCMESSAGE" &amp;&amp;
MAC=$(echo -n "$MACMESSAGE" | xxd -p -r | openssl dgst -binary -mac "HMAC" -macopt "hexkey:$MACKEY" -sha256 | xxd -p | tr -d "\n") &amp;&amp;
FULLMESSAGE="$MACMESSAGE$MAC" &amp;&amp;
OUTPUT=$(echo -n "$FULLMESSAGE" | xxd -p -r | openssl base64 | tr -d "\n") &amp;&amp;
echo "$OUTPUT"</pre>
  <input type="button" class="btn btn-primary float-end" id="copy-to-clipboard-symmetric" value="Copy to Clipboard!" />

  <div id="spacer" />

  <link href="<?= html(cache_bust_url("/resources/css/".SECRET_ACTION."/".REQUEST_METHOD.".css")) ?>" integrity="<?= html(subresource_integrity("/resources/css/".SECRET_ACTION."/".REQUEST_METHOD.".css")) ?>" rel="stylesheet" type="text/css" />
  <script src="<?= html(cache_bust_url("/resources/js/".SECRET_ACTION."/".REQUEST_METHOD.".js")) ?>" integrity="<?= html(subresource_integrity("/resources/js/".SECRET_ACTION."/".REQUEST_METHOD.".js")) ?>" type="text/javascript"></script>

<?php
# include header
require_once(ROOT_DIR."/template/footer.php");
