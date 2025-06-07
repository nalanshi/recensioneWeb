/**
 * Script per la validazione lato client dei form di login e registrazione
 * 
 * Questo script contiene funzioni per validare i dati inseriti nei form
 * prima dell'invio al server, migliorando l'esperienza utente e riducendo
 * il carico sul server.
 * 
 * @author DishDiveReview Team
 * @version 1.0
 */

// Funzione per validare il form di login
function validateLoginForm() {
  const username = document.getElementById('username').value;
  const password = document.getElementById('password').value;
  let isValid = true;
  
  // Validazione username
  if (username.trim() === '') {
    showError('username', 'Il campo username è obbligatorio');
    isValid = false;
  } else {
    clearError('username');
  }
  
  // Validazione password
  if (password.trim() === '') {
    showError('password', 'Il campo password è obbligatorio');
    isValid = false;
  } else {
    clearError('password');
  }
  
  return isValid;
}

// Funzione per validare il form di registrazione
function validateRegistrationForm() {
  const nome = document.getElementById('nome').value;
  const cognome = document.getElementById('cognome').value;
  const email = document.getElementById('email').value;
  const username = document.getElementById('username').value;
  const password = document.getElementById('password').value;
  const confirmPassword = document.getElementById('confirm-password').value;
  const terms = document.getElementById('terms').checked;
  let isValid = true;
  
  // Validazione nome
  if (nome.trim() === '') {
    showError('nome', 'Il campo nome è obbligatorio');
    isValid = false;
  } else {
    clearError('nome');
  }
  
  // Validazione cognome
  if (cognome.trim() === '') {
    showError('cognome', 'Il campo cognome è obbligatorio');
    isValid = false;
  } else {
    clearError('cognome');
  }
  
  // Validazione email
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (email.trim() === '') {
    showError('email', 'Il campo email è obbligatorio');
    isValid = false;
  } else if (!emailRegex.test(email)) {
    showError('email', 'Inserisci un indirizzo email valido');
    isValid = false;
  } else {
    clearError('email');
  }
  
  // Validazione username
  if (username.trim() === '') {
    showError('username', 'Il campo username è obbligatorio');
    isValid = false;
  } else if (username.length < 3) {
    showError('username', 'L\'username deve contenere almeno 3 caratteri');
    isValid = false;
  } else {
    clearError('username');
  }
  
  // Validazione password
  if (password.trim() === '') {
    showError('password', 'Il campo password è obbligatorio');
    isValid = false;
  } else if (password.length < 8) {
    showError('password', 'La password deve contenere almeno 8 caratteri');
    isValid = false;
  } else if (!/[A-Z]/.test(password) || !/[a-z]/.test(password) || !/[0-9]/.test(password)) {
    showError('password', 'La password deve contenere almeno una lettera maiuscola, una lettera minuscola e un numero');
    isValid = false;
  } else {
    clearError('password');
  }
  
  // Validazione conferma password
  if (confirmPassword.trim() === '') {
    showError('confirm-password', 'Il campo conferma password è obbligatorio');
    isValid = false;
  } else if (confirmPassword !== password) {
    showError('confirm-password', 'Le password non coincidono');
    isValid = false;
  } else {
    clearError('confirm-password');
  }
  
  // Validazione termini e condizioni
  if (!terms) {
    showError('terms', 'Devi accettare i termini e le condizioni');
    isValid = false;
  } else {
    clearError('terms');
  }
  
  return isValid;
}

// Funzione per verificare la forza della password
function checkPasswordStrength() {
  const password = document.getElementById('password').value;
  const strengthBar = document.getElementById('password-strength-bar');
  const strengthText = document.getElementById('password-strength-text');
  
  // Rimuovi tutte le classi
  strengthBar.className = 'password-strength-bar';
  
  if (password.length === 0) {
    strengthBar.style.width = '0';
    strengthText.textContent = '';
    return;
  }
  
  // Calcola il punteggio della password
  let score = 0;
  
  // Lunghezza minima
  if (password.length >= 8) score++;
  
  // Lettere maiuscole e minuscole
  if (/[A-Z]/.test(password) && /[a-z]/.test(password)) score++;
  
  // Numeri
  if (/[0-9]/.test(password)) score++;
  
  // Caratteri speciali
  if (/[^A-Za-z0-9]/.test(password)) score++;
  
  // Aggiorna la barra e il testo in base al punteggio
  switch (score) {
    case 0:
    case 1:
      strengthBar.className += ' weak';
      strengthText.textContent = 'Debole';
      break;
    case 2:
    case 3:
      strengthBar.className += ' medium';
      strengthText.textContent = 'Media';
      break;
    case 4:
      strengthBar.className += ' strong';
      strengthText.textContent = 'Forte';
      break;
  }
}

// Funzione per mostrare un messaggio di errore
function showError(fieldId, message) {
  const field = document.getElementById(fieldId);
  const errorElement = document.getElementById(`${fieldId}-error`);
  
  field.classList.add('error');
  field.setAttribute('aria-invalid', 'true');
  
  if (errorElement) {
    errorElement.textContent = message;
  } else {
    const newErrorElement = document.createElement('div');
    newErrorElement.id = `${fieldId}-error`;
    newErrorElement.className = 'error-message';
    newErrorElement.textContent = message;
    newErrorElement.setAttribute('aria-live', 'polite');
    field.parentNode.appendChild(newErrorElement);
  }
}

// Funzione per rimuovere un messaggio di errore
function clearError(fieldId) {
  const field = document.getElementById(fieldId);
  const errorElement = document.getElementById(`${fieldId}-error`);
  
  field.classList.remove('error');
  field.setAttribute('aria-invalid', 'false');
  
  if (errorElement) {
    errorElement.textContent = '';
  }
}

// Funzione per mostrare i messaggi di errore dal server
document.addEventListener('DOMContentLoaded', function() {
  // Verifica se ci sono messaggi di errore nella sessione
  const urlParams = new URLSearchParams(window.location.search);
  const errorMessage = urlParams.get('error');
  const successMessage = urlParams.get('success');
  
  if (errorMessage) {
    const errorContainer = document.getElementById('error-container');
    if (errorContainer) {
      errorContainer.className = 'error-message';
      errorContainer.textContent = decodeURIComponent(errorMessage);
    }
  }
  
  if (successMessage) {
    const errorContainer = document.getElementById('error-container');
    if (errorContainer) {
      errorContainer.className = 'success-message';
      errorContainer.textContent = decodeURIComponent(successMessage);
    }
  }
  
  // Inizializza la verifica della forza della password se siamo nella pagina di registrazione
  const passwordField = document.getElementById('password');
  if (passwordField && document.getElementById('password-strength-bar')) {
    passwordField.addEventListener('keyup', checkPasswordStrength);
  }
});

