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

document.addEventListener("DOMContentLoaded", function () {
    const senha1 = document.querySelector('[name="nova_senha"]');
    const senha2 = document.querySelector('[name="confirmar"]');

    const toggle1 = document.getElementById("toggleSenha1");
    const toggle2 = document.getElementById("toggleSenha2");

    toggle1.addEventListener("click", () => {
        const tipo = senha1.type === "password" ? "text" : "password";
        senha1.type = tipo;
        toggle1.classList.toggle("fa-eye");
        toggle1.classList.toggle("fa-eye-slash");
    });

    toggle2.addEventListener("click", () => {
        const tipo = senha2.type === "password" ? "text" : "password";
        senha2.type = tipo;
        toggle2.classList.toggle("fa-eye");
        toggle2.classList.toggle("fa-eye-slash");
    });
});
document.addEventListener("DOMContentLoaded", () => {
    const senhaInput = document.getElementById("senha3");
    const toggle = document.getElementById("toggleSenha3");

    toggle.addEventListener("click", () => {
        if (senhaInput.type === "password") {
            senhaInput.type = "text";
            toggle.classList.remove("fa-eye-slash");
            toggle.classList.add("fa-eye");
        } else {
            senhaInput.type = "password";
            toggle.classList.remove("fa-eye");
            toggle.classList.add("fa-eye-slash");
        }
    });
});


