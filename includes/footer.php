    </div><!-- /.content-wrap -->

    <footer class="site-footer">
        <div class="site-wrap">
            <p><?= SITE_FOOTER ?></p>
            <nav class="footer-nav">
                <a href="/about">About</a>
                <a href="/contact">Contact</a>
                <a href="/privacy">Privacy</a>
            </nav>
        </div>
    </footer>

    <script>
        // Dark mode toggle — persisted in localStorage
        (function () {
            var saved = localStorage.getItem('theme');
            if (saved) document.documentElement.setAttribute('data-theme', saved);
        })();
        document.getElementById('theme-toggle').addEventListener('click', function () {
            var current = document.documentElement.getAttribute('data-theme');
            var next    = current === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
        });
    </script>
</body>
</html>
