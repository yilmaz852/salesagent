# Visual Guide - Sales Agent System v2.0

## User Interface Overview

### For Sales Agents

#### 1. My Dashboard Page

```
┌─────────────────────────────────────────────────────────┐
│ Welcome, John Smith                                     │
│ Your Sales Agent Dashboard                              │
├─────────────────────────────────────────────────────────┤
│                                                          │
│  ┌──────────────────────┐  ┌──────────────────────┐   │
│  │  My Customers        │  │  Commission Rate     │   │
│  │  (Blue Background)   │  │  (Green Background)  │   │
│  │                      │  │                      │   │
│  │       15             │  │       5%             │   │
│  └──────────────────────┘  └──────────────────────┘   │
│                                                          │
│  Quick Actions                                          │
│  [View My Customers] [View My Earnings]                │
└─────────────────────────────────────────────────────────┘
```

#### 2. My Customers Page

```
┌─────────────────────────────────────────────────────────────────────────┐
│ My Customers                                                            │
│ Manage customers assigned to you                                        │
├─────────────────────────────────────────────────────────────────────────┤
│ Customer      │ Email           │ Total Orders │ Total Spent │ Actions │
├───────────────┼─────────────────┼──────────────┼─────────────┼─────────┤
│ Jane Doe      │ jane@email.com  │ 5            │ $1,250      │ [Act as]│
│               │                 │              │             │ [Shop]  │
├───────────────┼─────────────────┼──────────────┼─────────────┼─────────┤
│ Bob Smith     │ bob@email.com   │ 3            │ $850        │ [Act as]│
│               │                 │              │             │ [Shop]  │
└─────────────────────────────────────────────────────────────────────────┘
```

#### 3. Acting as Customer - Visual Banner

When "Act as Customer" is clicked:

```
┌──────────────────────────────────────────────────────────────┐
│ 🔶 Sales Agent Mode: Shopping as Jane Doe (jane@email.com)  │
│                                           [Exit Customer View]│
└──────────────────────────────────────────────────────────────┘
↓ (Orange banner at top of website)
┌──────────────────────────────────────────────────────────────┐
│ Your Store Logo                                       [Cart] │
├──────────────────────────────────────────────────────────────┤
│                                                               │
│                  Shop Products Here                          │
│                  (Agent is shopping as Jane Doe)             │
│                                                               │
└──────────────────────────────────────────────────────────────┘
```

#### 4. My Earnings Page

```
┌─────────────────────────────────────────────────────────────┐
│ My Earnings                                                 │
│ View your commission earnings                               │
├─────────────────────────────────────────────────────────────┤
│ Start Date: [2024-03-01] End Date: [2024-03-31] [View]    │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  ┌────────────────────────┐  ┌──────────────────────────┐ │
│  │ Total Sales (Net)      │  │ Total Commission (5%)    │ │
│  │ (Cyan)                 │  │ (Pink)                   │ │
│  │                        │  │                          │ │
│  │    $10,000             │  │    $500                  │ │
│  └────────────────────────┘  └──────────────────────────┘ │
│                                                              │
│  Order ID │ Date       │ Status     │ Net      │ Commission│
│  ─────────┼────────────┼────────────┼──────────┼───────────│
│  #1234    │ 2024-03-05 │ Completed  │ $250     │ $12.50   │
│  #1235    │ 2024-03-10 │ Completed  │ $500     │ $25.00   │
│  #1236    │ 2024-03-15 │ Processing │ $350     │ $17.50   │
└─────────────────────────────────────────────────────────────┘
```

### For Administrators

#### 1. Sales Dashboard (Original - Enhanced)

```
┌─────────────────────────────────────────────────────────────┐
│ Sales Dashboard                                             │
│ View sales report including calculations and commissions    │
├─────────────────────────────────────────────────────────────┤
│ Select Sales Agent: [All Sales Agents ▼]                   │
│ Start Date: [2024-03-01] End Date: [2024-03-31]           │
│ Exclude Order Statuses:                                     │
│   ☐ Pending  ☐ Failed  ☐ Cancelled  ☐ Refunded           │
│ [Generate Report]                                           │
├─────────────────────────────────────────────────────────────┤
│ Results showing all agents...                               │
└─────────────────────────────────────────────────────────────┘
```

#### 2. User Profile - Sales Agent Settings (Admin View)

