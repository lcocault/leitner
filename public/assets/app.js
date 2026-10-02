(function () {
  var show = document.getElementById('show-answer');
  if (show) {
    show.addEventListener('click', function () {
      document.getElementById('answer').hidden = false;
      show.hidden = true;
    });
  }
  var copy = document.getElementById('copy-prompt');
  if (copy) {
    copy.addEventListener('click', function () {
      var t = document.getElementById('prompt');
      t.select();
      if (navigator.clipboard) { navigator.clipboard.writeText(t.value); } else { document.execCommand('copy'); }
      copy.textContent = 'Copié !';
    });
  }
  var meta = document.querySelector('meta[name=csrf]');
  document.querySelectorAll('textarea[data-preview]').forEach(function (ta) {
    var target = document.querySelector(ta.dataset.preview), timer;
    function update() {
      var body = new URLSearchParams({ text: ta.value, _csrf: meta ? meta.content : '' });
      fetch('index.php?page=fiches&action=preview', { method: 'POST', body: body, credentials: 'same-origin' })
        .then(function (r) { return r.text(); })
        .then(function (h) { target.innerHTML = h; });
    }
    ta.addEventListener('input', function () { clearTimeout(timer); timer = setTimeout(update, 300); });
    if (ta.value) { update(); }
  });
})();
