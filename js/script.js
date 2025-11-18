function updateDarkModeUI(isDarkMode) {
    document.body.classList.toggle('dark-mode', isDarkMode);
    const icon = document.querySelector('#darkModeToggle i');
    const text = document.getElementById('darkModeText');
    const lang = (typeof translations !== 'undefined' && translations[currentLanguage]) || translations.en;
    if (icon) icon.className = isDarkMode ? 'fas fa-sun' : 'fas fa-moon';
    if (text) text.textContent = isDarkMode ? lang.lightMode : lang.darkMode;
}

function toggleDarkMode() {
    const shouldEnableDarkMode = !document.body.classList.contains('dark-mode');
    updateDarkModeUI(shouldEnableDarkMode);
    localStorage.setItem('darkMode', shouldEnableDarkMode);
    
    if (isLoggedIn) {
        updateUserPreference('dark_mode', shouldEnableDarkMode);
    }
}

function initDarkMode() {
    const savedDarkMode = localStorage.getItem('darkMode') === 'true';
    updateDarkModeUI(savedDarkMode);
}

function updateUserPreference(key, value) {
    fetch('api/preferences.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ [key]: value })
    }).catch(error => console.error('Error updating preference:', error));
}

function initWishlist() {
    const savedWishlist = localStorage.getItem('wishlist');
    if (savedWishlist) {
        wishlist = JSON.parse(savedWishlist);
    }
    
    checkLoginStatus();
    updateWishlistCount();
}

function checkLoginStatus() {
    fetch('auth.php?check=1')
        .then(response => response.json())
        .then(data => {
            if (data.logged_in) {
                isLoggedIn = true;
                loadWishlistFromAPI();
                loadUserPreferences();
            }
        })
        .catch(error => console.log('Not logged in'));
}

function loadWishlistFromAPI() {
    fetch('api/wishlist.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                wishlist = data.data.map(p => p.id);
                localStorage.setItem('wishlist', JSON.stringify(wishlist));
                updateWishlistCount();
                updateWishlistButtons();
            }
        })
        .catch(error => console.error('Error loading wishlist:', error));
}

function loadUserPreferences() {
    fetch('api/preferences.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const prefs = data.data;
                if (typeof prefs.dark_mode === 'boolean') {
                    updateDarkModeUI(prefs.dark_mode);
                    localStorage.setItem('darkMode', prefs.dark_mode);
                }
                if (prefs.language) {
                    currentLanguage = prefs.language;
                    applyLanguage();
                }
                if (prefs.currency) {
                    currentCurrency = prefs.currency;
                    updateCurrencyButton();
                    updateAllPrices();
                }
            }
        })
        .catch(error => console.error('Error loading preferences:', error));
}

function toggleWishlist(propertyId) {
    const index = wishlist.indexOf(propertyId);
    
    if (index > -1) {
        // Remove from wishlist
        wishlist.splice(index, 1);
        if (isLoggedIn) {
            removeFromWishlistAPI(propertyId);
        }
    } else {
        // Add to wishlist
        wishlist.push(propertyId);
        if (isLoggedIn) {
            addToWishlistAPI(propertyId);
        }
    }
    
    localStorage.setItem('wishlist', JSON.stringify(wishlist));
    updateWishlistCount();
    updateWishlistButtons();
}

function addToWishlistAPI(propertyId) {
    fetch('api/wishlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ property_id: propertyId })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Failed to add to wishlist:', data.message);
        }
    })
    .catch(error => console.error('Error adding to wishlist:', error));
}

function removeFromWishlistAPI(propertyId) {
    fetch('api/wishlist.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ property_id: propertyId })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Failed to remove from wishlist:', data.message);
        }
    })
    .catch(error => console.error('Error removing from wishlist:', error));
}

function updateWishlistCount() {
    const countElement = document.getElementById('wishlistCount');
    if (countElement) {
        countElement.textContent = wishlist.length > 0 ? `(${wishlist.length})` : '';
    }
    
    const wishlistBtn = document.getElementById('wishlistToggle');
    if (wishlistBtn) {
        const icon = wishlistBtn.querySelector('i');
        if (icon) {
            icon.className = wishlist.length > 0 ? 'fas fa-heart' : 'far fa-heart';
        }
    }
}

