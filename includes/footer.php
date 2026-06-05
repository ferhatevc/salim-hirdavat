    </main><!-- /mainContent -->

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-main">
                <!-- About -->
                <div class="footer-about">
                    <a href="<?= SITE_URL ?>" class="logo">
                        <div class="logo-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div>
                            <div class="logo-text">Salim <span>Hırdavat</span></div>
                        </div>
                    </a>
                    <p>Sivas'ın en güvenilir hırdavat mağazası. 25.000'den fazla ürün, uygun fiyatlar ve hızlı kurye teslimat ile yanınızdayız. Kaliteli ürünler, profesyonel hizmet.</p>
                    <div class="footer-social">
                        <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                        <a href="#" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                        <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4>Hızlı Erişim</h4>
                    <ul class="footer-links">
                        <li><a href="<?= SITE_URL ?>/hakkimizda">Hakkımızda</a></li>
                        <li><a href="<?= SITE_URL ?>/iletisim">İletişim</a></li>
                        <li><a href="<?= SITE_URL ?>/urunler">Tüm Ürünler</a></li>
                        <li><a href="<?= SITE_URL ?>/urunler?sale=1">İndirimli Ürünler</a></li>
                        <li><a href="<?= SITE_URL ?>/urunler?featured=1">Öne Çıkanlar</a></li>
                        <li><a href="<?= SITE_URL ?>/hesabim">Hesabım</a></li>
                        <li><a href="<?= SITE_URL ?>/siparislerim">Siparişlerim</a></li>
                    </ul>
                </div>

                <!-- Categories -->
                <div>
                    <h4>Kategoriler</h4>
                    <ul class="footer-links">
                        <?php
                        $footerCategories = db()->fetchAll("SELECT name, slug FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order LIMIT 8");
                        foreach ($footerCategories as $fcat):
                        ?>
                        <li><a href="<?= SITE_URL ?>/kategori/<?= $fcat['slug'] ?>"><?= sanitize($fcat['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Contact & Newsletter -->
                <div>
                    <h4>İletişim Bilgileri</h4>
                    <ul class="footer-contact">
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span><?= SITE_ADDRESS ?></span>
                        </li>
                        <li>
                            <i class="fas fa-phone-alt"></i>
                            <span><a href="tel:<?= SITE_PHONE ?>" style="color: var(--gray-400);"><?= SITE_PHONE ?></a></span>
                        </li>
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span><a href="mailto:<?= SITE_EMAIL ?>" style="color: var(--gray-400);"><?= SITE_EMAIL ?></a></span>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <span>Pazartesi - Cumartesi<br>08:00 - 19:00</span>
                        </li>
                    </ul>
                    
                    <div class="footer-newsletter">
                        <p>Kampanya ve fırsatlardan haberdar olun!</p>
                        <form id="newsletterForm">
                            <input type="email" placeholder="E-posta adresiniz" required>
                            <button type="submit"><i class="fas fa-paper-plane"></i></button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. Tüm hakları saklıdır.</p>
                <div class="footer-payments">
                    <span style="margin-right: 8px; font-size: 12px;">Güvenli Ödeme:</span>
                    <i class="fab fa-cc-visa" style="font-size: 28px; color: var(--gray-400);"></i>
                    <i class="fab fa-cc-mastercard" style="font-size: 28px; color: var(--gray-400);"></i>
                    <i class="fas fa-money-bill-wave" style="font-size: 20px; color: var(--gray-400); margin-left: 4px;" title="Kapıda Ödeme"></i>
                </div>
            </div>
        </div>
    </footer>

    <!-- WhatsApp Button -->
    <a href="https://wa.me/<?= SITE_WHATSAPP ?>?text=Merhaba, ürün hakkında bilgi almak istiyorum." 
       class="whatsapp-btn" target="_blank" rel="noopener" aria-label="WhatsApp ile iletişim" id="whatsappBtn">
        <i class="fab fa-whatsapp"></i>
    </a>

    <!-- Scroll to Top -->
    <button class="scroll-top" id="scrollTopBtn" aria-label="Yukarı çık">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- Cookie Consent -->
    <div class="cookie-consent" id="cookieConsent">
        <div class="container">
            <p><i class="fas fa-cookie-bite"></i> Bu web sitesi, deneyiminizi geliştirmek için çerezleri kullanmaktadır.</p>
            <button class="btn btn-primary btn-sm" id="cookieAccept">Kabul Et</button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="<?= SITE_URL ?>/assets/js/main.js"></script>
    <script src="<?= SITE_URL ?>/assets/js/cart.js"></script>
    <script src="<?= SITE_URL ?>/assets/js/search.js"></script>
</body>
</html>
