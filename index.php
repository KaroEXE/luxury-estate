<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Luxury Estate | Find Your Dream Home</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
</head>
<body>
    <header class="header">
        <nav class="navbar">
            <div class="nav-brand">
                <span>Luxury Estate</span>
            </div>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#properties">Properties</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <div class="nav-actions">
                <div class="action-buttons">
                    <button id="wishlistToggle" class="btn btn-outline" onclick="showWishlist()">
                        <i class="far fa-heart"></i> <span id="wishlistCount"></span>
                    </button>
                    <button id="darkModeToggle" class="btn btn-outline" onclick="toggleDarkMode()" title="Toggle Dark Mode">
                        <i class="fas fa-moon"></i> <span id="darkModeText">Dark Mode</span>
                    </button>
                    <button id="currencyToggle" class="btn btn-outline" onclick="toggleCurrency()">
                        Switch to IQD
                    </button>
                    <button id="languageToggle" class="btn btn-outline" onclick="toggleLanguage()">
                        <i class="fas fa-language"></i> کوردی
                    </button>
                </div>
                <div class="auth-buttons">
                    <a href="login.php" class="btn btn-outline">Login</a>
                    <a href="signup.php" class="btn btn-primary">Sign Up</a>
                </div>
            </div>
            <div class="hamburger">
                <div class="line"></div>
                <div class="line"></div>
                <div class="line"></div>
            </div>
        </nav>
    </header>

    <section class="hero" id="home">
        <div class="hero-content">
            <h1>Find Your Dream Home</h1>
            <p>Discover the perfect property that matches your lifestyle</p>
            <div class="search-bar">
                <div class="search-input-group">
                    <input type="text" id="searchInput" placeholder="Search by location, property type, or price...">
                    <button id="searchBtn"><i class="fas fa-search"></i> Search</button>
                </div>
                <div class="search-filters">
                    <select id="propertyType">
                        <option value="">All Types</option>
                        <option value="apartment">Apartment</option>
                        <option value="house">House</option>
                        <option value="villa">Villa</option>
                        <option value="townhouse">Townhouse</option>
                        <option value="penthouse">Penthouse</option>
                    </select>
                    <select id="priceRange">
                        <option value="">Any Price</option>
                        <option value="0-100000">Under $100K</option>
                        <option value="100000-200000">$100K - $200K</option>
                        <option value="200000-300000">$200K - $300K</option>
                        <option value="300000+">$300K+</option>
                    </select>
                    <button id="mapToggle" class="btn btn-outline">
                        <i class="fas fa-map"></i> Show Map
                    </button>
                </div>
            </div>
        </div>
    </section>


    <section class="featured" id="properties">
        <h2>Featured Properties</h2>
        <div class="properties-container">
            <div class="properties-grid" id="propertiesGrid">
            </div>
            <div class="map-container" id="mapContainer" style="display: none;">
                <div id="map"></div>
            </div>
        </div>
    </section>


    <section class="about" id="about">
        <div class="about-content">
            <h2>Why Choose Us</h2>
            <div class="features">
                <div class="feature">
                    <h3>Wide Selection</h3>
                    <p>Hundreds of properties to choose from across Kurdistan's most desirable locations</p>
                </div>
                <div class="feature">
                    <h3>Best Prices</h3>
                    <p>Competitive pricing and great deals on properties throughout the region</p>
                </div>
                <div class="feature">
                    <h3>Local Expertise</h3>
                    <p>Our team has in-depth knowledge of the Kurdistan real estate market</p>
                </div>
            </div>
        </div>
    </section>

    <section class="contact" id="contact">
        <h2>Contact Us</h2>
        <div class="contact-container">
            <form id="contactForm">
                <input type="text" placeholder="Your Name" required>
                <input type="email" placeholder="Your Email" required>
                <textarea placeholder="Your Message" required></textarea>
                <button type="submit">Send Message</button>
            </form>
            <div class="contact-info">
                <h3>Get in Touch</h3>
                <p><i class="fas fa-phone"></i> +964 750 123 4567</p>
                <p><i class="fas fa-envelope"></i> info@luxuryestates.com</p>
                <p><i class="fas fa-map-marker-alt"></i> Zarayan, lay Komar, Kurdistan</p>
            </div>
        </div>
    </section>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Luxury Estate</h3>
                <p>Your trusted partner in finding the perfect home.</p>
            </div>
            <div class="footer-section">
                <h3>Connect</h3>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2024 Luxury Estate. All rights reserved.</p>
        </div>
    </footer>

    <div id="propertyModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <div id="propertyDetails">
    
            </div>
        </div>
    </div>

    <div id="wishlistModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeWishlistModal()">&times;</span>
            <h2 style="margin-bottom: 2rem;">My Wishlist</h2>
            <div id="wishlistContent" class="properties-grid">
                <!-- Wishlist properties will be loaded here -->
            </div>
        </div>
    </div>

    <script src="js/script.js"></script>
</body>
</html>