function updateWishlistButtons() {
    document.querySelectorAll('.wishlist-btn').forEach(btn => {
        const propertyId = parseInt(btn.getAttribute('data-property-id'));
        if (wishlist.includes(propertyId)) {
            btn.classList.add('active');
            btn.querySelector('i').className = 'fas fa-heart';
        } else {
            btn.classList.remove('active');
            btn.querySelector('i').className = 'far fa-heart';
        }
    });
}

function showWishlist() {
    const modal = document.getElementById('wishlistModal');
    const content = document.getElementById('wishlistContent');
    
    if (wishlist.length === 0) {
        content.innerHTML = `
            <div style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                <i class="far fa-heart" style="font-size: 4rem; color: #d1d5db; margin-bottom: 1rem;"></i>
                <h3>Your wishlist is empty</h3>
                <p style="color: var(--text-light); margin-top: 0.5rem;">Start adding properties you love!</p>
            </div>
        `;
    } else {
        const wishlistProperties = properties.filter(p => wishlist.includes(p.id));
        const lang = translations[currentLanguage];
        
        content.innerHTML = wishlistProperties.map(property => `
            <div class="property-card">
                <button class="wishlist-btn active" data-property-id="${property.id}" onclick="toggleWishlist(${property.id})">
                    <i class="fas fa-heart"></i>
                </button>
                <div class="property-image">
                    <img loading="lazy" src="${property.image}" alt="${property.title}">
                </div>
                <div class="property-info">
                    <h3>${property.title}</h3>
                    <p class="property-location"><i class="fas fa-map-marker-alt"></i> ${property.location}</p>
                    <div class="property-price" data-price="${property.price}">${formatPrice(property.price)}</div>
                    <p class="property-features">${property.features}</p>
                    <button class="btn" onclick="viewProperty(${property.id})">
                        ${lang.viewDetails}
                    </button>
                </div>
            </div>
        `).join('');
    }
    
    modal.style.display = 'block';
}

function closeWishlistModal() {
    document.getElementById('wishlistModal').style.display = 'none';
}

const USD_TO_IQD = 1450;
let currentCurrency = 'USD';
let properties = []; 
let map = null;
let markers = [];
let mapVisible = false;
let wishlist = [];
let isLoggedIn = false;

// Debounce utility for performance optimization
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

const propertiesGrid = document.getElementById('propertiesGrid');
const hamburger = document.querySelector('.hamburger');

// Fetch properties from API
async function fetchProperties() {
    try {
        console.log('Fetching properties from API...');
        const response = await fetch('properties.php?action=list');
        
        // Log response status and headers for debugging
        console.log('Response status:', response.status, response.statusText);
        
        // Get response as text first to see what we're dealing with
        const responseText = await response.text();
        console.log('Raw response:', responseText);
        
        try {
            const data = JSON.parse(responseText);
            console.log('Parsed data:', data);
            
            if (data.success && data.data) {
                properties = data.data;
                console.log(`Successfully loaded ${properties.length} properties`);
                displayProperties(properties);
                if (map) {
                    addPropertyMarkers();
                }
                preloadImages(properties);
            } else {
                console.error('Failed to fetch properties:', data.message || 'Unknown error');
            }
        } catch (parseError) {
            console.error('Failed to parse JSON response. Raw response:', responseText);
            console.error('Parse error:', parseError);
        }
    } catch (error) {
        console.error('Error in fetchProperties:', error);
    }
}
const navLinks = document.querySelector('.nav-links');
const contactForm = document.getElementById('contactForm');
const searchInput = document.getElementById('searchInput');
const searchBtn = document.getElementById('searchBtn');
const propertyTypeSelect = document.getElementById('propertyType');
const priceRangeSelect = document.getElementById('priceRange');
const mapToggle = document.getElementById('mapToggle');
const mapContainer = document.getElementById('mapContainer');

