<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>API Documentation — PepeCC Shop</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@300;400;500;600;700&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<style>
  *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
  :root{
    --bg:#0a0a0f;--bg2:#12121a;--bg3:#1a1a2e;--bg4:#22223a;
    --fg:#e0e0e0;--fg2:#999;--green:#00c853;--red:#ff4444;--yellow:#ffd600;
    --blue:#448aff;--purple:#b388ff;
    --mono:'Space Mono',monospace;--sans:'Space Grotesk',sans-serif;
  }
  html{scroll-behavior:smooth}
  body{background:var(--bg);color:var(--fg);font-family:var(--sans);line-height:1.6;padding:0}
  a{color:var(--green);text-decoration:none}
  a:hover{text-decoration:underline}

  .topbar{background:var(--bg2);border-bottom:1px solid rgba(255,255,255,.06);padding:16px 32px;display:flex;align-items:center;gap:16px;position:sticky;top:0;z-index:100;backdrop-filter:blur(12px)}
  .topbar .logo{font-family:var(--mono);font-size:20px;font-weight:700;color:var(--green)}
  .topbar nav{margin-left:auto;display:flex;gap:20px;font-size:14px}
  .topbar nav a{color:var(--fg2)}
  .topbar nav a:hover{color:var(--green);text-decoration:none}

  .container{max-width:960px;margin:0 auto;padding:40px 24px}
  h1{font-size:36px;font-weight:700;margin-bottom:8px}
  h1 span{color:var(--green)}
  .subtitle{color:var(--fg2);font-size:16px;margin-bottom:40px}

  .auth-box{background:var(--bg2);border:1px solid rgba(0,200,83,.2);border-radius:12px;padding:20px 24px;margin-bottom:32px}
  .auth-box h3{font-size:15px;color:var(--green);margin-bottom:8px;font-family:var(--mono)}
  .auth-box code{font-family:var(--mono);font-size:13px;background:var(--bg3);padding:3px 8px;border-radius:4px;color:var(--yellow)}
  .auth-box p{font-size:14px;color:var(--fg2)}

  .endpoint{background:var(--bg2);border:1px solid rgba(255,255,255,.06);border-radius:12px;padding:28px;margin-bottom:28px}
  .endpoint-header{display:flex;align-items:center;gap:12px;margin-bottom:12px}
  .method{font-family:var(--mono);font-size:13px;font-weight:700;padding:4px 12px;border-radius:6px;letter-spacing:.5px}
  .method.get{background:rgba(0,200,83,.15);color:var(--green)}
  .method.post{background:rgba(68,138,255,.15);color:var(--blue)}
  .endpoint-header h2{font-size:18px;font-weight:600}
  .endpoint-header .path{font-family:var(--mono);font-size:14px;color:var(--fg2)}
  .endpoint>p{font-size:14px;color:var(--fg2);margin-bottom:16px}

  .params{margin-bottom:16px}
  .params h4{font-size:13px;color:var(--fg2);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px}
  table{width:100%;border-collapse:collapse;font-size:13px}
  th{text-align:left;padding:8px 12px;color:var(--fg2);border-bottom:1px solid rgba(255,255,255,.06);font-weight:500}
  td{padding:8px 12px;border-bottom:1px solid rgba(255,255,255,.04)}
  td code{font-family:var(--mono);font-size:12px;background:var(--bg3);padding:2px 6px;border-radius:3px}
  td .req{color:var(--red);font-size:11px;font-weight:600}
  td .opt{color:var(--fg2);font-size:11px}

  .code-block{background:var(--bg3);border:1px solid rgba(255,255,255,.06);border-radius:8px;padding:16px 20px;margin-bottom:12px;overflow-x:auto}
  .code-block pre{font-family:var(--mono);font-size:12px;line-height:1.5;color:var(--fg);white-space:pre}
  .code-label{font-family:var(--mono);font-size:11px;color:var(--fg2);text-transform:uppercase;letter-spacing:1px;margin-bottom:6px}

  .error-table td:first-child{font-family:var(--mono);font-weight:600}

  footer{text-align:center;padding:40px 24px;color:var(--fg2);font-size:13px;border-top:1px solid rgba(255,255,255,.06);margin-top:40px}
  footer a{color:var(--green)}
