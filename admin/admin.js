/* Konvergenz 53 – kleine Komfortfunktionen im Dashboard (funktioniert auch ohne JS) */
(function () {
  'use strict';

  // Ruhetag: Zeitfelder ausgrauen
  document.querySelectorAll('[data-day]').forEach(function (row) {
    var cb = row.querySelector('[data-closed]');
    var sync = function () { row.classList.toggle('is-closed', cb.checked); };
    cb.addEventListener('change', sync);
    sync();
  });

  // Montag auf Di–Fr übertragen
  var copy = document.querySelector('[data-copy-monday]');
  if (copy) {
    copy.addEventListener('click', function () {
      ['tue', 'wed', 'thu', 'fri'].forEach(function (d) {
        ['from1', 'to1', 'from2', 'to2'].forEach(function (f) {
          var src = document.querySelector('[name="mon_' + f + '"]');
          var dst = document.querySelector('[name="' + d + '_' + f + '"]');
          if (src && dst) dst.value = src.value;
        });
        var c = document.querySelector('[name="' + d + '_closed"]');
        var m = document.querySelector('[name="mon_closed"]');
        if (c && m) { c.checked = m.checked; c.dispatchEvent(new Event('change')); }
      });
      copy.textContent = 'Übertragen. Speichern nicht vergessen!';
    });
  }

  // Lange Vorgänge: Button sperren, Hinweis zeigen
  document.querySelectorAll('form').forEach(function (f) {
    f.addEventListener('submit', function () {
      var btn = f.querySelector('button:not([type=button])');
      if (f.dataset.busy && btn) btn.textContent = f.dataset.busy;
      setTimeout(function () { f.classList.add('is-busy'); }, 0);
    });
  });

  // Vorschau beim Bild-Upload
  document.querySelectorAll('input[type=file][name="image_upload"]').forEach(function (input) {
    input.addEventListener('change', function () {
      var file = input.files && input.files[0];
      if (!file) return;
      var prev = input.closest('form').querySelector('.slot-preview');
      if (!prev) return;
      var img = prev.querySelector('img') || document.createElement('img');
      img.src = URL.createObjectURL(file);
      img.alt = 'Vorschau';
      prev.textContent = '';
      prev.appendChild(img);
    });
  });
})();