const translations = {
    en: {
        home: 'Home',
        properties: 'Properties',
        about: 'About',
        contact: 'Contact',
        login: 'Login',
        signup: 'Sign Up',
        switchToIQD: 'Switch to IQD',
        switchToUSD: 'Switch to USD',
        kurdish: 'کوردی',
        english: 'English',
        darkMode: 'Dark Mode',
        lightMode: 'Light Mode',
        
        heroTitle: 'Find Your Dream Home',
        heroSubtitle: 'Discover the perfect property that matches your lifestyle',
        searchPlaceholder: 'Search by location, property type, or price...',
        searchButton: 'Search',
        showMap: 'Show Map',
        showList: 'Show List',
        
        allTypes: 'All Types',
        apartment: 'Apartment',
        house: 'House',
        villa: 'Villa',
        townhouse: 'Townhouse',
        penthouse: 'Penthouse',
        anyPrice: 'Any Price',
        under100k: 'Under $100K',
        range100to200: '$100K - $200K',
        range200to300: '$200K - $300K',
        over300k: '$300K+',
        
        featured: 'Featured Properties',
        viewDetails: 'View Details',
        bedrooms: 'Bedrooms',
        bathrooms: 'Bathrooms',
        
        aboutTitle: 'Why Choose Us',
        feature1: 'Wide Selection',
        feature1Text: 'Hundreds of properties to choose from across Kurdistan\'s most desirable locations',
        feature2: 'Best Prices',
        feature2Text: 'Competitive pricing and great deals on properties throughout the region',
        feature3: 'Local Expertise',
        feature3Text: 'Our team has in-depth knowledge of the Kurdistan real estate market',
        
        contactTitle: 'Contact Us',
        yourName: 'Your Name',
        yourEmail: 'Your Email',
        yourMessage: 'Your Message',
        sendMessage: 'Send Message',
        getInTouch: 'Get in Touch',
        
        propertyFeatures: 'Property Features',
        description: 'Description',
        interested: 'Interested in this property?',
        scheduleViewing: 'Contact our agent for more information or to schedule a viewing.',
        contactAgent: 'Contact Agent',
        
        companyName: 'Luxury Estate',
        companyTagline: 'Your trusted partner in finding the perfect home.',
        quickLinks: 'Quick Links',
        connect: 'Connect',
        rightsReserved: 'All rights reserved',
        
        noResults: 'No properties found matching your criteria',
        adjustFilters: 'Try adjusting your search filters or browse our featured properties'
    },
    ckb: {
        home: 'سەرەکی',
        properties: 'خانووەکان',
        about: 'دەربارە',
        contact: 'پەیوەندی',
        login: 'چوونەژوورەوە',
        signup: 'تۆمارکردن',
        switchToIQD: 'گۆڕین بۆ دینار',
        switchToUSD: 'گۆڕین بۆ دۆلار',
        kurdish: 'کوردی',
        english: 'English',
        darkMode: 'دۆخی تاریک',
        lightMode: 'دۆخی ڕووناک',
        
        heroTitle: 'خانووی خەونەکانت بدۆزەوە',
        heroSubtitle: 'خانوویەکی گونجاو بدۆزەوە کە گونجاو بێت بۆ ژیانت',
        searchPlaceholder: 'گەڕان بەپێی شوێن، جۆری خانوو، یان نرخ...',
        searchButton: 'گەڕان',
        showMap: 'پیشاندانی نەخشە',
        showList: 'پیشاندانی لیست',
        
        allTypes: 'هەموو جۆرەکان',
        apartment: 'شوقە',
        house: 'خانوو',
        villa: 'ڤیلا',
        townhouse: 'خانووی شارستانی',
        penthouse: 'پێنتهاوس',
        anyPrice: 'هەر نرخێک',
        under100k: 'کەمتر لە $100K',
        range100to200: '$100K - $200K',
        range200to300: '$200K - $300K',
        over300k: '$300K+',
        
        featured: 'خانووە تایبەتەکان',
        viewDetails: 'بینینی وردەکاری',
        bedrooms: 'ژووری نوستن',
        bathrooms: 'ژووری ئاو',
        
        aboutTitle: 'بۆچی ئێمە هەڵبژێرین',
        feature1: 'هەڵبژاردنی فرە',
        feature1Text: 'سەدان خانوو لە هەموو شوێنە خوازراوەکانی کوردستان',
        feature2: 'نرخی باش',
        feature2Text: 'نرخی کێبڕکێ و مامەڵەی باش لە هەموو هەرێمەکە',
        feature3: 'شارەزایی ناوخۆیی',
        feature3Text: 'تیمەکەمان زانیاری قووڵی هەیە لە بازاڕی خانووبەرەی کوردستان',
        
        contactTitle: 'پەیوەندیمان پێوە بکە',
        yourName: 'ناوت',
        yourEmail: 'ئیمەیڵەکەت',
        yourMessage: 'پەیامەکەت',
        sendMessage: 'ناردنی پەیام',
        getInTouch: 'پەیوەندی بکە',
        
        propertyFeatures: 'تایبەتمەندییەکانی خانوو',
        description: 'وەسف',
        interested: 'ئایا سەرنجت ڕاکێشاوە بەم خانووە؟',
        scheduleViewing: 'پەیوەندی بە بریکارەکەمانەوە بکە بۆ زانیاری زیاتر یان دیاریکردنی کاتی سەردانکردن.',
        contactAgent: 'پەیوەندی بە بریکار',
        
        companyName: 'خانووبەرەی لوکس',
        companyTagline: 'هاوبەشی متمانەپێکراوت بۆ دۆزینەوەی خانووی تەواو.',
        quickLinks: 'بەستەرە خێراکان',
        connect: 'بەستنەوە',
        rightsReserved: 'هەموو مافەکان پارێزراون',
        
        noResults: 'هیچ خانوویەک نەدۆزرایەوە کە لەگەڵ پێداویستیەکانت بگونجێت',
        adjustFilters: 'هەوڵبدە فلتەرەکانی گەڕان بگۆڕیت یان سەیری خانووە تایبەتەکانمان بکە'
    }
};

