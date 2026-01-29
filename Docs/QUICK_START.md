# Net Seller Sheet - Quick Start Guide

## 5-Minute Setup

### Step 1: Install Dependencies
```bash
cd wp-content/plugins/net-seller-sheet
composer install
```

### Step 2: Activate Plugin
1. Go to WordPress Admin Dashboard
2. Click Plugins
3. Find "Net Seller Sheet"
4. Click "Activate"

### Step 3: Configure Fees (Admin Only)
1. Go to Admin → Net Seller Sheet → Fee Configuration
2. Click on each tab (Conveyance, Tax Rates, etc.)
3. Add your county-specific fees
4. Click "Add New" to create entries

### Step 4: Add to Pages
Add these shortcodes to your pages:

**Calculator Page:**
```
[nss_calculator]
```

**User's Sheets Page:**
```
[nss_my_sheets]
```

**User Profile Page:**
```
[nss_profile]
```

### Step 5: Test
1. Log in as a user
2. Go to calculator page
3. Fill in sample data
4. Click "Calculate"
5. Results should appear
6. Click "Save Sheet" and "Download PDF"

## First-Time Admin Tasks

### 1. Update Plugin Info
Edit: `wp-plugin/includes/class-nss-updater.php`

Change this line:
```php
const GITHUB_REPO = 'yourusername/net-seller-sheet';
```

To your actual GitHub repository.

### 2. Configure Default Company Name
1. Admin → Net Seller Sheet → Settings
2. Enter your company name
3. Save Settings

### 3. Add County Fees
1. Admin → Net Seller Sheet → Fee Configuration
2. Click "Conveyance Fees" tab
3. Click "Add New Conveyance Fees"
4. Fill in county name, rate, and seller percentage
5. Save

### 4. Set Tax Rates
1. Fee Configuration → Tax Rates tab
2. Click "Add New Tax Rate"
3. Enter county name and tax rate
4. Save

## File Locations

| Component | Location |
|-----------|----------|
| Main Plugin | `net-seller-sheet.php` |
| Database Setup | `includes/database/class-nss-database.php` |
| Calculations | `includes/calculations/` |
| Admin Pages | `includes/admin/pages/` |
| Frontend Pages | `includes/frontend/templates/` |
| API Routes | `includes/api/class-nss-rest-api.php` |
| Admin Styles | `assets/css/admin.css` |
| Frontend Styles | `assets/css/frontend.css` |
| Stylesheets | `assets/css/` |
| JavaScript | `assets/js/` |

## Common Tasks

### Add a New County
1. Admin → Net Seller Sheet → Fee Configuration
2. Click the appropriate fee type tab
3. Click "Add New [Fee Type]"
4. Fill in the county information
5. Save

### Edit a Fee
1. Admin → Net Seller Sheet → Fee Configuration
2. Find the fee in the table
3. Click "Edit"
4. Update values
5. Save

### Delete a Fee
1. Admin → Net Seller Sheet → Fee Configuration
2. Find the fee in the table
3. Click "Delete"
4. Confirm deletion

### View User Sheets
1. Admin → Net Seller Sheet → All Sheets
2. View all sheets created by users
3. Click "View" to see details
4. Click "Edit" to modify

### Download a User's PDF
1. Admin → Net Seller Sheet → All Sheets
2. Click "View" on the sheet
3. Click "Download PDF"

## Troubleshooting

### "Fatal error: Uncaught Error: Class 'Brick\Math\BigDecimal' not found"
**Solution**: Run `composer install` in the plugin directory

### "Call to undefined function mpdf()"
**Solution**: Run `composer install` to install mPDF

### "Permission denied when uploading logo"
**Solution**: Check `wp-content/uploads/` is writable

### "PDF not generating"
**Solution**: Ensure `/wp-content/uploads/nss-pdfs/` directory exists and is writable

### "Calculations showing incorrect results"
**Solution**: Check fee configuration for the property's county

## User Permissions

### Subscriber Role (Regular Users)
- Create sheets
- Edit own sheets
- Delete own sheets
- Download own PDFs
- View own profile
- Upload company logo

### Administrator Role
- All subscriber permissions plus:
- View all sheets
- Edit any sheet
- Delete any sheet
- Manage fee configurations
- Access admin dashboard
- View analytics

## Key Shortcodes

| Shortcode | Purpose | Who Can Use |
|-----------|---------|------------|
| `[nss_calculator]` | Main calculator form | Logged-in users |
| `[nss_my_sheets]` | List of user's sheets | Logged-in users |
| `[nss_sheet id="123"]` | View specific sheet | Sheet owner or admin |
| `[nss_profile]` | User profile/company info | Logged-in users |

## Keyboard Shortcuts

- **Calculator Form**: Tab through fields, Enter to calculate
- **Admin Fees**: Click table rows to edit inline
- **Delete Actions**: Confirm with OK button

## Tips & Tricks

1. **Test with Sample Data**: Create test sheets before going live
2. **Backup First**: Always backup your database before plugin updates
3. **Check Logs**: Look at WordPress debug.log for any errors
4. **Mobile Test**: Test calculator on mobile devices
5. **PDF Preview**: Download sample PDF to verify company branding

## Next Steps

1. ✅ Install and activate plugin
2. ✅ Configure fees for your counties
3. ✅ Create test pages with shortcodes
4. ✅ Test with sample calculations
5. ✅ Have users create their profiles
6. ✅ Import legacy data (if available)
7. ✅ Train users on usage
8. ✅ Go live!

## Support Resources

- **README.md** - Full documentation
- **IMPLEMENTATION_SUMMARY.md** - What was built
- **WordPress Codex** - WordPress functions reference
- **GitHub Issues** - Report bugs and request features

## Version Information

- **Plugin Version**: 1.0.0
- **Requires PHP**: 8.0+
- **Requires WordPress**: 6.0+
- **License**: GPL v2 or later

---

**Ready to get started? Activate the plugin and follow the steps above!**
