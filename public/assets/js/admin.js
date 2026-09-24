(function () {
  'use strict';

  var copyBtn = document.querySelector('[data-copy-csv]');
  if (copyBtn) {
    var copyDefaultText = copyBtn.textContent;

    copyBtn.addEventListener('click', function () {
      fetch('export.php', { credentials: 'same-origin' })
        .then(function (response) {
          if (!response.ok) {
            throw new Error('export falhou');
          }
          return response.text();
        })
        .then(function (text) {
          var withoutBom = text.replace(/^﻿/, '');
          return navigator.clipboard.writeText(withoutBom);
        })
        .then(function () {
          copyBtn.textContent = 'Copiado!';
          setTimeout(function () {
            copyBtn.textContent = copyDefaultText;
          }, 2000);
        })
        .catch(function () {
          copyBtn.textContent = 'Não foi possível copiar';
          setTimeout(function () {
            copyBtn.textContent = copyDefaultText;
          }, 2000);
        });
    });
  }

  var statusForms = document.querySelectorAll('[data-status-form]');
  statusForms.forEach(function (form) {
    var select = form.querySelector('[data-status-select]');
    var saveBtn = form.querySelector('[data-status-save]');
    var feedback = form.querySelector('[data-status-feedback]');
    if (!select || !feedback) {
      return;
    }

    if (saveBtn) {
      saveBtn.hidden = true;
    }

    var previousValue = select.value;

    select.addEventListener('change', function () {
      var chosenValue = select.value;
      feedback.textContent = 'Salvando...';

      fetch(form.getAttribute('action'), {
        method: 'POST',
        body: new FormData(form),
        credentials: 'same-origin',
        headers: { Accept: 'application/json' }
      })
        .then(function (response) {
          return response.json().then(function (data) {
            return { ok: response.ok, data: data };
          });
        })
        .then(function (result) {
          if (!result.ok || !result.data || !result.data.ok) {
            throw new Error('status falhou');
          }
          previousValue = chosenValue;
          feedback.textContent = 'Salvo';
          setTimeout(function () {
            feedback.textContent = '';
          }, 2000);
        })
        .catch(function () {
          select.value = previousValue;
          feedback.textContent = 'Erro';
          setTimeout(function () {
            feedback.textContent = '';
          }, 2000);
        });
    });
  });
})();