</style>
</head>
<body>

<div class="topbar">
  <a class="logo" href="/">🐸 PepeCC</a>
  <nav>
    <a href="/shop.php">Shop</a>
    <a href="/api/">API Docs</a>
    <a href="/orders.php">Orders</a>
  </nav>
</div>

<div class="container">
  <h1>🐸 PepeCC <span>API</span></h1>
  <p class="subtitle">RESTful API for integrating with the PepeCC Shop card marketplace.</p>

  <div class="auth-box">
    <h3>🔐 Authentication</h3>
    <p>All API requests require a <code>Bearer</code> token in the <code>Authorization</code> header. Generate tokens from your <a href="/user/balance.php">profile page</a>.</p>
    <p style="margin-top:8px">Header format: <code>Authorization: Bearer YOUR_API_TOKEN</code></p>
  </div>

  <!-- GET /api/cards -->
  <div class="endpoint">
    <div class="endpoint-header">
      <span class="method get">GET</span>
      <h2>List Cards</h2>
      <span class="path">/api/cards</span>
    </div>
    <p>Returns a paginated list of available cards. Card numbers and CVVs are masked in the response.</p>

    <div class="params">
      <h4>Query Parameters</h4>
      <table>
        <tr><th>Parameter</th><th>Type</th><th>Description</th></tr>
        <tr><td><code>brand</code></td><td>string</td><td>Filter by brand: <code>visa</code>, <code>mastercard</code>, <code>amex</code>, <code>discover</code>, <code>jcb</code>, <code>diners</code></td></tr>
        <tr><td><code>country</code></td><td>string</td><td>Filter by country name (e.g. <code>United States</code>)</td></tr>
        <tr><td><code>type</code></td><td>string</td><td><code>credit</code> or <code>debit</code></td></tr>
        <tr><td><code>min_price</code></td><td>float</td><td>Minimum price filter</td></tr>
        <tr><td><code>max_price</code></td><td>float</td><td>Maximum price filter</td></tr>
        <tr><td><code>bin</code></td><td>string</td><td>BIN range search (partial match)</td></tr>
        <tr><td><code>page</code></td><td>int</td><td>Page number (default: <code>1</code>)</td></tr>
        <tr><td><code>limit</code></td><td>int</td><td>Results per page (default: <code>20</code>, max: <code>100</code>)</td></tr>
      </table>
    </div>

    <div class="code-label">Example Request</div>
    <div class="code-block"><pre>curl -X GET "https://yourdomain.com/api/cards?brand=visa&min_price=50&limit=5" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"</pre></div>

    <div class="code-label">Response (200 OK)</div>
    <div class="code-block"><pre>{
  "success": true,
  "data": {
    "cards": [
      {
        "id": 1,
        "price": 149.99,
        "brand": "visa",
        "bin": "424242",
        "expiry": "12/26",
        "country": "United States",
        "bank_name": "JPMorgan Chase Bank",
        "balance_range": "$5K-$10K",
        "vbv": "vbv",
        "card_status": "live",
        "card_type": "credit"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 5,
      "total": 12,
      "total_pages": 3
    }
  }
}</pre></div>
  </div>

  <!-- GET /api/cards/{id} -->
  <div class="endpoint">
    <div class="endpoint-header">
      <span class="method get">GET</span>
      <h2>Get Card Details</h2>
      <span class="path">/api/cards/{id}</span>
    </div>
    <p>Returns full details for a single card. Card number and CVV remain masked — use the buy endpoint to get full details after purchase.</p>

    <div class="code-label">Example Request</div>
    <div class="code-block"><pre>curl -X GET "https://yourdomain.com/api/cards/1" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"</pre></div>

    <div class="code-label">Response (200 OK)</div>
    <div class="code-block"><pre>{
  "success": true,
  "data": {
    "id": 1,
    "price": 149.99,
    "brand": "visa",
    "bin": "424242",
    "expiry": "12/26",
    "country": "United States",
    "bank_name": "JPMorgan Chase Bank",
    "balance_range": "$5K-$10K",
    "vbv": "vbv",
    "card_status": "live",
    "card_type": "credit",
    "card_name": "JOHN DOE",
    "card_address": "123 Main Street, New York, NY, 10001",
    "stock": 5
  }
}</pre></div>
  </div>

  <!-- POST /api/buy -->
  <div class="endpoint">
    <div class="endpoint-header">
      <span class="method post">POST</span>
      <h2>Buy Card</h2>
      <span class="path">/api/buy</span>
    </div>
    <p>Purchases a card using your wallet balance. Returns the full card details including unmasked card number and CVV.</p>

    <div class="params">
      <h4>Request Body (JSON)</h4>
      <table>
        <tr><th>Field</th><th>Type</th><th>Required</th><th>Description</th></tr>
        <tr><td><code>card_id</code></td><td>int</td><td><span class="req">required</span></td><td>The product ID of the card to purchase</td></tr>
      </table>
    </div>

    <div class="code-label">Example Request</div>
    <div class="code-block"><pre>curl -X POST "https://yourdomain.com/api/buy" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -H "Content-Type: application/json" \
  -d '{"card_id": 1}'</pre></div>

    <div class="code-label">Response (200 OK)</div>
    <div class="code-block"><pre>{
  "success": true,
  "data": {
    "order_code": "ORD-A1B2C3D4",
    "card": {
      "id": 1,
      "card_number": "4242424242424242",
      "card_cvv": "123",
      "card_expiry": "12/26",
      "card_name": "JOHN DOE",
      "card_bin": "424242",
      "card_type": "credit",
      "card_brand": "visa",
      "card_email": "john@email.com",
      "card_phone": "+15551234567",
      "card_address": "123 Main Street, New York, NY, 10001",
      "card_country": "United States",
      "bank_name": "JPMorgan Chase Bank"
    },
    "price_paid": 149.99,
    "new_balance": 350.01
  }
}</pre></div>

    <div class="code-label">Error Responses</div>
    <div class="code-block"><pre>{
  "success": false,
  "error": "Card not available or out of stock"
}

{
  "success": false,
  "error": "Insufficient balance"
}

{
  "success": false,
  "error": "Invalid card ID"
}</pre></div>
  </div>

  <!-- GET /api/balance -->
  <div class="endpoint">
    <div class="endpoint-header">
      <span class="method get">GET</span>
      <h2>Check Balance</h2>
      <span class="path">/api/balance</span>
    </div>
    <p>Returns your current wallet balance, total amount spent, and VIP level information.</p>

    <div class="code-label">Example Request</div>
    <div class="code-block"><pre>curl -X GET "https://yourdomain.com/api/balance" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"</pre></div>

    <div class="code-label">Response (200 OK)</div>
    <div class="code-block"><pre>{
  "success": true,
  "data": {
    "balance": 500.00,
    "total_spent": 1299.94,
    "vip_level": {
      "name": "Silver",
      "discount_percent": 3,
      "badge_color": "#c0c0c0",
      "badge_icon": "silver"
    }
  }
}</pre></div>
  </div>

  <!-- Error Codes -->
  <div class="endpoint">
    <div class="endpoint-header">
      <span class="method post" style="background:rgba(255,68,68,.15);color:var(--red)">ERR</span>
      <h2>Error Codes</h2>
    </div>
    <table class="error-table">
      <tr><th>Code</th><th>HTTP Status</th><th>Description</th></tr>
      <tr><td><code>401</code></td><td>Unauthorized</td><td>Missing or invalid API token</td></tr>
      <tr><td><code>400</code></td><td>Bad Request</td><td>Missing required parameters</td></tr>
      <tr><td><code>404</code></td><td>Not Found</td><td>Resource not found (e.g., card ID)</td></tr>
      <tr><td><code>409</code></td><td>Conflict</td><td>Card out of stock or already purchased</td></tr>
      <tr><td><code>402</code></td><td>Payment Required</td><td>Insufficient wallet balance</td></tr>
      <tr><td><code>405</code></td><td>Method Not Allowed</td><td>Wrong HTTP method for endpoint</td></tr>
      <tr><td><code>500</code></td><td>Server Error</td><td>Internal server error</td></tr>
    </table>
  </div>

</div>

<footer>
  <p>🐸 PepeCC Shop API v1 &mdash; <a href="/api/index.php">Documentation</a></p>
</footer>

</body>
</html>
