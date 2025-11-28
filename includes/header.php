<header class="site-header">
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="<?php echo SITE_URL; ?>">
                <?php echo SITE_NAME; ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#navbarMain" aria-controls="navbarMain" 
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/products.php">Produits</a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/cart.php">
                                Panier <span class="cart-count badge bg-primary">0</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" 
                               role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Mon compte
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li>
                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/profile.php">
                                        Profil
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?php echo SITE_URL; ?>/orders.php">
                                        Mes commandes
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="<?php echo SITE_URL; ?>/logout.php" method="post" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                        <button type="submit" class="dropdown-item">Déconnexion</button>
                                    </form>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/login.php">Connexion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo SITE_URL; ?>/register.php">Inscription</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
</header>

<?php if (isset($_SESSION['flash'])): ?>
    <div class="container mt-3">
        <div class="alert alert-<?php echo $_SESSION['flash']['type']; ?> alert-dismissible fade show">
            <?php echo $_SESSION['flash']['message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
    <?php unset($_SESSION['flash']); ?>
<?php endif; ?>