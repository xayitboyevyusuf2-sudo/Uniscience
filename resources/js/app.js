document.addEventListener('click', (event) => {
	if (!(event.target instanceof Element)) {
		return;
	}

	const button = event.target.closest('[data-password-toggle]');

	if (!button) {
		return;
	}

	const input = document.getElementById(button.getAttribute('aria-controls'));

	if (!(input instanceof HTMLInputElement)) {
		return;
	}

	const showPassword = input.type === 'password';

	input.type = showPassword ? 'text' : 'password';
	button.textContent = showPassword ? 'Yashirish' : 'Ko‘rsatish';
	button.setAttribute('aria-label', showPassword ? 'Parolni yashirish' : 'Parolni ko‘rsatish');
	button.setAttribute('aria-pressed', String(showPassword));
});
