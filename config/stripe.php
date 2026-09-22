<?php
// ============================================
// config/stripe.php
// Stripe Payment Integration Helper
//
// SETUP:
//   1. Install Stripe PHP SDK:
//      composer require stripe/stripe-php
//
//   2. Set your real keys below
//      (or use environment variables)
//
//   3. In checkout.php replace the simulation
//      block with real Stripe code (see bottom)
// ============================================

define('STRIPE_PUBLIC_KEY',  'pk_test_YOUR_PUBLISHABLE_KEY_HERE');
define('STRIPE_SECRET_KEY',  'sk_test_YOUR_SECRET_KEY_HERE');
define('STRIPE_WEBHOOK_SECRET', 'whsec_YOUR_WEBHOOK_SECRET');

// ── REQUIRE STRIPE SDK ───────────────────────
// Uncomment after: composer require stripe/stripe-php
// require_once __DIR__ . '/../vendor/autoload.php';
// \Stripe\Stripe::setApiKey(STRIPE_SECRET_KEY);

// ── CREATE PAYMENT INTENT ────────────────────
/**
 * Create a Stripe PaymentIntent.
 *
 * @param  float  $amount    Amount in dollars (e.g. 49.99)
 * @param  string $currency  ISO currency code (default: usd)
 * @param  array  $metadata  Extra data to attach (orderId, userId…)
 * @return array  ['success'=>bool, 'client_secret'=>string|null, 'error'=>string|null]
 */
function createPaymentIntent(float $amount, string $currency = 'usd', array $metadata = []): array {
    // ── SIMULATION (remove in production) ────
    if (defined('STRIPE_SECRET_KEY') && str_starts_with(STRIPE_SECRET_KEY, 'sk_test_YOUR')) {
        return [
            'success'       => true,
            'payment_id'    => 'pi_sim_' . bin2hex(random_bytes(10)),
            'client_secret' => 'cs_sim_' . bin2hex(random_bytes(10)),
            'error'         => null,
        ];
    }

    // ── REAL STRIPE ──────────────────────────
    try {
        $intent = \Stripe\PaymentIntent::create([
            'amount'   => (int)round($amount * 100), // Stripe uses cents
            'currency' => $currency,
            'metadata' => $metadata,
            'automatic_payment_methods' => ['enabled' => true],
        ]);
        return [
            'success'       => true,
            'payment_id'    => $intent->id,
            'client_secret' => $intent->client_secret,
            'error'         => null,
        ];
    } catch (\Stripe\Exception\CardException $e) {
        return ['success'=>false, 'payment_id'=>null, 'client_secret'=>null, 'error'=>$e->getError()->message];
    } catch (\Exception $e) {
        return ['success'=>false, 'payment_id'=>null, 'client_secret'=>null, 'error'=>$e->getMessage()];
    }
}

// ── VERIFY WEBHOOK ───────────────────────────
/**
 * Verify a Stripe webhook and return the event.
 * Use in a dedicated webhook endpoint (e.g. /stripe/webhook.php).
 */
function verifyStripeWebhook(string $payload, string $sigHeader): ?\Stripe\Event {
    try {
        return \Stripe\Webhook::constructEvent($payload, $sigHeader, STRIPE_WEBHOOK_SECRET);
    } catch (\Exception $e) {
        return null;
    }
}

/*
// ── HOW TO USE IN checkout.php ───────────────
//
// Replace the simulation block with:
//
//   require_once 'config/stripe.php';
//
//   $result = createPaymentIntent(
//       $total,
//       'usd',
//       ['order_code'=>$orderCode, 'user_id'=>$userId]
//   );
//
//   if ($result['success']) {
//       $paymentIntentId = $result['payment_id'];
//       // ... save order to DB
//   } else {
//       $error = 'Payment failed: ' . $result['error'];
//   }
//
// ── WEBHOOK ENDPOINT (stripe/webhook.php) ────
//
//   $payload   = file_get_contents('php://input');
//   $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'];
//   $event     = verifyStripeWebhook($payload, $sigHeader);
//
//   if ($event && $event->type === 'payment_intent.succeeded') {
//       $pi = $event->data->object;
//       // Mark order as paid using $pi->metadata->order_code
//   }
*/
?>
