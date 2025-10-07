// Initialize AOS (Animate On Scroll)
AOS.init({
    duration: 1000,
    once: true,
    offset: 100
});

// Responsive navigation functionality
document.addEventListener('DOMContentLoaded', function() {
    // Hamburger Menu Toggle
    const hamburger = document.querySelector('.hamburger');
    const mobileMenu = document.querySelector('.mobile-menu');
    const navLinks = document.querySelector('.nav-links');
    const sidebar = document.querySelector('.sidebar');
    const body = document.body;
    const wrapper = document.querySelector('.wrapper');
    const hero = document.querySelector('.hero');

    // Fix for blue sidebar on mobile
    function adjustLayout() {
        if (window.innerWidth <= 767) {
            if (sidebar) {
                sidebar.style.transform = 'translateX(-100%)';
                sidebar.style.left = '-260px';
            }
            if (wrapper) {
                wrapper.style.display = 'block';
                wrapper.style.width = '100%';
            }
            document.querySelectorAll('.main-content').forEach(content => {
                content.style.marginLeft = '0';
                content.style.width = '100%';
                content.style.maxWidth = '100%';
            });
            
            // Adjust hero section if it exists
            if (hero) {
                hero.style.paddingTop = '80px'; // Add space for the navbar
            }
        } else {
            if (sidebar) {
                sidebar.style.transform = '';
                sidebar.style.left = '';
            }
            if (wrapper && sidebar) {
                wrapper.style.display = 'flex';
            }
            
            // Reset hero padding on desktop
            if (hero) {
                hero.style.paddingTop = '';
            }
        }
    }

    // Run on page load
    adjustLayout();
    
    // Run on resize
    window.addEventListener('resize', adjustLayout);

    if (hamburger) {
        hamburger.addEventListener('click', (e) => {
            e.stopPropagation(); // Prevent event from bubbling
            hamburger.classList.toggle('active');
            
            // Toggle mobile menu
            if (mobileMenu) {
                mobileMenu.classList.toggle('active');
                
                // Log for debugging
                console.log('Mobile menu toggled, active status:', mobileMenu.classList.contains('active'));
            }
            
            // Toggle navigation on mobile
            if (navLinks) {
                navLinks.classList.toggle('active');
            }
            
            // Toggle sidebar on mobile if it exists
            if (sidebar) {
                sidebar.classList.toggle('active');
                if (sidebar.classList.contains('active')) {
                    sidebar.style.transform = 'translateX(0)';
                    sidebar.style.left = '0';
                } else {
                    sidebar.style.transform = 'translateX(-100%)';
                    sidebar.style.left = '-260px';
                }
            }
            
            // Prevent body scrolling when menu is open
            if ((mobileMenu && mobileMenu.classList.contains('active')) || 
                (navLinks && navLinks.classList.contains('active')) || 
                (sidebar && sidebar.classList.contains('active'))) {
                body.style.overflow = 'hidden';
            } else {
                body.style.overflow = '';
            }
        });
    }

    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
        // Make sure we have a hamburger, mobile menu, or sidebar that's active
        const hasActiveMenu = (hamburger && hamburger.classList.contains('active')) || 
                             (mobileMenu && mobileMenu.classList.contains('active')) ||
                             (navLinks && navLinks.classList.contains('active')) ||
                             (sidebar && sidebar.classList.contains('active'));
        
        // Check if the click was outside the menu elements
        const clickedOutside = hamburger && !hamburger.contains(e.target) && 
                              (!mobileMenu || !mobileMenu.contains(e.target)) &&
                              (!navLinks || !navLinks.contains(e.target)) &&
                              (!sidebar || !sidebar.contains(e.target));
        
        // Only close if we have an active menu and clicked outside
        if (hasActiveMenu && clickedOutside) {
            console.log('Closing mobile menu - clicked outside');
            
            if (hamburger) hamburger.classList.remove('active');
            if (mobileMenu) mobileMenu.classList.remove('active');
            if (navLinks) navLinks.classList.remove('active');
            
            if (sidebar) {
                sidebar.classList.remove('active');
                sidebar.style.transform = 'translateX(-100%)';
                sidebar.style.left = '-260px';
            }
            
            body.style.overflow = '';
        }
    });

    // Handle window resize
    const handleResize = () => {
        if (window.innerWidth > 767) {
            // Reset mobile menu state on desktop
            if (hamburger) {
                hamburger.classList.remove('active');
            }
            
            if (mobileMenu) {
                mobileMenu.classList.remove('active');
            }
            
            if (navLinks) {
                navLinks.classList.remove('active');
            }
            
            body.style.overflow = '';
        }
        
        // Always adjust layout on resize
        adjustLayout();
    };

    window.addEventListener('resize', handleResize);
    
    // Check screen size on load
    handleResize();
    
    // Make sure clicking mobile menu links closes the menu
    if (mobileMenu) {
        mobileMenu.querySelectorAll('a').forEach(link => {
            link.addEventListener('click', () => {
                mobileMenu.classList.remove('active');
                if (hamburger) hamburger.classList.remove('active');
                body.style.overflow = '';
                
                // Also close sidebar if on user dashboard
                if (sidebar) {
                    sidebar.classList.remove('active');
                    sidebar.style.transform = 'translateX(-100%)';
                    sidebar.style.left = '-260px';
                }
            });
        });
    }

    // Mobile menu toggle
    if (hamburger && navLinks) {
        hamburger.addEventListener('click', function() {
            navLinks.classList.toggle('active');
            hamburger.classList.toggle('active');
        });

        // Close menu when clicking outside
        document.addEventListener('click', function(e) {
            if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
                navLinks.classList.remove('active');
                hamburger.classList.remove('active');
            }
        });
    }

    // Password visibility toggle
    const toggleButtons = document.querySelectorAll('.password-toggle');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = this.previousElementSibling;
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            
            const icon = this.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
    });

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('error');
                } else {
                    field.classList.remove('error');
                }
            });

            if (!isValid) {
                e.preventDefault();
            }
        });
    });
});

