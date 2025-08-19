// Minimal JS placeholder; modules add their own scripts when needed
document.addEventListener('click', (e) => {
  const target = e.target;
  if (target.matches('[data-print]')) {
    window.print();
  }
});

