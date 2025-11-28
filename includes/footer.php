<footer class="footer mt-5 py-3 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h5><?php echo SITE_NAME; ?></h5>
                <p class="text-muted">
                    Votre spécialiste en produits multimédias et électroniques au Maroc.
                    Livraison rapide et paiement sécurisé.
                </p>
            </div>
            <div class="col-md-4">
                <h5>Liens utiles</h5>
                <ul class="list-unstyled">
                    <li><a href="<?php echo SITE_URL; ?>/about.php">À propos</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/contact.php">Contact</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/privacy.php">Politique de confidentialité</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/terms.php">Conditions générales</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>Newsletter</h5>
                <form class="newsletter-form">
                    <div class="input-group mb-3">
                        <input type="email" class="form-control" placeholder="Votre email" 
                               aria-label="Adresse email" required>
                        <button class="btn btn-primary" type="submit">S'abonner</button>
                    </div>
                </form>
                <div class="social-links mt-3">
                    <a href="#" class="me-2"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="me-2"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="me-2"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
        </div>
        <hr>
        <div class="text-center">
            <small class="text-muted">
                &copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Tous droits réservés.
            </small>
        </div>
    </div>
</footer>

<!-- Bootstrap & autres scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>

<?php if (defined('RECAPTCHA_SITE_KEY') && defined('RECAPTCHA_ENABLED') && RECAPTCHA_ENABLED): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
<?php endif; ?>