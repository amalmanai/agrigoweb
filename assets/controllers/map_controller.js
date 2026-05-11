import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['container'];
    static values = {
        mapUrl: String,
        venteId: Number,
    };

    connect() {
        console.log('🗺️ Map controller connected');
        console.log('Map URL:', this.getMapUrl());
        this.initializeMap();
    }

    getContainerElement() {
        return this.hasContainerTarget ? this.containerTarget : this.element;
    }

    getMapUrl() {
        // Primary (Stimulus value): data-map-map-url-value
        if (this.hasMapUrlValue && this.mapUrlValue) {
            return this.mapUrlValue;
        }

        // Backward compatibility: data-map-url-value
        const legacyUrl = this.element?.dataset?.mapUrlValue;
        if (legacyUrl) {
            return legacyUrl;
        }

        return '';
    }

    async initializeMap() {
        try {
            const mapUrl = this.getMapUrl();
            console.log('🗺️ Map initialization starting');
            console.log('Map URL:', mapUrl);
            
            if (!mapUrl) {
                throw new Error('Map API URL is missing on this page');
            }

            // Load Leaflet first
            console.log('Loading Leaflet CSS and JS...');
            this.loadLeafletCSS();
            await this.loadLeafletJS();

            if (typeof L === 'undefined') {
                throw new Error('Leaflet library failed to load');
            }

            console.log('✅ Leaflet loaded, fetching location data...');

            // Fetch location data from API with timeout
            let data = null;
            try {
                const controller = new AbortController();
                const timeoutId = setTimeout(() => {
                    console.warn('⏱️ API request timeout triggered after 5 seconds');
                    controller.abort();
                }, 5000);
                
                console.log('Sending API request to:', mapUrl);
                const response = await fetch(mapUrl, {
                    credentials: 'include',
                    headers: {
                        'Accept': 'application/json',
                    },
                    signal: controller.signal,
                });
                clearTimeout(timeoutId);
                console.log('✅ API response received with status:', response.status);

                if (!response.ok) {
                    const errorText = await response.text();
                    console.error(`API error ${response.status}:`, errorText);
                    throw new Error(`API error ${response.status}`);
                }

                const contentType = response.headers.get('content-type') || '';
                if (!contentType.includes('application/json')) {
                    console.error('Wrong content type:', contentType);
                    throw new Error(`Expected JSON but got: ${contentType}`);
                }

                data = await response.json();
                console.log('✅ Location data parsed:', data);

                if (!data.seller?.coordinates) {
                    throw new Error('No seller coordinates in API response');
                }

            } catch (fetchError) {
                console.error('❌ API fetch failed:', fetchError.message);
                // Try to use fallback data from page
                data = this.createFallbackData();
            }

            if (data) {
                console.log('Rendering map with data:', data);
                this.renderMap(data);
                console.log('✅ Map rendering complete');
            }

        } catch (error) {
            console.error('❌ Critical error initializing map:', error.message);
            console.error('Stack trace:', error.stack);
            this.displayError(error.message);
        }
    }

    createFallbackData() {
        // Try to extract basic info from page
        console.log('📍 Creating fallback map data...');
        
        // Default to Tunisia center
        return {
            seller: {
                name: 'Pickup Location',
                address: 'Unknown location',
                coordinates: { lat: 35.8, lng: 10.6 }
            },
            delivery: null,
            sale: { id: 'N/A', buyer: 'N/A', price: 'N/A', date: 'N/A' }
        };
    }

    loadLeafletCSS() {
        if (document.querySelector('link[href*="leaflet.css"]')) {
            return;
        }

        const link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css';
        document.head.appendChild(link);
        console.log('Leaflet CSS injected');
    }

    loadLeafletJS() {
        return new Promise((resolve, reject) => {
            if (typeof L !== 'undefined') {
                resolve();
                return;
            }

            const timeoutId = window.setTimeout(() => {
                reject(new Error('Leaflet timeout: impossible de charger la bibliotheque cartographique'));
            }, 8000);

            const script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js';
            script.onload = () => {
                window.clearTimeout(timeoutId);
                console.log('✅ Leaflet JS loaded');
                resolve();
            };
            script.onerror = () => {
                window.clearTimeout(timeoutId);
                reject(new Error('Failed to load Leaflet from CDN'));
            };
            document.head.appendChild(script);
        });
    }

    renderMap(data) {
        try {
            // Get container
            const container = this.getContainerElement();
            
            if (!container) {
                throw new Error('Map container not found');
            }

            console.log('🗺️ Container element:', container);
            console.log('Container dimensions:', {
                width: container.offsetWidth,
                height: container.offsetHeight,
                clientWidth: container.clientWidth,
                clientHeight: container.clientHeight
            });

            // Ensure container has dimensions
            if (container.offsetWidth === 0 || container.offsetHeight === 0) {
                console.warn('⚠️ Container has no dimensions, setting fallback styles');
                container.style.width = '100%';
                container.style.height = '400px';
            }

            // Clear container but preserve styles
            while (container.firstChild) {
                container.removeChild(container.firstChild);
            }

            console.log('Creating Leaflet map...');
            
            // Create map with explicit sizing
            const map = L.map(container, {
                center: [data.seller.coordinates.lat, data.seller.coordinates.lng],
                zoom: 13,
                scrollWheelZoom: true,
                attributionControl: true,
            });

            console.log('✅ Map created');

            // Add tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19,
            }).addTo(map);

            console.log('✅ Tiles added');

            // Create seller marker with emoji
            const sellerMarker = L.marker(
                [data.seller.coordinates.lat, data.seller.coordinates.lng],
                {
                    title: 'Pickup Location'
                }
            );

            sellerMarker.bindPopup(`
                <div style="font-size: 12px;">
                    <strong style="color: #319800;">🚜 Pickup Location</strong><br>
                    <strong>${data.seller.name}</strong><br>
                    📍 ${data.seller.address}
                </div>
            `);

            sellerMarker.addTo(map).openPopup();
            console.log('✅ Seller marker added');

            // Add delivery marker if available
            if (data.delivery?.coordinates) {
                const deliveryMarker = L.marker(
                    [data.delivery.coordinates.lat, data.delivery.coordinates.lng],
                    {
                        title: 'Delivery Location'
                    }
                );

                deliveryMarker.bindPopup(`
                    <div style="font-size: 12px;">
                        <strong style="color: #F57316;">📦 Delivery</strong><br>
                        📍 ${data.delivery.address}
                    </div>
                `);

                deliveryMarker.addTo(map);
                console.log('✅ Delivery marker added');

                // Draw route
                L.polyline(
                    [
                        [data.seller.coordinates.lat, data.seller.coordinates.lng],
                        [data.delivery.coordinates.lat, data.delivery.coordinates.lng],
                    ],
                    { 
                        color: '#FF6B6B', 
                        weight: 2, 
                        opacity: 0.6, 
                        dashArray: '5, 5'
                    }
                ).addTo(map);

                console.log('✅ Route line added');

                // Adjust bounds
                const featureGroup = L.featureGroup([sellerMarker, deliveryMarker]);
                map.fitBounds(featureGroup.getBounds().pad(0.1));
            }

            // Add info box
            this.addInfoBox(map, data);

            // Invalidate size
            setTimeout(() => map.invalidateSize(), 200);

        } catch (error) {
            console.error('Render error:', error);
            throw error;
        }
    }

    addInfoBox(map, data) {
        const info = L.control({ position: 'topright' });

        info.onAdd = () => {
            const div = L.DomUtil.create('div', 'leaflet-control leaflet-bar');
            div.style.backgroundColor = '#fff';
            div.style.padding = '10px';
            div.style.borderRadius = '4px';
            div.style.fontSize = '12px';
            div.style.boxShadow = '0 2px 4px rgba(0,0,0,0.2)';

            let html = `<strong>Sale #${data.sale.id}</strong><br>`;
            html += `Buyer: ${data.sale.buyer}<br>`;
            html += `Price: ${data.sale.price} €<br>`;
            html += `Date: ${data.sale.date}`;
            
            if (data.delivery?.address) {
                html += `<br><br><strong>Delivery:</strong><br>${data.delivery.address}`;
            }

            div.innerHTML = html;
            return div;
        };

        info.addTo(map);
    }

    displayError(message) {
        const container = this.getContainerElement();
        container.innerHTML = `
            <div style="display: flex; align-items: center; justify-content: center; height: 100%; background: #fff3cd; padding: 20px;">
                <div style="text-align: center;">
                    <div style="font-size: 40px; margin-bottom: 10px;">⚠️</div>
                    <p style="color: #dc3545; font-weight: bold; margin: 0; font-size: 14px;">${message}</p>
                    <p style="color: #6c757d; font-size: 11px; margin-top: 8px;">Open console (F12) for details</p>
                    <p style="color: #6c757d; font-size: 11px; margin-top: 8px;">Pickup and Delivery are shown below the map card.</p>
                </div>
            </div>
        `;
    }
}

// Add global error handler to catch any unhandled promise rejections
window.addEventListener('unhandledrejection', event => {
    console.error('Unhandled promise rejection:', event.reason);
});
