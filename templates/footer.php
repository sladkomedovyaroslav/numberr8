    <footer class="footer" id="contacts">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3><i class="fas fa-utensils"></i> Вкус Востока</h3>
                    <p>Авторская кухня с восточным акцентом. Создаём гастрономические впечатления с 2015 года.</p>
                </div>
                <div class="footer-col">
                    <h4>Контакты</h4>
                    <p><i class="fas fa-map-marker-alt"></i> ул. Примерная, 123</p>
                    <p><i class="fas fa-phone"></i> +7 (999) 123-45-67</p>
                    <p><i class="fas fa-envelope"></i> info@vostok-restaurant.ru</p>
                </div>
                <div class="footer-col">
                    <h4>Часы работы</h4>
                    <p>Пн-Чт: 12:00 – 23:00</p>
                    <p>Пт-Сб: 12:00 – 01:00</p>
                    <p>Вс: 12:00 – 22:00</p>
                </div>
                <div class="footer-col">
                    <h4>Мы в соцсетях</h4>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-telegram"></i></a>
                        <a href="#"><i class="fab fa-vk"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> Ресторан «Вкус Востока». Все права защищены.</p>
            </div>
        </div>
    </footer>

    <!-- Передаём правила валидации в JavaScript -->
    <script>
        window.VALIDATION_RULES = <?= json_encode(Validator::getRulesForClient()) ?>;
        window.API_BASE_URL = '/restaurant/api';
    </script>
    <script src="public/js/main.js"></script>
    <noscript>
        <!-- Сообщение для пользователей с отключенным JS -->
        <div class="noscript-notice">
            <div class="container">
                <i class="fas fa-info-circle"></i> JavaScript отключен. Форма работает в обычном режиме.
            </div>
        </div>
    </noscript>
</body>
</html>