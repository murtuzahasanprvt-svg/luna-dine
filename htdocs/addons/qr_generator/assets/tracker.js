/**
 * QR Generator Analytics Tracker
 * 
 * Tracks QR code scans and user interactions
 */

(function() {
    'use strict';
    
    // Check if user came from QR scan
    function checkQRSource() {
        const urlParams = new URLSearchParams(window.location.search);
        const qrCode = urlParams.get('qr');
        const branchId = urlParams.get('branch');
        const tableId = urlParams.get('table');
        
        if (qrCode) {
            // Track the scan
            trackQRScan(qrCode, branchId, tableId);
        }
    }
    
    // Track QR scan
    function trackQRScan(qrCode, branchId, tableId) {
        const data = {
            qr_code: qrCode,
            branch_id: branchId,
            table_id: tableId,
            user_agent: navigator.userAgent,
            screen_resolution: screen.width + 'x' + screen.height,
            timestamp: new Date().toISOString()
        };
        
        // Send tracking data to server
        fetch('/api/qr/track', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            console.log('QR scan tracked:', data);
        })
        .catch(error => {
            console.error('QR tracking error:', error);
        });
    }
    
    // Track user interactions
    function trackInteraction(eventType, eventData) {
        const data = {
            event_type: eventType,
            event_data: eventData,
            url: window.location.href,
            timestamp: new Date().toISOString()
        };
        
        // Send interaction data to server
        fetch('/api/qr/interaction', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
        .catch(error => {
            console.error('Interaction tracking error:', error);
        });
    }
    
    // Initialize tracker
    document.addEventListener('DOMContentLoaded', function() {
        checkQRSource();
        
        // Track menu item views
        document.addEventListener('click', function(e) {
            if (e.target.closest('.menu-item')) {
                const menuItem = e.target.closest('.menu-item');
                const itemId = menuItem.dataset.itemId;
                trackInteraction('menu_item_click', { item_id: itemId });
            }
        });
        
        // Track order button clicks
        document.addEventListener('click', function(e) {
            if (e.target.closest('.order-button')) {
                trackInteraction('order_button_click', {});
            }
        });
    });
    
    // Make tracker available globally
    window.QRTracker = {
        trackScan: trackQRScan,
        trackInteraction: trackInteraction
    };
})();