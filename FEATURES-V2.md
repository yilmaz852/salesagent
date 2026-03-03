# WooCommerce Sales Agent System v2.0 - New Features

## Overview

Version 2.0 introduces a complete sales agent management system with customer assignment, commission tracking, and the ability for sales agents to place orders on behalf of their customers.

## New Features

### 1. Sales Agent Dashboard

**What it does:**
- Custom dashboard for users with the `sales_agent` role
- Displays quick statistics (customer count, commission rate)
- Quick action buttons to view customers and earnings

**Access:**
- Menu: "My Dashboard" (appears for sales agents only)
- URL: `admin.php?page=sales-agent-dashboard`
- Icon: dashicons-businessman

**Features:**
- Automatic redirect on login (sales agents go directly to their dashboard)
- Shows number of assigned customers
- Shows their personal commission rate
- Quick links to customers and earnings

### 2. Login Redirect

**What it does:**
- Automatically redirects sales agents to their dashboard when they log in
- Prevents confusion by taking them directly to their workspace

**How it works:**
- Checks if user has `sales_agent` role
- Redirects to `sales-agent-dashboard` page
- Uses session flag to prevent redirect loops

### 3. Customer Assignment System

**What it does:**
- Allows administrators to assign customers to specific sales agents
- Customers are assigned via user profile edit page

**How to use:**
1. Go to Users → All Users
2. Click on a customer (non-admin, non-sales-agent user)
3. Scroll to "Sales Agent Settings" section
4. Select a sales agent from the dropdown
5. Save the profile

**Technical details:**
- User meta key: `assigned_sales_agent`
- Stores the sales agent's user ID
- Only admins can assign customers

### 4. My Customers Page

**What it does:**
- Shows all customers assigned to the logged-in sales agent
- Provides ability to "act as customer" to place orders on their behalf

**Access:**
- Menu: "My Dashboard → My Customers"
- URL: `admin.php?page=sales-agent-customers`

**Features:**
- Lists all assigned customers
- Shows customer email, order count, and total spent
- "Act as Customer" button to switch to customer view
- "Shop Now" button to go directly to the shop

**Customer Table Columns:**
1. Customer name
2. Email address
3. Total orders
4. Total amount spent
5. Actions (Act as Customer, Shop Now)

### 5. Act as Customer Feature

**What it does:**
- Allows sales agents to impersonate their assigned customers
- Places orders on behalf of customers
- Orders are automatically tagged with the sales agent

**How it works:**
1. Sales agent clicks "Act as Customer" button
2. System stores customer ID in WooCommerce session
3. Sales agent can shop as that customer
4. Cart, checkout, and orders are created for the customer
5. Orders are automatically assigned to the sales agent (for commission tracking)

**Visual feedback:**
- Orange banner appears at top of site when acting as customer
- Shows which customer they're acting as
- Provides "Exit Customer View" link

**Security:**
- Only works for customers assigned to that sales agent
- Uses WordPress nonces for security
- Stores original agent ID to prevent confusion

### 6. My Earnings Page

**What it does:**
- Shows commission earnings for the logged-in sales agent
- Filtered to show only their own orders and commissions

**Access:**
- Menu: "My Dashboard → My Earnings"
- URL: `admin.php?page=sales-agent-earnings`

**Features:**
- Date range selector
- Shows total sales (net subtotal)
- Shows total commission earned
- Detailed order-by-order breakdown
- Uses the agent's personal commission rate

**Calculations:**
- Uses the commission rate from the agent's profile
- Calculates net subtotal (items - refunds)
- Commission = Net Subtotal × Commission Rate

### 7. Commission Rate Field

**What it does:**
- Allows setting a custom commission rate for each sales agent
- Default is 3% if not set

**How to use:**
1. Go to Users → All Users
2. Click on a sales agent
3. Scroll to "Sales Agent Settings" section
4. Enter commission rate (e.g., 5 for 5%)
5. Save the profile

**Technical details:**
- User meta key: `sales_agent_commission_rate`
- Stored as decimal (e.g., 3 = 3%)
- Used in earnings calculations
- Shown on agent dashboard

### 8. Enhanced Admin Dashboard

**What it does:**
- Original sales dashboard remains for administrators
- Shows all agents and comprehensive reports

**Access:**
- Menu: "Sales Dashboard" (only visible to admins)
- URL: `admin.php?page=sales-dashboard`

**Unchanged:**
- All original features remain
- Filter by agent, date, status
- Full commission reports
- All metrics and calculations

## Technical Implementation

### Database Structure

**User Meta Keys:**
- `sales_agent_commission_rate` - Commission percentage for sales agents
- `assigned_sales_agent` - Sales agent ID assigned to customers
- `_sales_agent_redirected` - Temporary flag for login redirect

**Order Meta Keys:**
- `wcb2bsa_sales_agent` - Sales agent ID for the order

**Session Keys:**
- `sales_agent_acting_as_customer` - Customer ID being impersonated
- `sales_agent_original_user` - Original sales agent user ID

### WordPress Hooks Used

**Actions:**
- `admin_init` - Login redirect for sales agents
- `admin_menu` - Register menu pages
- `show_user_profile` - Add custom fields to profile
- `edit_user_profile` - Add custom fields to profile (admin editing)
- `personal_options_update` - Save profile fields (own profile)
- `edit_user_profile_update` - Save profile fields (admin editing)
- `woocommerce_checkout_order_processed` - Assign orders to agent
- `wp_footer` - Show acting-as-customer notice
- `woo_clear_redirect_flag` - Clear redirect flag (scheduled)