let currentLanguage = 'en';

function formatPrice(price) {
    if (!price) return '';
    
    if (currentCurrency === 'IQD') {
        const iqdPrice = Math.round(price * USD_TO_IQD);
        return iqdPrice.toLocaleString();
    }
    
    return price.toLocaleString();
}

function toggleCurrency() {
    currentCurrency = currentCurrency === 'USD' ? 'IQD' : 'USD';
    updateCurrencyButton();
    updateAllPrices();
}

function updateCurrencyButton() {
    const lang = translations[currentLanguage];
    const currencyText = currentCurrency === 'USD' ? lang.switchToIQD : lang.switchToUSD;
    const currencyButton = document.getElementById('currencyToggle');
    if (currencyButton) {
        currencyButton.textContent = currencyText;
    }
}

function updateAllPrices() {
    document.querySelectorAll('.property-price').forEach(priceElement => {
        const price = parseFloat(priceElement.getAttribute('data-price'));
        priceElement.textContent = formatPrice(price);
    });
    
    const modalPrice = document.querySelector('.property-details .property-price');
    if (modalPrice) {
        const price = parseFloat(modalPrice.getAttribute('data-price'));
        modalPrice.textContent = formatPrice(price);
    }
}

function toggleLanguage() {
    currentLanguage = currentLanguage === 'en' ? 'ckb' : 'en';
    document.documentElement.lang = currentLanguage;
    document.documentElement.dir = currentLanguage === 'ckb' ? 'rtl' : 'ltr';
    applyLanguage();

    updatePropertyCards();
    
    const modal = document.getElementById('propertyModal');
    if (modal && modal.style.display === 'block') {
        const propertyId = parseInt(modal.getAttribute('data-property-id'));
        if (propertyId) {
            viewProperty(propertyId);
        }
    }
}

