# Standalone Sales Agent Dashboard - v2.1

## Overview

Version 2.1 introduces a completely standalone frontend dashboard for sales agents that is completely separate from the WordPress admin area. This provides a clean, simple, and focused interface for sales agents to manage their customers and earnings.

## Key Features

### 1. Standalone Frontend Pages

Sales agents are NO LONGER redirected to WordPress admin. Instead, they access their own custom dashboard at:

```
https://yoursite.com/sales-agent-dashboard/
```

### 2. Three Main Pages

#### Home Dashboard (`/sales-agent-dashboard/`)
- Welcome message with agent name
- Statistics cards showing:
  - Number of assigned customers
  - Personal commission rate
- Quick start guide

#### My Customers (`/sales-agent-dashboard/customers/`)
- Complete list of assigned customers
- Customer details: name, email, order count, total spent
- "Act as Customer" button to switch accounts
- "Shop Now" button for quick access
- Visual indicator when acting as a customer

#### My Earnings (`/sales-agent-dashboard/earnings/`)
- Date range filter (default: current month)
- Summary statistics:
  - Total sales (net)
  - Total commission earned
- Detailed order table with:
  - Order ID
  - Order date
  - Order status (NEW!)
  - Net subtotal
  - Commission amount

### 3. Clean, Modern Design

The new dashboard features:
- **Professional styling** - Clean, modern interface
- **Responsive grid layout** - Works on all devices
- **Color-coded stats** - Blue for customers, cyan for sales
- **Easy navigation** - Top navigation bar with logout
- **No clutter** - Focused on essential features only

## Technical Implementation

### URL Structure

The dashboard uses custom rewrite rules:

```php
/sales-agent-dashboard/          → Home page
/sales-agent-dashboard/customers/ → Customer management
/sales-agent-dashboard/earnings/  → Earnings report
```

### Rewrite Rules

WordPress rewrite API is used to create virtual pages:

```php
add_rewrite_rule('^sales-agent-dashboard/?$', 'index.php?sales_agent_dashboard=1', 'top');
add_rewrite_rule('^sales-agent-dashboard/customers/?$', 'index.php?sales_agent_dashboard=customers', 'top');
add_rewrite_rule('^sales-agent-dashboard/earnings/?$', 'index.php?sales_agent_dashboard=earnings', 'top');
```

### Template System

Custom PHP functions render the HTML:

- `woo_sales_agent_render_frontend_page()` - Main template wrapper
- `woo_render_frontend_dashboard_home()` - Home page content
- `woo_render_frontend_dashboard_customers()` - Customers page content
- `woo_render_frontend_dashboard_earnings()` - Earnings page content

### Security

- Login requirement: Non-logged-in users redirected to login
- Role verification: Only sales_agent role can access
- Nonce verification: All form submissions verified
- User meta validation: Customer assignments verified before switching

## Installation & Setup

### 1. Activate Plugin

After updating, **deactivate and reactivate** the plugin to flush rewrite rules:

```
WordPress Admin → Plugins → Deactivate "WooCommerce Sales Agent System"
WordPress Admin → Plugins → Activate "WooCommerce Sales Agent System"
```

Or via WP-CLI:

```bash
wp plugin deactivate woocommerce-sales-dashboard
wp plugin activate woocommerce-sales-dashboard
```

### 2. Test Access

Log in as a sales agent and you should be automatically redirected to:

```
https://yoursite.com/sales-agent-dashboard/
```

### 3. Verify Pages

Test each page by clicking navigation links:
- Dashboard (home)
- My Customers
- My Earnings

## User Experience Flow

### Login Flow

```
1. Sales agent logs in to WordPress
   ↓
2. Plugin detects sales_agent role
   ↓
3. Redirects to /sales-agent-dashboard/
   ↓
4. Shows welcome page with stats
```

### Customer Management Flow

```
1. Click "My Customers" in navigation
   ↓
2. View list of assigned customers
   ↓
3. Click "Act as Customer"
   ↓
4. Warning notice appears (orange banner)
   ↓
5. Click "Shop Now"
   ↓
6. Browse shop as customer
   ↓
7. Add items to cart
   ↓
8. Complete checkout
   ↓
9. Order created for customer
   ↓
10. Order tagged with sales agent ID
   ↓
11. Commission calculated automatically
```

### Earnings Review Flow

```
1. Click "My Earnings" in navigation
   ↓
2. Select date range
   ↓
3. Click "View Earnings"
   ↓
4. See summary stats (sales + commission)
   ↓
5. Review detailed order table
   ↓
6. Check order statuses and amounts
```

## Design Elements

### Color Scheme

- **Primary Blue**: `#2271b1` - Buttons, headers
- **Success Green**: `#00a32a` - Positive stats
- **Warning Orange**: `#dba617` - Warnings
- **Error Red**: `#d63638` - Logout, errors
- **Background**: `#f0f0f1` - Page background
- **White**: `#ffffff` - Cards, content areas

### Typography

- **Font Family**: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto
- **Header**: 28px, weight 400
- **Stat Value**: 36px, weight 600
- **Body**: 14-16px, line-height 1.6

### Layout

- **Max Width**: 1200px centered
- **Grid**: Auto-fit columns with 250px minimum
- **Card Padding**: 25px
- **Border Radius**: 8px for cards, 3px for buttons
- **Shadows**: Subtle shadows for depth

