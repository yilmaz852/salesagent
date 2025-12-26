# WooCommerce Sales Dashboard - Technical Documentation

## Plugin Architecture

### Main Components

1. **Menu Registration** (`woo_sales_dashboard_menu`)
   - Adds "Sales Dashboard" to WordPress admin menu
   - Position: 33 (after WooCommerce)
   - Icon: dashicons-chart-bar
   - Capability required: manage_woocommerce

2. **Form Handler** (`woo_create_sales_form`)
   - Generates the filter form
   - Handles form submissions with nonce verification
   - Preserves user selections after submission

3. **Report Generator** (`woo_generate_sales_report`)
   - Queries orders based on filters
   - Calculates all metrics
   - Generates visual widgets and table

4. **Helper Functions**
   - `woo_get_sales_agents()`: Retrieves users with sales_agent role
   - `woo_get_refund_ids()`: Gets refund IDs for an order
   - `woo_get_refund_item_totals()`: Calculates refund subtotals

## Data Flow

```
User Input (Form)
    ↓
Security Validation (Nonce)
    ↓
Data Sanitization
    ↓
Query Orders (WP_Query)
    ↓
Calculate Metrics
    ↓
Display Results (Widgets + Table)
```

## Metric Calculations

### Gross Total
Sum of `$order->get_total()` for all orders

### Refund Total
Sum of `$order->get_total_refunded()` for all orders

### Item Subtotal
Sum of `$order->get_subtotal()` for all orders (before taxes/fees)

### Refund Subtotal
Sum of all `_line_subtotal` meta values from refund items

### Net Item Subtotal
```php
$net = $items_subtotal - $refund_subtotal;
if ($net < 0) $net = 0;
```

### Commission (3%)
```php
$commission = $net_item_subtotal * 0.03;
```

## Database Queries

### Order Query
```php
$args = [
    'post_type' => 'shop_order',
    'posts_per_page' => -1,
    'date_query' => [/* date range */],
    'meta_query' => [/* sales agent filter */],
    'post_status' => [/* excluded statuses */]
];
```

### Refund IDs Query
```sql
SELECT ID FROM wp_posts 
WHERE post_type = 'shop_order_refund' 
AND post_parent = {order_id}
```

### Refund Item Totals Query
```sql
SELECT meta_key, meta_value 
FROM wp_woocommerce_order_itemmeta 
WHERE order_item_id IN (
    SELECT order_item_id 
    FROM wp_woocommerce_order_items 
    WHERE order_id = {refund_id}
)
```

## Security Measures

1. **Nonce Verification**
   - Field: `woo_sales_dashboard_nonce`
   - Action: `woo_sales_dashboard_action`

2. **Data Sanitization**
   - `intval()` for numeric values
   - `sanitize_text_field()` for text inputs
   - `array_map('sanitize_text_field')` for arrays

3. **Output Escaping**
   - `esc_html()` for text output
   - `esc_attr()` for HTML attributes
   - `wc_price()` for price formatting (includes escaping)

4. **Direct Access Prevention**
   - `if (!defined('ABSPATH')) exit;`

## Filter Behavior

### Sales Agent Filter
- Empty value: Shows all agents
- Specific ID: Shows only orders with `wcb2bsa_sales_agent` meta matching the ID

### Date Range Filter
- Inclusive of both start and end dates
- Default: First day of current month to today

### Status Exclusion Filter
- Checkbox-based selection
- Preserves selections after form submission
- Uses `array_diff()` to exclude selected statuses

## UI Components

### Metric Widgets
Four color-coded boxes displaying:
- Gross Total (Green #4CAF50)
- Refund Total (Orange #FF9800)
- Net Item Subtotal (Cyan #00BCD4)
- Commission (Pink #E91E63)

### Order Table
Columns:
1. Order ID
2. Order Date (Y-m-d format)
3. Order Status
4. Gross Total
5. Refund Amount
6. Item Subtotal
7. Refund Subtotal
8. Net Item Subtotal
9. Commission

## Requirements for Sales Agent Tracking

To use the sales agent filtering feature:

1. Create a 'sales_agent' user role in WordPress
2. Assign this role to your sales team members
3. Ensure orders have the `wcb2bsa_sales_agent` meta key
4. The meta value should be the user ID of the sales agent

## Extending the Plugin

### Changing Commission Rate
Modify the calculation in `woo_generate_sales_report()`:
```php
$commission = $net_item_subtotal * 0.05; // 5% instead of 3%
```

### Adding Custom Filters
Add to the `$args` array in `woo_generate_sales_report()`:
```php
if ($custom_filter) {
    $args['meta_query'][] = [
        'key' => 'custom_meta_key',
        'value' => $custom_value,
        'compare' => '='
    ];
}
```

### Modifying Visual Style
Update the `$widget_style` variable or add custom CSS via WordPress hooks.

## Performance Considerations

- Uses `posts_per_page => -1` to get all matching orders
- May need pagination for stores with thousands of orders
- Database queries are optimized with proper indexing
- Consider caching for large datasets

## Compatibility

- WordPress 5.0+
- PHP 7.2+
- WooCommerce (any recent version)
- Works with HPOS (High-Performance Order Storage) via WC compatibility layer
