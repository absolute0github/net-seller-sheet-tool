# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Net Seller Sheet is a WordPress plugin for calculating real estate net proceeds with PDF generation. It features county-specific fees, tiered commission rates, tax proration calculations, and professional PDF output with company branding.

**Requirements:** PHP 8.0+, WordPress 6.0+, MySQL 5.7+

## Common Commands

```bash
# Install dependencies (required before first use)
composer install

# Install without dev dependencies (production)
composer install --no-dev

# Run PHPUnit tests
composer test

# Run PHPStan static analysis
composer stan
```

## Architecture

### Entry Point & Initialization Flow

1. `net-seller-sheet.php` - Defines constants, loads Composer autoloader
2. `plugins_loaded` hook - Creates `NSS_Plugin` instance and calls `run()`
3. `NSS_Plugin::run()` - Initializes database, registers capabilities, instantiates Admin/Shortcodes/API/Updater components

### Key Directories

```
includes/
├── calculations/    # Financial calculation engine (precision math, taxes, commissions)
├── models/          # NSS_Sheet and NSS_Fee - database CRUD operations
├── helpers/         # NSS_Security (nonces, sanitization) and NSS_Formatting
├── admin/           # Admin interface, AJAX handlers, and admin page templates
├── frontend/        # Shortcode handlers and user-facing templates
├── api/             # REST API endpoint registration
├── pdf/             # mPDF integration for PDF generation
└── database/        # Schema creation and table management
```

### Calculation Engine (includes/calculations/)

- `NSS_Calculator` - Orchestrates all calculation operations
- `NSS_Precision_Math` - Wrapper around brick/math library for guaranteed decimal accuracy in financial calculations
- `NSS_Tax_Proration` - Handles date-based tax calculations
- `NSS_Property_Value` - Tiered commission structure lookup

### Database Schema (7 tables, prefix: `wp_nss_`)

- `sheets` - Main calculation sheets (stores tax/commission/fees as JSON)
- `conveyance_fees` - Recording fees by county
- `tax_rates` - Property tax rates by county
- `property_value_rates` - Tiered commission rates (4 default tiers seeded on activation)
- `title_closing_fees` - Title closing fees by county
- `title_exam_fees` - Title examination fees by county
- `static_title_fees` - Fixed fees (courier, deed prep, wire)

### Shortcodes

- `[nss_calculator]` - Main calculator form
- `[nss_my_sheets]` - User's saved sheets list
- `[nss_sheet id="123"]` - View specific sheet
- `[nss_profile]` - User profile/company info

### REST API

Namespace: `/wp-json/nss/v1`

- `GET|POST /sheets` - List/create sheets
- `GET|PATCH|DELETE /sheets/{id}` - Single sheet operations
- `GET /sheets/{id}/pdf` - Get PDF URL
- `GET|PATCH /profile` - User profile

## Key Dependencies

- **brick/math** - Precision decimal arithmetic for financial calculations
- **mpdf/mpdf** - PDF document generation

## Class Naming Convention

All classes use `NSS_` prefix (Net Seller Sheet). Autoloading is handled via Composer's classmap over `includes/`.

## Custom Capabilities

User-level: `view_nss_sheets`, `create_nss_sheets`, `edit_nss_sheets`, `delete_nss_sheets`, `download_nss_pdf`

Admin-level: `manage_nss_fees`, `view_all_nss_sheets`, `edit_all_nss_sheets`, `delete_all_nss_sheets`, `manage_nss_settings`

## Admin AJAX Actions

Fee management: `nss_create_fee`, `nss_update_fee`, `nss_delete_fee`, `nss_toggle_fee_status`
Dashboard: `nss_get_stats`

## Frontend AJAX Actions

`nss_calculate`, `nss_save_sheet`, `nss_delete_sheet`, `nss_download_pdf`, `nss_update_profile`

## Asset Loading

Assets are conditionally enqueued:
- Admin CSS/JS only load on NSS admin pages
- Frontend CSS/JS only load on pages with shortcodes
- Vendor: `decimal.min.js` (Decimal.js v10.4.3) for client-side precision math

## Development Workflow

- Document all significant changes in `CHANGELOG.md`
- When adding new features, update this file if architecture changes
- Commit after completing each feature or fix
- Push to GitHub after commits are ready