## Navigation Menu

Top navigation bar includes:

```
[Dashboard] [My Customers] [My Earnings] ... [Logout]
```

- **Active page**: Darker blue background
- **Logout**: Right-aligned, red color
- **Hover effect**: Darker shade on hover

## Statistics Cards

Home page and earnings page use consistent card design:

```css
.stat-card {
    background: white;
    padding: 25px;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
```

## Tables

All tables use consistent styling:

- **Header**: Light gray background, bold text
- **Rows**: Hover effect for better UX
- **Borders**: Subtle borders between rows
- **Price**: WooCommerce formatted prices

## Notices

Three types of notices:

1. **Success** (green): Action completed successfully
2. **Warning** (yellow): Acting as customer mode
3. **Info** (blue): General information

## Mobile Responsiveness

The dashboard is fully responsive:

- **Desktop**: Full grid layout, all features visible
- **Tablet**: Adjusted grid, maintains functionality
- **Mobile**: Stacked layout, touch-friendly buttons

## Troubleshooting

### Dashboard shows 404

**Solution**: Flush rewrite rules

```php
// Via code (temporary)
flush_rewrite_rules();

// Or deactivate/activate plugin
```

### Redirect not working

**Solution**: Check user role

```php
$user = wp_get_current_user();
var_dump($user->roles); // Should include 'sales_agent'
```

### Pages not styling correctly

**Solution**: Clear browser cache and check if CSS is loaded

### Customer switching not working

**Solution**: Check user meta

```php
$acting_as = get_user_meta($user_id, '_acting_as_customer', true);
var_dump($acting_as); // Should show customer ID when active
```

## Customization

### Change Colors

Edit the `<style>` section in `woo_sales_agent_render_frontend_page()`:

```php
.dashboard-header {
    background: #YOUR_COLOR; // Change header color
}
```

### Add Custom Page

1. Add rewrite rule:
```php
add_rewrite_rule('^sales-agent-dashboard/reports/?$', 'index.php?sales_agent_dashboard=reports', 'top');
```

2. Add to template handler:
```php
elseif ($page === 'reports') {
    woo_render_frontend_dashboard_reports($user);
}
```

3. Create render function:
```php
function woo_render_frontend_dashboard_reports($user) {
    // Your custom page content
}
```

### Modify Stats

Edit `woo_render_frontend_dashboard_home()`:

```php
// Add new stat card
echo '<div class="stat-card">';
echo '<h3>Your Custom Stat</h3>';
echo '<div class="stat-value">' . $your_value . '</div>';
echo '</div>';
```

## Comparison: Admin vs Standalone

| Feature | WordPress Admin (Old) | Standalone Dashboard (New) |
|---------|----------------------|---------------------------|
| URL | wp-admin/admin.php?page=... | /sales-agent-dashboard/ |
| Design | WordPress admin theme | Custom clean design |
| Navigation | WordPress sidebar | Custom top nav |
| Complexity | Many options, complex | Simple, focused |
| Branding | WordPress branding | Can be fully customized |
| Mobile | Admin responsive | Fully optimized |
| Access | Requires admin access | Frontend access only |
| Performance | Loads admin scripts | Minimal, fast loading |

## Benefits of Standalone Dashboard

1. **Cleaner UX**: No WordPress admin clutter
2. **Focused**: Only what sales agents need
3. **Customizable**: Easy to brand and style
4. **Faster**: No admin overhead
5. **Secure**: No access to admin features
6. **Professional**: Custom, branded experience
7. **Mobile-friendly**: Optimized for all devices

## Future Enhancements

Potential additions:

- **Charts/Graphs**: Visual representation of earnings
- **Export**: Download earnings as CSV/PDF
- **Notifications**: Email alerts for new assignments
- **Calendar**: View earnings by month/year
- **Goals**: Set and track sales targets
- **Notes**: Add notes to customer accounts
- **Search**: Search and filter customers
- **Bulk Actions**: Manage multiple customers

## Code Location

All frontend dashboard code is in the main plugin file:

```
woocommerce-sales-dashboard.php
Lines: 365-803 (approximately)
```

Key functions:
- Lines 365-377: Rewrite rules setup
- Lines 379-423: Template rendering
- Lines 425-480: Home page
- Lines 482-554: Customers page
- Lines 556-629: Earnings page

## Debugging

Enable WordPress debugging:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Check debug.log for errors:

```
/wp-content/debug.log
```

## Version History

### v2.1 (Current)
- Added standalone frontend dashboard
- Removed WordPress admin dependency
- Custom clean design
- Three main pages
- Mobile responsive
- Professional styling

### v2.0
- Added sales agent features
- Customer assignment
- Commission tracking
- Act as customer
- User meta session management

### v1.0
- Basic admin dashboard
- Commission calculation
- Report generation

## Support

For issues with the standalone dashboard:

1. Check rewrite rules are flushed
2. Verify user has sales_agent role
3. Test with default WordPress theme
4. Disable other plugins to check conflicts
5. Check browser console for JavaScript errors
6. Review debug.log for PHP errors

## Conclusion

The standalone dashboard provides a professional, focused experience for sales agents without the complexity of WordPress admin. It's fast, secure, mobile-friendly, and fully customizable to match your brand.
