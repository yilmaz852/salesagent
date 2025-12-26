<?php
/**
 * Plugin Name: WooCommerce Sales Dashboard
 * Plugin URI: https://github.com/yilmaz852/salesagent
 * Description: A comprehensive sales dashboard for WooCommerce that displays sales reports including subtotal calculations, refunds, net values, commissions, visualizations, and exclusions.
 * Version: 1.0.0
 * Author: yilmaz852
 * Author URI: https://github.com/yilmaz852
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Text Domain: woo-sales-dashboard
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'woo_sales_dashboard_woocommerce_missing_notice');
    return;
}

function woo_sales_dashboard_woocommerce_missing_notice() {
    echo '<div class="error"><p><strong>WooCommerce Sales Dashboard</strong> requires WooCommerce to be installed and active.</p></div>';
}

// Add menu to admin
add_action('admin_menu', 'woo_sales_dashboard_menu');

function woo_sales_dashboard_menu() {
    add_menu_page(
        'Sales Dashboard',
        'Sales Dashboard',
        'manage_woocommerce',
        'sales-dashboard',
        'woo_sales_dashboard_callback',
        'dashicons-chart-bar',
        33
    );
}

function woo_sales_dashboard_callback() {
    echo '<div class="wrap">';
    echo '<h1>Sales Dashboard</h1>';
    echo '<p>View sales report including subtotal calculations, refunds, net values, commissions, visualizations, and exclusions.</p>';
    echo woo_create_sales_form();
    echo '</div>';
}

function woo_create_sales_form() {
    $default_start_date = date('Y-m-01');
    $default_end_date = date('Y-m-d');

    // Seçimlerin korunması için gönderim sonrası değerler alınır
    $selected_statuses = !empty($_POST['exclude_statuses']) ? $_POST['exclude_statuses'] : [];

    $output = '<form method="post">';
    
    // Add nonce field for security
    $output .= wp_nonce_field('woo_sales_dashboard_action', 'woo_sales_dashboard_nonce', true, false);
    
    $output .= '<label for="sales_agent_id">Select Sales Agent:</label><br />';

    $users = woo_get_sales_agents();
    if (!$users) {
        $output .= '<p style="color: red;">No users found with the role "sales_agent".</p>';
        return $output;
    }

    // Dropdown menü
    $selected_agent = !empty($_POST['sales_agent_id']) ? intval($_POST['sales_agent_id']) : '';
    $output .= '<select name="sales_agent_id" id="sales_agent_id">';
    $output .= '<option value="">All Sales Agents</option>';
    foreach ($users as $user) {
        $selected = ($selected_agent === $user->ID) ? 'selected' : '';
        $output .= '<option value="' . esc_attr($user->ID) . '" ' . $selected . '>' . esc_html($user->display_name . ' (' . $user->user_email . ')') . '</option>';
    }
    $output .= '</select><br /><br />';

    $output .= '<label for="start_date">Select Start Date:</label><br />';
    $output .= '<input type="date" id="start_date" name="start_date" value="' . esc_attr(isset($_POST['start_date']) ? $_POST['start_date'] : $default_start_date) . '"><br /><br />';
    $output .= '<label for="end_date">Select End Date:</label><br />';
    $output .= '<input type="date" id="end_date" name="end_date" value="' . esc_attr(isset($_POST['end_date']) ? $_POST['end_date'] : $default_end_date) . '"><br /><br />';

    // Checkbox durumları
    $order_statuses = wc_get_order_statuses();
    $output .= '<label for="exclude_statuses">Exclude Order Statuses:</label><br />';
    $output .= '<div style="display: flex; flex-wrap: wrap; gap: 10px;">';
    foreach ($order_statuses as $status_key => $status_label) {
        $checked = in_array($status_key, $selected_statuses) ? 'checked' : ''; // Seçim korunuyor
        $output .= '<div><input type="checkbox" name="exclude_statuses[]" value="' . esc_attr($status_key) . '" ' . $checked . ' /> ' . esc_html($status_label) . '</div>';
    }
    $output .= '</div><br />';

    $output .= '<input type="submit" value="Generate Report" class="button button-primary">';
    $output .= '</form>';

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Verify nonce
        if (!isset($_POST['woo_sales_dashboard_nonce']) || !wp_verify_nonce($_POST['woo_sales_dashboard_nonce'], 'woo_sales_dashboard_action')) {
            $output .= '<div class="notice notice-error"><p>Security check failed. Please try again.</p></div>';
            return $output;
        }

        $sales_agent_id = !empty($_POST['sales_agent_id']) ? intval($_POST['sales_agent_id']) : null;
        $start_date = !empty($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : $default_start_date;
        $end_date = !empty($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : $default_end_date;
        $exclude_statuses = !empty($_POST['exclude_statuses']) ? array_map('sanitize_text_field', $_POST['exclude_statuses']) : [];

        $output .= woo_generate_sales_report($sales_agent_id, $start_date, $end_date, $exclude_statuses);
    }

    return $output;
}

function woo_get_sales_agents() {
    return get_users(['role' => 'sales_agent']);
}

function woo_generate_sales_report($sales_agent_id = null, $start_date = null, $end_date = null, $exclude_statuses = []) {
    $args = [
        'post_type' => 'shop_order',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'date_query' => [
            [
                'after' => $start_date,
                'before' => $end_date,
                'inclusive' => true,
            ],
        ],
        'meta_query' => [],
    ];

    if ($sales_agent_id) {
        $args['meta_query'][] = [
            'key' => 'wcb2bsa_sales_agent',
            'value' => $sales_agent_id,
            'compare' => '=',
        ];
    }

    if (!empty($exclude_statuses)) {
        $args['post_status'] = array_diff(array_keys(wc_get_order_statuses()), $exclude_statuses);
    }

    $orders = get_posts($args);

    if (empty($orders)) {
        return '<p>No orders found for the selected filters.</p>';
    }

    // Toplam değişkenler
    $gross_total = 0;
    $refund_total = 0;
    $items_subtotal_sum = 0;
    $refund_subtotal_total = 0;
    $net_item_subtotal_sum = 0;
    $commission_total = 0;

    foreach ($orders as $order_post) {
        $order = wc_get_order($order_post->ID);
        $refund_ids = woo_get_refund_ids($order->get_id());

        $items_subtotal = floatval($order->get_subtotal());
        $refund_subtotal = 0;

        foreach ($refund_ids as $refund_id) {
            $refund_data = woo_get_refund_item_totals($refund_id);
            $refund_subtotal += abs(floatval($refund_data['subtotal']));
        }

        $net_item_subtotal = $items_subtotal - $refund_subtotal;
        if ($net_item_subtotal < 0) {
            $net_item_subtotal = 0;
        }

        $commission = $net_item_subtotal * 0.03;

        $gross_total += $order->get_total();
        $refund_total += $order->get_total_refunded();
        $items_subtotal_sum += $items_subtotal;
        $refund_subtotal_total += $refund_subtotal;
        $net_item_subtotal_sum += $net_item_subtotal;
        $commission_total += $commission;
    }

    // Widget'lar
    $output = '<div style="display: flex; justify-content: space-between; margin-bottom: 30px;">';

    $widget_style = 'padding: 20px; border-radius: 8px; text-align: center; flex: 1; margin-right: 10px; color: white; font-size: 16px;';
    $output .= '<div style="background-color: #4CAF50; ' . $widget_style . '"><strong>Gross Total</strong><br />' . wc_price($gross_total) . '</div>';
    $output .= '<div style="background-color: #FF9800; ' . $widget_style . '"><strong>Refund Total</strong><br />' . wc_price($refund_total) . '</div>';
    $output .= '<div style="background-color: #00BCD4; ' . $widget_style . '"><strong>Net Item Subtotal</strong><br />' . wc_price($net_item_subtotal_sum) . '</div>';
    $output .= '<div style="background-color: #E91E63; ' . $widget_style . '"><strong>Commission (3%)</strong><br />' . wc_price($commission_total) . '</div>';
    $output .= '</div>';

    // Rapor tablosu
    $output .= '<table class="widefat" style="margin-top: 20px;">';
    $output .= '<thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Order Date</th>
                        <th>Order Status</th>
                        <th>Gross Total</th>
                        <th>Refund Amount</th>
                        <th>Item Subtotal</th>
                        <th>Refund Subtotal</th>
                        <th>Net Item Subtotal</th>
                        <th>Commission</th>
                    </tr>
                </thead><tbody>';

    foreach ($orders as $order_post) {
        $order = wc_get_order($order_post->ID);
        $order_date = $order->get_date_created()->date('Y-m-d');
        $order_status = wc_get_order_status_name($order->get_status());
        $refund_ids = woo_get_refund_ids($order->get_id());

        $items_subtotal = floatval($order->get_subtotal());
        $refund_subtotal = 0;

        foreach ($refund_ids as $refund_id) {
            $refund_data = woo_get_refund_item_totals($refund_id);
            $refund_subtotal += abs(floatval($refund_data['subtotal']));
        }

        $net_item_subtotal = $items_subtotal - $refund_subtotal;
        if ($net_item_subtotal < 0) {
            $net_item_subtotal = 0;
        }

        $commission = $net_item_subtotal * 0.03;

        $output .= '<tr>';
        $output .= '<td>' . esc_html($order->get_id()) . '</td>';
        $output .= '<td>' . esc_html($order_date) . '</td>';
        $output .= '<td>' . esc_html($order_status) . '</td>';
        $output .= '<td>' . wc_price($order->get_total()) . '</td>';
        $output .= '<td>' . wc_price($order->get_total_refunded()) . '</td>';
        $output .= '<td>' . wc_price($items_subtotal) . '</td>';
        $output .= '<td>' . wc_price($refund_subtotal) . '</td>';
        $output .= '<td>' . wc_price($net_item_subtotal) . '</td>';
        $output .= '<td>' . wc_price($commission) . '</td>';
        $output .= '</tr>';
    }

    $output .= '</tbody></table>';

    return $output;
}

function woo_get_refund_ids($parent_order_id) {
    global $wpdb;

    return $wpdb->get_col(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'shop_order_refund' AND post_parent = %d",
            $parent_order_id
        )
    ) ?: [];
}

function woo_get_refund_item_totals($refund_id) {
    global $wpdb;

    $results = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT meta_key, meta_value 
             FROM {$wpdb->prefix}woocommerce_order_itemmeta 
             WHERE order_item_id IN (
                 SELECT order_item_id 
                 FROM {$wpdb->prefix}woocommerce_order_items 
                 WHERE order_id = %d
             )",
            $refund_id
        ),
        ARRAY_A
    );

    $subtotal = 0;

    foreach ($results as $meta) {
        if ($meta['meta_key'] === '_line_subtotal') {
            $subtotal += floatval($meta['meta_value']);
        }
    }

    return ['subtotal' => $subtotal];
}
