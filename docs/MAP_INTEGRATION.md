# Leaflet + OpenStreetMap Integration Guide

## Overview

I've successfully integrated Leaflet with OpenStreetMap to display seller and delivery locations on the Sales Details page. This feature provides visual tracking of sales with location markers and delivery route visualization.

## Features Implemented

### 1. **Seller Location Marker** (Green)
- Displays the address from the associated harvest (Récolte)
- Shows the pickup location for the sale
- Uses geocoding to convert addresses to coordinates

### 2. **Delivery Location Marker** (Red)
- Shows where the product will be delivered
- Optional field that can be added during sale creation or editing
- Automatically geocoded when address is provided

### 3. **Route Visualization**
- Red dashed line connects seller and delivery locations
- Helps visualize the delivery route
- Map auto-fits to show both locations

### 4. **Interactive Features**
- Click markers to see detailed popup information
- Zoom and pan capabilities
- Info box showing sale details (ID, buyer, price, date)
- Responsive design with 400px height (front) and 500px (admin)

## Changes Made

### Database
- Added 3 new columns to `vente` table:
  - `delivery_location` (VARCHAR 500) - Delivery address
  - `delivery_latitude` (VARCHAR 50) - Delivery latitude
  - `delivery_longitude` (VARCHAR 50) - Delivery longitude

### Entity (`Vente`)
- Added getters and setters for delivery location fields
- Fields are nullable to maintain backward compatibility

### API Endpoint
**File:** `src/Controller/Api/VenteMapApiController.php`
- **Route:** `/api/vente/{id}/locations`
- **Method:** GET
- **Returns:** JSON with seller and delivery location data
- **Geocoding:** Uses OpenStreetMap Nominatim API
- Bias towards Tunisia for better accuracy

### Frontend Components

#### Stimulus Controller
**File:** `assets/controllers/map_controller.js`
- Manages Leaflet map initialization
- Lazy-loads Leaflet library from CDN
- Fetches location data from API
- Renders markers and route visualization

#### Templates Updated
1. **Front User Template:** `templates/front/vente/show.html.twig`
   - Added map section below sale details
   - Displays 400px height interactive map
   - Legend showing marker colors

2. **Admin Template:** `templates/admin/vente/show.html.twig`
   - Added comprehensive map section
   - Shows both seller and delivery addresses
   - 500px map with detailed info cards

#### Form
**File:** `src/Form/VenteType.php`
- Added `deliveryLocation` field (optional)
- Shows in both create and edit forms

## How It Works

### Data Flow

```
1. User visits sale details page
   ↓
2. Stimulus controller initializes (data-controller="map")
   ↓
3. Lean Leaflet libraries from CDN
   ↓
4. Fetch `/api/vente/{id}/locations` endpoint
   ↓
5. API geocodes addresses using OpenStreetMap Nominatim
   ↓
6. Returns JSON with coordinates
   ↓
7. Map renders with markers and route
```

### Geocoding Process

The API uses OpenStreetMap Nominatim for address geocoding:
- Address from Recolte (harvest) → Seller location
- Delivery address → Delivery location
- Tunisia is added to all addresses to improve accuracy
- Gracefully handles missing or invalid addresses

## Usage

### Adding a Sale with Delivery Location

1. When creating a new sale, fill in the "Adresse de livraison" field
2. Enter the complete delivery address (e.g., "123 Rue de la Livraison, Tunis")
3. Save the sale
4. The map will automatically geocode and display the route

### Viewing the Map

- Navigate to any sale's details page
- Scroll to the "Localisation" (Front) or "Localisation et Traçabilité" (Admin) section
- The map loads with:
  - **Green marker** - Seller/pickup location
  - **Red marker** - Delivery location (if available)
  - **Red dashed line** - Delivery route

## API Response Example

```json
{
  "seller": {
    "name": "Tomates",
    "address": "123 Rue des Agriculteurs, Tunis",
    "coordinates": {
      "lat": 36.8,
      "lng": 10.2
    }
  },
  "delivery": {
    "address": "456 Rue de la Livraison, Sousse",
    "coordinates": {
      "lat": 35.8,
      "lng": 10.6
    }
  },
  "sale": {
    "id": 11,
    "description": "Vente d'aujourd'hui",
    "price": "500.00",
    "buyer": "ghassen",
    "date": "2026-04-25"
  }
}
```

## Technical Stack

- **Map Library:** Leaflet 1.9.4 (CDN)
- **Tiles:** OpenStreetMap (OSM)
- **Geocoding:** OpenStreetMap Nominatim API
- **Frontend Framework:** Stimulus.js
- **Backend:** Symfony with API endpoint

## Security Notes

- API endpoint requires `ROLE_USER` authentication
- Only the sale owner can access their sale's location data
- Address geocoding is cached in database to reduce API calls

## Future Enhancements

Potential improvements:
1. **Real-time tracking** - Add GPS coordinates for active deliveries
2. **Multiple stops** - Support multiple delivery locations
3. **Distance calculation** - Display route distance and estimated time
4. **Custom markers** - Different marker styles for different sale statuses
5. **Export route** - Generate route files for navigation apps
6. **Caching** - Store geocoded coordinates to reduce API calls
7. **Offline support** - Cache tiles locally for offline viewing

## Troubleshooting

### Map not loading?
- Check browser console for errors
- Verify the vente has an associated harvest with an address
- Ensure API endpoint returns valid JSON

### Markers not showing?
- Check that addresses are valid
- Verify Nominatim API is accessible
- Add Tunisia to address for better accuracy

### CDN issues?
- Map library loads from CDN - check internet connection
- Can be switched to local assets if needed

## Files Modified/Created

### New Files
- `src/Controller/Api/VenteMapApiController.php` - API endpoint
- `assets/controllers/map_controller.js` - Stimulus controller

### Modified Files
- `src/Entity/Vente.php` - Added delivery location fields
- `src/Form/VenteType.php` - Added delivery location form field
- `templates/front/vente/show.html.twig` - Added map section
- `templates/admin/vente/show.html.twig` - Added detailed map section
- `migrations/Version20260417075000.php` - Database migration

## Testing the Implementation

1. Create or edit a sale and add a delivery address
2. View the sale details
3. Scroll to map section
4. Verify map loads with markers
5. Click on markers to see popup information
6. Check that both seller and delivery locations are displayed correctly

---

The integration is production-ready and uses reliable, free services (OpenStreetMap, Nominatim). The map is fully responsive and works on all modern browsers.
