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
        document.querySelectorAll('audio').forEach(function (a) { a.pause(); });
        var target = document.querySelector(btn.dataset.speak);
        var parts = [];
        if (btn.dataset.speakTitle) { parts.push(end(btn.dataset.speakTitle)); }
        blocks(target).forEach(function (b) { parts = parts.concat(sentences(b)); });
        if (!parts.length) { return; }
        speaking = btn;
        btn.textContent = '⏹ Arrêter';
        var voice = frVoice();
        // Un énoncé à la fois : une longue file d'attente (cours complet) est mal gérée par certains navigateurs.
        var next = function (i) {
          if (speaking !== btn) { return; }
          if (i >= parts.length) {
            btn.textContent = label;
            speaking = null;
            btn.dispatchEvent(new Event('speakend'));
            return;
          }
          var u = new SpeechSynthesisUtterance(parts[i].trim());
          u.lang = 'fr-FR';
          if (voice) { u.voice = voice; }
          u.onend = function () { next(i + 1); };
          u.onerror = function (e) { if (e.error !== 'canceled' && e.error !== 'interrupted') { next(i + 1); } };
          synth.speak(u);
        };
        next(0);
      });
    });
    synth.getVoices();
    window.addEventListener('pagehide', function () { synth.cancel(); });
    window.addEventListener('hashchange', stop);
    document.querySelectorAll('audio').forEach(function (a) { a.addEventListener('play', stop); });
  }
  // Version audio du cours : reprise à la dernière position écoutée, message si le fichier est absent.
  var bloc = document.getElementById('audio-cours');
  if (bloc) {
    var audio = bloc.querySelector('audio');
    var clePos = 'leitner-audio-' + bloc.dataset.document, dernierePos = 0;
    var lire = function () { try { return +localStorage.getItem(clePos) || 0; } catch (e) { return 0; } };
    var ecrire = function (t) { try { localStorage.setItem(clePos, t); } catch (e) { /* stockage indisponible */ } };
    audio.addEventListener('loadedmetadata', function () {
      var t = lire();
      if (t > 5 && t < audio.duration - 5) { audio.currentTime = t; }
    });
    audio.addEventListener('timeupdate', function () {
      if (Math.abs(audio.currentTime - dernierePos) >= 5) { dernierePos = audio.currentTime; ecrire(Math.floor(dernierePos)); }
    });
    audio.addEventListener('ended', function () { ecrire(0); });
    audio.addEventListener('error', function () {
      audio.hidden = true;
      document.getElementById('audio-erreur').hidden = false;
    });
  }
  // Cours chapitré : sommaire, un chapitre affiché à la fois, enchaînement automatique en lecture vocale.
  var cours = document.getElementById('cours');
  if (cours) {
    var sommaire = document.getElementById('sommaire');
    var chapitres = Array.prototype.slice.call(cours.querySelectorAll('.chapitre'));
    var cle = 'leitner-chapitre-' + cours.dataset.document;
    var memo = function (n) { try { if (n) { localStorage.setItem(cle, n); } return +localStorage.getItem(cle) || 0; } catch (e) { return 0; } };
    // n = numéro du chapitre à afficher (1…), 0 = sommaire
    var afficher = function (n) {
      sommaire.hidden = n > 0;
      chapitres.forEach(function (c, i) { c.hidden = i !== n - 1; });
      if (n > 0) { memo(n); }
      var dernier = memo(0), reprendre = document.getElementById('reprendre');
      if (dernier > 0 && dernier <= chapitres.length) {
        reprendre.innerHTML = '';
        var a = document.createElement('a');
        a.href = '#chapitre-' + dernier;
        a.className = 'btn small';
        a.textContent = 'Reprendre au chapitre ' + dernier + ' : ' + chapitres[dernier - 1].dataset.titre;
        reprendre.appendChild(a);
        reprendre.hidden = false;
      }
      window.scrollTo(0, 0);
    };
    var courant = function () {
      var m = /^#chapitre-(\d+)$/.exec(location.hash);
      return m && +m[1] >= 1 && +m[1] <= chapitres.length ? +m[1] : 0;
    };
    window.addEventListener('hashchange', function () { afficher(courant()); });
    // Fin de la lecture d'un chapitre : passage au suivant, dont la lecture démarre aussitôt.
    chapitres.forEach(function (c, i) {
      var btn = c.querySelector('button[data-speak]'), suivant = chapitres[i + 1];
      if (!btn || !suivant) { return; }
      btn.addEventListener('speakend', function () {
        if (courant() !== i + 1) { return; }
        history.pushState(null, '', '#chapitre-' + (i + 2));
        afficher(i + 2);
        suivant.querySelector('button[data-speak]').click();
      });
    });
    afficher(courant());
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
