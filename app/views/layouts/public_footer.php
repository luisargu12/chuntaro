<?php

use App\Config\App;

$year = date('Y');
?>
<footer class="site-footer text-white">
    <div class="container mt-4">
        <div class="row g-4">
            <div class="col-lg-8 col-md-6">
                <h5 class="fw-bold mb-3 typewriter-container">
                    <span id="typewriterText" class="typewriter-text"></span><span class="typewriter-cursor">|</span>
                </h5>
                <div class="linea-foter"></div>
            </div>
            <div class="col-lg-4 col-md-12">
                <div class="d-flex gap-3 social-links justify-content-lg-end justify-content-center">
                    <a href="https://facebook.com" target="_blank" rel="noopener" class="social-icon" aria-label="Facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951"/></svg>
                    </a>
                    <a href="https://instagram.com" target="_blank" rel="noopener" class="social-icon" aria-label="Instagram">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.9 3.9 0 0 0-1.417.923A3.9 3.9 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.244 1.393.493 1.722.255.33.566.53 1.06.63.496.1 1.1.12 1.942.14C5.555 15.99 5.827 16 8 16s2.445-.01 3.297-.048c.842-.02 1.446-.04 1.942-.14.494-.1 1.005-.3 1.06-.63.249-.329.453-.87.493-1.722.038-.853.048-1.125.048-3.297s-.01-2.445-.048-3.297c-.04-.852-.244-1.393-.493-1.722-.255-.33-.566-.53-1.06-.63C12.743.068 12.14.048 11.297.048 10.445.01 10.173 0 8 0zm0 3.892a4.108 4.108 0 1 1 0 8.216 4.108 4.108 0 0 1 0-8.216"/></svg>
                    </a>
                </div>
            </div>
        </div>
        <hr class="border-white mt-4 mb-3">
        <div class="row">
            <div class="col-12 text-center text-white small mb-2">
                &copy; <?= $year ?> <?= htmlspecialchars(App::name()) ?> FC. Todos los derechos reservados.
                · <a class="text-white-50" href="<?= htmlspecialchars(App::url('/admin/login')) ?>">Admin</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/gsap.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/gsap@3.12.5/dist/ScrollTrigger.min.js"></script>
<script src="<?= htmlspecialchars(App::asset('js/home.js')) ?>"></script>
</body>
</html>
