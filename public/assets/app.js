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
  // Lecture à voix haute (synthèse vocale du navigateur) des blocs désignés par data-speak.
  var synth = window.speechSynthesis;
  if (synth && window.SpeechSynthesisUtterance) {
    var speaking = null;
    var label = '🔊 Écouter';
    var clean = function (el) {
      var c = el.cloneNode(true);
      c.querySelectorAll('br').forEach(function (br) { br.replaceWith(' '); });
      return c.textContent.replace(/\s+/g, ' ').trim();
    };
    var end = function (s) { return /[.!?:;]$/.test(s) ? s : s + '.'; };
    // Un tableau est lu ligne par ligne : « 1re cellule. En-tête : cellule. »
    var tableText = function (table) {
      var heads = Array.prototype.map.call(table.querySelectorAll('thead th'), clean);
      return Array.prototype.map.call(table.querySelectorAll('tbody tr'), function (tr) {
        var cells = Array.prototype.map.call(tr.children, clean);
        if (cells.length === 2) { return end(cells[0] + ' : ' + (cells[1] || 'rien')); }
        return cells.map(function (c, i) {
          if (!c || c === '—') { return ''; }
          return end(i === 0 || !heads[i] ? c : heads[i] + ' : ' + c);
        }).join(' ');
      });
    };
    var blocks = function (root) {
      var out = [];
      Array.prototype.forEach.call(root.children, function (el) {
        var tag = el.tagName;
        if (tag === 'TABLE') { out = out.concat(tableText(el)); }
        else if (tag === 'UL' || tag === 'OL') { Array.prototype.forEach.call(el.children, function (li) { out.push(end(clean(li))); }); }
        else if (tag !== 'PRE') { out.push(end(clean(el))); }
      });
      return out.filter(function (s) { return s.length > 1; });
    };
    // Découpage en phrases : certains navigateurs coupent les énoncés trop longs.
    var sentences = function (text) { return text.replace(/([.!?])\s+/g, '$1\n').split('\n'); };
    var stop = function () {
      synth.cancel();
      if (speaking) { speaking.textContent = label; speaking = null; }
    };
    var frVoice = function () {
      var v = synth.getVoices().filter(function (x) { return /^fr/i.test(x.lang); });
      return v.filter(function (x) { return /^fr[-_]FR/i.test(x.lang); })[0] || v[0] || null;
    };
    document.querySelectorAll('button[data-speak]').forEach(function (btn) {
      btn.hidden = false;
      btn.addEventListener('click', function () {
        var mine = speaking === btn;
        stop();
        if (mine) { return; }
        var target = document.querySelector(btn.dataset.speak);
        var parts = [];
        blocks(target).forEach(function (b) { parts = parts.concat(sentences(b)); });
        if (!parts.length) { return; }
        speaking = btn;
        btn.textContent = '⏹ Arrêter';
        var voice = frVoice();
        parts.forEach(function (p, i) {
          var u = new SpeechSynthesisUtterance(p.trim());
          u.lang = 'fr-FR';
          if (voice) { u.voice = voice; }
          if (i === parts.length - 1) {
            u.onend = function () { if (speaking === btn) { btn.textContent = label; speaking = null; } };
          }
          synth.speak(u);
        });
      });
    });
    synth.getVoices();
    window.addEventListener('pagehide', function () { synth.cancel(); });
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