// Add animation delay to nav links
document.querySelectorAll('.nav-links a').forEach((link, index) => {
    link.style.setProperty('--i', index);
});

// Navbar scroll effect with enhanced animations
let lastScroll = 0;
const navbar = document.querySelector('.navbar');
const navLinks = document.querySelectorAll('.nav-links a');

window.addEventListener('scroll', () => {
    if (!navbar) return;
    
    const currentScroll = window.pageYOffset;
    
    if (currentScroll <= 0) {
        navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
        navbar.style.transform = 'translateY(0)';
        return;
    }
    
    // Only hide navbar when scrolling down on non-mobile or when mobile menu is closed
    const mobileMenuActive = document.querySelector('.nav-links.active, .sidebar.active');
    if (currentScroll > lastScroll && !mobileMenuActive) {
        // Scrolling down with enhanced animation
        navbar.style.transform = 'translateY(-100%)';
        navbar.style.transition = 'transform 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
    } else {
        // Scrolling up with enhanced animation
        navbar.style.transform = 'translateY(0)';
        navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
        navbar.style.transition = 'all 0.5s cubic-bezier(0.4, 0, 0.2, 1)';
    }
    
    lastScroll = currentScroll;
});

// Enhanced hover effects for nav links
navLinks.forEach(link => {
    link.addEventListener('mouseenter', () => {
        link.style.transform = 'translateY(-2px)';
        link.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
    });
    
    link.addEventListener('mouseleave', () => {
        link.style.transform = 'translateY(0)';
        link.style.transition = 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)';
    });
});

// Smooth scroll for anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth'
            });
            
            // Close mobile menu after clicking a link
            const hamburger = document.querySelector('.hamburger');
            const navLinks = document.querySelector('.nav-links');
            const sidebar = document.querySelector('.sidebar');
            
            if (hamburger) hamburger.classList.remove('active');
            if (navLinks) navLinks.classList.remove('active');
            if (sidebar) sidebar.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});

// Add loading animation for buttons
const buttons = document.querySelectorAll('.primary-btn, .secondary-btn, .btn');
buttons.forEach(button => {
    button.addEventListener('click', function() {
        this.style.transform = 'scale(0.98)';
        setTimeout(() => {
            this.style.transform = 'scale(1)';
        }, 200);
    });
});

// Make tables responsive
const tables = document.querySelectorAll('table');
tables.forEach(table => {
    const wrapper = document.createElement('div');
    wrapper.classList.add('table-responsive');
    wrapper.style.overflowX = 'auto';
    table.parentNode.insertBefore(wrapper, table);
    wrapper.appendChild(table);
});

// Notification system
class NotificationSystem {
    constructor() {
        this.container = document.createElement('div');
        this.container.className = 'notification-container';
        document.body.appendChild(this.container);
    }

    show(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        
        this.container.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }
}

// Initialize notification system
const notifications = new NotificationSystem();

