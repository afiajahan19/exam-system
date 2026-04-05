// Basic JavaScript functionality for the Exam System

document.addEventListener('DOMContentLoaded', function() {
    // Form validation enhancements
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            // Basic client-side validation
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;
            
            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.style.borderColor = '#ff6b6b';
                    
                    // Remove error styling on input
                    field.addEventListener('input', function() {
                        this.style.borderColor = '#ddd';
                    }, { once: true });
                }
            });
            
            // Password confirmation validation
            const password = form.querySelector('#password');
            const confirmPassword = form.querySelector('#confirm_password');
            
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                isValid = false;
                confirmPassword.style.borderColor = '#ff6b6b';
                
                // Show error message
                const existingError = confirmPassword.parentNode.querySelector('.password-error');
                if (!existingError) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'password-error';
                    errorDiv.style.color = '#ff6b6b';
                    errorDiv.style.fontSize = '14px';
                    errorDiv.style.marginTop = '5px';
                    errorDiv.textContent = 'Passwords do not match';
                    confirmPassword.parentNode.appendChild(errorDiv);
                }
                
                // Remove error message on input
                confirmPassword.addEventListener('input', function() {
                    const error = this.parentNode.querySelector('.password-error');
                    if (error) {
                        error.remove();
                    }
                }, { once: true });
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    });
    
    // Add smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth'
                });
            }
        });
    });
    
    // Add loading state to buttons
    const buttons = document.querySelectorAll('.btn[type="submit"]');
    buttons.forEach(button => {
        button.addEventListener('click', function() {
            if (!this.disabled) {
                const originalText = this.textContent;
                this.textContent = 'Loading...';
                this.disabled = true;
                
                // Reset button after 3 seconds (in case form submission fails)
                setTimeout(() => {
                    this.textContent = originalText;
                    this.disabled = false;
                }, 3000);
            }
        });
    });
    
    // Auto-hide success messages after 5 seconds
    const successMessages = document.querySelectorAll('.alert.success');
    successMessages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            message.style.transition = 'opacity 0.5s ease';
            setTimeout(() => {
                message.remove();
            }, 500);
        }, 5000);
    });
    
    // Add input field focus effects
    const inputFields = document.querySelectorAll('input, select, textarea');
    inputFields.forEach(field => {
        field.addEventListener('focus', function() {
            this.parentNode.classList.add('focused');
        });
        
        field.addEventListener('blur', function() {
            this.parentNode.classList.remove('focused');
        });
    });
});

// Utility function to show notifications
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 5px;
        color: white;
        font-weight: 500;
        z-index: 1000;
        transform: translateX(100%);
        transition: transform 0.3s ease;
    `;
    
    // Set background color based on type
    switch(type) {
        case 'success':
            notification.style.background = 'linear-gradient(135deg, #3c3 0%, #6c6 100%)';
            break;
        case 'error':
            notification.style.background = 'linear-gradient(135deg, #c33 0%, #f66 100%)';
            break;
        case 'warning':
            notification.style.background = 'linear-gradient(135deg, #f90 0%, #fc6 100%)';
            break;
        default:
            notification.style.background = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)';
    }
    
    document.body.appendChild(notification);
    
    // Animate in
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 100);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
}

// Export for use in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { showNotification };
}
