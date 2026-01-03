<?php
/**
 * Plugin Name: WooCommerce Sales Agent System
 * Plugin URI: https://github.com/yilmaz852/salesagent
 * Description: Complete sales agent management system with customer assignment, commission tracking, order placement on behalf of customers, and comprehensive sales panel.
 * Version: 3.0.0
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

// Check if WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    add_action('admin_notices', 'woo_sales_dashboard_woocommerce_missing_notice');
    return;
}

function woo_sales_dashboard_woocommerce_missing_notice() {
    echo '<div class="error"><p><strong>WooCommerce Sales Agent System</strong> requires WooCommerce to be installed and active.</p></div>';
}

/**
 * =====================================================
 * HELPER – HOME URL SAFE (Subdomain uyumlu)
 * =====================================================
 */
function get_home_url_safe($path = '') {
    return untrailingslashit(get_option('home')) . $path;
}

/**
 * =====================================================
 * ROLES
 * =====================================================
 */
add_action('init', function () {
    add_role('musteri', 'Müşteri', ['read' => true]);
    add_role('sales_agent', 'Sales Agent', [
        'read' => true,
        'switch_to_customer' => true,
    ]);

    if ($admin = get_role('administrator')) {
        $admin->add_cap('switch_to_customer');
    }
});

/**
 * =====================================================
 * SALES AGENT → WP-ADMIN & ADMIN BAR KAPAT
 * =====================================================
 */
add_action('after_setup_theme', function () {
    if (current_user_can('sales_agent')) {
        show_admin_bar(false);
    }
});

add_action('init', function () {
    if (is_admin() && current_user_can('sales_agent')) {
        wp_redirect(get_home_url_safe('/sales-panel'));
        exit;
    }
});

/**
 * =====================================================
 * CUSTOMER → SALES AGENT ASSIGN (USER PROFILE)
 * =====================================================
 */
add_action('show_user_profile', 'customer_agent_field');
add_action('edit_user_profile', 'customer_agent_field');