function applyLanguage() {
    const lang = translations[currentLanguage];
    
    // Navigation
    document.querySelectorAll('.nav-links a[href="#home"]').forEach(el => el.textContent = lang.home);
    document.querySelectorAll('.nav-links a[href="#properties"]').forEach(el => el.textContent = lang.properties);
    document.querySelectorAll('.nav-links a[href="#about"]').forEach(el => el.textContent = lang.about);
    document.querySelectorAll('.nav-links a[href="#contact"]').forEach(el => el.textContent = lang.contact);
    
    // Auth buttons
    const loginBtn = document.querySelector('a[href="login.php"]');
    const signupBtn = document.querySelector('a[href="signup.php"]');
    if (loginBtn) loginBtn.textContent = lang.login;
    if (signupBtn) signupBtn.textContent = lang.signup;
    
    // Hero section
    const hero = document.querySelector('.hero-content');
    if (hero) {
        hero.querySelector('h1').textContent = lang.heroTitle;
        hero.querySelector('p').textContent = lang.heroSubtitle;
        const searchInput = hero.querySelector('input[type="text"]');
        if (searchInput) searchInput.placeholder = lang.searchPlaceholder;
        const searchButton = hero.querySelector('.search-input-group button');
        if (searchButton) searchButton.innerHTML = `<i class="fas fa-search"></i> ${lang.searchButton}`;
    }
    
    // Property type dropdown
    const propertyType = document.getElementById('propertyType');
    if (propertyType) {
        propertyType.options[0].text = lang.allTypes;
        propertyType.options[1].text = lang.apartment;
        propertyType.options[2].text = lang.house;
        propertyType.options[3].text = lang.villa;
        propertyType.options[4].text = lang.townhouse;
        propertyType.options[5].text = lang.penthouse;
    }
    
    // Price range dropdown
    const priceRange = document.getElementById('priceRange');
    if (priceRange) {
        priceRange.options[0].text = lang.anyPrice;
        priceRange.options[1].text = lang.under100k;
        priceRange.options[2].text = lang.range100to200;
        priceRange.options[3].text = lang.range200to300;
        priceRange.options[4].text = lang.over300k;
    }
    
    // Map toggle
    const mapToggleBtn = document.getElementById('mapToggle');
    if (mapToggleBtn) {
        const isMapVisible = mapContainer ? mapContainer.style.display !== 'none' : false;
        mapToggleBtn.innerHTML = `<i class="fas fa-${isMapVisible ? 'list' : 'map'}"></i> ${isMapVisible ? lang.showList : lang.showMap}`;
    }
    
    const featuredHeading = document.querySelector('.featured h2');
    if (featuredHeading) {
        featuredHeading.textContent = lang.featured;
    }
    
    const aboutSection = document.querySelector('.about');
    if (aboutSection) {
        aboutSection.querySelector('h2').textContent = lang.aboutTitle;
        const features = aboutSection.querySelectorAll('.feature');
        if (features.length >= 3) {
            features[0].querySelector('h3').textContent = lang.feature1;
            features[0].querySelector('p').textContent = lang.feature1Text;
            features[1].querySelector('h3').textContent = lang.feature2;
            features[1].querySelector('p').textContent = lang.feature2Text;
            features[2].querySelector('h3').textContent = lang.feature3;
            features[2].querySelector('p').textContent = lang.feature3Text;
        }
    }
    
    // Contact section
    const contactSection = document.querySelector('.contact');
    if (contactSection) {
        contactSection.querySelector('h2').textContent = lang.contactTitle;
        const form = contactSection.querySelector('form');
        if (form) {
            form.querySelector('input[type="text"]').placeholder = lang.yourName;
            form.querySelector('input[type="email"]').placeholder = lang.yourEmail;
            form.querySelector('textarea').placeholder = lang.yourMessage;
            form.querySelector('button[type="submit"]').textContent = lang.sendMessage;
        }
        const contactInfo = contactSection.querySelector('.contact-info');
        if (contactInfo) {
            contactInfo.querySelector('h3').textContent = lang.getInTouch;
        }
    }
    
    // Footer
    const footer = document.querySelector('footer');
    if (footer) {
        const footerSections = footer.querySelectorAll('.footer-section');
        if (footerSections.length >= 3) {
            footerSections[0].querySelector('h3').textContent = lang.companyName;
            footerSections[0].querySelector('p').textContent = lang.companyTagline;
            footerSections[1].querySelector('h3').textContent = lang.quickLinks;
            footerSections[2].querySelector('h3').textContent = lang.connect;
        }
        const footerBottom = footer.querySelector('.footer-bottom p');
        if (footerBottom) {
            footerBottom.innerHTML = `&copy; ${new Date().getFullYear()} ${lang.companyName}. ${lang.rightsReserved}.`;
        }
    }
    
    updatePropertyCards();
    
    updateCurrencyButton();
    updateDarkModeUI(document.body.classList.contains('dark-mode'));
    updateLanguageButton();
}

function updatePropertyCards() {
    const lang = translations[currentLanguage];
    document.querySelectorAll('#propertiesGrid .property-card').forEach(card => {
        const button = card.querySelector('.property-info .btn');
        if (button) {
            button.textContent = lang.viewDetails;
        }
    });

    const noResults = document.querySelector('#propertiesGrid .no-results');
    if (noResults) {
        const heading = noResults.querySelector('h3');
        if (heading) heading.textContent = lang.noResults;
        const paragraph = noResults.querySelector('p');
        if (paragraph) paragraph.textContent = lang.adjustFilters;
    }
}

function updateLanguageButton() {
    const lang = translations[currentLanguage];
    const languageButton = document.getElementById('languageToggle');
    if (languageButton) {
        languageButton.innerHTML = `<i class="fas fa-language"></i> ${currentLanguage === 'en' ? lang.kurdish : lang.english}`;
    }
}

