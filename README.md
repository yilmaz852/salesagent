# WooCommerce Sales Dashboard

A comprehensive WordPress plugin for WooCommerce that provides a powerful sales dashboard with detailed reporting capabilities.

## Description

The WooCommerce Sales Dashboard plugin adds a dedicated admin page to your WordPress/WooCommerce installation that allows you to:

- View comprehensive sales reports for sales agents
- Filter reports by sales agent, date range, and order status
- Calculate and display key metrics including:
  - Gross Total
  - Refund Total
  - Net Item Subtotal (after refunds)
  - Commission (3% of net subtotal)
- View detailed order-by-order breakdown
- Exclude specific order statuses from reports
- Visual widgets for quick metric overview

## Features

### Sales Agent Filtering
- Select individual sales agents or view all agents
- Requires users with the 'sales_agent' role
- Works with the 'wcb2bsa_sales_agent' order meta key

### Date Range Selection
- Default date range: Current month to current date
- Flexible date selection with date pickers
- Inclusive date range filtering

### Order Status Exclusion
- Select which order statuses to exclude from reports
- Checkboxes for all WooCommerce order statuses
- Selections persist after report generation

### Comprehensive Metrics
- **Gross Total**: Total order value including all charges
- **Refund Total**: Total amount refunded
- **Item Subtotal**: Sum of item subtotals (before taxes and fees)
- **Refund Subtotal**: Sum of refunded item subtotals
- **Net Item Subtotal**: Item subtotal minus refund subtotal
- **Commission**: 3% of net item subtotal

### Visual Dashboard
- Color-coded metric widgets
- Detailed order table with all key columns
- Clean, professional WordPress admin UI

## Installation

1. Upload the `woocommerce-sales-dashboard.php` file to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure WooCommerce is installed and active
4. Access the Sales Dashboard from the WordPress admin menu

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- WooCommerce (latest version recommended)
- Users with 'sales_agent' role (for agent-specific reports)

## Usage

1. Navigate to **Sales Dashboard** in the WordPress admin menu
2. Select a sales agent (or leave as "All Sales Agents")
3. Choose your date range
4. Optionally exclude order statuses by checking the appropriate boxes
5. Click "Generate Report" to view the results

## Security Features

- Nonce verification for form submissions
- Proper data sanitization and escaping
- Direct access prevention
- WooCommerce dependency check

## Calculations

### Net Item Subtotal
```
Net Item Subtotal = Item Subtotal - Refund Subtotal
(Minimum value: 0)
```

### Commission
```
Commission = Net Item Subtotal × 3%
```

## Database Queries

The plugin uses optimized WordPress queries to:
- Fetch orders with proper filtering
- Retrieve refund information
- Calculate item totals accurately

## Support

For issues, questions, or contributions, please visit:
https://github.com/yilmaz852/salesagent

## License

GPL v2 or later
https://www.gnu.org/licenses/gpl-2.0.html

## Changelog

### 1.0.0
- Initial release
- Sales agent filtering
- Date range selection
- Order status exclusion
- Comprehensive metric calculations
- Visual dashboard widgets
- Detailed order table
- Security features (nonce, sanitization)