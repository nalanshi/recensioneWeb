// Validazione migliorata per i form di autenticazione
// Ispirata al design Huawei con feedback visivo immediato

// Funzioni di utilità
function showError(fieldId, message) {
  const field = document.getElementById(fieldId);
  const errorDiv = document.getElementById(fieldId + '-error');
  
  if (field && errorDiv) {
    field.classList.add('error');
    field.classList.remove('success');
    errorDiv.innerHTML = `
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10"></circle>
        <line x1="15" y1="9" x2="9" y2="15"></line>
        <line x1="9" y1="9" x2="15" y2="15"></line>
      </svg>
      ${message}
    `;
  }
}

function showSuccess(fieldId) {
  const field = document.getElementById(fieldId);
  const errorDiv = document.getElementById(fieldId + '-error');
  
  if (field && errorDiv) {
    field.classList.add('success');
    field.classList.remove('error');
    errorDiv.innerHTML = '';
  }
}

function clearValidation(fieldId) {
  const field = document.getElementById(fieldId);
  const errorDiv = document.getElementById(fieldId + '-error');
  
  if (field && errorDiv) {
    field.classList.remove('error', 'success');
    errorDiv.innerHTML = '';
  }
}

function showMessage(message, type = 'error') {
  const container = document.getElementById('error-container');
  if (container) {
    const icon = type === 'error' 
      ? '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>'
      : '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22,4 12,14.01 9,11.01"></polyline></svg>';
    
    container.innerHTML = `
      <div class="message ${type}">
        ${icon}
        ${message}
      </div>
    `;
    
    // Scomparsa automatica dopo 5 secondi
    setTimeout(() => {
      container.innerHTML = '';
    }, 5000);
  }
}

// Funzioni di validazione
function validateEmail(email) {
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  return emailRegex.test(email);
}

function validatePassword(password) {
  const minLength = password.length >= 8;
  const hasUpperCase = /[A-Z]/.test(password);
  const hasLowerCase = /[a-z]/.test(password);
  const hasNumbers = /\d/.test(password);
  
  return {
    isValid: minLength && hasUpperCase && hasLowerCase && hasNumbers,
    minLength,
    hasUpperCase,
    hasLowerCase,
    hasNumbers,
    strength: calculatePasswordStrength(password)
  };
}

function calculatePasswordStrength(password) {
  let score = 0;
  
  if (password.length >= 8) score += 1;
  if (password.length >= 12) score += 1;
  if (/[a-z]/.test(password)) score += 1;
  if (/[A-Z]/.test(password)) score += 1;
  if (/[0-9]/.test(password)) score += 1;
  if (/[^A-Za-z0-9]/.test(password)) score += 1;
  
  if (score < 3) return 'weak';
  if (score < 5) return 'medium';
  return 'strong';
}

function validateUsername(username) {
  const minLength = username.length >= 3;
  const maxLength = username.length <= 20;
  const validChars = /^[a-zA-Z0-9_]+$/.test(username);
  
  return minLength && maxLength && validChars;
}