function displayProperties(propertiesToShow) {
    const lang = translations[currentLanguage];
    propertiesGrid.innerHTML = '';
    
    if (propertiesToShow.length === 0) {
        propertiesGrid.innerHTML = `
            <div class="no-results" style="grid-column: 1 / -1; text-align: center; padding: 2rem;">
                <h3>${lang.noResults}</h3>
                <p>${lang.adjustFilters}</p>
            </div>
        `;
        return;
    }
    
    const fragment = document.createDocumentFragment();
    
    propertiesToShow.forEach(property => {
        const isInWishlist = wishlist.includes(property.id);
        const propertyElement = document.createElement('div');
        propertyElement.className = 'property-card';
        propertyElement.innerHTML = `
            <button class="wishlist-btn ${isInWishlist ? 'active' : ''}" data-property-id="${property.id}" onclick="toggleWishlist(${property.id})">
                <i class="${isInWishlist ? 'fas' : 'far'} fa-heart"></i>
            </button>
            <div class="property-image">
                <img loading="lazy" src="${property.image}" alt="${property.title}">
            </div>
            <div class="property-info">
                <h3>${property.title}</h3>
                <p class="property-location"><i class="fas fa-map-marker-alt"></i> ${property.location}</p>
                <div class="property-price" data-price="${property.price}">${formatPrice(property.price)}</div>
                <p class="property-features">${property.features}</p>
                <button class="btn" onclick="viewProperty(${property.id})">
                    ${lang.viewDetails}
                </button>
            </div>
        `;
        fragment.appendChild(propertyElement);
    });
    
    propertiesGrid.appendChild(fragment);
}

function viewProperty(id) {
    const property = properties.find(p => p.id === id);
    if (!property) return;

    const thumbnailsHTML = property.images.map((img, index) => `
        <img src="${img}" 
             alt="${property.title} - Image ${index + 1}" 
             onerror="this.src='https://via.placeholder.com/150/eee/999?text=Image+Not+Available'"
             data-index="${index}" 
             class="${index === 0 ? 'active' : ''}">
    `).join('');

    const detailsHTML = `
        <div class="property-details">
            <div class="property-gallery">
                <img src="${property.images[0]}" 
                     alt="${property.title}" 
                     class="main-image"
                     onerror="this.src='https://via.placeholder.com/800x500/eee/999?text=Image+Not+Available'">
                
                <div class="gallery-nav">
                    <button class="gallery-prev">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <button class="gallery-next">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
                
                <div class="image-counter">1 / ${property.images.length}</div>
                
                <div class="gallery-thumbnails">
                    ${thumbnailsHTML}
                </div>
            </div>
            
            <div class="property-details-info">
                <h2>${property.title}</h2>
                <p class="property-location"><i class="fas fa-map-marker-alt"></i> ${property.location}</p>
                <div class="property-price">${formatPrice(property.price)}</div>
                
                <div class="property-details-features">
                    <h3>Property Features</h3>
                    <div class="property-features-grid">
                        ${property.features.split('•').filter(Boolean).map(feature => 
                            `<div class="feature-item">
                                <i class="fas fa-check"></i>
                                <span>${feature.trim()}</span>
                            </div>`
                        ).join('')}
                    </div>
                </div>
                
                <div class="property-description">
                    <h3>Description</h3>
                    <p>This beautiful property is located in the heart of ${property.location}. ${getPropertyDescription(property)}</p>
                </div>
                
                <div class="contact-agent">
                    <h3>Interested in this property?</h3>
                    <p>Contact our agent for more information or to schedule a viewing.</p>
                    <button class="btn" onclick="document.getElementById('contact').scrollIntoView({behavior: 'smooth'});
                                              document.getElementById('propertyModal').style.display = 'none';">
                        Contact Agent
                    </button>
                </div>
            </div>
        </div>
    `;

    const modal = document.getElementById('propertyModal');
    document.getElementById('propertyDetails').innerHTML = detailsHTML;
    modal.style.display = 'block';
    
    initGallery(property.images);
    
    document.querySelector('.close').onclick = function() {
        modal.style.display = 'none';
    }
    
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
}

