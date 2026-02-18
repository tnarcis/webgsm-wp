<?php
/**
 * Trimite webhook către WebGSM la schimbare status comandă.
 * Payload conform SPEC-plugin-woocommerce-webgsm (order.status_changed).
 */

namespace WebGSM_Woo_Sync;

if (!defined('ABSPATH')) exit;

class Webhook_Sender {

    const OPTION_URL = 'webgsm_woo_sync_endpoint_url';
    const OPTION_SECRET = 'webgsm_woo_sync_secret';
    const OPTION_STATUS_COMPLETED = 'webgsm_woo_sync_status_completed';
    const OPTION_STATUS_CANCELLED = 'webgsm_woo_sync_status_cancelled';
    const OPTION_STATUS_REFUNDED = 'webgsm_woo_sync_status_refunded';
    const OPTION_LOG = 'webgsm_woo_sync_log_requests';
    const TIMEOUT = 15;
    const RETRY_DELAYS = [5, 15];

    /** @var self */
    private static $instance;

    public static function instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('woocommerce_order_status_changed', [$this, 'on_order_status_changed'], 10, 4);
    }

    /**
     * @param int $order_id
     * @param string $old_status
     * @param string $new_status
     * @param \WC_Order $order
     */
    public function on_order_status_changed($order_id, $old_status, $new_status, $order) {
        try {
            $url = get_option(self::OPTION_URL, '');
            $secret = get_option(self::OPTION_SECRET, '');
            if (empty($url) || empty($secret)) {
                $this->log('WebGSM Woo Sync: URL sau Secret lipsă în setări. Webhook netrimis.');
                return;
            }

            $send = false;
            if ($new_status === 'completed' && get_option(self::OPTION_STATUS_COMPLETED, 1)) {
                $send = true;
            }
            if ($new_status === 'cancelled' && get_option(self::OPTION_STATUS_CANCELLED, 1)) {
                $send = true;
            }
            if ($new_status === 'refunded' && get_option(self::OPTION_STATUS_REFUNDED, 1)) {
                $send = true;
            }
            if (!$send) {
                return;
            }

            $payload = $this->build_order_payload($order_id, $old_status, $new_status, $order);
            if (!$payload) {
                return;
            }

            $body = wp_json_encode($payload);
            $signature = $this->hmac_signature($body, $secret);

            $this->send_request($url, $body, $signature, $order_id, $new_status);
        } catch (\Throwable $e) {
            $this->log(sprintf('WebGSM Woo Sync: eroare order_id=%s - %s', $order_id, $e->getMessage()));
            error_log(sprintf('WebGSM Woo Sync: %s in %s:%d', $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    /**
     * @param int $order_id
     * @param string $old_status
     * @param string $new_status
     * @param \WC_Order $order
     * @return array|null
     */
    private function build_order_payload($order_id, $old_status, $new_status, $order) {
        if (!$order || !is_a($order, 'WC_Order')) {
            $order = wc_get_order($order_id);
        }
        if (!$order) {
            return null;
        }

        $billing_cui = $order->get_meta('_billing_cif');
        if (empty($billing_cui)) {
            $billing_cui = '';
        }

        $line_items = [];
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            $product_id = $product ? $product->get_id() : 0;
            $sku = $product ? $product->get_sku() : '';
            if (empty($sku) && $product) {
                $sku = 'PROD-' . $product->get_id();
            }
            $ean = '';
            if ($product) {
                $ean = $product->get_meta('gtin_ean');
                if (empty($ean)) {
                    $ean = get_post_meta($product_id, 'gtin_ean', true);
                }
            }
            $line_items[] = [
                'product_id' => $product_id,
                'sku' => $sku,
                'ean' => (string) $ean,
                'name' => $item->get_name(),
                'quantity' => (int) $item->get_quantity(),
                'price' => $order->get_item_total($item, false, false) ? wc_format_decimal($order->get_item_total($item, false, false), 2) : '0.00',
                'total' => $item->get_total() ? wc_format_decimal($item->get_total(), 2) : '0.00',
            ];
        }

        $date_created = $order->get_date_created();
        $date_created_str = $date_created ? $date_created->format('Y-m-d H:i:s') : '';

        $payload = [
            'event' => 'order.status_changed',
            'timestamp' => gmdate('Y-m-d\TH:i:s\Z'),
            'order_id' => (int) $order_id,
            'order_number' => $order->get_order_number(),
            'status_old' => $old_status,
            'status_new' => $new_status,
            'order' => [
                'id' => (int) $order_id,
                'billing' => [
                    'first_name' => $order->get_billing_first_name(),
                    'last_name' => $order->get_billing_last_name(),
                    'company' => $order->get_billing_company(),
                    'address_1' => $order->get_billing_address_1(),
                    'address_2' => $order->get_billing_address_2(),
                    'city' => $order->get_billing_city(),
                    'postcode' => $order->get_billing_postcode(),
                    'country' => $order->get_billing_country(),
                    'state' => $order->get_billing_state(),
                    'email' => $order->get_billing_email(),
                    'phone' => $order->get_billing_phone(),
                    'cui' => $billing_cui,
                ],
                'line_items' => $line_items,
                'total' => wc_format_decimal($order->get_total(), 2),
                'currency' => $order->get_currency(),
                'date_created' => $date_created_str,
                'payment_method' => $order->get_payment_method(),
                'payment_method_title' => $order->get_payment_method_title(),
            ],
        ];

        return $payload;
    }

    private function hmac_signature($body, $secret) {
        return hash_hmac('sha256', $body, $secret);
    }

    /**
     * Trimite request cu retry (backoff 5s, 15s). Nu aruncă excepții — doar loghează; nu blochează WooCommerce.
     */
    private function send_request($url, $body, $signature, $order_id, $status_new) {
        try {
            $log = get_option(self::OPTION_LOG, 0);
            if ($log) {
                $this->log(sprintf('WebGSM Woo Sync: trimitere order_id=%s status_new=%s', $order_id, $status_new));
            }

            $args = [
                'method' => 'POST',
                'timeout' => self::TIMEOUT,
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-WebGSM-Signature' => $signature,
                ],
                'body' => $body,
            ];

            $attempt = 0;
            $delays = [0] + self::RETRY_DELAYS;
            $last_code = 0;

            foreach ($delays as $delay) {
                if ($delay > 0) {
                    sleep($delay);
                }
                $attempt++;
                $response = wp_remote_post($url, $args);

                if (is_wp_error($response)) {
                    $this->log(sprintf('WebGSM Woo Sync: order_id=%s attempt=%d WP_Error - %s', $order_id, $attempt, $response->get_error_message()));
                    error_log(sprintf('WebGSM Woo Sync: order_id=%s %s', $order_id, $response->get_error_message()));
                    continue;
                }

                $code = wp_remote_retrieve_response_code($response);
                $last_code = $code;

                if ($code >= 200 && $code < 300) {
                    if ($log) {
                        $this->log(sprintf('WebGSM Woo Sync: succes order_id=%s (attempt %d)', $order_id, $attempt));
                    }
                    return;
                }

                $body_res = wp_remote_retrieve_body($response);
                $this->log(sprintf(
                    'WebGSM Woo Sync: order_id=%s attempt=%d HTTP %s - %s',
                    $order_id,
                    $attempt,
                    $code,
                    wp_remote_retrieve_response_message($response)
                ));
                if (!empty($body_res)) {
                    $this->log('WebGSM Woo Sync response body: ' . substr($body_res, 0, 500));
                }
            }

            if ($last_code >= 400 && $last_code < 600) {
                error_log(sprintf('WebGSM Woo Sync: eșec final order_id=%s HTTP %s', $order_id, $last_code));
            }
        } catch (\Throwable $e) {
            $this->log(sprintf('WebGSM Woo Sync: send_request eroare order_id=%s - %s', $order_id, $e->getMessage()));
            error_log(sprintf('WebGSM Woo Sync send_request: %s in %s:%d', $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    private function log($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($message);
        }
    }
}
