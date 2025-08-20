/**
 * Push Notifications System
 * 
 * Handles push notification registration and display
 */

(function() {
    'use strict';
    
    class PushNotifications {
        constructor() {
            this.isSupported = 'Notification' in window;
            this.isSubscribed = false;
            this.registration = null;
            this.serviceWorker = null;
            
            if (this.isSupported) {
                this.init();
            }
        }
        
        async init() {
            try {
                // Register service worker
                this.registration = await navigator.serviceWorker.register('/addons/notifications/service-worker.js');
                this.serviceWorker = this.registration.active;
                
                // Request notification permission
                await this.requestPermission();
                
                // Subscribe to push notifications
                await this.subscribe();
                
                // Listen for messages
                this.setupMessageListener();
                
            } catch (error) {
                console.error('Push notification initialization failed:', error);
            }
        }
        
        async requestPermission() {
            if (Notification.permission === 'default') {
                const permission = await Notification.requestPermission();
                return permission === 'granted';
            }
            return Notification.permission === 'granted';
        }
        
        async subscribe() {
            try {
                const subscription = await this.registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: this.urlBase64ToUint8Array(
                        'YOUR_VAPID_PUBLIC_KEY' // Replace with actual VAPID key
                    )
                });
                
                // Send subscription to server
                await this.sendSubscriptionToServer(subscription);
                this.isSubscribed = true;
                
            } catch (error) {
                console.error('Push subscription failed:', error);
            }
        }
        
        async sendSubscriptionToServer(subscription) {
            try {
                const response = await fetch('/api/notifications/subscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        endpoint: subscription.endpoint,
                        keys: {
                            p256dh: subscription.toJSON().keys.p256dh,
                            auth: subscription.toJSON().keys.auth
                        }
                    })
                });
                
                if (!response.ok) {
                    throw new Error('Failed to send subscription to server');
                }
            } catch (error) {
                console.error('Subscription server error:', error);
            }
        }
        
        setupMessageListener() {
            navigator.serviceWorker.addEventListener('message', (event) => {
                const data = event.data;
                
                if (data.type === 'notification') {
                    this.showNotification(data.title, data.options);
                }
            });
        }
        
        showNotification(title, options = {}) {
            if (Notification.permission === 'granted') {
                const notification = new Notification(title, {
                    body: options.body,
                    icon: options.icon || '/assets/img/logo.png',
                    badge: options.badge || '/assets/img/badge.png',
                    tag: options.tag || 'default',
                    data: options.data || {},
                    requireInteraction: options.requireInteraction || false,
                    silent: options.silent || false
                });
                
                notification.onclick = (event) => {
                    event.preventDefault();
                    if (options.clickUrl) {
                        window.open(options.clickUrl, '_blank');
                    }
                    notification.close();
                };
                
                // Play sound if enabled
                if (options.sound && !options.silent) {
                    this.playNotificationSound();
                }
            }
        }
        
        playNotificationSound() {
            const audio = new Audio('/addons/notifications/assets/notification.mp3');
            audio.play().catch(error => {
                console.log('Sound play failed:', error);
            });
        }
        
        urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding)
                .replace(/-/g, '+')
                .replace(/_/g, '/');
            
            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);
            
            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            
            return outputArray;
        }
        
        async unsubscribe() {
            try {
                const subscription = await this.registration.pushManager.getSubscription();
                if (subscription) {
                    await subscription.unsubscribe();
                    await this.sendUnsubscriptionToServer(subscription);
                    this.isSubscribed = false;
                }
            } catch (error) {
                console.error('Unsubscription failed:', error);
            }
        }
        
        async sendUnsubscriptionToServer(subscription) {
            try {
                await fetch('/api/notifications/unsubscribe', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        endpoint: subscription.endpoint
                    })
                });
            } catch (error) {
                console.error('Unsubscription server error:', error);
            }
        }
    }
    
    // Initialize push notifications when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        window.pushNotifications = new PushNotifications();
    });
    
    // Make available globally
    window.PushNotifications = PushNotifications;
})();