```
┌─────────────────────────────────────────────────────────────┐
│ Edit User: John Smith                                       │
├─────────────────────────────────────────────────────────────┤
│ Username: john_smith                                        │
│ Email: john@email.com                                       │
│ Role: Sales Agent                                           │
│                                                              │
│ ─── Sales Agent Settings ────────────────────────────────  │
│                                                              │
│ Commission Rate (%): [5.00]                                │
│ Commission percentage for this sales agent (e.g., 3 for 3%)│
│                                                              │
│ [Update User]                                               │
└─────────────────────────────────────────────────────────────┘
```

#### 3. User Profile - Customer Assignment (Admin View)

```
┌─────────────────────────────────────────────────────────────┐
│ Edit User: Jane Doe (Customer)                             │
├─────────────────────────────────────────────────────────────┤
│ Username: jane_doe                                          │
│ Email: jane@email.com                                       │
│ Role: Customer                                              │
│                                                              │
│ ─── Sales Agent Settings ────────────────────────────────  │
│                                                              │
│ Assigned Sales Agent: [John Smith ▼]                       │
│ Assign this customer to a sales agent                       │
│                                                              │
│ [Update User]                                               │
└─────────────────────────────────────────────────────────────┘
```

## Menu Structure Comparison

### Version 1.0 (Old)

```
WordPress Admin Menu:
├── WooCommerce
│   └── ...
├── Sales Dashboard (All users with manage_woocommerce)
└── ...
```

### Version 2.0 (New)

```
WordPress Admin Menu (Sales Agent View):
├── WooCommerce (limited)
├── My Dashboard ★ NEW
│   ├── My Dashboard (main page)
│   ├── My Earnings ★ NEW
│   └── My Customers ★ NEW
└── ...

WordPress Admin Menu (Admin View):
├── WooCommerce
│   └── ...
├── Sales Dashboard (enhanced)
└── ...
```

## Workflow Diagrams

### Workflow 1: Customer Assignment

```
Admin Action                     System Action
───────────────                 ─────────────

1. Go to Users
   → All Users

2. Click on Customer          
   (Jane Doe)

3. Scroll to                    → Shows "Sales Agent Settings"
   "Sales Agent Settings"

4. Select Sales Agent           → Dropdown lists all agents
   (John Smith)

5. Click Update User            → Saves: assigned_sales_agent = John's ID
                                → Customer now assigned to John
```

### Workflow 2: Place Order for Customer

```
Sales Agent Action              System Action                 Result
──────────────────             ─────────────                ──────

1. Login                        → Check role: sales_agent    
                                → Redirect to dashboard

2. Click "My Customers"         → Query customers where       Shows assigned
                                  assigned_sales_agent =      customers
                                  agent's ID

3. Click "Act as Customer"      → Verify assignment          Orange banner
   for Jane Doe                 → Store in session:          appears
                                  - acting_as_customer = Jane
                                  - original_user = John

4. Click "Shop Now"             → Load shop page             Normal shop
   Browse products              → Cart belongs to Jane       experience

5. Add to cart                  → Items added to Jane's      Cart updates
                                  cart

6. Complete checkout            → Create order:              Order created
                                  - Customer: Jane Doe       #1234
                                  - Agent: John Smith        
                                  - Commission: 5%

7. Click "Exit Customer"        → Clear session variables    Back to normal
                                → Return to agent view
```

### Workflow 3: View Earnings

```
Sales Agent Action              System Action                Display
──────────────────             ─────────────                ───────

1. Click "My Earnings"          → Load earnings page         Date selector

2. Select date range            → Remember selections        Form filled
   March 1 - March 31

3. Click "View Earnings"        → Query orders where:        
                                  - agent = John's ID
                                  - date in range
                                → Calculate for each:
                                  - Net subtotal
                                  - Commission (agent's rate)

4. View results                 → Display:                   Widgets +
                                  - Total sales              Table
                                  - Total commission
                                  - Order table
```

## Security Flow

### Customer Switching Security

```
Step 1: Verify Agent           Step 2: Verify Customer       Step 3: Create Session
────────────────────          ──────────────────────        ──────────────────────

User clicks "Act as           System checks:                 If verified:
Customer" for Jane            1. Is user sales_agent?        - Store in WC session
                              2. Is Jane assigned to         - Show banner
Generates nonce:                 this agent?                 - Allow shopping
switch_customer_123           3. Nonce valid?                
                                                             If not verified:
                              Query DB:                      - Show error
                              assigned_sales_agent           - Prevent switching
                              meta for Jane                  - Log attempt
```

