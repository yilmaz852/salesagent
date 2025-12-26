# WooCommerce Sales Agent System

A comprehensive WordPress plugin for WooCommerce that provides a complete sales agent management system with customer assignment, commission tracking, order placement on behalf of customers, and detailed reporting capabilities.

## Version 2.0 - Major Update

Version 2.0 introduces a complete sales agent ecosystem with customer management and the ability for sales agents to place orders on behalf of their assigned customers, inspired by B2B Sales King.

## Description

The WooCommerce Sales Agent System plugin adds powerful sales agent capabilities to your WordPress/WooCommerce installation:

### For Sales Agents:
- **Personal Dashboard**: Custom dashboard with quick stats and actions
- **Customer Management**: View and manage assigned customers
- **Act as Customer**: Switch to customer view to place orders on their behalf
- **Earnings Tracking**: View personal commission earnings with customizable rates
- **Auto-redirect**: Automatically redirected to dashboard on login

### For Administrators:
- **Comprehensive Reports**: View sales reports for all agents
- **Customer Assignment**: Assign customers to sales agents from user profiles
- **Commission Management**: Set custom commission rates per agent
- **Full Analytics**: Track performance across all agents

## Key Features

### Sales Agent Dashboard
- Quick statistics (customer count, commission rate)
- Easy navigation to customers and earnings
- Automatic redirect on login
- Clean, professional interface

### Customer Assignment
- Assign customers to specific sales agents
- Manage assignments from user profiles
- Track customer relationships
- View customer order history and spending

### Act as Customer
- Sales agents can impersonate their assigned customers
- Place orders on behalf of customers
- Add items to cart as the customer
- Orders automatically tagged with sales agent
- Visual banner shows current customer mode
- Easy exit from customer view

### Commission Tracking
- Custom commission rate per sales agent
- Default 3% if not configured
- Calculate commissions on net subtotals
- Handle refunds correctly
- Date range filtering
- Detailed order-by-order breakdown

### Advanced Reporting
- Filter by sales agent, date range, and order status
- Calculate and display key metrics:
  - Gross Total
  - Refund Total
  - Net Item Subtotal (after refunds)
  - Commission (customizable % of net subtotal)
- Visual metric widgets
- Detailed order-by-order breakdown
- Export-friendly table format

## Installation

1. Upload the `woocommerce-sales-dashboard.php` file to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure WooCommerce is installed and active
4. Create the 'sales_agent' role (see setup instructions below)
5. Configure sales agents and assign customers

## Initial Setup

### 1. Create Sales Agent Role

Add this code temporarily to your theme's functions.php or use a code snippets plugin:

```php
add_role('sales_agent', 'Sales Agent', [
    'read' => true,
    'edit_posts' => false
]);
```

### 2. Create Sales Agent Users

1. Go to Users → Add New
2. Create new user
3. Assign role: Sales Agent
4. Save user

### 3. Set Commission Rates

1. Go to Users → All Users
2. Click on a sales agent
3. Scroll to "Sales Agent Settings"
4. Enter commission rate (e.g., 5 for 5%)
5. Save profile

### 4. Assign Customers to Sales Agents

1. Go to Users → All Users
2. Click on a customer (non-admin, non-sales-agent)
3. Scroll to "Sales Agent Settings"
4. Select assigned sales agent from dropdown
5. Save profile

## Usage

### For Sales Agents

#### Accessing Your Dashboard
- Log in to WordPress
- You'll be automatically redirected to "My Dashboard"
- View your statistics and quick actions

#### Managing Your Customers
1. Click "My Customers" in the menu
2. View list of assigned customers
3. See customer details, orders, and spending

#### Placing Orders for Customers
1. Go to "My Customers"
2. Click "Act as Customer" next to the customer
3. Click "Shop Now" or navigate to the shop
4. Add products to cart as normal
5. Complete checkout
6. Order is created for the customer and tagged with your ID
7. Click "Exit Customer View" when done

#### Viewing Your Earnings
1. Click "My Earnings" in the menu
2. Select date range
3. Click "View Earnings"
4. See your total commission and detailed breakdown

### For Administrators

#### Accessing Sales Dashboard
- Navigate to "Sales Dashboard" in the admin menu
- View comprehensive reports for all agents

#### Generating Reports
1. Select a sales agent (or "All Sales Agents")
2. Choose your date range
3. Optionally exclude order statuses
4. Click "Generate Report"
5. View metrics and detailed order table

#### Managing Sales Agents
- Assign customers from user profile pages
- Set custom commission rates per agent
- Monitor all agent activity and earnings

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- WooCommerce (latest version recommended)
- Users with 'sales_agent' role

## Menu Structure

### For Sales Agents
- **My Dashboard** - Main dashboard with stats and quick actions
  - My Earnings - View commission earnings
  - My Customers - Manage assigned customers

### For Administrators
- **Sales Dashboard** - Comprehensive reports for all agents

## Features in Detail

### Customer Switching (Act as Customer)

When a sales agent activates "Act as Customer" mode:

