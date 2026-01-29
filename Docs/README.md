# Net Seller Sheet - WordPress Plugin

Real estate net proceeds calculator with PDF generation for WordPress.

## Overview

Net Seller Sheet is a WordPress plugin that calculates net proceeds from real estate sales. It features:

- **Property Information Capture** - Address, county, sales price, and closing date
- **Loan Payoff Calculations** - Support for up to 3 loans plus wire fees
- **County-Specific Fees** - Conveyance fees, tax rates, title fees based on property location
- **Tiered Commission Rates** - Automatic commission calculation based on property value tiers
- **PDF Generation** - Professional white-label PDF documents with company logos
- **Admin Dashboard** - Complete fee management and analytics
- **GitHub Updates** - Automatic update checking and installation
- **User Profiles** - Company information and logo management
- **REST API** - Full REST API for external integrations

## Requirements

- PHP 8.0 or higher
- WordPress 6.0 or higher
- MySQL 5.7 or higher
- Composer (for dependency management)

## Installation

1. Clone or download the plugin to `wp-content/plugins/net-seller-sheet/`

2. Install PHP dependencies:
```bash
cd wp-content/plugins/net-seller-sheet
composer install
```

3. Activate the plugin in WordPress Admin → Plugins

4. Configure fees in Admin Panel → Net Seller Sheet → Fee Configuration

## Plugin Structure

```
net-seller-sheet/
├── includes/
│   ├── calculations/          # Calculation engine (math, taxes, commissions)
│   ├── models/                # Sheet and Fee models (database operations)
│   ├── helpers/               # Security, formatting, validation
│   ├── admin/                 # Admin interface pages and handlers
│   ├── frontend/              # Frontend shortcodes and templates
│   ├── pdf/                   # PDF generation with mPDF
│   ├── api/                   # REST API endpoints
│   └── database/              # Database schema and migration
├── assets/
│   ├── css/                   # Stylesheets
│   ├── js/                    # JavaScript files
│   └── images/                # Default assets
└── net-seller-sheet.php       # Main plugin file
```

## Usage

### For End Users

Add calculator to a page:
```
[nss_calculator]
```

Show user's sheets:
```
[nss_my_sheets]
```

User profile page:
```
[nss_profile]
```

### For Administrators

1. **Configure Fees** - Set up county-specific fees in Admin → Net Seller Sheet → Fee Configuration
2. **View Analytics** - See statistics and recent sheets on the dashboard
3. **Manage Sheets** - View, edit, and delete user sheets
4. **Import Data** - Import from legacy Next.js database (Settings page)

## Database Tables

The plugin creates 7 custom tables:

- `wp_nss_sheets` - Main calculation sheets
- `wp_nss_conveyance_fees` - Conveyance fee rates by county
- `wp_nss_tax_rates` - Tax rates by county
- `wp_nss_property_value_rates` - Commission tiers by property value
- `wp_nss_title_closing_fees` - Title closing fees by county
- `wp_nss_title_exam_fees` - Title exam fees by county
- `wp_nss_static_title_fees` - Fixed fees (courier, deed prep, wire)

## API Endpoints

REST API namespace: `/wp-json/nss/v1`

### Sheets
- `GET /sheets` - Get user's sheets
- `POST /sheets` - Create new sheet
- `GET /sheets/{id}` - Get single sheet
- `PATCH /sheets/{id}` - Update sheet
- `DELETE /sheets/{id}` - Delete sheet
- `GET /sheets/{id}/pdf` - Get PDF (returns URL)

### Profile
- `GET /profile` - Get current user's profile
- `PATCH /profile` - Update profile

## Classes

### Calculation Engine
- `NSS_Calculator` - Orchestrates all calculations
- `NSS_Precision_Math` - Precise decimal arithmetic using brick/math
- `NSS_Tax_Proration` - Tax prorating by closing date
- `NSS_Property_Value` - Tiered commission rates

### Models
- `NSS_Sheet` - CRUD operations for sheets
- `NSS_Fee` - Fee management

### Admin & Frontend
- `NSS_Admin` - Admin interface
- `NSS_Shortcodes` - Frontend shortcodes
- `NSS_REST_API` - REST API endpoints

### Utilities
- `NSS_Security` - Sanitization and permission checks
- `NSS_Formatting` - Data formatting for display
- `NSS_PDF_Generator` - PDF generation with mPDF
- `NSS_Updater` - GitHub-based automatic updates

## Settings

No configuration required. The plugin works with sensible defaults:

- **Default Property Value Tiers** - 4 tiered commission structure
- **Default Fees** - Can be customized per county in admin panel
- **PDF Settings** - Configure in settings page

## Permissions

The plugin defines several custom capabilities:

- `view_nss_sheets` - View own sheets
- `create_nss_sheets` - Create new sheets
- `edit_nss_sheets` - Edit own sheets
- `delete_nss_sheets` - Delete own sheets
- `download_nss_pdf` - Download PDFs
- `manage_nss_fees` - Admin: Manage fee configurations
- `view_all_nss_sheets` - Admin: View all sheets
- `edit_all_nss_sheets` - Admin: Edit any sheet
- `delete_all_nss_sheets` - Admin: Delete any sheet
- `manage_nss_settings` - Admin: Manage plugin settings

## Security

The plugin implements:

- **Nonce verification** - All AJAX requests require valid nonces
- **Capability checks** - All actions require appropriate WordPress capabilities
- **Data sanitization** - All inputs are sanitized using WordPress functions
- **Output escaping** - All outputs are escaped appropriately
- **SQL injection prevention** - Prepared statements for all database queries

## Updates

The plugin checks GitHub for updates every 12 hours. To manually check:

1. Go to Plugins in WordPress admin
2. Updates will appear automatically when available
3. Click "Update Now" to install

## Troubleshooting

### PDF not generating
- Check that mPDF library is installed: `composer install`
- Ensure `/wp-content/uploads/nss-pdfs/` directory is writable

### Calculations not matching
- Verify fee configurations in admin panel
- Check county spelling matches exactly
- Ensure property value tiers are configured

### Permissions errors
- Check user has appropriate capabilities
- Admin users should have all capabilities
- Subscriber role gets default user capabilities

## Development

### Running Tests
```bash
composer test
```

### Code Quality
```bash
composer stan
```

## Support

For issues, questions, or feature requests, visit the GitHub repository:
https://github.com/yourusername/net-seller-sheet

## License

GPL v2 or later

## Changelog

### 1.0.0
- Initial release
- Full calculator functionality
- Admin fee management
- PDF generation
- User profiles
- REST API
- GitHub automatic updates
