/* Dark Mode — F3 */
(function () {
    var KEY = 'ps_theme';
    function applyTheme(t) {
        document.documentElement.setAttribute('data-theme', t);
        var btn = document.getElementById('ps-dark-toggle');
        if (btn) btn.innerHTML = t === 'dark' ? '☀️' : '🌙';
    }
    var saved = localStorage.getItem(KEY) ||
        (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    applyTheme(saved);

    document.addEventListener('DOMContentLoaded', function () {
        var btn = document.createElement('button');
        btn.id = 'ps-dark-toggle';
        btn.title = 'Cambiar tema';
        btn.innerHTML = saved === 'dark' ? '☀️' : '🌙';
        (document.getElementById('ps-fab-stack') || document.body).appendChild(btn);
        btn.addEventListener('click', function () {
            var cur = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            applyTheme(cur);
            localStorage.setItem(KEY, cur);
        });
    });
})();
