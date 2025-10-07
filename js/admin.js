// ScholarHub Admin Panel JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Admin sidebar toggle for mobile
    const hamburgerAdmin = document.querySelector('.hamburger-admin');
    const adminSidebar = document.querySelector('.admin-sidebar');
    const body = document.body;
    
    if (hamburgerAdmin && adminSidebar) {
        hamburgerAdmin.addEventListener('click', function() {
            adminSidebar.classList.toggle('active');
            this.classList.toggle('active');
            
            // Animate hamburger to X
            if (this.classList.contains('active')) {
                this.querySelector('span:nth-child(1)').style.transform = 'rotate(45deg) translate(5px, 5px)';
                this.querySelector('span:nth-child(2)').style.opacity = '0';
                this.querySelector('span:nth-child(3)').style.transform = 'rotate(-45deg) translate(5px, -5px)';
            } else {
                this.querySelector('span:nth-child(1)').style.transform = 'none';
                this.querySelector('span:nth-child(2)').style.opacity = '1';
                this.querySelector('span:nth-child(3)').style.transform = 'none';
            }
            
            // Prevent body scrolling when sidebar is open on mobile
            if (window.innerWidth <= 767) {
                if (adminSidebar.classList.contains('active')) {
                    body.style.overflow = 'hidden';
                } else {
                    body.style.overflow = '';
                }
            }
        });
        
        // Close sidebar when clicking outside
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 767 && 
                adminSidebar.classList.contains('active') && 
                !adminSidebar.contains(e.target) && 
                !hamburgerAdmin.contains(e.target)) {
                
                adminSidebar.classList.remove('active');
                hamburgerAdmin.classList.remove('active');
                
                // Reset hamburger icon
                hamburgerAdmin.querySelector('span:nth-child(1)').style.transform = 'none';
                hamburgerAdmin.querySelector('span:nth-child(2)').style.opacity = '1';
                hamburgerAdmin.querySelector('span:nth-child(3)').style.transform = 'none';
                
                body.style.overflow = '';
            }
        });
    }
    
    // Make tables responsive
    const tables = document.querySelectorAll('table');
    tables.forEach(table => {
        // Only wrap if not already wrapped
        if (table.parentElement.className !== 'table-responsive') {
            const wrapper = document.createElement('div');
            wrapper.className = 'table-responsive';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 767) {
            body.style.overflow = '';
            
            if (adminSidebar) {
                adminSidebar.classList.remove('active');
            }
            
            if (hamburgerAdmin) {
                hamburgerAdmin.classList.remove('active');
                hamburgerAdmin.querySelector('span:nth-child(1)').style.transform = 'none';
                hamburgerAdmin.querySelector('span:nth-child(2)').style.opacity = '1';
                hamburgerAdmin.querySelector('span:nth-child(3)').style.transform = 'none';
            }
        }
    });
    
    // Form validation with visual feedback
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateInput(this);
            });
        });
        
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            inputs.forEach(input => {
                if (!validateInput(input)) {
                    isValid = false;
                }
            });
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
    
    function validateInput(input) {
        if (input.required && !input.value.trim()) {
            input.classList.add('is-invalid');
            return false;
        } else {
            input.classList.remove('is-invalid');
            input.classList.add('is-valid');
            return true;
        }
    }
    
    // Tooltips initialization if Bootstrap is available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltips.forEach(tooltip => {
            new bootstrap.Tooltip(tooltip);
        });
    }
    
    // Confirmation dialogs
    const confirmedActions = document.querySelectorAll('[data-confirm]');
    confirmedActions.forEach(action => {
        action.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
}); 