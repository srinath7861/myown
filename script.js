// Wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    // Search tabs functionality
    const searchTabs = document.querySelectorAll('.search-tab');
    const searchInput = document.querySelector('.search-input');
    
    searchTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            searchTabs.forEach(t => t.classList.remove('active'));
            // Add active class to clicked tab
            this.classList.add('active');
            
            // Update search placeholder based on selected tab
            const type = this.dataset.type;
            if (type === 'buy') {
                searchInput.placeholder = 'Enter location, neighborhood, city, or ZIP to buy';
            } else if (type === 'rent') {
                searchInput.placeholder = 'Enter location for rental properties';
            } else if (type === 'sell') {
                searchInput.placeholder = 'Enter your property address to get an estimate';
            }
            
            // Add animation effect
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 100);
        });
    });
    
    // Search button functionality
    const searchButton = document.querySelector('.search-button');
    searchButton.addEventListener('click', function() {
        const location = searchInput.value;
        const propertyType = document.querySelectorAll('.filter-select')[0].value;
        const priceRange = document.querySelectorAll('.filter-select')[1].value;
        const bedrooms = document.querySelectorAll('.filter-select')[2].value;
        
        if (location.trim() === '') {
            // Add shake animation if no location entered
            searchInput.style.animation = 'shake 0.5s';
            searchInput.focus();
            setTimeout(() => {
                searchInput.style.animation = '';
            }, 500);
            return;
        }
        
        // Log search parameters (in real app, this would trigger search)
        console.log('Search Parameters:', {
            location,
            propertyType,
            priceRange,
            bedrooms,
            type: document.querySelector('.search-tab.active').dataset.type
        });
        
        // Add click animation
        this.style.transform = 'scale(0.95)';
        setTimeout(() => {
            this.style.transform = 'scale(1)';
        }, 100);
        
        // Show success message (in real app, this would navigate to results)
        showNotification('Searching for properties...', 'success');
    });
    
    // Mobile menu toggle
    const hamburger = document.querySelector('.hamburger');
    const navMenu = document.querySelector('.nav-menu');
    const navButtons = document.querySelector('.nav-buttons');
    
    hamburger.addEventListener('click', function() {
        // Toggle mobile menu
        navMenu.classList.toggle('mobile-active');
        navButtons.classList.toggle('mobile-active');
        
        // Animate hamburger
        this.classList.toggle('active');
    });
    
    // Smooth scroll for navigation links
    const navLinks = document.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Add click effect
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 100);
            
            // Show notification (in real app, this would navigate)
            showNotification(`Navigating to ${this.textContent}...`, 'info');
        });
    });
    
    // Property card interactions
    const propertyCards = document.querySelectorAll('.property-card');
    propertyCards.forEach(card => {
        card.addEventListener('click', function() {
            const propertyName = this.querySelector('h3').textContent;
            showNotification(`Opening details for ${propertyName}`, 'info');
        });
    });
    
    // Animate numbers on scroll
    const animateNumbers = () => {
        const stats = document.querySelectorAll('.stat-number');
        stats.forEach(stat => {
            const target = parseInt(stat.textContent.replace(/[^0-9]/g, ''));
            const increment = target / 100;
            let current = 0;
            
            const updateNumber = () => {
                if (current < target) {
                    current += increment;
                    stat.textContent = Math.ceil(current).toLocaleString() + '+';
                    requestAnimationFrame(updateNumber);
                } else {
                    stat.textContent = target.toLocaleString() + '+';
                }
            };
            
            // Start animation when element is in view
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        updateNumber();
                        observer.unobserve(entry.target);
                    }
                });
            });
            
            observer.observe(stat);
        });
    };
    
    animateNumbers();
    
    // Input field animations
    const inputs = document.querySelectorAll('.search-input, .filter-select');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });
    
    // Button hover effects
    const buttons = document.querySelectorAll('.btn-primary, .btn-secondary, .search-button');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
    
    // Parallax effect for floating cards
    document.addEventListener('mousemove', function(e) {
        const cards = document.querySelectorAll('.floating-card');
        const x = e.clientX / window.innerWidth;
        const y = e.clientY / window.innerHeight;
        
        cards.forEach((card, index) => {
            const speed = (index + 1) * 10;
            const xOffset = (x - 0.5) * speed;
            const yOffset = (y - 0.5) * speed;
            
            card.style.transform = `translate(${xOffset}px, ${yOffset}px)`;
        });
    });
    
    // Notification function
    function showNotification(message, type = 'info') {
        // Remove existing notification if any
        const existingNotification = document.querySelector('.notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.textContent = message;
        
        // Add styles
        notification.style.cssText = `
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 1rem 1.5rem;
            background: ${type === 'success' ? '#10B981' : '#4F46E5'};
            color: white;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            z-index: 9999;
            animation: slideInRight 0.3s ease-out;
        `;
        
        document.body.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
    
    // Add keyframe animations dynamically
    const style = document.createElement('style');
    style.textContent = `
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .nav-menu.mobile-active {
            display: flex !important;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            flex-direction: column;
            padding: 1rem 2rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .nav-buttons.mobile-active {
            display: flex !important;
            position: absolute;
            top: calc(100% + 200px);
            left: 0;
            right: 0;
            background: white;
            padding: 1rem 2rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        
        .hamburger.active span:nth-child(1) {
            transform: rotate(45deg) translate(5px, 5px);
        }
        
        .hamburger.active span:nth-child(2) {
            opacity: 0;
        }
        
        .hamburger.active span:nth-child(3) {
            transform: rotate(-45deg) translate(7px, -6px);
        }
    `;
    document.head.appendChild(style);
    
    // Lazy load images (placeholder for real implementation)
    const lazyImages = document.querySelectorAll('.property-image');
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // In real app, load actual image here
                entry.target.style.opacity = '1';
                imageObserver.unobserve(entry.target);
            }
        });
    });
    
    lazyImages.forEach(img => {
        img.style.opacity = '0';
        img.style.transition = 'opacity 0.5s';
        imageObserver.observe(img);
    });
    
    // Add entrance animations to elements
    const animateOnScroll = () => {
        const elements = document.querySelectorAll('.property-card, .section-header');
        
        const elementObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animation = 'fadeInUp 0.6s ease-out';
                    elementObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.1 });
        
        elements.forEach(element => {
            elementObserver.observe(element);
        });
    };
    
    animateOnScroll();
    
    // Search input autocomplete simulation
    const locations = [
        'New York, NY',
        'Los Angeles, CA',
        'Chicago, IL',
        'Houston, TX',
        'Phoenix, AZ',
        'Philadelphia, PA',
        'San Antonio, TX',
        'San Diego, CA',
        'Dallas, TX',
        'San Jose, CA'
    ];
    
    searchInput.addEventListener('input', function() {
        const value = this.value.toLowerCase();
        if (value.length > 2) {
            const matches = locations.filter(loc => 
                loc.toLowerCase().includes(value)
            );
            
            // In a real app, show autocomplete dropdown
            console.log('Matching locations:', matches);
        }
    });
    
    // Keyboard navigation
    searchInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            searchButton.click();
        }
    });
    
    // Form validation
    const validateSearch = () => {
        const location = searchInput.value.trim();
        if (location.length < 3) {
            searchInput.setCustomValidity('Please enter at least 3 characters');
            return false;
        }
        searchInput.setCustomValidity('');
        return true;
    };
    
    searchInput.addEventListener('input', validateSearch);
});

// Performance optimization - debounce function
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

// Smooth scroll behavior
document.documentElement.style.scrollBehavior = 'smooth';