function validateName(name) {
  const minLength = name.length >= 2;
  const maxLength = name.length <= 50;
  const validChars = /^[a-zA-ZÀ-ÿ\s']+$/.test(name);
  
  return minLength && maxLength && validChars;
}

// Indicatore di forza della password
function checkPasswordStrength() {
  const passwordField = document.getElementById('password');
  const strengthBar = document.getElementById('password-strength-bar');
  const strengthText = document.getElementById('password-strength-text');
  
  if (!passwordField || !strengthBar || !strengthText) return;
  
  const password = passwordField.value;
  const validation = validatePassword(password);
  
  // Aggiorna barra di forza
  strengthBar.className = 'password-strength-bar';
  if (password.length > 0) {
    strengthBar.classList.add(validation.strength);
  }
  
  // Aggiorna testo di forza
  if (password.length === 0) {
    strengthText.textContent = '';
  } else {
    const strengthTexts = {
      weak: 'Password debole',
      medium: 'Password media',
      strong: 'Password forte'
    };
    strengthText.textContent = strengthTexts[validation.strength];
    strengthText.style.color = validation.strength === 'weak' ? '#f44336' : 
                              validation.strength === 'medium' ? '#ff9800' : '#4CAF50';
  }
}

// Validazione in tempo reale
function setupRealTimeValidation() {
  // Validazione email
  const emailField = document.getElementById('email');
  if (emailField) {
    emailField.addEventListener('blur', function() {
      const email = this.value.trim();
      if (email && !validateEmail(email)) {
        showError('email', 'Inserisci un indirizzo email valido');
      } else if (email) {
        showSuccess('email');
      }
    });
    
    emailField.addEventListener('input', function() {
      if (this.classList.contains('error') && validateEmail(this.value.trim())) {
        showSuccess('email');
      }
    });
  }
  
  // Validazione username
  const usernameField = document.getElementById('username');
  if (usernameField) {
    usernameField.addEventListener('blur', function() {
      const username = this.value.trim();
      if (username && !validateUsername(username)) {
        showError('username', 'Username deve essere 3-20 caratteri, solo lettere, numeri e underscore');
      } else if (username) {
        showSuccess('username');
      }
    });
    
    usernameField.addEventListener('input', function() {
      if (this.classList.contains('error') && validateUsername(this.value.trim())) {
        showSuccess('username');
      }
    });
  }
  
  // Validazione nome e cognome
  ['nome', 'cognome'].forEach(fieldName => {
    const field = document.getElementById(fieldName);
    if (field) {
      field.addEventListener('blur', function() {
        const name = this.value.trim();
        if (name && !validateName(name)) {
          showError(fieldName, 'Nome deve essere 2-50 caratteri, solo lettere');
        } else if (name) {
          showSuccess(fieldName);
        }
      });
      
      field.addEventListener('input', function() {
        if (this.classList.contains('error') && validateName(this.value.trim())) {
          showSuccess(fieldName);
        }
      });
    }
  });
  
  // Validazione password
  const passwordField = document.getElementById('password');
  if (passwordField) {
    passwordField.addEventListener('input', function() {
      checkPasswordStrength();
      
      const validation = validatePassword(this.value);
      if (this.value && !validation.isValid) {
        const missing = [];
        if (!validation.minLength) missing.push('almeno 8 caratteri');
        if (!validation.hasUpperCase) missing.push('una maiuscola');
        if (!validation.hasLowerCase) missing.push('una minuscola');
        if (!validation.hasNumbers) missing.push('un numero');
        
        showError('password', `Password deve contenere: ${missing.join(', ')}`);
      } else if (this.value && validation.isValid) {
        showSuccess('password');
      }
      
      // Controlla la conferma password se presente
      const confirmField = document.getElementById('confirm-password');
      if (confirmField && confirmField.value) {
        if (this.value !== confirmField.value) {
          showError('confirm-password', 'Le password non corrispondono');
        } else {
          showSuccess('confirm-password');
        }
      }
    });
  }
  
  // Validazione conferma password
  const confirmPasswordField = document.getElementById('confirm-password');
  if (confirmPasswordField) {
    confirmPasswordField.addEventListener('input', function() {
      const password = document.getElementById('password').value;
      if (this.value && this.value !== password) {
        showError('confirm-password', 'Le password non corrispondono');
      } else if (this.value && this.value === password) {
        showSuccess('confirm-password');
      }
    });
  }
}

// Validazione modulo login
function validateLoginForm() {
  let isValid = true;
  
  // Pulisce i messaggi precedenti
  document.getElementById('error-container').innerHTML = '';
  
  // Validazione username/email
  const username = document.getElementById('username').value.trim();
  if (!username) {
    showError('username', 'Username o email è richiesto');
    isValid = false;
  } else {
    showSuccess('username');
  }
  
  // Validazione password
  const password = document.getElementById('password').value;
  if (!password) {
    showError('password', 'Password è richiesta');
    isValid = false;
  } else {
    showSuccess('password');
  }
  
  if (!isValid) {
    showMessage('Correggi gli errori evidenziati per continuare');
    return false;
  }
  
  // Mostra stato di caricamento
  const submitBtn = document.querySelector('.btn-primary');
  if (submitBtn) {
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;
  }
  
  return true;
}

// Validazione modulo registrazione
function validateRegistrationForm() {
  let isValid = true;
  
  // Pulisce i messaggi precedenti
  document.getElementById('error-container').innerHTML = '';
  
  // Validazione nome
  const nome = document.getElementById('nome').value.trim();
  if (!nome) {
    showError('nome', 'Nome è richiesto');
    isValid = false;
  } else if (!validateName(nome)) {
    showError('nome', 'Nome deve essere 2-50 caratteri, solo lettere');
    isValid = false;
  } else {
    showSuccess('nome');
  }
  
  // Validazione cognome
  const cognome = document.getElementById('cognome').value.trim();
  if (!cognome) {
    showError('cognome', 'Cognome è richiesto');
    isValid = false;
  } else if (!validateName(cognome)) {
    showError('cognome', 'Cognome deve essere 2-50 caratteri, solo lettere');
    isValid = false;
  } else {
    showSuccess('cognome');
  }
  
  // Validazione email
  const email = document.getElementById('email').value.trim();
  if (!email) {
    showError('email', 'Email è richiesta');
    isValid = false;
  } else if (!validateEmail(email)) {
    showError('email', 'Inserisci un indirizzo email valido');
    isValid = false;
  } else {
    showSuccess('email');
  }
  
  // Validazione username
  const username = document.getElementById('username').value.trim();
  if (!username) {
    showError('username', 'Username è richiesto');
    isValid = false;
  } else if (!validateUsername(username)) {
    showError('username', 'Username deve essere 3-20 caratteri, solo lettere, numeri e underscore');
    isValid = false;
  } else {
    showSuccess('username');
  }
  
  // Validazione password
  const password = document.getElementById('password').value;
  if (!password) {
    showError('password', 'Password è richiesta');
    isValid = false;
  } else {
    const validation = validatePassword(password);
    if (!validation.isValid) {
      const missing = [];
      if (!validation.minLength) missing.push('almeno 8 caratteri');
      if (!validation.hasUpperCase) missing.push('una maiuscola');
      if (!validation.hasLowerCase) missing.push('una minuscola');
      if (!validation.hasNumbers) missing.push('un numero');
      
      showError('password', `Password deve contenere: ${missing.join(', ')}`);
      isValid = false;
    } else {
      showSuccess('password');
    }
  }
  
  // Validazione conferma password
  const confirmPassword = document.getElementById('confirm-password').value;
  if (!confirmPassword) {
    showError('confirm-password', 'Conferma password è richiesta');
    isValid = false;
  } else if (password !== confirmPassword) {
    showError('confirm-password', 'Le password non corrispondono');
    isValid = false;
  } else {
    showSuccess('confirm-password');
  }
  
  // Validazione accettazione termini
  const terms = document.getElementById('terms').checked;
  if (!terms) {
    showError('terms', 'Devi accettare i termini e condizioni');
    isValid = false;
  }
  
  if (!isValid) {
    showMessage('Correggi gli errori evidenziati per continuare');
    return false;
  }
  
  // Mostra stato di caricamento
  const submitBtn = document.querySelector('.btn-primary');
  if (submitBtn) {
    submitBtn.classList.add('btn-loading');
    submitBtn.disabled = true;
  }
  
  return true;
}

// Inizializza quando il DOM è pronto
document.addEventListener('DOMContentLoaded', function() {
  setupRealTimeValidation();
  
  // Imposta il controllo forza password
  const passwordField = document.getElementById('password');
  if (passwordField) {
    passwordField.addEventListener('input', checkPasswordStrength);
  }
  
  // Pulisce la validazione al focus dell'input
  const inputs = document.querySelectorAll('.form-input');
  inputs.forEach(input => {
    input.addEventListener('focus', function() {
      if (this.classList.contains('error')) {
        clearValidation(this.id);
      }
    });
  });
  const params = new URLSearchParams(window.location.search);
  const error = params.get('error');
  if (error) {
    const container = document.getElementById('error-container');
    if (container) {
      container.innerHTML = `
        <div class="message error">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
          </svg>
          ${decodeURIComponent(error)}
        </div>
      `;
    }
  }
});

