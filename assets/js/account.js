const form = document.getElementById('accountLoginForm');
const message = document.getElementById('accountMessage');
const submit = document.getElementById('accountSubmit');

if (localStorage.getItem('hamsterAccount')) {
  window.location.href = 'profile.html';
}

form.addEventListener('submit', async (event) => {
  event.preventDefault();
  setMessage('');

  const payload = {
    nickname: document.getElementById('accountNickname').value.trim(),
    password: document.getElementById('accountPassword').value,
  };

  submit.disabled = true;
  submit.textContent = 'Проверяем...';

  try {
    const response = await fetch('api/login.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload),
    });
    const data = await response.json();

    if (!response.ok) {
      throw new Error(data.message || 'Не удалось войти в кабинет.');
    }

    localStorage.setItem('hamsterAccount', JSON.stringify(data.account));
    window.location.href = 'profile.html';
  } catch (error) {
    setMessage(error.message, true);
    submit.disabled = false;
    submit.textContent = 'Войти в кабинет';
  }
});

function setMessage(text, isError = false) {
  message.textContent = text;
  message.classList.toggle('is-error', isError);
}
