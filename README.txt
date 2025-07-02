# Fuel Calculator Module

This module provides a configurable fuel cost calculator as a page, block, and REST API for Drupal 10/11.

## Features
- Configurable default values for distance, fuel consumption, and fuel price
- Calculator available as a standalone page and as a block
- REST API for programmatic access
- All calculations are logged (IP, username, input, result)
- Accessible, keyboard-friendly form

## Calculator Usage

### Calculator Page
- URL: `/fuel-calculator`
- Use this page to access the calculator as a standalone form.

### Block
- Place the "Fuel Calculator" block in any region via Structure > Block layout.
- The block is typically hidden on `/fuel-calculator` to avoid duplication.

### Configuration
- Set default values at `/admin/config/fuel-calculator`.
- These values prefill the form for all users.

### Prefill via URL
- You can prefill the calculator form by adding parameters to the URL, e.g.:
  `/fuel-calculator?distance=250&fuel_consumption=6.5&fuel_price=1.49`

### Accessibility Note
The calculator uses number input spinner controls (steppers) for numeric fields to improve accessibility and allow keyboard or mouse input. For visual centering and to match the design, the spinner controls are hidden with CSS, but the input type remains 'number' for accessibility and validation.

## Logging
All calculations (form and REST API) are logged in Drupal (admin/reports/dblog) with IP address, username, input values, and result values.



# Fuel Calculator REST API

The Fuel Calculator module provides a secure REST API endpoint for programmatic fuel cost and consumption calculations. This allows integration with external systems, custom frontends, or automated scripts.

## API Endpoint
- URL: `/api/fuel-calculate`
- Method: `POST`
- Content-Type: `application/json`
- Authentication: Cookie-based (user must be logged in)
- CSRF Protection: `X-CSRF-Token` header required

## Request Example
```json
{
  "distance": 250,
  "fuel_consumption": 6.5,
  "fuel_price": 1.49
}
```

## Response Example
```json
{
  "fuel_spent": "16,3",
  "fuel_cost": "24,2"
}
```
Values use a comma as the decimal separator (locale-aware).

## Validation Rules
- All fields required and numeric
- `distance`: 0.1 - 10000
- `fuel_consumption`: 0.1 - 100
- `fuel_price`: 0.01 - 10

## Authentication & CSRF Token
1. Log in to your Drupal site in a browser or via Postman.
2. Obtain a CSRF token by sending a GET request to `/session/token` while authenticated.
3. Include the token in the `X-CSRF-Token` header for your POST request.
4. Ensure your session cookie is included (handled automatically if authenticated in Postman or browser).

## Testing the REST API

### Using Postman
- Set method to `POST` and URL to your API endpoint.
- Add headers:
  - `Content-Type: application/json`
  - `X-CSRF-Token: [your token]`
- In the Body tab, select `raw` and `JSON`, then enter your input JSON.
- Ensure you are authenticated (import cookies if needed).

### Using Browser Developer Tools
- Log in to your Drupal site in your browser.
- Open DevTools, go to the Console tab.
- Paste and run:
```javascript
fetch('/session/token')
  .then(response => response.text())
  .then(token => {
    return fetch('/api/fuel-calculate', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token
      },
      body: JSON.stringify({
        distance: 250,
        fuel_consumption: 6.5,
        fuel_price: 1.49
      })
    });
  })
  .then(response => response.json())
  .then(data => {
    console.log('API Response:', data);
  })
  .catch(error => {
    console.error('Error:', error);
  });
```
You can also watch the request in the Network tab. Change the numbers in the JSON to test different values.
