/* interface functions */

// find copy-to-clipboard-asymmetric button
var copy_to_clipboard_asymmetric_button = document.getElementById("copy-to-clipboard-asymmetric");
if (null != copy_to_clipboard_asymmetric_button) {
  // attach onClick event
  copy_to_clipboard_asymmetric_button.addEventListener("click", function(){copy_to_clipboard_asymmetric(document.getElementById("asymmetric").textContent);});
}

// find copy-to-clipboard-symmetric button
var copy_to_clipboard_symmetric_button = document.getElementById("copy-to-clipboard-symmetric");
if (null != copy_to_clipboard_symmetric_button) {
  // attach onClick event
  copy_to_clipboard_symmetric_button.addEventListener("click", function(){copy_to_clipboard_symmetric(document.getElementById("symmetric").textContent);});
}

// action happening on copy to clipboard
async function copy_to_clipboard_asymmetric(text) {
  try {
    await navigator.clipboard.writeText(text);

    document.getElementById("copy-to-clipboard-asymmetric").value = "✔ Copied to Clipboard!";
  } catch (error) {
    console.error(error.message);
  }
}

// action happening on copy to clipboard
async function copy_to_clipboard_symmetric(text) {
  try {
    await navigator.clipboard.writeText(text);

    document.getElementById("copy-to-clipboard-symmetric").value = "✔ Copied to Clipboard!";
  } catch (error) {
    console.error(error.message);
  }
}
