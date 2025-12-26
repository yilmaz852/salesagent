# Quick Start Guide

## What This Plugin Does

The WooCommerce Sales Dashboard plugin adds a powerful reporting page to your WordPress admin that helps you track sales performance, calculate commissions, and analyze order data.

## Installation

1. **Upload the plugin:**
   ```
   Upload woocommerce-sales-dashboard.php to:
   /wp-content/plugins/
   ```

2. **Activate:**
   - Go to WordPress Admin → Plugins
   - Find "WooCommerce Sales Dashboard"
   - Click "Activate"

3. **Access:**
   - Look for "Sales Dashboard" in the WordPress admin menu
   - It appears below the WooCommerce menu with a chart icon 📊

## Before First Use

### Required Setup

1. **Create Sales Agent Role** (if not exists)
   ```php
   // Add this code temporarily to your theme's functions.php
   add_role('sales_agent', 'Sales Agent', [
       'read' => true,
       'edit_posts' => false
   ]);
   ```

2. **Assign Users to Sales Agent Role**
   - Go to Users in WordPress admin
   - Edit user
   - Change role to "Sales Agent"

3. **Tag Orders with Sales Agents**
   - Orders need a custom meta field: `wcb2bsa_sales_agent`
   - Value should be the sales agent's user ID
   - This can be done via a checkout plugin or programmatically

## Using the Dashboard

### Step 1: Access the Dashboard
Navigate to **Sales Dashboard** in the admin menu.

### Step 2: Select Filters

**Sales Agent:**
- Choose a specific agent to see their orders only
- Or select "All Sales Agents" to see everything

**Date Range:**
- Start Date: When to begin the report
- End Date: When to end the report
- Default: Current month to today

**Exclude Order Statuses:**
- Check any order statuses you want to exclude
- Example: Exclude "Cancelled" and "Failed" orders
- Your selections are remembered when you regenerate the report

### Step 3: Generate Report
Click the **"Generate Report"** button.

## Understanding the Report

### Top Row: Key Metrics (Widgets)

```
┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐ ┌─────────────────┐
│  Gross Total    │ │  Refund Total   │ │ Net Subtotal    │ │  Commission     │
│  (Green)        │ │  (Orange)       │ │  (Cyan)         │ │  (Pink)         │
│  $10,000        │ │  $500           │ │  $9,000         │ │  $270 (3%)      │
└─────────────────┘ └─────────────────┘ └─────────────────┘ └─────────────────┘
```

- **Gross Total**: Total of all orders (including taxes, shipping, etc.)
- **Refund Total**: Total amount refunded to customers
- **Net Item Subtotal**: Item subtotal minus refund subtotal (what commission is based on)
- **Commission**: 3% of the Net Item Subtotal

### Bottom: Detailed Table

Each row shows one order with:
1. Order ID (clickable link)
2. Order Date
3. Order Status (Processing, Completed, etc.)
4. Gross Total (order total)
5. Refund Amount (if any)
6. Item Subtotal (items before taxes/fees)
7. Refund Subtotal (items refunded)
8. Net Item Subtotal (items - refunds)
9. Commission (3% of net)

## Calculations Explained

### Example Order

**Order #1234:**
- 2 products @ $50 each = $100 (Item Subtotal)
- Shipping: $10
- Tax: $8
- **Total Order: $118** (Gross Total)

**Later, customer returns 1 product:**
- Refund: $50 (item) + proportional tax
- **Total Refund: $54** (Refund Total)
- **Refund Subtotal: $50** (item only, no shipping/tax)

**Calculations:**
- Net Item Subtotal = $100 - $50 = $50
- Commission = $50 × 3% = $1.50

**Why these calculations?**
- We use Item Subtotal (not Gross Total) because commission shouldn't include shipping and taxes
- We subtract refunds because you don't earn commission on returned items
- 3% is calculated on the final Net Item Subtotal

## Common Scenarios

### Scenario 1: Order with No Refunds
```
Item Subtotal: $200
Refund Subtotal: $0
Net Item Subtotal: $200
Commission: $6.00 (3%)
```

### Scenario 2: Order with Partial Refund
```
Item Subtotal: $200
Refund Subtotal: $50
Net Item Subtotal: $150
Commission: $4.50 (3%)
```

### Scenario 3: Order with Full Refund
```
Item Subtotal: $200
Refund Subtotal: $200
Net Item Subtotal: $0
Commission: $0.00
```

## Tips

✅ **Run Monthly Reports**: Set date range to the first and last day of each month

✅ **Compare Agents**: Run the report for each agent individually to compare performance

✅ **Exclude Test Orders**: Check "Pending Payment" and "Failed" to exclude incomplete orders

✅ **Export Data**: Copy the table and paste into Excel or Google Sheets for further analysis

✅ **Verify Numbers**: Click on order IDs in the table to view the actual orders in WooCommerce

## Troubleshooting

### "No users found with the role 'sales_agent'"
→ You need to create the sales_agent role and assign users to it

### "No orders found for the selected filters"
→ Check that:
- Orders exist in the date range
- Orders have the wcb2bsa_sales_agent meta key (if filtering by agent)
- You haven't excluded all order statuses

### Numbers seem wrong
→ Remember:
- Calculations use Item Subtotal, not Gross Total
- Refunds are subtracted from subtotals
- Commission is always 3% of Net Item Subtotal

### Plugin not appearing in menu
→ Make sure:
- WooCommerce is installed and active
- You have the "manage_woocommerce" capability
- Plugin is activated

## Need Help?

- Check TECHNICAL.md for detailed architecture
- Check IMPLEMENTATION.md for development details
- Check README.md for full documentation
- Visit: https://github.com/yilmaz852/salesagent

## Summary

This plugin gives you a complete sales dashboard with:
- ✅ Easy filtering by agent, date, and status
- ✅ Automatic commission calculation (3%)
- ✅ Refund handling
- ✅ Visual metric widgets
- ✅ Detailed order breakdown
- ✅ Secure and reliable

Perfect for sales managers, accountants, and business owners who need accurate sales reporting!