function customer_agent_field($user) {
    if (!in_array('musteri', (array) $user->roles)) return;

    $selected = get_user_meta($user->ID, 'bagli_agent_id', true);
    $agents   = get_users(['role__in' => ['sales_agent','administrator']]);
    ?>
    <h3>Sales Agent</h3>
    <table class="form-table">
        <tr>
            <th><label>Assigned Sales Agent</label></th>
            <td>
                <select name="bagli_agent_id">
                    <option value="">Select</option>
                    <?php foreach ($agents as $a): ?>
                        <option value="<?= $a->ID ?>" <?= selected($selected, $a->ID) ?>>
                            <?= esc_html($a->display_name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
    </table>
    <?php
}

add_action('personal_options_update', 'customer_agent_save');
add_action('edit_user_profile_update', 'customer_agent_save');

function customer_agent_save($user_id) {
    if (isset($_POST['bagli_agent_id'])) {
        update_user_meta($user_id, 'bagli_agent_id', intval($_POST['bagli_agent_id']));
    }
}

/**
 * =====================================================
 * ROUTES
 * =====================================================
 */
add_action('init', function () {
    add_rewrite_rule('^sales-login/?$', 'index.php?sales_login=1', 'top');
    add_rewrite_rule('^sales-panel/?$', 'index.php?sales_panel=customers', 'top');
    add_rewrite_rule('^sales-panel/([^/]+)/?$', 'index.php?sales_panel=$matches[1]', 'top');
});

add_filter('query_vars', function ($vars) {
    $vars[] = 'sales_login';
    $vars[] = 'sales_panel';
    return $vars;
});

// Activation hook
register_activation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

/**
 * =====================================================
 * SALES LOGIN PAGE
 * =====================================================
 */
add_action('template_redirect', function () {
    if (!get_query_var('sales_login')) return;

    if (is_user_logged_in()) {
        wp_redirect(get_home_url_safe('/sales-panel'));
        exit;
    }
    ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sales Agent Login</title>
<style>
body{background:#f5f7fb;font-family:Arial,sans-serif;margin:0;padding:0}
.box{max-width:400px;margin:120px auto;background:#fff;padding:30px;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.05)}
h2{margin:0 0 20px;color:#333}
input,button{width:100%;padding:12px;margin-bottom:10px;border-radius:6px;border:1px solid #ddd;box-sizing:border-box}
button{background:#6366f1;color:#fff;border:none;cursor:pointer;font-size:16px}
button:hover{background:#5558e3}
.error{color:red;font-size:14px}
</style>
</head>
<body>
<div class="box">
<h2>Sales Agent Login</h2>
<form method="post">
<input name="log" placeholder="Username" required>
<input type="password" name="pwd" placeholder="Password" required>
<button>Login</button>
</form>
<?php
if ($_POST) {
    $user = wp_signon([
        'user_login' => $_POST['log'],
        'user_password' => $_POST['pwd'],
        'remember' => true,
    ]);
    if (is_wp_error($user)) {
        echo '<p class="error">'.$user->get_error_message().'</p>';
    } else {
        wp_redirect(get_home_url_safe('/sales-panel'));
        exit;
    }
}
?>
</div>
</body>
</html>
<?php exit;
});

/**
 * =====================================================
 * PERFORMANCE – CUSTOMER IDS CACHE
 * =====================================================
 */
function sales_agent_get_customer_ids($agent_id) {
    $cache_key = 'sales_agent_customers_'.$agent_id;
    $ids = get_transient($cache_key);
    if ($ids !== false) return $ids;

    $users = get_users([
        'role' => 'musteri',
        'meta_key' => 'bagli_agent_id',
        'meta_value' => $agent_id,
        'fields' => 'ID',
    ]);

    set_transient($cache_key, $users, 10 * MINUTE_IN_SECONDS);
    return $users;
}

/**
 * =====================================================
 * PERFORMANCE – SINGLE ORDER QUERY
 * =====================================================
 */
function sales_agent_get_orders_fast($customer_ids) {
    if (empty($customer_ids)) return [];

    $cache_key = 'sales_agent_orders_'.md5(implode('_',$customer_ids));
    $orders = get_transient($cache_key);
    if ($orders !== false) return $orders;

    if (!function_exists('wc_get_orders')) return [];

    $orders = wc_get_orders([
        'customer_id' => $customer_ids,
        'limit' => -1,
        'orderby' => 'date',
        'order' => 'DESC',
        'return' => 'objects',
    ]);

    set_transient($cache_key, $orders, 5 * MINUTE_IN_SECONDS);
    return $orders;
}

/**
 * =====================================================
 * SALES PANEL (CUSTOMERS / COMMISSIONS / ORDERS)
 * =====================================================
 */
add_action('template_redirect', function () {

    $section = get_query_var('sales_panel');
    if (!$section) return;

    if (!is_user_logged_in() || !current_user_can('switch_to_customer')) {
        wp_redirect(get_home_url_safe('/sales-login'));
        exit;
    }

    $agent_id = get_current_user_id();
    show_admin_bar(false);

    // Customers
    $customers = get_users([
        'role' => 'musteri',
        'meta_key' => 'bagli_agent_id',
        'meta_value' => $agent_id
    ]);

    // Orders - Use performance optimizations
    $customer_ids = sales_agent_get_customer_ids($agent_id);
    $all_orders = sales_agent_get_orders_fast($customer_ids);
    
    $total_sales = 0;
    $completed_orders = 0;
    $pending_orders = 0;

    foreach ($all_orders as $o) {
        $total_sales += $o->get_total();
        if($o->get_status()=='completed') $completed_orders++;
        if($o->get_status()=='pending') $pending_orders++;
    }

    $commission_rate = get_option('sales_commission_rate', 10);
    $commission_total = ($total_sales * $commission_rate)/100;

    // Filters
    $filter_status = isset($_GET['order_status']) ? sanitize_text_field($_GET['order_status']) : '';
    $filter_customer = isset($_GET['customer_id']) ? intval($_GET['customer_id']) : '';
    $filtered_orders = [];

    foreach ($all_orders as $order) {
        if ($filter_status && $order->get_status() != $filter_status) continue;
        if ($filter_customer && $order->get_customer_id() != $filter_customer) continue;
        $filtered_orders[] = $order;
    }
    if (!$filtered_orders) $filtered_orders = $all_orders;

    ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sales Panel</title>
<style>
body{margin:0;font-family:Inter,Arial,sans-serif;background:#f5f7fb}
.sidebar{width:240px;position:fixed;top:0;left:0;bottom:0;background:#0f172a;color:#fff;padding:20px;overflow-y:auto}
.sidebar h2{margin:0 0 20px;font-size:20px}
.sidebar a{display:block;color:#cbd5f5;padding:10px;border-radius:6px;text-decoration:none;margin-bottom:5px}
.sidebar a.active,.sidebar a:hover{background:#6366f1;color:#fff}
.content{margin-left:260px;padding:30px}
.table{width:100%;background:#fff;border-collapse:collapse;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.05)}
.table th,.table td{padding:14px;border-bottom:1px solid #eef;text-align:left}
.table th{background:#f8fafc;font-weight:600}
.table tr:last-child td{border-bottom:none}
.btn{background:#7c3aed;color:#fff;padding:8px 14px;border-radius:6px;text-decoration:none;font-size:13px;margin-right:5px;display:inline-block}
.btn:hover{background:#6d28d9}
.btn.gray{background:#334155}
.btn.gray:hover{background:#1e293b}
.avatar{width:36px;height:36px;border-radius:50%;background:#7c3aed;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:bold;text-transform:uppercase}
.flex{display:flex;align-items:center;gap:10px}
.stats{display:flex;gap:20px;margin-bottom:20px;flex-wrap:wrap}
.stat-card{background:#fff;padding:20px;border-radius:12px;flex:1;min-width:150px;box-shadow:0 4px 12px rgba(0,0,0,0.05)}
.stat-card h3{margin:0 0 10px 0;font-size:14px;color:#374151;text-transform:uppercase;letter-spacing:0.5px}
.stat-card p{margin:0;font-size:24px;font-weight:bold;color:#111}
.filters{margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap}
select,button{padding:8px 12px;border-radius:6px;border:1px solid #ddd;background:#fff;cursor:pointer;font-size:14px}
button{background:#7c3aed;color:#fff;border:none}
button:hover{background:#6d28d9}
h1{margin:0 0 20px;font-size:28px;color:#111}
@media(max-width:768px){
.sidebar{position:relative;width:100%;height:auto}
.content{margin-left:0}
.stats{flex-direction:column}
}
</style>
</head>
<body>

<div class="sidebar">
<h2>Sales Panel</h2>
<a class="<?= $section==='customers'?'active':'' ?>" href="<?= get_home_url_safe('/sales-panel/customers') ?>">👥 Customers</a>
<a class="<?= $section==='commissions'?'active':'' ?>" href="<?= get_home_url_safe('/sales-panel/commissions') ?>">💰 Commissions</a>
<a class="<?= $section==='orders'?'active':'' ?>" href="<?= get_home_url_safe('/sales-panel/orders') ?>">📦 Orders</a>
<a href="<?= wp_logout_url(get_home_url_safe('/sales-login')) ?>">🚪 Logout</a>
</div>

<div class="content">
<?php
if($section==='customers') {
    echo '<h1>My Customers</h1>';
    if(empty($customers)){
        echo '<p>No customers assigned to you yet.</p>';
    } else {
        echo '<table class="table"><thead><tr>
        <th>Customer</th><th>Email</th><th>Phone</th><th>Orders</th><th>Total Spend</th><th>Actions</th></tr></thead><tbody>';
        foreach($customers as $c){
            $orders_count = function_exists('wc_get_orders') ? count(wc_get_orders(['customer_id'=>$c->ID,'return'=>'ids'])) : 0;
            $total = function_exists('wc_get_customer_total_spent') ? wc_get_customer_total_spent($c->ID) : 0;
            $initials = strtoupper(substr($c->display_name, 0, 1));
            echo '<tr>
            <td><div class="flex"><div class="avatar">'.$initials.'</div><span>'.esc_html($c->display_name).'</span></div></td>
            <td>'.esc_html($c->user_email).'</td>
            <td>'.esc_html(get_user_meta($c->ID,'billing_phone',true)).'</td>
            <td>'.$orders_count.'</td>
            <td>$'.number_format($total,2).'</td>
            <td>
                <a class="btn" href="'.wp_nonce_url(get_home_url_safe('?switch_customer='.$c->ID),'switch_customer').'">Shop Now</a>
            </td>
            </tr>';
        }
        echo '</tbody></table>';
    }
}


if($section==='commissions'){
    echo '<h1>My Commissions</h1>';
    echo '<div class="stats">';
    echo '<div class="stat-card"><h3>Total Sales</h3><p>$'.number_format($total_sales,2).'</p></div>';
    echo '<div class="stat-card"><h3>Commission Rate</h3><p>'.$commission_rate.'%</p></div>';
    echo '<div class="stat-card"><h3>Total Commission</h3><p>$'.number_format($commission_total,2).'</p></div>';
    echo '<div class="stat-card"><h3>Completed Orders</h3><p>'.$completed_orders.'</p></div>';
    echo '<div class="stat-card"><h3>Pending Orders</h3><p>'.$pending_orders.'</p></div>';
    echo '</div>';
}

if($section==='orders'){
    echo '<h1>My Orders</h1>';
    echo '<form method="get" class="filters">
    <input type="hidden" name="sales_panel" value="orders">
    <select name="order_status">
        <option value="">All Status</option>
        <option value="completed" '.selected($filter_status,'completed',false).'>Completed</option>
        <option value="pending" '.selected($filter_status,'pending',false).'>Pending</option>
        <option value="on-hold" '.selected($filter_status,'on-hold',false).'>On-Hold</option>
        <option value="cancelled" '.selected($filter_status,'cancelled',false).'>Cancelled</option>
    </select>
    <select name="customer_id">
        <option value="">All Customers</option>';
    foreach($customers as $c){
        echo '<option value="'.$c->ID.'" '.selected($filter_customer,$c->ID,false).'>'.esc_html($c->display_name).'</option>';
    }
    echo '</select><button>Filter</button></form>';

    if(empty($filtered_orders)){echo '<p>No orders found.</p>';}
    else{
        echo '<table class="table"><thead><tr><th>Order #</th><th>Customer</th><th>Date</th><th>Status</th><th>Total</th><th>Payment</th></tr></thead><tbody>';
        foreach($filtered_orders as $order){
            $status = $order->get_status();
            $status_color = ['completed'=>'#10b981','pending'=>'#f59e0b','on-hold'=>'#6366f1','cancelled'=>'#ef4444','failed'=>'#b91c1c','refunded'=>'#f97316'];
            $color = $status_color[$status] ?? '#374151';
            $customer_name = $order->get_billing_first_name().' '.$order->get_billing_last_name();
            echo '<tr>
            <td><strong>#'.$order->get_id().'</strong></td>
            <td>'.esc_html($customer_name).'</td>
            <td>'.$order->get_date_created()->date('Y-m-d H:i').'</td>
            <td><span style="color:#fff;background:'.$color.';padding:4px 10px;border-radius:6px;font-size:12px;text-transform:capitalize;display:inline-block">'.$status.'</span></td>
            <td><strong>$'.number_format($order->get_total(),2).'</strong></td>
            <td>'.esc_html($order->get_payment_method_title()).'</td>
            </tr>';
        }
        echo '</tbody></table>';
    }
}
?>
</div>
</body>
</html>
<?php exit;
});

/**
 * =====================================================
 * SWITCH TO CUSTOMER
 * =====================================================
 */
add_action('init', function () {
    if (!isset($_GET['switch_customer'])) return;
    check_admin_referer('switch_customer');

    $customer_id = intval($_GET['switch_customer']);
    $agent_id = get_current_user_id();
    $assigned = get_user_meta($customer_id,'bagli_agent_id',true);

    if($assigned != $agent_id && !current_user_can('administrator')){
        wp_die('Unauthorized');
    }

    setcookie('switch_back_agent',$agent_id,time()+3600,COOKIEPATH,COOKIE_DOMAIN);
    wp_clear_auth_cookie();
    wp_set_current_user($customer_id);
    wp_set_auth_cookie($customer_id,true);
    wp_redirect(get_home_url_safe());
    exit;
});

/**
 * =====================================================
 * FLOATING SWITCH BACK BUTTON
 * =====================================================
 */
add_action('wp_footer', function () {
    if(!isset($_COOKIE['switch_back_agent'])) return;
    $url = wp_nonce_url(get_home_url_safe('?switch_back=1'),'switch_back');
    echo "<a href='".esc_url($url)."' style='position:fixed;bottom:20px;right:20px;background:#0f172a;color:#fff;padding:12px 18px;border-radius:30px;text-decoration:none;z-index:9999;box-shadow:0 4px 12px rgba(0,0,0,0.3);font-size:14px'>← Back to Sales Panel</a>";
});

/**
 * =====================================================
 * SWITCH BACK
 * =====================================================
 */
add_action('init', function () {
    if(!isset($_GET['switch_back'])) return;
    check_admin_referer('switch_back');

    $agent_id = intval($_COOKIE['switch_back_agent']);
    setcookie('switch_back_agent','',time()-3600,COOKIEPATH,COOKIE_DOMAIN);

    wp_clear_auth_cookie();
    wp_set_current_user($agent_id);
    wp_set_auth_cookie($agent_id,true);
    wp_redirect(get_home_url_safe('/sales-panel'));
    exit;
});

/**
 * =====================================================
 * ADMIN SETTINGS → COMMISSION RATE
 * =====================================================
 */
add_action('admin_menu', function () {
    add_menu_page(
        'Sales Settings',
        'Sales Settings',
        'manage_options',
        'sales-settings',
        'sales_settings_page',
        'dashicons-chart-line',
        58
    );
});

function sales_settings_page() {
    if(isset($_POST['commission_rate'])){
        check_admin_referer('sales_settings_nonce');
        update_option('sales_commission_rate', floatval($_POST['commission_rate']));
        echo '<div class="updated"><p>Settings saved successfully!</p></div>';
    }
    $rate = get_option('sales_commission_rate', 10);
    ?>
    <div class="wrap">
        <h1>Sales Agent Settings</h1>
        <form method="post">
            <?php wp_nonce_field('sales_settings_nonce'); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="commission_rate">Commission Rate (%)</label></th>
                    <td>
                        <input type="number" step="0.1" min="0" max="100" id="commission_rate" name="commission_rate" value="<?= esc_attr($rate) ?>" class="regular-text">
                        <p class="description">Default commission rate for all sales agents.</p>
                    </td>
                </tr>
            </table>
            <p class="submit"><button type="submit" class="button button-primary">Save Changes</button></p>
        </form>
    </div>
    <?php
}