function initGallery(images) {
    const mainImage = document.querySelector('.main-image');
    const thumbnails = document.querySelectorAll('.gallery-thumbnails img');
    const prevBtn = document.querySelector('.gallery-prev');
    const nextBtn = document.querySelector('.gallery-next');
    const imageCounter = document.querySelector('.image-counter');
    let currentIndex = 0;
    
    
    function updateGallery(index) {
        mainImage.style.opacity = '0';
        setTimeout(() => {
            mainImage.src = images[index];
            mainImage.style.opacity = '1';
        }, 200);
        
        thumbnails.forEach((thumb, i) => {
            if (i === index) {
                thumb.classList.add('active');
                thumb.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            } else {
                thumb.classList.remove('active');
            }
        });
        
        imageCounter.textContent = `${index + 1} / ${images.length}`;
        currentIndex = index;
    }
    
    thumbnails.forEach((thumb, index) => {
        thumb.addEventListener('click', () => {
            updateGallery(index);
        });
    });
    
    prevBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const newIndex = (currentIndex - 1 + images.length) % images.length;
        updateGallery(newIndex);
    });
    
    nextBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        const newIndex = (currentIndex + 1) % images.length;
        updateGallery(newIndex);
    });
    
    document.addEventListener('keydown', (e) => {
        if (document.getElementById('propertyModal').style.display === 'block') {
            if (e.key === 'ArrowLeft') {
                const newIndex = (currentIndex - 1 + images.length) % images.length;
                updateGallery(newIndex);
            } else if (e.key === 'ArrowRight') {
                const newIndex = (currentIndex + 1) % images.length;
                updateGallery(newIndex);
            } else if (e.key === 'Escape') {
                document.getElementById('propertyModal').style.display = 'none';
            }
        }
    });
    
    document.querySelector('.property-gallery').addEventListener('click', (e) => {
        e.stopPropagation();
    });
}

function getPropertyDescription(property) {
    const features = property.features.split('•').filter(Boolean).map(f => f.trim().toLowerCase());
    let description = '';
    
    if (features.some(f => f.includes('garden'))) {
        description += 'It features a beautiful garden perfect for outdoor relaxation. ';
    }
    if (features.some(f => f.includes('mountain'))) {
        description += 'Enjoy stunning mountain views from this property. ';
    }
    if (features.some(f => f.includes('modern'))) {
        description += 'The modern design and finishes provide a contemporary living experience. ';
    }
    if (features.some(f => f.includes('traditional'))) {
        description += 'This property features traditional Kurdish architectural elements. ';
    }
    
    return description || 'This well-maintained property offers comfortable living spaces and modern amenities.';
}

hamburger.addEventListener('click', () => {
    navLinks.classList.toggle('active');
    hamburger.classList.toggle('active');
});

document.querySelectorAll('.nav-links a').forEach(link => {
    link.addEventListener('click', () => {
        navLinks.classList.remove('active');
        hamburger.classList.remove('active');
    });
});

document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            window.scrollTo({
                top: targetElement.offsetTop - 80,
                behavior: 'smooth'
            });
        }
    });
});

if (contactForm) {
    contactForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(contactForm);
        const formValues = Object.fromEntries(formData.entries());
        
        console.log('Form submitted:', formValues);
        
        alert('Thank you for your message! We will get back to you soon.');
        
        contactForm.reset();
    });
}

function initMap() {
    if (typeof L === 'undefined') {
        console.error('Leaflet library not loaded');
        return;
    }
    
    const kurdistanCenter = [36.2, 44.0];
    
    map = L.map('map').setView(kurdistanCenter, 8);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
        maxZoom: 18
    }).addTo(map);
    
    const kurdistanBoundary = L.polygon([
        [37.0, 42.0],
        [37.0, 46.0],
        [35.0, 46.0],
        [35.0, 42.0]
    ], {
        color: '#3b82f6',
        weight: 2,
        opacity: 0.8,
        fillColor: '#3b82f6',
        fillOpacity: 0.1
    }).addTo(map);
    
    console.log('OpenStreetMap initialized for Kurdistan region');
}

function toggleMap() {
    const lang = translations[currentLanguage];
    mapVisible = !mapVisible;
    
    if (mapVisible) {
        mapContainer.style.display = 'block';
        mapToggle.innerHTML = `<i class="fas fa-list"></i> ${lang.showList}`;
        
        if (!map) {
            setTimeout(() => {
                initMap();
                addPropertyMarkers();
            }, 100);
        } else {
            addPropertyMarkers();
        }
    } else {
        mapContainer.style.display = 'none';
        mapToggle.innerHTML = `<i class="fas fa-map"></i> ${lang.showMap}`;
    }
}