## Commission Calculation Examples

### Example 1: Simple Order

```
Order Details:                  Calculation:
─────────────                  ─────────────

Product A: $100                Item Subtotal: $100
Product B: $50                               + $50
                                           = $150
Subtotal: $150                 
Shipping: $10                  Refund Subtotal: $0
Tax: $12                       
Total: $172                    Net Subtotal: $150 - $0 = $150

                               Commission: $150 × 5% = $7.50
Refunds: None
```

### Example 2: Order with Partial Refund

```
Order Details:                  Calculation:
─────────────                  ─────────────

Product A: $100                Item Subtotal: $100
Product B: $50                               + $50
                                           = $150
Subtotal: $150                 
Shipping: $10                  Refund: Product B refunded
Tax: $12                       Refund Subtotal: $50
Total: $172                    
                               Net Subtotal: $150 - $50 = $100
Refund: -$54                   
(Product B + proportional)     Commission: $100 × 5% = $5.00
```

### Example 3: Full Refund

```
Order Details:                  Calculation:
─────────────                  ─────────────

Product A: $100                Item Subtotal: $100
Product B: $50                               + $50
                                           = $150
Subtotal: $150                 
Shipping: $10                  Refund Subtotal: $150
Tax: $12                       (All items refunded)
Total: $172                    
                               Net Subtotal: $150 - $150 = $0
Refund: -$172                  (Minimum 0)
(Full refund)                  
                               Commission: $0 × 5% = $0.00
```

## Color Coding Reference

### Dashboard Widgets

- 🟦 **Blue (#2271b1)**: Customer count statistics
- 🟩 **Green (#00a32a)**: Commission rate display
- 🟨 **Cyan (#00BCD4)**: Net sales totals
- 🟥 **Pink (#E91E63)**: Commission earnings

### Notices and Alerts

- 🟧 **Orange (#ff9800)**: Acting as customer banner (warning level)
- 🟢 **Green**: Success messages
- 🔴 **Red**: Error messages
- 🔵 **Blue**: Info messages

## Quick Reference Card

### For Sales Agents

| I want to...                    | Go to...              | Action...                    |
|--------------------------------|----------------------|------------------------------|
| See my dashboard               | My Dashboard         | Auto-redirected on login     |
| View assigned customers        | My Customers         | Lists all assigned           |
| Shop for a customer            | My Customers         | Click "Act as Customer"      |
| View my commissions            | My Earnings          | Select date range, view      |
| Stop acting as customer        | Orange banner        | Click "Exit Customer View"   |

### For Administrators

| I want to...                    | Go to...              | Action...                    |
|--------------------------------|----------------------|------------------------------|
| Assign customer to agent       | Users → Edit User    | Select agent in dropdown     |
| Set agent commission rate      | Users → Edit Agent   | Enter rate in profile        |
| View all agent reports         | Sales Dashboard      | Select agent, generate       |
| See all commissions            | Sales Dashboard      | Leave "All Agents" selected  |

## Icon Reference

Menu icons used:
- **My Dashboard**: `dashicons-businessman` (👨‍💼)
- **Sales Dashboard**: `dashicons-chart-bar` (📊)

## Tips and Best Practices

### For Sales Agents

1. **Always exit customer view** after placing an order
2. **Check the orange banner** to confirm who you're acting as
3. **Review your earnings regularly** to track performance
4. **Communicate with your customers** about orders placed on their behalf

### For Administrators

1. **Set commission rates immediately** when creating new agents
2. **Review assignments regularly** to ensure proper distribution
3. **Monitor agent activity** through the sales dashboard
4. **Back up customer assignments** (user meta data)

### For Developers

1. **Test customer switching thoroughly** before production
2. **Verify WooCommerce sessions** are working correctly
3. **Check user meta keys** are saving properly
4. **Monitor order meta** to ensure agent assignment

## Common Scenarios - Quick Solutions

| Scenario                          | Solution                                      |
|----------------------------------|-----------------------------------------------|
| Agent can't see customers        | Check assigned_sales_agent user meta          |
| Commission shows as 0%           | Set commission rate in agent profile          |
| Can't switch to customer         | Verify customer is assigned to that agent     |
| Orders not showing commission    | Check wcb2bsa_sales_agent order meta          |
| Dashboard not appearing          | Verify user has 'sales_agent' role            |
| Banner won't disappear           | Click "Exit Customer View" or clear sessions  |
