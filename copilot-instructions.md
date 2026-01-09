# Copilot Instructions for salesagent

## Project Overview
This is the **salesagent** repository - a B2B Admin Panel system for WordPress/WooCommerce (V12.0). The project provides comprehensive admin, warehouse, and B2B Pro functionality for managing business-to-business operations within WordPress.

## Tech Stack
- **Platform**: WordPress/WooCommerce
- **Language**: PHP
- **Frontend**: HTML, CSS, JavaScript (WordPress admin context)
- **Database**: WordPress database (MySQL/MariaDB)
- **Architecture**: WordPress plugin architecture with custom rewrite rules and hooks
- **Version Control**: Git/GitHub

## Coding Guidelines

### General Principles
- Write clean, maintainable, and well-documented code
- Follow the DRY (Don't Repeat Yourself) principle
- Use meaningful variable and function names
- Keep functions small and focused on a single responsibility
- Add comments for complex logic, but prefer self-documenting code
- Follow WordPress coding standards and best practices

### Code Style
- Use consistent indentation (spaces or tabs as established in the project)
- Follow WordPress PHP Coding Standards
- Use WordPress function prefixes (b2b_adm_) to avoid namespace conflicts
- Always check `defined('ABSPATH')` at the beginning of PHP files for security
- Include appropriate error handling
- Write defensive code that validates inputs
- Use WordPress capabilities for permission checks (e.g., `current_user_can('manage_options')`)

### WordPress-Specific Guidelines
- Use WordPress hooks and filters appropriately (`add_action`, `add_filter`)
- Follow WordPress rewrite rules patterns for custom URL structures
- Use WordPress database functions and APIs (avoid direct SQL when possible)
- Implement proper security measures (nonces, capability checks, sanitization)
- Use WordPress functions for redirects (`wp_redirect()`) and authentication (`is_user_logged_in()`)
- Always call `flush_rewrite_rules()` responsibly and cache the flush action

### Documentation
- Update README.md when adding new features or changing functionality
- Document public APIs and functions
- Include usage examples where appropriate
- Keep documentation in sync with code changes

### Testing
- Write tests for new functionality
- Ensure tests are clear and maintainable
- Follow existing test patterns in the codebase
- Test edge cases and error conditions

### Version Control
- Write clear, descriptive commit messages
- Keep commits focused and atomic
- Reference issue numbers in commits when applicable
- Keep the main branch stable

## Project Structure
```
salesagent/
├── README.md                   # Project documentation
├── copilot-instructions.md     # This file - Copilot configuration
└── [Future WordPress plugin files]
    ├── Core admin pages: login, dashboard, orders, products, customers
    ├── B2B Pro pages: approvals, b2b-groups, b2b-settings
    ├── Custom rewrite rules for admin panel URLs
    └── Security and logging functionality
```

**Note:** This repository is configured for a WordPress/WooCommerce B2B Admin Panel plugin. The plugin files will be added as the project develops.

## Key Features
- **Custom URL Routing**: Custom rewrite rules for B2B admin pages (e.g., `/b2b-panel/orders`)
- **Access Control**: Role-based access with WordPress capabilities
- **Multi-Module System**: Admin, Warehouse, and B2B Pro modules
- **Product Management**: CRUD operations for B2B products
- **Customer Management**: B2B customer administration
- **Order Management**: B2B order processing and tracking
- **Approval System**: B2B Pro approval workflow
- **Group Management**: B2B customer grouping functionality

## Development Workflow
1. Create feature branches for new work
2. Make small, incremental changes
3. Test changes thoroughly before committing
4. Write clear commit messages
5. Keep pull requests focused and reviewable

## Resources
- [GitHub Repository](https://github.com/yilmaz852/salesagent)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [WooCommerce Developer Documentation](https://woocommerce.com/documentation/plugins/woocommerce/woocommerce-codex/)
- [WordPress Rewrite API](https://developer.wordpress.org/apis/rewrite/)
- [GitHub Copilot Best Practices](https://docs.github.com/en/copilot/get-started/best-practices)
- [Copilot Coding Agent Guide](https://docs.github.com/en/copilot/how-tos/use-copilot-agents/coding-agent)

## Notes for Copilot
- This is a WordPress/WooCommerce B2B plugin project (V12.0)
- Always follow WordPress security best practices (check ABSPATH, validate capabilities, sanitize inputs)
- Use WordPress-specific functions and APIs whenever possible
- Maintain backward compatibility with WordPress and WooCommerce standards
- Consider performance implications, especially with database queries
- Test all custom rewrite rules thoroughly
- Ensure proper role and capability checks for all admin functions
- Follow the established function naming convention (b2b_adm_ prefix)
- When suggesting changes, consider long-term maintainability within WordPress ecosystem
- Always explain significant architectural or design decisions in the WordPress context
