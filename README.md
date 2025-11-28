# E-Commerce Multimédia Maroc - Mini-projet Base de Données Multimédia

## 📋 Description du Projet

Ce projet est un site e-commerce complet développé en PHP avec des fonctionnalités avancées de sécurité et de gestion multimédia, adapté au marché marocain. Il respecte toutes les contraintes demandées dans le mini-projet de base de données multimédias.

## 🇲🇦 Contexte Marocain

- **Devise** : Dirhams marocains (DH/MAD)
- **Fuseau horaire** : Africa/Casablanca
- **Pays par défaut** : Maroc
- **Livraison** : Partout au Maroc
- **Contact** : Casablanca, Maroc

## ✨ Fonctionnalités Implémentées

### ✅ Sécurité
- **Captcha reCAPTCHA** : Protection des formulaires d'inscription contre les bots
- **Authentification biométrique** : Accès sécurisé à l'espace client et au panier
- **Chiffrement des données** : Protection des informations sensibles
- **Rate limiting** : Protection contre les attaques par force brute
- **Tokens CSRF** : Protection contre les attaques Cross-Site Request Forgery

### 💳 Système de Paiement
- **PayPal** : Intégration complète avec l'API PayPal (devise MAD)
- **Cartes bancaires** : Simulation du traitement CB en dirhams
- **Paiement mobile** : Support des trousseaux mobiles (Apple Pay, Google Pay)
- **Sécurité** : Chiffrement des données de paiement

### 🖼️ Gestion d'Images
- **Upload automatique** : Système d'upload avec validation
- **Conversion multi-formats** : Conversion automatique en JPG, PNG, WebP
- **Protection par watermark** : Ajout automatique de watermark sur les images
- **Miniatures** : Génération automatique de miniatures en différentes tailles
- **Optimisation** : Compression et optimisation des images

### 📊 Tableau de Bord
- **Statistiques JpGraph** : Graphiques interactifs des ventes
- **Métriques de performance** : Suivi des KPIs du site
- **Rapports détaillés** : Analyses par catégorie, produit, méthode de paiement
- **Interface responsive** : Tableau de bord adaptatif

### 🎨 Interface Utilisateur
- **Design responsive** : Compatible mobile, tablette et desktop
- **Bootstrap 5** : Framework CSS moderne
- **Animations** : Transitions et effets visuels
- **Accessibilité** : Interface accessible et intuitive

## 🛠️ Technologies Utilisées

### Backend
- **PHP 8.0+** : Langage de programmation principal
- **MySQL 8.0** : Base de données relationnelle
- **PDO** : Interface d'accès aux données
- **Sessions** : Gestion des sessions utilisateur

### Frontend
- **HTML5** : Structure sémantique
- **CSS3** : Styles et animations
- **JavaScript ES6+** : Interactivité côté client
- **Bootstrap 5** : Framework CSS responsive
- **Chart.js** : Graphiques interactifs
- **Font Awesome** : Icônes vectorielles

### Sécurité
- **reCAPTCHA v2** : Protection anti-bot
- **WebAuthn API** : Authentification biométrique
- **OpenSSL** : Chiffrement des données
- **Password hashing** : Sécurisation des mots de passe

### APIs Externes
- **PayPal API** : Paiements en ligne
- **reCAPTCHA API** : Vérification humaine

## 📁 Structure du Projet

```
ecommerce-multimedia-maroc/
├── config/
│   └── config.php              # Configuration principale
├── database/
│   └── schema.sql              # Schéma de base de données
├── models/
│   └── User.php                # Modèle utilisateur
├── classes/
│   ├── Security.php            # Classe de sécurité
│   ├── ImageManager.php        # Gestionnaire d'images
│   ├── PaymentManager.php      # Gestionnaire de paiements
│   └── DashboardStats.php     # Statistiques du tableau de bord
├── ajax/
│   ├── add-to-cart.php         # AJAX ajout panier
│   ├── biometric-auth.php      # AJAX authentification biométrique
│   └── get-cart-count.php      # AJAX compteur panier
├── assets/
│   ├── css/
│   │   └── style.css          # Styles personnalisés
│   ├── js/
│   │   └── main.js            # JavaScript principal
│   └── images/
│       └── products/           # Images des produits (12 produits)
├── uploads/
│   ├── products/               # Images des produits uploadées
│   ├── watermarks/            # Images de watermark
│   └── temp/                  # Fichiers temporaires
├── index.php                   # Page d'accueil
├── products.php                # Page produits avec images
├── login.php                   # Page de connexion
├── register.php                # Page d'inscription
├── cart.php                    # Page panier
├── dashboard.php               # Tableau de bord
├── biometric-auth.php          # Authentification biométrique
└── README.md                   # Documentation
```