function addPropertyMarkers() {
    if (!map) {
        console.log('Map not initialized yet');
        return;
    }
    
    markers.forEach(marker => map.removeLayer(marker));
    markers = [];
    
    console.log('Adding markers for', properties.length, 'properties');
    
    const propertyIcon = L.divIcon({
        className: 'custom-property-marker',
        html: `
            <div style="
                width: 40px;
                height: 40px;
                background: #3b82f6;
                border: 3px solid white;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                box-shadow: 0 2px 10px rgba(0,0,0,0.3);
                cursor: pointer;
            ">
                <i class="fas fa-home" style="color: white; font-size: 16px;"></i>
            </div>
        `,
        iconSize: [40, 40],
        iconAnchor: [20, 20]
    });
    
    properties.forEach(property => {
        if (property.latitude && property.longitude) {
            const marker = L.marker([property.latitude, property.longitude], {
                icon: propertyIcon
            }).addTo(map);
            
            const popupContent = `
                <div style="padding: 10px; max-width: 250px;">
                    <h3 style="margin: 0 0 10px 0; color: #1f2937; font-size: 16px;">${property.title}</h3>
                    <p style="margin: 5px 0; color: #3b82f6; font-weight: bold; font-size: 18px;">${formatPrice(property.price)}</p>
                    <p style="margin: 5px 0; color: #6b7280; font-size: 14px;"><i class="fas fa-map-marker-alt"></i> ${property.location}</p>
                    <p style="margin: 5px 0; color: #6b7280; font-size: 12px;">${property.features}</p>
                    <button onclick="viewProperty(${property.id})" 
                            style="background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-top: 10px;">
                        View Details
                    </button>
                </div>
            `;
            
            marker.bindPopup(popupContent);
            markers.push(marker);
        }
    });
    
    console.log('Added', markers.length, 'markers to map');
}

function searchProperties() {
    const searchTerm = searchInput.value.toLowerCase();
    const propertyType = propertyTypeSelect.value;
    const priceRange = priceRangeSelect.value;
    
    let filteredProperties = properties;
    
    if (searchTerm) {
        filteredProperties = filteredProperties.filter(property => 
            property.title.toLowerCase().includes(searchTerm) || 
            property.location.toLowerCase().includes(searchTerm) ||
            property.features.toLowerCase().includes(searchTerm)
        );
    }
    
    if (propertyType) {
        filteredProperties = filteredProperties.filter(property => 
            property.property_type === propertyType
        );
    }
    
    if (priceRange) {
        const [min, max] = priceRange.split('-').map(p => p === '+' ? Infinity : parseInt(p));
        filteredProperties = filteredProperties.filter(property => {
            const price = parseInt(property.price);
            return price >= min && (max === Infinity || price <= max);
        });
    }
    
    displayProperties(filteredProperties);
    
    if (mapVisible && map) {
        addPropertyMarkers();
    }
    
    if (filteredProperties.length === 0) {
        propertiesGrid.innerHTML = `
            <div class="no-results" style="grid-column: 1 / -1; text-align: center; padding: 2rem;">
                <h3>No properties found matching your criteria</h3>
                <p>Try adjusting your search filters or browse our featured properties</p>
            </div>
        `;
    }
}

searchBtn.addEventListener('click', searchProperties);
searchInput.addEventListener('keyup', (e) => {
    if (e.key === 'Enter') {
        searchProperties();
    }
});

propertyTypeSelect.addEventListener('change', searchProperties);
priceRangeSelect.addEventListener('change', searchProperties);
mapToggle.addEventListener('click', toggleMap);

function preloadImages(list = properties) {
    // Preload only visible property images to reduce initial load
    list.slice(0, 6).forEach(property => {
        const img = new Image();
        img.src = property.image;
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initDarkMode();
    initWishlist();
    fetchProperties();

    const sections = document.querySelectorAll('section');
    const navItems = document.querySelectorAll('.nav-links a');

    const handleScroll = debounce(() => {
        let current = '';
        const scrollPos = window.pageYOffset;

        sections.forEach(section => {
            const sectionTop = section.offsetTop;
            if (scrollPos >= sectionTop - 200) {
                current = section.getAttribute('id');
            }
        });

        navItems.forEach(item => {
            item.classList.remove('active');
            if (item.getAttribute('href') === `#${current}`) {
                item.classList.add('active');
            }
        });
    }, 100);

    window.addEventListener('scroll', handleScroll, { passive: true });
});

// Lazy loading for images (will work with data-src attributes if added)
if ('IntersectionObserver' in window) {
    const imageOptions = {
        threshold: 0,
        rootMargin: '0px 0px 100px 0px'
    };

    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (!entry.isIntersecting) return;
            
            const img = entry.target;
            if (img.dataset.src) {
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    }, imageOptions);

    // Observe images with data-src attribute
    document.querySelectorAll('img[data-src]').forEach(image => {
        imageObserver.observe(image);
    });
}