**Filters:**
- `woocommerce_cart_hash` - Differentiate cart when acting as customer

### Security Features

**Nonce Verification:**
- All form submissions use WordPress nonces
- Customer switching uses nonces
- Stop switching uses nonces

**Capability Checks:**
- Admin features require `manage_options`
- Sales agent features require `read` capability
- Customer verification before allowing switch

**Data Sanitization:**
- All POST data sanitized with `sanitize_text_field()` or `intval()`
- All output escaped with `esc_html()`, `esc_attr()`, `esc_url()`

## User Roles

### Administrator
- Full access to Sales Dashboard
- Can assign customers to sales agents
- Can set commission rates for sales agents
- Views all reports and data

### Sales Agent
- Access to My Dashboard
- Access to My Customers
- Access to My Earnings
- Can act as assigned customers
- Can place orders on behalf of customers
- Views only their own data

### Customer
- Can be assigned to a sales agent
- Orders placed by their agent are tracked
- Normal WooCommerce customer experience

## Workflow Examples

### Example 1: New Sales Agent Setup

1. **Create User:**
   - Admin creates new user with `sales_agent` role
   
2. **Set Commission Rate:**
   - Admin edits user profile
   - Sets commission rate to 5%
   
3. **Assign Customers:**
   - Admin edits customer profiles
   - Assigns customers to this sales agent
   
4. **Agent Login:**
   - Sales agent logs in
   - Automatically redirected to dashboard
   - Sees their customers and commission rate

### Example 2: Placing Order for Customer

1. **Sales Agent Action:**
   - Goes to My Customers
   - Clicks "Act as Customer" for a specific customer
   
2. **Shopping:**
   - Orange banner appears showing they're acting as customer
   - Clicks "Shop Now" or navigates to shop
   - Adds products to cart
   - Goes through checkout
   
3. **Order Creation:**
   - Order is created for the customer
   - Order is tagged with sales agent ID
   - Commission will be calculated for this agent
   
4. **Exit Customer View:**
   - Clicks "Exit Customer View" in banner
   - Returns to normal agent view

### Example 3: Viewing Earnings

1. **Access Earnings:**
   - Sales agent goes to My Earnings
   
2. **Select Date Range:**
   - Chooses start and end date
   - Clicks "View Earnings"
   
3. **View Results:**
   - Sees total sales and commission
   - Views detailed table of orders
   - Commission calculated using their personal rate

## Comparison with B2B Sales King

**Similar Features:**
- Customer assignment to sales agents
- Place orders on behalf of customers
- Commission tracking
- Sales agent dashboard

**Our Implementation:**
- Integrated with existing dashboard
- Customizable commission rates per agent
- Clean, simple interface
- Full WordPress security standards
- Works with existing WooCommerce orders

## Configuration

### Required Setup

1. **Create Sales Agent Role** (if not exists):
   ```php
   add_role('sales_agent', 'Sales Agent', [
       'read' => true,
       'edit_posts' => false
   ]);
   ```

2. **Create Users:**
   - Create users with `sales_agent` role
   
3. **Set Commission Rates:**
   - Edit each sales agent profile
   - Set their commission rate
   
4. **Assign Customers:**
   - Edit customer profiles
   - Assign to sales agents

### Optional Settings

- Adjust default commission rate (currently 3%)
- Customize redirect behavior
- Modify dashboard widgets
- Add custom fields

## Troubleshooting

### Sales agent not redirected on login
- Check if user has `sales_agent` role
- Clear browser cache
- Check if already on dashboard page

### Cannot act as customer
- Verify customer is assigned to that sales agent
- Check user meta `assigned_sales_agent`
- Verify WooCommerce sessions are working

### Commission not showing correctly
- Check sales agent has commission rate set
- Verify orders have `wcb2bsa_sales_agent` meta
- Check date range in earnings report

### Customer assignment not saving
- Verify admin privileges
- Check for JavaScript errors
- Clear WordPress cache

## API Reference

### Main Functions

```php
// Get customers for an agent
woo_get_agent_customers($agent_id)

// Get customer count
woo_get_agent_customer_count($agent_id)

// Generate earnings report
woo_generate_agent_earnings_report($agent_id, $start_date, $end_date, $commission_rate)

// Get sales agents
woo_get_sales_agents()
```

### User Meta Functions

```php
// Get commission rate
get_user_meta($user_id, 'sales_agent_commission_rate', true)

// Get assigned agent
get_user_meta($customer_id, 'assigned_sales_agent', true)
```

### Session Functions

```php
// Check if acting as customer
WC()->session->get('sales_agent_acting_as_customer')

// Get original agent ID
WC()->session->get('sales_agent_original_user')
```

## Future Enhancements

Potential additions for future versions:
- Email notifications for new assignments
- Sales agent performance analytics
- Customer notes and history
- Team management for sales agents
- Product restrictions per agent
- Automatic customer assignment rules
- Sales targets and goals
- Agent activity logs

## Version History

### v2.0.0
- Added sales agent dashboard
- Added customer assignment system
- Added act as customer feature
- Added earnings page for agents
- Added commission rate per agent
- Added login redirect
- Enhanced security
- Comprehensive documentation

### v1.0.0
- Initial release
- Admin sales dashboard
- Commission calculation
- Report generation
