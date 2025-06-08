// Script ESQUECI A SENHA
function showNotification() {
    var notification = document.getElementById('notification');

    // Exibe a notificação
    notification.classList.add('show');

    // Esconde a notificação após 3 segundos
    setTimeout(function () {
        notification.classList.remove('show');
    }, 3000);
}