## 🚀 Installation

### Prérequis
- PHP 8.0 ou supérieur
- MySQL 8.0 ou supérieur
- Serveur web (Apache/Nginx)
- Extension PHP GD pour la gestion d'images
- Extension PHP OpenSSL pour le chiffrement
- Extension PHP cURL pour les appels API

### Étapes d'installation

1. **Cloner le projet**
   ```bash
   git clone [url-du-repo]
   cd ecommerce-multimedia
   ```

2. **Configurer la base de données**
   - Créer une base de données MySQL
   - Importer le schéma : `database/schema.sql`
   - Configurer les paramètres dans `config/config.php`

3. **Configurer les permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/products/
   chmod 755 uploads/watermarks/
   chmod 755 uploads/temp/
   ```

4. **Configurer les APIs externes**
   - Obtenir les clés reCAPTCHA sur [Google reCAPTCHA](https://www.google.com/recaptcha/)
   - Obtenir les identifiants PayPal sur [PayPal Developer](https://developer.paypal.com/)
   - Mettre à jour les constantes dans `config/config.php`

5. **Démarrer le serveur**
   ```bash
   php -S localhost:8000
   ```

## ⚙️ Configuration

### Base de données
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce_multimedia');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### reCAPTCHA
```php
define('RECAPTCHA_SITE_KEY', 'your_recaptcha_site_key');
define('RECAPTCHA_SECRET_KEY', 'your_recaptcha_secret_key');
```

### PayPal
```php
define('PAYPAL_CLIENT_ID', 'your_paypal_client_id');
define('PAYPAL_CLIENT_SECRET', 'your_paypal_client_secret');
define('PAYPAL_MODE', 'sandbox'); // ou 'live'
```

## 🔐 Sécurité

### Authentification
- Mots de passe hachés avec `password_hash()`
- Sessions sécurisées avec tokens CSRF
- Rate limiting sur les formulaires sensibles
- Vérification email obligatoire

### Données sensibles
- Chiffrement AES-256-CBC pour les données biométriques
- Validation et sanitisation de toutes les entrées
- Protection contre l'injection SQL avec PDO
- Headers de sécurité HTTP

### Images
- Validation des types MIME
- Limitation de la taille des fichiers
- Watermark automatique pour la protection
- Stockage sécurisé des fichiers

## 📊 Fonctionnalités du Tableau de Bord

### Statistiques Générales
- Chiffre d'affaires (30 derniers jours)
- Nombre de commandes
- Panier moyen
- Clients uniques

### Graphiques
- Évolution des ventes quotidiennes
- Répartition par catégorie
- Méthodes de paiement utilisées
- Statut des commandes

### Top Produits
- Produits les plus vendus
- Revenus par produit
- Nombre de commandes

## 🧪 Tests

### Tests de sécurité
- Test des injections SQL
- Test des attaques XSS
- Test du rate limiting
- Test de l'authentification biométrique

### Tests fonctionnels
- Processus d'inscription avec captcha
- Ajout au panier
- Processus de paiement
- Upload et conversion d'images

## 📱 Responsive Design

Le site est entièrement responsive et s'adapte à tous les écrans :
- **Mobile** : Interface optimisée pour les smartphones
- **Tablette** : Layout adapté aux écrans moyens
- **Desktop** : Interface complète pour les ordinateurs

## 🔧 Maintenance

### Logs
- Logs d'activité des utilisateurs
- Logs d'erreurs PHP
- Logs de sécurité

### Sauvegarde
- Sauvegarde automatique de la base de données
- Sauvegarde des fichiers uploadés
- Rotation des logs

## 📈 Performance

### Optimisations
- Compression des images
- Cache des requêtes fréquentes
- Minification CSS/JS
- CDN pour les ressources statiques

### Monitoring
- Surveillance des performances
- Alertes en cas de problème
- Métriques de disponibilité

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Commit les changements (`git commit -am 'Ajout nouvelle fonctionnalité'`)
4. Push vers la branche (`git push origin feature/nouvelle-fonctionnalite`)
5. Créer une Pull Request

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 👥 Auteurs

- **Développeur Principal** : [Votre Nom]
- **Projet** : Mini-projet Base de Données Multimédia

## 📞 Support

Pour toute question ou problème :
- Email : contact@ecommerce-multimedia.com
- Documentation : Voir les commentaires dans le code
- Issues : Utiliser le système d'issues GitHub

---

**Note** : Ce projet est un exemple éducatif et ne doit pas être utilisé en production sans audit de sécurité complet.
