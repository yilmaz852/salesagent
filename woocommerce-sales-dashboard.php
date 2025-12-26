<?php
/**
 * Plugin Name: WooCommerce Sales Agent System
 * Plugin URI: https://github.com/yilmaz852/salesagent
 * Description: Complete sales agent management system with customer assignment, commission tracking, order placement on behalf of customers, and comprehensive dashboards.
 * Version: 2.0.0
 * Author: yilmaz852
 * Author URI: https://github.com/yilmaz852
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Requires PHP: 7.2
 * Text Domain: woo-sales-agent
 * Domain Path: /languages
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Activation hook to flush rewrite rules
register_activation_hook(__FILE__, 'woo_sales_agent_activate');

function woo_sales_agent_activate() {
    woo_sales_agent_add_rewrite_rules();
    flush_rewrite_rules();
}

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'woo_sales_dashboard_woocommerce_missing_notice');
    return;
}

function woo_sales_dashboard_woocommerce_missing_notice() {
    echo '<div class="error"><p><strong>WooCommerce Sales Agent System</strong> requires WooCommerce to be installed and active.</p></div>';
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

    // Preserve selections after form submission
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

    // Dropdown menu for sales agents
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

    // Order status checkboxes
    $order_statuses = wc_get_order_statuses();
    $output .= '<label for="exclude_statuses">Exclude Order Statuses:</label><br />';
    $output .= '<div style="display: flex; flex-wrap: wrap; gap: 10px;">';
    foreach ($order_statuses as $status_key => $status_label) {
        $checked = in_array($status_key, $selected_statuses) ? 'checked' : ''; // Preserve selection
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

    // Total variables
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

    // Metric widgets
    $output = '<div style="display: flex; justify-content: space-between; margin-bottom: 30px;">';

    $widget_style = 'padding: 20px; border-radius: 8px; text-align: center; flex: 1; margin-right: 10px; color: white; font-size: 16px;';
    $output .= '<div style="background-color: #4CAF50; ' . $widget_style . '"><strong>Gross Total</strong><br />' . wc_price($gross_total) . '</div>';
    $output .= '<div style="background-color: #FF9800; ' . $widget_style . '"><strong>Refund Total</strong><br />' . wc_price($refund_total) . '</div>';
    $output .= '<div style="background-color: #00BCD4; ' . $widget_style . '"><strong>Net Item Subtotal</strong><br />' . wc_price($net_item_subtotal_sum) . '</div>';
    $output .= '<div style="background-color: #E91E63; ' . $widget_style . '"><strong>Commission (3%)</strong><br />' . wc_price($commission_total) . '</div>';
    $output .= '</div>';

    // Report table
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

// ============================================================================
// SALES AGENT SYSTEM - Enhanced Features (v2.0)
// ============================================================================

// 1. Redirect sales agents on login - completely bypass WordPress admin
add_filter('login_redirect', 'woo_sales_agent_login_redirect', 10, 3);
add_action('admin_init', 'woo_sales_agent_block_admin_access');

function woo_sales_agent_login_redirect($redirect_to, $request, $user) {
    // Check if user has sales_agent role
    if (isset($user->roles) && is_array($user->roles) && in_array('sales_agent', $user->roles)) {
        // Redirect directly to custom dashboard, not admin
        return home_url('/sales-agent-dashboard/');
    }
    
    return $redirect_to;
}

function woo_sales_agent_block_admin_access() {
    $user = wp_get_current_user();
    
    // Block sales agents from accessing admin area entirely
    if (in_array('sales_agent', $user->roles) && is_admin() && !wp_doing_ajax()) {
        wp_redirect(home_url('/sales-agent-dashboard/'));
        exit;
    }
}

// 2. Add sales agent menu items
add_action('admin_menu', 'woo_sales_agent_menu');

function woo_sales_agent_menu() {
    $user = wp_get_current_user();
    
    // Admin menu - full access
    if (current_user_can('manage_woocommerce')) {
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
    
    // Sales agent menu - restricted access
    if (in_array('sales_agent', $user->roles)) {
        add_menu_page(
            'My Dashboard',
            'My Dashboard',
            'read',
            'sales-agent-dashboard',
            'woo_sales_agent_dashboard_callback',
            'dashicons-businessman',
            26
        );
        
        add_submenu_page(
            'sales-agent-dashboard',
            'My Earnings',
            'My Earnings',
            'read',
            'sales-agent-earnings',
            'woo_sales_agent_earnings_callback'
        );
        
        add_submenu_page(
            'sales-agent-dashboard',
            'My Customers',
            'My Customers',
            'read',
            'sales-agent-customers',
            'woo_sales_agent_customers_callback'
        );
    }
}

// 2a. Create virtual frontend page for sales agent dashboard
add_action('init', 'woo_sales_agent_add_rewrite_rules');
add_filter('query_vars', 'woo_sales_agent_query_vars');
add_action('template_redirect', 'woo_sales_agent_frontend_dashboard');

function woo_sales_agent_add_rewrite_rules() {
    add_rewrite_rule('^sales-agent-dashboard/?$', 'index.php?sales_agent_dashboard=1', 'top');
    add_rewrite_rule('^sales-agent-dashboard/customers/?$', 'index.php?sales_agent_dashboard=customers', 'top');
    add_rewrite_rule('^sales-agent-dashboard/earnings/?$', 'index.php?sales_agent_dashboard=earnings', 'top');
}

function woo_sales_agent_query_vars($vars) {
    $vars[] = 'sales_agent_dashboard';
    return $vars;
}

function woo_sales_agent_frontend_dashboard() {
    $dashboard_page = get_query_var('sales_agent_dashboard');
    
    if (!$dashboard_page) {
        return;
    }
    
    // Check if user is sales agent
    if (!is_user_logged_in()) {
        wp_redirect(wp_login_url(home_url('/sales-agent-dashboard/')));
        exit;
    }
    
    $user = wp_get_current_user();
    if (!in_array('sales_agent', $user->roles)) {
        wp_die('Access denied. Only sales agents can view this page.');
    }
    
    // Load template based on page
    woo_sales_agent_render_frontend_page($dashboard_page, $user);
    exit;
}

function woo_sales_agent_render_frontend_page($page, $user) {
    // Start output
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sales Agent Dashboard - <?php bloginfo('name'); ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                background: #f0f0f1;
                color: #3c434a;
                line-height: 1.6;
            }
            .dashboard-header {
                background: #2271b1;
                color: white;
                padding: 20px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            .dashboard-header h1 {
                font-size: 28px;
                font-weight: 400;
            }
            .dashboard-nav {
                background: white;
                padding: 15px 20px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                margin-bottom: 20px;
            }
            .dashboard-nav a {
                display: inline-block;
                padding: 8px 15px;
                margin-right: 10px;
                background: #2271b1;
                color: white;
                text-decoration: none;
                border-radius: 3px;
                transition: background 0.3s;
            }
            .dashboard-nav a:hover {
                background: #135e96;
            }
            .dashboard-nav a.active {
                background: #135e96;
            }
            .dashboard-nav a.logout {
                background: #d63638;
                float: right;
            }
            .dashboard-content {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            .stat-card {
                background: white;
                padding: 25px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                text-align: center;
            }
            .stat-card h3 {
                color: #50575e;
                font-size: 16px;
                margin-bottom: 10px;
                font-weight: 400;
            }
            .stat-card .stat-value {
                font-size: 36px;
                font-weight: 600;
                color: #2271b1;
            }
            .content-card {
                background: white;
                padding: 25px;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                margin-bottom: 20px;
            }
            .content-card h2 {
                margin-bottom: 20px;
                color: #1d2327;
                font-size: 22px;
                font-weight: 400;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            table th {
                background: #f6f7f7;
                padding: 12px;
                text-align: left;
                font-weight: 600;
                border-bottom: 1px solid #c3c4c7;
            }
            table td {
                padding: 12px;
                border-bottom: 1px solid #dcdcde;
            }
            table tr:hover {
                background: #f6f7f7;
            }
            .button {
                display: inline-block;
                padding: 8px 15px;
                background: #2271b1;
                color: white;
                text-decoration: none;
                border-radius: 3px;
                border: none;
                cursor: pointer;
                font-size: 14px;
            }
            .button:hover {
                background: #135e96;
            }
            .button-small {
                padding: 6px 12px;
                font-size: 13px;
            }
            .notice {
                padding: 12px 15px;
                margin-bottom: 20px;
                border-left: 4px solid;
                border-radius: 3px;
            }
            .notice-success {
                background: #edfaef;
                border-color: #00a32a;
                color: #00a32a;
            }
            .notice-warning {
                background: #fcf9e8;
                border-color: #dba617;
                color: #7c7318;
            }
            .notice-info {
                background: #f0f6fc;
                border-color: #72aee6;
                color: #2c3338;
            }
            .form-group {
                margin-bottom: 15px;
            }
            .form-group label {
                display: inline-block;
                margin-bottom: 5px;
                font-weight: 600;
            }
            .form-group input[type="date"] {
                padding: 8px 12px;
                border: 1px solid #8c8f94;
                border-radius: 3px;
                font-size: 14px;
            }
        </style>
    </head>
    <body>
        <div class="dashboard-header">
            <h1>Sales Agent Dashboard</h1>
        </div>
        
        <div class="dashboard-nav">
            <a href="<?php echo home_url('/sales-agent-dashboard/'); ?>" class="<?php echo $page === '1' ? 'active' : ''; ?>">Dashboard</a>
            <a href="<?php echo home_url('/sales-agent-dashboard/customers/'); ?>" class="<?php echo $page === 'customers' ? 'active' : ''; ?>">My Customers</a>
            <a href="<?php echo home_url('/sales-agent-dashboard/earnings/'); ?>" class="<?php echo $page === 'earnings' ? 'active' : ''; ?>">My Earnings</a>
            <a href="<?php echo wp_logout_url(home_url()); ?>" class="logout">Logout</a>
        </div>
        
        <div class="dashboard-content">
            <?php
            if ($page === '1' || $page === 'home') {
                woo_render_frontend_dashboard_home($user);
            } elseif ($page === 'customers') {
                woo_render_frontend_dashboard_customers($user);
            } elseif ($page === 'earnings') {
                woo_render_frontend_dashboard_earnings($user);
            }
            ?>
        </div>
    </body>
    </html>
    <?php
}

function woo_render_frontend_dashboard_home($user) {
    $customer_count = woo_get_agent_customer_count($user->ID);
    $commission_rate = get_user_meta($user->ID, 'sales_agent_commission_rate', true) ?: 3;
    
    echo '<h2 style="margin-bottom: 20px;">Welcome, ' . esc_html($user->display_name) . '</h2>';
    
    echo '<div class="stats-grid">';
    echo '<div class="stat-card">';
    echo '<h3>My Customers</h3>';
    echo '<div class="stat-value">' . esc_html($customer_count) . '</div>';
    echo '</div>';
    
    echo '<div class="stat-card">';
    echo '<h3>Commission Rate</h3>';
    echo '<div class="stat-value">' . esc_html($commission_rate) . '%</div>';
    echo '</div>';
    echo '</div>';
    
    echo '<div class="content-card">';
    echo '<h2>Quick Start</h2>';
    echo '<p>Use the navigation above to:</p>';
    echo '<ul style="margin: 15px 0 15px 20px;">';
    echo '<li><strong>My Customers</strong> - View and manage your assigned customers</li>';
    echo '<li><strong>My Earnings</strong> - Check your commission earnings</li>';
    echo '</ul>';
    echo '</div>';
}

function woo_render_frontend_dashboard_customers($user) {
    $customers = woo_get_agent_customers($user->ID);
    
    // Handle customer switch
    if (isset($_GET['switch_to_customer']) && isset($_GET['_wpnonce'])) {
        if (wp_verify_nonce($_GET['_wpnonce'], 'switch_customer_' . intval($_GET['switch_to_customer']))) {
            $customer_id = intval($_GET['switch_to_customer']);
            $customer_agent = get_user_meta($customer_id, 'assigned_sales_agent', true);
            if ($customer_agent == $user->ID) {
                update_user_meta($user->ID, '_acting_as_customer', $customer_id);
                
                // If shop_now parameter is set, redirect to shop
                if (isset($_GET['shop_now'])) {
                    wp_redirect(wc_get_page_permalink('shop'));
                    exit;
                }
                
                echo '<div class="notice notice-success">You are now acting as ' . esc_html(get_userdata($customer_id)->display_name) . '. <a href="' . esc_url(wc_get_page_permalink('shop')) . '">Go to Shop</a></div>';
            }
        }
    }
    
    // Handle stop switch
    if (isset($_GET['stop_switch']) && isset($_GET['_wpnonce'])) {
        if (wp_verify_nonce($_GET['_wpnonce'], 'stop_switch')) {
            delete_user_meta($user->ID, '_acting_as_customer');
            echo '<div class="notice notice-info">You are no longer acting as a customer.</div>';
        }
    }
    
    // Check if currently acting as customer
    $acting_as = get_user_meta($user->ID, '_acting_as_customer', true);
    if ($acting_as) {
        $customer_user = get_userdata($acting_as);
        if ($customer_user) {
            echo '<div class="notice notice-warning">Currently Acting As: ' . esc_html($customer_user->display_name) . ' (' . esc_html($customer_user->user_email) . ') | <a href="' . esc_url(home_url('/sales-agent-dashboard/customers/?stop_switch=1&_wpnonce=' . wp_create_nonce('stop_switch'))) . '">Stop Acting as Customer</a></div>';
        }
    }
    
    if (empty($customers)) {
        echo '<div class="content-card">';
        echo '<p>No customers assigned to you yet.</p>';
        echo '</div>';
        return;
    }
    
    echo '<div class="content-card">';
    echo '<h2>My Customers</h2>';
    echo '<table>';
    echo '<thead><tr><th>Customer</th><th>Email</th><th>Total Orders</th><th>Total Spent</th><th>Actions</th></tr></thead><tbody>';
    
    foreach ($customers as $customer) {
        $order_count = wc_get_customer_order_count($customer->ID);
        $total_spent = wc_get_customer_total_spent($customer->ID);
        
        echo '<tr>';
        echo '<td>' . esc_html($customer->display_name) . '</td>';
        echo '<td>' . esc_html($customer->user_email) . '</td>';
        echo '<td>' . esc_html($order_count) . '</td>';
        echo '<td>' . wc_price($total_spent) . '</td>';
        echo '<td>';
        $switch_url = home_url('/sales-agent-dashboard/customers/?switch_to_customer=' . $customer->ID . '&_wpnonce=' . wp_create_nonce('switch_customer_' . $customer->ID));
        $shop_url = home_url('/sales-agent-dashboard/customers/?switch_to_customer=' . $customer->ID . '&shop_now=1&_wpnonce=' . wp_create_nonce('switch_customer_' . $customer->ID));
        echo '<a href="' . esc_url($switch_url) . '" class="button button-small">Act as Customer</a> ';
        echo '<a href="' . esc_url($shop_url) . '" class="button button-small">Shop Now</a>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>';
}

function woo_render_frontend_dashboard_earnings($user) {
    $commission_rate = floatval(get_user_meta($user->ID, 'sales_agent_commission_rate', true) ?: 3) / 100;
    $default_start_date = date('Y-m-01');
    $default_end_date = date('Y-m-d');
    
    echo '<div class="content-card">';
    echo '<h2>My Earnings</h2>';
    echo '<form method="post" style="margin-bottom: 20px;">';
    echo wp_nonce_field('woo_agent_earnings_action', 'woo_agent_earnings_nonce', true, false);
    echo '<div class="form-group">';
    echo '<label for="start_date">Start Date:</label> ';
    echo '<input type="date" id="start_date" name="start_date" value="' . esc_attr(isset($_POST['start_date']) ? $_POST['start_date'] : $default_start_date) . '"> ';
    echo '<label for="end_date">End Date:</label> ';
    echo '<input type="date" id="end_date" name="end_date" value="' . esc_attr(isset($_POST['end_date']) ? $_POST['end_date'] : $default_end_date) . '"> ';
    echo '<button type="submit" class="button">View Earnings</button>';
    echo '</div>';
    echo '</form>';
    
    if (isset($_POST['woo_agent_earnings_nonce'])) {
        if (!wp_verify_nonce($_POST['woo_agent_earnings_nonce'], 'woo_agent_earnings_action')) {
            echo '<div class="notice notice-error">Security check failed.</div>';
        } else {
            $start_date = !empty($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : $default_start_date;
            $end_date = !empty($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : $default_end_date;
            echo woo_generate_agent_earnings_report_frontend($user->ID, $start_date, $end_date, $commission_rate);
        }
    }
    
    echo '</div>';
}

function woo_generate_agent_earnings_report_frontend($sales_agent_id, $start_date, $end_date, $commission_rate) {
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
        'meta_query' => [
            [
                'key' => 'wcb2bsa_sales_agent',
                'value' => $sales_agent_id,
                'compare' => '=',
            ],
        ],
    ];
    
    $orders = get_posts($args);
    
    if (empty($orders)) {
        return '<p>No orders found for the selected period.</p>';
    }
    
    $total_commission = 0;
    $net_subtotal_sum = 0;
    $order_data = [];
    
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
        
        $commission = $net_item_subtotal * $commission_rate;
        
        $total_commission += $commission;
        $net_subtotal_sum += $net_item_subtotal;
        
        $order_data[] = [
            'order' => $order,
            'net_subtotal' => $net_item_subtotal,
            'commission' => $commission,
        ];
    }
    
    // Display summary
    $output = '<div class="stats-grid" style="margin-bottom: 20px;">';
    $output .= '<div class="stat-card">';
    $output .= '<h3>Total Sales (Net)</h3>';
    $output .= '<div class="stat-value" style="color: #00a32a;">' . strip_tags(wc_price($net_subtotal_sum)) . '</div>';
    $output .= '</div>';
    $output .= '<div class="stat-card">';
    $output .= '<h3>Total Commission (' . ($commission_rate * 100) . '%)</h3>';
    $output .= '<div class="stat-value" style="color: #d63638;">' . strip_tags(wc_price($total_commission)) . '</div>';
    $output .= '</div>';
    $output .= '</div>';
    
    // Order table
    $output .= '<table>';
    $output .= '<thead><tr><th>Order ID</th><th>Date</th><th>Status</th><th>Net Subtotal</th><th>Commission</th></tr></thead><tbody>';
    
    foreach ($order_data as $data) {
        $order = $data['order'];
        $output .= '<tr>';
        $output .= '<td>#' . esc_html($order->get_id()) . '</td>';
        $output .= '<td>' . esc_html($order->get_date_created()->date('Y-m-d')) . '</td>';
        $output .= '<td>' . esc_html(wc_get_order_status_name($order->get_status())) . '</td>';
        $output .= '<td>' . wc_price($data['net_subtotal']) . '</td>';
        $output .= '<td>' . wc_price($data['commission']) . '</td>';
        $output .= '</tr>';
    }
    
    $output .= '</tbody></table>';
    
    return $output;
}

// 3. Sales agent dashboard page
function woo_sales_agent_dashboard_callback() {
    $user = wp_get_current_user();
    
    echo '<div class="wrap">';
    echo '<h1>Welcome, ' . esc_html($user->display_name) . '</h1>';
    echo '<p>Your Sales Agent Dashboard</p>';
    
    // Quick stats
    $customer_count = woo_get_agent_customer_count($user->ID);
    $commission_rate = get_user_meta($user->ID, 'sales_agent_commission_rate', true) ?: 3;
    
    echo '<div style="display: flex; gap: 20px; margin: 20px 0;">';
    echo '<div style="background: #2271b1; color: white; padding: 20px; border-radius: 8px; flex: 1;">';
    echo '<h3 style="margin: 0; color: white;">My Customers</h3>';
    echo '<p style="font-size: 32px; margin: 10px 0;">' . esc_html($customer_count) . '</p>';
    echo '</div>';
    
    echo '<div style="background: #00a32a; color: white; padding: 20px; border-radius: 8px; flex: 1;">';
    echo '<h3 style="margin: 0; color: white;">Commission Rate</h3>';
    echo '<p style="font-size: 32px; margin: 10px 0;">' . esc_html($commission_rate) . '%</p>';
    echo '</div>';
    echo '</div>';
    
    // Quick actions
    echo '<h2>Quick Actions</h2>';
    echo '<div style="display: flex; gap: 10px; flex-wrap: wrap;">';
    echo '<a href="' . admin_url('admin.php?page=sales-agent-customers') . '" class="button button-primary button-large">View My Customers</a>';
    echo '<a href="' . admin_url('admin.php?page=sales-agent-earnings') . '" class="button button-secondary button-large">View My Earnings</a>';
    echo '</div>';
    
    echo '</div>';
}

// 4. Sales agent earnings page
function woo_sales_agent_earnings_callback() {
    $user = wp_get_current_user();
    
    echo '<div class="wrap">';
    echo '<h1>My Earnings</h1>';
    echo '<p>View your commission earnings</p>';
    
    // Get commission rate for this agent
    $commission_rate = floatval(get_user_meta($user->ID, 'sales_agent_commission_rate', true) ?: 3) / 100;
    
    // Create form with date range
    $default_start_date = date('Y-m-01');
    $default_end_date = date('Y-m-d');
    
    echo '<form method="post">';
    echo wp_nonce_field('woo_agent_earnings_action', 'woo_agent_earnings_nonce', true, false);
    echo '<label for="start_date">Start Date:</label> ';
    echo '<input type="date" id="start_date" name="start_date" value="' . esc_attr(isset($_POST['start_date']) ? $_POST['start_date'] : $default_start_date) . '"> ';
    echo '<label for="end_date">End Date:</label> ';
    echo '<input type="date" id="end_date" name="end_date" value="' . esc_attr(isset($_POST['end_date']) ? $_POST['end_date'] : $default_end_date) . '"> ';
    echo '<input type="submit" value="View Earnings" class="button button-primary">';
    echo '</form><br />';
    
    if (isset($_POST['woo_agent_earnings_nonce'])) {
        if (!wp_verify_nonce($_POST['woo_agent_earnings_nonce'], 'woo_agent_earnings_action')) {
            echo '<div class="notice notice-error"><p>Security check failed.</p></div>';
            echo '</div>';
            return;
        }
        
        $start_date = !empty($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : $default_start_date;
        $end_date = !empty($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : $default_end_date;
        
        // Generate report for this agent only
        echo woo_generate_agent_earnings_report($user->ID, $start_date, $end_date, $commission_rate);
    }
    
    echo '</div>';
}

// 5. Generate earnings report for specific agent
function woo_generate_agent_earnings_report($sales_agent_id, $start_date, $end_date, $commission_rate) {
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
        'meta_query' => [
            [
                'key' => 'wcb2bsa_sales_agent',
                'value' => $sales_agent_id,
                'compare' => '=',
            ],
        ],
    ];
    
    $orders = get_posts($args);
    
    if (empty($orders)) {
        return '<p>No orders found for the selected period.</p>';
    }
    
    // Calculate totals and store order data in single loop
    $total_commission = 0;
    $net_subtotal_sum = 0;
    $order_data = [];
    
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
        
        $commission = $net_item_subtotal * $commission_rate;
        
        $total_commission += $commission;
        $net_subtotal_sum += $net_item_subtotal;
        
        // Store for display
        $order_data[] = [
            'order' => $order,
            'net_subtotal' => $net_item_subtotal,
            'commission' => $commission,
        ];
    }
    
    // Display summary
    $output = '<div style="display: flex; gap: 20px; margin: 20px 0;">';
    $output .= '<div style="background: #00BCD4; color: white; padding: 20px; border-radius: 8px; flex: 1; text-align: center;">';
    $output .= '<strong>Total Sales (Net)</strong><br />' . wc_price($net_subtotal_sum) . '</div>';
    $output .= '<div style="background: #E91E63; color: white; padding: 20px; border-radius: 8px; flex: 1; text-align: center;">';
    $output .= '<strong>Total Commission (' . ($commission_rate * 100) . '%)</strong><br />' . wc_price($total_commission) . '</div>';
    $output .= '</div>';
    
    // Order table using cached data
    $output .= '<table class="widefat">';
    $output .= '<thead><tr><th>Order ID</th><th>Date</th><th>Status</th><th>Net Subtotal</th><th>Commission</th></tr></thead><tbody>';
    
    foreach ($order_data as $data) {
        $order = $data['order'];
        $output .= '<tr>';
        $output .= '<td>#' . esc_html($order->get_id()) . '</td>';
        $output .= '<td>' . esc_html($order->get_date_created()->date('Y-m-d')) . '</td>';
        $output .= '<td>' . esc_html(wc_get_order_status_name($order->get_status())) . '</td>';
        $output .= '<td>' . wc_price($data['net_subtotal']) . '</td>';
        $output .= '<td>' . wc_price($data['commission']) . '</td>';
        $output .= '</tr>';
    }
    
    $output .= '</tbody></table>';
    
    return $output;
}

// 6. Sales agent customers page
function woo_sales_agent_customers_callback() {
    $user = wp_get_current_user();
    
    echo '<div class="wrap">';
    echo '<h1>My Customers</h1>';
    echo '<p>Manage customers assigned to you</p>';
    
    $customers = woo_get_agent_customers($user->ID);
    
    if (empty($customers)) {
        echo '<p>No customers assigned to you yet.</p>';
        echo '</div>';
        return;
    }
    
    // Handle customer switch
    if (isset($_GET['switch_to_customer']) && isset($_GET['_wpnonce'])) {
        if (wp_verify_nonce($_GET['_wpnonce'], 'switch_customer_' . intval($_GET['switch_to_customer']))) {
            $customer_id = intval($_GET['switch_to_customer']);
            
            // Verify this customer is assigned to this agent
            $customer_agent = get_user_meta($customer_id, 'assigned_sales_agent', true);
            if ($customer_agent == $user->ID) {
                // Store the switch using WordPress options (not WC session in admin)
                update_user_meta($user->ID, '_acting_as_customer', $customer_id);
                
                echo '<div class="notice notice-success"><p>You are now acting as ' . esc_html(get_userdata($customer_id)->display_name) . '. <a href="' . esc_url(wc_get_page_permalink('shop')) . '">Go to Shop</a> | <a href="' . esc_url(admin_url('admin.php?page=sales-agent-customers&stop_switch=1&_wpnonce=' . wp_create_nonce('stop_switch'))) . '">Stop Acting as Customer</a></p></div>';
            }
        }
    }
    
    // Handle stop switch
    if (isset($_GET['stop_switch']) && isset($_GET['_wpnonce'])) {
        if (wp_verify_nonce($_GET['_wpnonce'], 'stop_switch')) {
            delete_user_meta($user->ID, '_acting_as_customer');
            echo '<div class="notice notice-info"><p>You are no longer acting as a customer.</p></div>';
        }
    }
    
    // Check if currently acting as customer
    $acting_as = get_user_meta($user->ID, '_acting_as_customer', true);
    if ($acting_as) {
        $customer_user = get_userdata($acting_as);
        if ($customer_user) {
            echo '<div class="notice notice-warning"><p><strong>Currently Acting As:</strong> ' . esc_html($customer_user->display_name) . ' (' . esc_html($customer_user->user_email) . ') | <a href="' . esc_url(admin_url('admin.php?page=sales-agent-customers&stop_switch=1&_wpnonce=' . wp_create_nonce('stop_switch'))) . '">Stop Acting as Customer</a></p></div>';
        }
    }
    
    // Display customer table
    echo '<table class="widefat">';
    echo '<thead><tr><th>Customer</th><th>Email</th><th>Total Orders</th><th>Total Spent</th><th>Actions</th></tr></thead><tbody>';
    
    foreach ($customers as $customer) {
        $order_count = wc_get_customer_order_count($customer->ID);
        $total_spent = wc_get_customer_total_spent($customer->ID);
        
        echo '<tr>';
        echo '<td>' . esc_html($customer->display_name) . '</td>';
        echo '<td>' . esc_html($customer->user_email) . '</td>';
        echo '<td>' . esc_html($order_count) . '</td>';
        echo '<td>' . wc_price($total_spent) . '</td>';
        echo '<td>';
        $switch_url = admin_url('admin.php?page=sales-agent-customers&switch_to_customer=' . $customer->ID . '&_wpnonce=' . wp_create_nonce('switch_customer_' . $customer->ID));
        echo '<a href="' . esc_url($switch_url) . '" class="button button-small">Act as Customer</a> ';
        echo '<a href="' . esc_url(wc_get_page_permalink('shop')) . '" class="button button-small button-primary">Shop Now</a>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody></table>';
    echo '</div>';
}

// 7. Get customers assigned to agent
function woo_get_agent_customers($agent_id) {
    $args = [
        'meta_key' => 'assigned_sales_agent',
        'meta_value' => $agent_id,
        'fields' => 'all',
    ];
    
    return get_users($args);
}

// 8. Get customer count for agent
function woo_get_agent_customer_count($agent_id) {
    $customers = woo_get_agent_customers($agent_id);
    return count($customers);
}

// 9. Add commission rate field to user profile
add_action('show_user_profile', 'woo_add_sales_agent_fields');
add_action('edit_user_profile', 'woo_add_sales_agent_fields');

function woo_add_sales_agent_fields($user) {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    $is_sales_agent = in_array('sales_agent', $user->roles);
    $commission_rate = get_user_meta($user->ID, 'sales_agent_commission_rate', true) ?: 3;
    $assigned_agent = get_user_meta($user->ID, 'assigned_sales_agent', true);
    
    echo '<h3>Sales Agent Settings</h3>';
    echo '<table class="form-table">';
    
    if ($is_sales_agent) {
        echo '<tr>';
        echo '<th><label for="sales_agent_commission_rate">Commission Rate (%)</label></th>';
        echo '<td>';
        echo '<input type="number" step="0.01" min="0" max="100" name="sales_agent_commission_rate" id="sales_agent_commission_rate" value="' . esc_attr($commission_rate) . '" class="regular-text" />';
        echo '<p class="description">Commission percentage for this sales agent (e.g., 3 for 3%). Must be between 0 and 100.</p>';
        echo '</td>';
        echo '</tr>';
    }
    
    if (!$is_sales_agent && !in_array('administrator', $user->roles)) {
        echo '<tr>';
        echo '<th><label for="assigned_sales_agent">Assigned Sales Agent</label></th>';
        echo '<td>';
        
        $sales_agents = woo_get_sales_agents();
        echo '<select name="assigned_sales_agent" id="assigned_sales_agent">';
        echo '<option value="">None</option>';
        foreach ($sales_agents as $agent) {
            $selected = ($assigned_agent == $agent->ID) ? 'selected' : '';
            echo '<option value="' . esc_attr($agent->ID) . '" ' . $selected . '>' . esc_html($agent->display_name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">Assign this customer to a sales agent</p>';
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</table>';
}

// 10. Save user meta fields
add_action('personal_options_update', 'woo_save_sales_agent_fields');
add_action('edit_user_profile_update', 'woo_save_sales_agent_fields');

function woo_save_sales_agent_fields($user_id) {
    if (!current_user_can('manage_options')) {
        return;
    }
    
    if (isset($_POST['sales_agent_commission_rate'])) {
        $rate = floatval($_POST['sales_agent_commission_rate']);
        // Validate commission rate (0-100)
        if ($rate >= 0 && $rate <= 100) {
            update_user_meta($user_id, 'sales_agent_commission_rate', $rate);
        }
    }
    
    if (isset($_POST['assigned_sales_agent'])) {
        $agent_id = intval($_POST['assigned_sales_agent']);
        if ($agent_id > 0) {
            update_user_meta($user_id, 'assigned_sales_agent', $agent_id);
        } else {
            delete_user_meta($user_id, 'assigned_sales_agent');
        }
    }
}

// 11. Override WooCommerce customer when sales agent is acting as customer
add_filter('woocommerce_cart_hash', 'woo_sales_agent_cart_hash', 10, 2);
add_action('init', 'woo_sales_agent_sync_session', 20);
add_filter('woocommerce_customer_get_id', 'woo_sales_agent_override_wc_customer_id', 99);

function woo_sales_agent_override_wc_customer_id($customer_id) {
    // Only override on frontend for WooCommerce operations
    if (!is_admin() && is_user_logged_in()) {
        $user_id = wp_get_current_user()->ID;
        
        // Check if this is the sales agent (not already overridden)
        if (in_array('sales_agent', wp_get_current_user()->roles)) {
            $acting_as = get_user_meta($user_id, '_acting_as_customer', true);
            if ($acting_as) {
                return intval($acting_as);
            }
        }
    }
    return $customer_id;
}

function woo_sales_agent_sync_session() {
    // Sync user meta to WC session on frontend
    if (!is_admin() && is_user_logged_in()) {
        $user_id = get_current_user_id();
        $acting_as = get_user_meta($user_id, '_acting_as_customer', true);
        
        if ($acting_as && function_exists('WC') && WC()->session) {
            WC()->session->set('sales_agent_acting_as_customer', $acting_as);
            WC()->session->set('sales_agent_original_user', $user_id);
        }
    }
}

function woo_sales_agent_cart_hash($hash, $cart) {
    if (is_user_logged_in()) {
        $user_id = get_current_user_id();
        $acting_as = get_user_meta($user_id, '_acting_as_customer', true);
        if ($acting_as) {
            $hash .= '_agent_' . $acting_as;
        }
    }
    return $hash;
}

// 12. Assign order to sales agent when placing order as customer
add_action('woocommerce_checkout_order_processed', 'woo_assign_order_to_sales_agent', 10, 1);

function woo_assign_order_to_sales_agent($order_id) {
    $acting_as = WC()->session->get('sales_agent_acting_as_customer');
    $agent_id = WC()->session->get('sales_agent_original_user');
    
    if ($acting_as && $agent_id) {
        // Assign the order to the sales agent
        update_post_meta($order_id, 'wcb2bsa_sales_agent', $agent_id);
        
        // Also set the customer
        $order = wc_get_order($order_id);
        $order->set_customer_id($acting_as);
        $order->save();
    }
}

// 13. Show notice when sales agent is acting as customer (frontend)
add_action('wp_footer', 'woo_sales_agent_acting_notice');

function woo_sales_agent_acting_notice() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $user_id = get_current_user_id();
    $acting_as = get_user_meta($user_id, '_acting_as_customer', true);
    
    if ($acting_as) {
        $customer = get_userdata($acting_as);
        if (!$customer) {
            return;
        }
        
        echo '<div style="position: fixed; top: 32px; left: 0; right: 0; background: #ff9800; color: white; padding: 10px; text-align: center; z-index: 999999; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">';
        echo '<strong>Sales Agent Mode:</strong> You are currently shopping as ' . esc_html($customer->display_name) . ' (' . esc_html($customer->user_email) . ')';
        echo ' | <a href="' . esc_url(admin_url('admin.php?page=sales-agent-customers&stop_switch=1&_wpnonce=' . wp_create_nonce('stop_switch'))) . '" style="color: white; text-decoration: underline;">Exit Customer View</a>';
        echo '</div>';
        
        // Add margin to body to prevent content hiding
        echo '<style>body { margin-top: 50px !important; }</style>';
    }
}
