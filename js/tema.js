// Aplica o tema salvo (claro/escuro) o quanto antes para evitar "piscada".
(function () {
    try {
        var tema = localStorage.getItem('tema');
        if (tema === 'dark' || tema === 'light') {
            document.documentElement.setAttribute('data-bs-theme', tema);
        }
    } catch (e) {}
})();
