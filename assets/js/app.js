function confirmDelete(message = 'Delete this item?') {
  return confirm(message);
}

function printAndCloseIfPopup() {
  window.print();
  // If opened as a popup, try to close after print
  setTimeout(() => { if (window.opener) window.close(); }, 500);
}

document.addEventListener('click', (e) => {
  const target = e.target;
  if (target && target.matches('[data-print]')) {
    e.preventDefault();
    printAndCloseIfPopup();
  }
});

