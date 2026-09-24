(function () {
  'use strict';

  var form = document.querySelector('[data-lead-form]');
  if (!form) {
    return;
  }

  var MESSAGES = {
    name: 'Informe seu nome.',
    phone: 'Informe um telefone com DDD.',
    email: 'Informe um e-mail válido.',
    message: 'Escreva sua mensagem.',
    consent: 'É preciso aceitar o uso dos dados para continuar.'
  };

  function maskPhone(value) {
    var digits = value.replace(/\D/g, '').slice(0, 11);
    if (digits.length <= 10) {
      return digits.replace(/^(\d{0,2})(\d{0,4})(\d{0,4}).*/, function (match, ddd, part1, part2) {
        var out = ddd ? '(' + ddd + ') ' : '';
        out += part1;
        if (part2) {
          out += '-' + part2;
        }
        return out.trim();
      });
    }
    return digits.replace(/^(\d{2})(\d{5})(\d{0,4}).*/, function (match, ddd, part1, part2) {
      var out = '(' + ddd + ') ' + part1;
      if (part2) {
        out += '-' + part2;
      }
      return out;
    });
  }

  var phoneField = form.querySelector('#phone');
  if (phoneField) {
    phoneField.addEventListener('input', function () {
      phoneField.value = maskPhone(phoneField.value);
    });
  }

  function errorEl(name) {
    return form.querySelector('[data-error-for="' + name + '"]');
  }

  function setError(name, message) {
    var field = form.querySelector('[name="' + name + '"]');
    var error = errorEl(name);
    if (error) {
      error.textContent = message || '';
    }
    if (field) {
      if (message) {
        field.setAttribute('aria-invalid', 'true');
      } else {
        field.removeAttribute('aria-invalid');
      }
    }
  }

  function validateField(name) {
    var field = form.querySelector('[name="' + name + '"]');
    if (!field) {
      return true;
    }
    var value = (field.value || '').trim();
    var valid = true;

    if (name === 'name') {
      valid = value.length >= 2 && value.length <= 120;
    } else if (name === 'phone') {
      var digits = value.replace(/\D/g, '');
      valid = digits.length === 10 || digits.length === 11;
    } else if (name === 'email') {
      valid = field.validity.valid && /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
    } else if (name === 'message') {
      valid = value.length >= 1 && value.length <= 2000;
    } else if (name === 'consent') {
      valid = field.checked === true;
    }

    setError(name, valid ? '' : MESSAGES[name]);
    return valid;
  }

  var FIELD_NAMES = ['name', 'phone', 'email', 'message', 'consent'];

  FIELD_NAMES.forEach(function (name) {
    var field = form.querySelector('[name="' + name + '"]');
    if (field) {
      field.addEventListener('blur', function () {
        validateField(name);
      });
    }
  });

  function validateAll() {
    var firstInvalid = null;
    FIELD_NAMES.forEach(function (name) {
      var ok = validateField(name);
      if (!ok && !firstInvalid) {
        firstInvalid = form.querySelector('[name="' + name + '"]');
      }
    });
    return firstInvalid;
  }

  function setStatus(message) {
    var status = form.querySelector('[data-form-status]');
    if (status) {
      status.textContent = message || '';
    }
  }

  function showSuccess(message, whatsappUrl) {
    var panel = document.createElement('div');
    panel.className = 'lead-success';
    panel.setAttribute('data-lead-success', '');

    var title = document.createElement('h3');
    title.textContent = 'Recebemos seu contato';
    title.setAttribute('tabindex', '-1');

    var text = document.createElement('p');
    text.textContent = message || 'Em breve entraremos em contato.';

    panel.appendChild(title);
    panel.appendChild(text);

    if (typeof whatsappUrl === 'string' && whatsappUrl.indexOf('https://wa.me/') === 0) {
      var link = document.createElement('a');
      link.className = 'btn btn-whatsapp';
      link.href = whatsappUrl;
      link.target = '_blank';
      link.rel = 'noopener';
      link.textContent = 'Falar no WhatsApp';
      panel.appendChild(link);
    }

    form.replaceWith(panel);
    title.focus();
  }

  if (typeof window.fetch !== 'function') {
    return;
  }

  form.addEventListener('submit', function (event) {
    event.preventDefault();

    var firstInvalid = validateAll();
    if (firstInvalid) {
      firstInvalid.focus();
      return;
    }

    var button = form.querySelector('button[type="submit"]');
    var originalLabel = button ? button.textContent : '';

    function reenableButton() {
      if (button) {
        button.disabled = false;
        button.textContent = originalLabel;
        button.classList.remove('is-sending');
      }
    }

    if (button) {
      button.disabled = true;
      button.textContent = 'Enviando...';
      button.classList.add('is-sending');
    }
    setStatus('');

    fetch(form.getAttribute('action'), {
      method: 'POST',
      body: new FormData(form),
      headers: { Accept: 'application/json' },
      credentials: 'same-origin'
    })
      .then(function (response) {
        return response.json().then(function (data) {
          return { status: response.status, data: data };
        });
      })
      .then(function (result) {
        var data = result.data;
        if (result.status === 200 && data.ok) {
          showSuccess(data.message, data.whatsapp_url);
          return;
        }
        if (result.status === 422 && data.error === 'validation' && data.errors) {
          Object.keys(data.errors).forEach(function (name) {
            setError(name, data.errors[name]);
          });
          setStatus('Corrija os campos destacados.');
        } else {
          setStatus(data.message || 'Não foi possível enviar agora.');
        }
        reenableButton();
      })
      .catch(function () {
        setStatus('Não foi possível enviar agora. Verifique sua conexão e tente novamente.');
        reenableButton();
      });
  });
})();
