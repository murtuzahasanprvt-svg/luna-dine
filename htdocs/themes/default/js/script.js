/**
 * Default Theme JavaScript
 * 
 * Interactive features and functionality
 */

(function() {
    'use strict';
    
    // Theme initialization
    class DefaultTheme {
        constructor() {
            this.init();
        }
        
        init() {
            this.setupEventListeners();
            this.setupAnimations();
            this.setupMobileMenu();
            this.setupSmoothScrolling();
            this.setupFormValidation();
            this.setupTooltips();
        }
        
        setupEventListeners() {
            // DOM ready
            document.addEventListener('DOMContentLoaded', () => {
                this.initializeComponents();
            });
            
            // Window resize
            window.addEventListener('resize', () => {
                this.handleResize();
            });
            
            // Window scroll
            window.addEventListener('scroll', () => {
                this.handleScroll();
            });
        }
        
        setupAnimations() {
            // Intersection Observer for fade-in animations
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('fade-in');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            
            // Observe elements with fade-in class
            document.querySelectorAll('.fade-in').forEach(el => {
                observer.observe(el);
            });
        }
        
        setupMobileMenu() {
            const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
            const mobileMenu = document.querySelector('.mobile-menu');
            
            if (mobileMenuToggle && mobileMenu) {
                mobileMenuToggle.addEventListener('click', () => {
                    mobileMenu.classList.toggle('active');
                    mobileMenuToggle.classList.toggle('active');
                });
                
                // Close mobile menu when clicking outside
                document.addEventListener('click', (e) => {
                    if (!mobileMenu.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
                        mobileMenu.classList.remove('active');
                        mobileMenuToggle.classList.remove('active');
                    }
                });
            }
        }
        
        setupSmoothScrolling() {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                });
            });
        }
        
        setupFormValidation() {
            const forms = document.querySelectorAll('form[data-validate]');
            
            forms.forEach(form => {
                form.addEventListener('submit', (e) => {
                    if (!this.validateForm(form)) {
                        e.preventDefault();
                    }
                });
                
                // Real-time validation
                const inputs = form.querySelectorAll('input, select, textarea');
                inputs.forEach(input => {
                    input.addEventListener('blur', () => {
                        this.validateField(input);
                    });
                    
                    input.addEventListener('input', () => {
                        this.clearFieldError(input);
                    });
                });
            });
        }
        
        setupTooltips() {
            const tooltips = document.querySelectorAll('[data-tooltip]');
            
            tooltips.forEach(element => {
                element.addEventListener('mouseenter', (e) => {
                    this.showTooltip(e.target, e.target.dataset.tooltip);
                });
                
                element.addEventListener('mouseleave', () => {
                    this.hideTooltip(e.target);
                });
            });
        }
        
        initializeComponents() {
            this.initializeMenuItems();
            this.initializeOrderSystem();
            this.initializeQuantityControls();
            this.initializeModals();
            this.initializeTabs();
            this.initializeAccordions();
        }
        
        initializeMenuItems() {
            const menuItems = document.querySelectorAll('.menu-item');
            
            menuItems.forEach(item => {
                const addToOrderBtn = item.querySelector('.add-to-order');
                if (addToOrderBtn) {
                    addToOrderBtn.addEventListener('click', () => {
                        this.addToOrder(item);
                    });
                }
                
                // Quick view functionality
                const quickViewBtn = item.querySelector('.quick-view');
                if (quickViewBtn) {
                    quickViewBtn.addEventListener('click', () => {
                        this.showQuickView(item);
                    });
                }
            });
        }
        
        initializeOrderSystem() {
            const orderForm = document.querySelector('#order-form');
            const orderSummary = document.querySelector('#order-summary');
            
            if (orderForm) {
                orderForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.submitOrder(orderForm);
                });
            }
            
            this.updateOrderSummary();
        }
        
        initializeQuantityControls() {
            const quantityControls = document.querySelectorAll('.quantity-control');
            
            quantityControls.forEach(control => {
                const decreaseBtn = control.querySelector('.decrease');
                const increaseBtn = control.querySelector('.increase');
                const input = control.querySelector('input');
                
                if (decreaseBtn && increaseBtn && input) {
                    decreaseBtn.addEventListener('click', () => {
                        const currentValue = parseInt(input.value);
                        if (currentValue > parseInt(input.min)) {
                            input.value = currentValue - 1;
                            this.updateQuantity(control);
                        }
                    });
                    
                    increaseBtn.addEventListener('click', () => {
                        const currentValue = parseInt(input.value);
                        if (currentValue < parseInt(input.max)) {
                            input.value = currentValue + 1;
                            this.updateQuantity(control);
                        }
                    });
                    
                    input.addEventListener('change', () => {
                        this.updateQuantity(control);
                    });
                }
            });
        }
        
        initializeModals() {
            const modalTriggers = document.querySelectorAll('[data-modal]');
            const modals = document.querySelectorAll('.modal');
            
            modalTriggers.forEach(trigger => {
                trigger.addEventListener('click', () => {
                    const modalId = trigger.dataset.modal;
                    const modal = document.querySelector(modalId);
                    if (modal) {
                        this.showModal(modal);
                    }
                });
            });
            
            modals.forEach(modal => {
                const closeBtn = modal.querySelector('.modal-close');
                const overlay = modal.querySelector('.modal-overlay');
                
                if (closeBtn) {
                    closeBtn.addEventListener('click', () => {
                        this.hideModal(modal);
                    });
                }
                
                if (overlay) {
                    overlay.addEventListener('click', () => {
                        this.hideModal(modal);
                    });
                }
            });
        }
        
        initializeTabs() {
            const tabContainers = document.querySelectorAll('.tabs');
            
            tabContainers.forEach(container => {
                const tabButtons = container.querySelectorAll('.tab-button');
                const tabContents = container.querySelectorAll('.tab-content');
                
                tabButtons.forEach(button => {
                    button.addEventListener('click', () => {
                        const targetTab = button.dataset.tab;
                        
                        // Update button states
                        tabButtons.forEach(btn => btn.classList.remove('active'));
                        button.classList.add('active');
                        
                        // Update content visibility
                        tabContents.forEach(content => {
                            if (content.dataset.tab === targetTab) {
                                content.classList.add('active');
                            } else {
                                content.classList.remove('active');
                            }
                        });
                    });
                });
            });
        }
        
        initializeAccordions() {
            const accordions = document.querySelectorAll('.accordion');
            
            accordions.forEach(accordion => {
                const headers = accordion.querySelectorAll('.accordion-header');
                
                headers.forEach(header => {
                    header.addEventListener('click', () => {
                        const content = header.nextElementSibling;
                        const isActive = header.classList.contains('active');
                        
                        // Close all accordion items
                        headers.forEach(h => {
                            h.classList.remove('active');
                            h.nextElementSibling.style.maxHeight = '0';
                        });
                        
                        // Open clicked item if it wasn't active
                        if (!isActive) {
                            header.classList.add('active');
                            content.style.maxHeight = content.scrollHeight + 'px';
                        }
                    });
                });
            });
        }
        
        // Utility methods
        validateForm(form) {
            let isValid = true;
            const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
            
            inputs.forEach(input => {
                if (!this.validateField(input)) {
                    isValid = false;
                }
            });
            
            return isValid;
        }
        
        validateField(field) {
            const value = field.value.trim();
            const type = field.type;
            let isValid = true;
            let errorMessage = '';
            
            if (field.hasAttribute('required') && !value) {
                isValid = false;
                errorMessage = 'This field is required';
            } else if (type === 'email' && value && !this.isValidEmail(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid email address';
            } else if (type === 'tel' && value && !this.isValidPhone(value)) {
                isValid = false;
                errorMessage = 'Please enter a valid phone number';
            }
            
            if (!isValid) {
                this.showFieldError(field, errorMessage);
            } else {
                this.clearFieldError(field);
            }
            
            return isValid;
        }
        
        isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }
        
        isValidPhone(phone) {
            const phoneRegex = /^[\+]?[1-9][\d]{0,15}$/;
            return phoneRegex.test(phone.replace(/[\s\-\(\)]/g, ''));
        }
        
        showFieldError(field, message) {
            this.clearFieldError(field);
            
            const errorElement = document.createElement('div');
            errorElement.className = 'field-error';
            errorElement.textContent = message;
            errorElement.style.color = '#dc3545';
            errorElement.style.fontSize = '12px';
            errorElement.style.marginTop = '5px';
            
            field.style.borderColor = '#dc3545';
            field.parentNode.appendChild(errorElement);
        }
        
        clearFieldError(field) {
            field.style.borderColor = '';
            const errorElement = field.parentNode.querySelector('.field-error');
            if (errorElement) {
                errorElement.remove();
            }
        }
        
        showTooltip(element, text) {
            const tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = text;
            tooltip.style.cssText = `
                position: absolute;
                background: #333;
                color: white;
                padding: 5px 10px;
                border-radius: 4px;
                font-size: 12px;
                white-space: nowrap;
                z-index: 1000;
                pointer-events: none;
            `;
            
            document.body.appendChild(tooltip);
            
            const rect = element.getBoundingClientRect();
            tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
            tooltip.style.top = rect.top - tooltip.offsetHeight - 5 + 'px';
            
            element._tooltip = tooltip;
        }
        
        hideTooltip(element) {
            if (element._tooltip) {
                element._tooltip.remove();
                delete element._tooltip;
            }
        }
        
        addToOrder(menuItem) {
            const itemId = menuItem.dataset.itemId;
            const itemName = menuItem.querySelector('.menu-item-name').textContent;
            const itemPrice = menuItem.querySelector('.menu-item-price').textContent;
            
            // Add to cart (localStorage or session storage)
            let cart = JSON.parse(localStorage.getItem('luna_dine_cart') || '[]');
            
            const existingItem = cart.find(item => item.id === itemId);
            if (existingItem) {
                existingItem.quantity += 1;
            } else {
                cart.push({
                    id: itemId,
                    name: itemName,
                    price: itemPrice,
                    quantity: 1
                });
            }
            
            localStorage.setItem('luna_dine_cart', JSON.stringify(cart));
            this.updateOrderSummary();
            this.showNotification('Item added to order!');
        }
        
        updateOrderSummary() {
            const cart = JSON.parse(localStorage.getItem('luna_dine_cart') || '[]');
            const orderItems = document.querySelector('#order-items');
            const orderTotal = document.querySelector('#order-total');
            
            if (orderItems) {
                orderItems.innerHTML = '';
                let total = 0;
                
                cart.forEach(item => {
                    const itemElement = document.createElement('div');
                    itemElement.className = 'order-item';
                    itemElement.innerHTML = `
                        <div class="order-item-info">
                            <div class="order-item-name">${item.name}</div>
                            <div class="order-item-details">Quantity: ${item.quantity}</div>
                        </div>
                        <div class="order-item-price">${item.price}</div>
                    `;
                    orderItems.appendChild(itemElement);
                    
                    // Calculate total (simplified - in real app, you'd parse price)
                    total += parseFloat(item.price.replace(/[^\d.]/g, '')) * item.quantity;
                });
                
                if (orderTotal) {
                    orderTotal.textContent = `৳${total.toFixed(2)}`;
                }
            }
        }
        
        showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.className = `alert alert-${type}`;
            notification.textContent = message;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 1000;
                min-width: 300px;
                animation: slideIn 0.3s ease-out;
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-out';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }
        
        showModal(modal) {
            modal.style.display = 'block';
            setTimeout(() => {
                modal.classList.add('active');
            }, 10);
        }
        
        hideModal(modal) {
            modal.classList.remove('active');
            setTimeout(() => {
                modal.style.display = 'none';
            }, 300);
        }
        
        updateQuantity(control) {
            const input = control.querySelector('input');
            const value = parseInt(input.value);
            
            // Trigger change event for any listeners
            input.dispatchEvent(new Event('change', { bubbles: true }));
        }
        
        handleResize() {
            // Handle responsive layout changes
            const mobileMenu = document.querySelector('.mobile-menu');
            if (mobileMenu && window.innerWidth > 768) {
                mobileMenu.classList.remove('active');
            }
        }
        
        handleScroll() {
            const header = document.querySelector('.header');
            if (header) {
                if (window.scrollY > 100) {
                    header.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
                } else {
                    header.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
                }
            }
        }
    }
    
    // Initialize theme when DOM is ready
    document.addEventListener('DOMContentLoaded', () => {
        window.defaultTheme = new DefaultTheme();
    });
    
    // Add CSS animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOut {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
            }
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
        }
        
        .modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
        }
        
        .modal-content {
            position: relative;
            background: white;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            z-index: 1001;
        }
        
        .modal-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #666;
        }
        
        .accordion-content {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
        }
        
        .accordion-header.active {
            background: #f8f9fa;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .tab-button.active {
            background: var(--primary-color);
            color: white;
        }
    `;
    document.head.appendChild(style);
})();