// Add notification styles
const style = document.createElement('style');
style.textContent = `
    .notification-container {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .notification {
        background: white;
        padding: 16px 24px;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        transform: translateX(100%);
        opacity: 0;
        transition: all 0.3s ease;
        min-width: 300px;
        max-width: 400px;
    }
    
    .notification.show {
        transform: translateX(0);
        opacity: 1;
    }
    
    .notification.success {
        border-left: 4px solid #4CAF50;
    }
    
    .notification.error {
        border-left: 4px solid #f44336;
    }
    
    .notification.info {
        border-left: 4px solid #2196F3;
    }
    
    /* Responsive adjustments */
    @media (max-width: 576px) {
        .notification-container {
            left: 20px;
            right: 20px;
        }
        
        .notification {
            min-width: auto;
            max-width: none;
            width: 100%;
        }
    }
`;
document.head.appendChild(style);

// Add this simple script at the end of the file to ensure the hamburger menu works
document.addEventListener('DOMContentLoaded', function() {
    // Direct hamburger menu toggle - simplified version
    const hamburger = document.querySelector('.hamburger');
    const mobileMenu = document.querySelector('.mobile-menu');
    
    if (hamburger && mobileMenu) {
        // Add click event to hamburger
        hamburger.addEventListener('click', function(e) {
            e.stopPropagation(); // Prevent event from bubbling
            
            // Toggle active classes
            hamburger.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            
            // Log for debugging
            console.log('Hamburger clicked. Mobile menu active:', mobileMenu.classList.contains('active'));
        });
        
        // Close when clicking outside
        document.addEventListener('click', function(e) {
            if (mobileMenu.classList.contains('active') && 
                !mobileMenu.contains(e.target) && 
                !hamburger.contains(e.target)) {
                
                mobileMenu.classList.remove('active');
                hamburger.classList.remove('active');
            }
        });
        
        // Prevent clicks inside mobile menu from closing it
        mobileMenu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }
});

// Mobile menu toggle
document.addEventListener('DOMContentLoaded', function() {
    const hamburger = document.querySelector('.hamburger');
    const mobileMenu = document.querySelector('.mobile-menu');
    const sidebar = document.querySelector('.sidebar');
    
    if (hamburger) {
        hamburger.addEventListener('click', function() {
            this.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            if (sidebar) {
                sidebar.classList.toggle('active');
            }
            
            // Animate hamburger
            const spans = this.getElementsByTagName('span');
            if (this.classList.contains('active')) {
                spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
                spans[1].style.opacity = '0';
                spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
            } else {
                spans[0].style.transform = 'none';
                spans[1].style.opacity = '1';
                spans[2].style.transform = 'none';
            }
        });
    }
    
    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        if (!event.target.closest('.hamburger') && !event.target.closest('.mobile-menu')) {
            if (mobileMenu && mobileMenu.classList.contains('active')) {
                mobileMenu.classList.remove('active');
                if (hamburger) {
                    hamburger.classList.remove('active');
                    const spans = hamburger.getElementsByTagName('span');
                    spans[0].style.transform = 'none';
                    spans[1].style.opacity = '1';
                    spans[2].style.transform = 'none';
                }
                if (sidebar) {
                    sidebar.classList.remove('active');
                }
            }
        }
    });
    
    // File upload validation
    const fileInputs = document.querySelectorAll('input[type="file"]');
    fileInputs.forEach(input => {
        input.addEventListener('change', function() {
            if (this.files[0]) {
                const fileSize = this.files[0].size / 1024 / 1024; // Convert to MB
                if (fileSize > 5) {
                    alert('File size should not exceed 5MB');
                    this.value = ''; // Clear the input
                    return;
                }
                
                const fileName = this.files[0].name;
                const fileExt = fileName.split('.').pop().toLowerCase();
                const allowedTypes = this.accept ? this.accept.split(',').map(type => type.trim().replace('.', '')) : [];
                
                if (allowedTypes.length > 0 && !allowedTypes.includes(fileExt)) {
                    alert('Invalid file type. Allowed types: ' + allowedTypes.join(', '));
                    this.value = ''; // Clear the input
                }
            }
        });
    });
    
    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredInputs = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredInputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('error');
                    
                    // Add error message
                    let errorMsg = input.nextElementSibling;
                    if (!errorMsg || !errorMsg.classList.contains('error-message')) {
                        errorMsg = document.createElement('div');
                        errorMsg.classList.add('error-message');
                        errorMsg.style.color = '#C53030';
                        errorMsg.style.fontSize = '0.875rem';
                        errorMsg.style.marginTop = '0.25rem';
                        input.parentNode.insertBefore(errorMsg, input.nextSibling);
                    }
                    errorMsg.textContent = 'This field is required';
                } else {
                    input.classList.remove('error');
                    const errorMsg = input.nextElementSibling;
                    if (errorMsg && errorMsg.classList.contains('error-message')) {
                        errorMsg.remove();
                    }
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
}); 