1. **Visual Feedback**: Orange banner appears at top of site
2. **Shopping**: Can browse and add items to cart as the customer
3. **Checkout**: Completes checkout as the customer
4. **Order Assignment**: Order is automatically assigned to the sales agent
5. **Commission**: Agent earns commission based on their rate
6. **Security**: Only works for assigned customers

### Commission Calculations

```
Net Item Subtotal = Item Subtotal - Refund Subtotal
(Minimum value: 0)

Commission = Net Item Subtotal × Commission Rate
(Rate is per-agent, default 3%)
```

Example with 5% commission rate:
- Order subtotal: $200
- Refund subtotal: $50
- Net subtotal: $150
- Commission: $150 × 5% = $7.50

## Database Schema

### User Meta Keys
- `sales_agent_commission_rate` - Commission percentage (e.g., 3 for 3%)
- `assigned_sales_agent` - Sales agent user ID assigned to customer

### Order Meta Keys
- `wcb2bsa_sales_agent` - Sales agent user ID for the order

### Session Keys (WooCommerce)
- `sales_agent_acting_as_customer` - Customer ID being impersonated
- `sales_agent_original_user` - Original sales agent user ID

## Workflow Examples

### Example 1: New Customer Order via Sales Agent

1. Admin assigns Customer A to Agent B
2. Agent B logs in and sees Customer A in "My Customers"
3. Agent B clicks "Act as Customer" for Customer A
4. Agent B shops and adds products worth $500
5. Agent B completes checkout
6. Order is created:
   - Customer: Customer A
   - Tagged with: Agent B's ID
   - Commission: $500 × Agent B's rate (e.g., 5% = $25)
7. Agent B can view this commission in "My Earnings"

### Example 2: Monthly Commission Report

1. Agent logs into "My Earnings"
2. Selects date range: March 1 - March 31
3. Clicks "View Earnings"
4. Sees:
   - Total sales: $5,000 (net subtotal)
   - Total commission: $250 (at 5% rate)
   - Detailed table of all 20 orders
5. Can export data for personal records

## Security Features

- **Nonce Verification**: All forms and actions use WordPress nonces
- **Capability Checks**: Role-based access control (admin vs agent)
- **Data Sanitization**: All input sanitized with `sanitize_text_field()`, `intval()`
- **Output Escaping**: All output escaped with `esc_html()`, `esc_attr()`, `esc_url()`
- **Prepared Statements**: All database queries use `$wpdb->prepare()`
- **Customer Verification**: Agents can only act as their assigned customers
- **Session Management**: Secure session handling for customer switching
- **Direct Access Prevention**: ABSPATH check
- **WooCommerce Dependency Check**: Validates WooCommerce is active

## Troubleshooting

### Sales agent not redirected to dashboard
- Verify user has 'sales_agent' role
- Clear browser cache
- Check if already on the dashboard page

### Cannot act as customer
- Verify customer is assigned to that sales agent
- Check user profile has correct "Assigned Sales Agent"
- Ensure WooCommerce sessions are working
- Try logging out and back in

### Commission not calculating
- Verify sales agent has commission rate set in profile
- Check orders have 'wcb2bsa_sales_agent' meta key
- Verify date range includes the orders
- Check order statuses are not excluded

### Customer assignment not saving
- Verify you have administrator privileges
- Check for JavaScript errors in browser console
- Clear WordPress cache
- Verify customer user ID is correct

## Comparison with B2B Sales King

Our plugin provides similar functionality with these advantages:

**Similarities:**
- Customer assignment to sales agents
- Place orders on behalf of customers
- Commission tracking
- Sales agent dashboard

**Our Advantages:**
- Cleaner, simpler interface
- Customizable commission rates per agent
- Integrated with standard WordPress/WooCommerce
- Better security implementation
- Detailed earnings reports with date filtering
- No additional dependencies
- Free and open source

## Changelog

### 2.0.0 (Current)
- Added sales agent dashboard with auto-redirect
- Added customer assignment system
- Added "Act as Customer" feature for order placement
- Added custom commission rates per agent
- Added "My Customers" page for agents
- Added "My Earnings" page with filtered reports
- Enhanced security with nonce verification
- Added visual feedback for customer switching
- Comprehensive documentation

### 1.0.0
- Initial release
- Admin sales dashboard
- Sales agent filtering
- Date range selection
- Order status exclusion
- Metric calculations (gross, refund, net, commission)
- Visual dashboard with widgets
- Secure form handling
## Support

For issues, questions, or contributions, please visit:
https://github.com/yilmaz852/salesagent

## License

GPL v2 or later
https://www.gnu.org/licenses/gpl-2.0.html

## Credits

Inspired by B2B Sales King plugin, with custom implementation focused on simplicity, security, and flexibility.

## Future Enhancements

Potential additions for future versions:
- Email notifications for agents when customers are assigned
- Sales performance analytics and charts
- Customer activity notes and history
- Team management for sales agents
- Product-specific commissions
- Automatic customer assignment rules
- Sales targets and goals tracking
- Export reports to CSV/Excel
