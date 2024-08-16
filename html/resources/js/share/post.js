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

// action happening on copy to clipboard
async function copy_to_clipboard(text) {
  try {
    await navigator.clipboard.writeText(text);

    document.getElementById("copy-to-clipboard").value = "✔ Copied to Clipboard!";
  } catch (error) {
    console.error(error.message);
  }
}
