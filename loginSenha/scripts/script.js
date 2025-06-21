document.addEventListener("DOMContentLoaded", function () {
    const senhaInput = document.getElementById('senha');
    const toggleIcon = document.getElementById('toggleSenha');

    if (senhaInput && toggleIcon) {
        toggleIcon.addEventListener('click', () => {
            const isPassword = senhaInput.type === 'password';
            senhaInput.type = isPassword ? 'text' : 'password';

            toggleIcon.classList.toggle('fa-eye-slash');
            toggleIcon.classList.toggle('fa-eye');
        });
    }
});
