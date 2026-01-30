# Changelog

All notable changes to the Net Seller Sheet plugin will be documented in this file.

## [Unreleased]

### Added
- Fee management modal for creating new fees via admin interface
- Modal form supports all 6 fee types (conveyance, tax rate, property value, title closing, title exam, static fees)
- JavaScript handlers for modal open/close/submit functionality
- Address autocomplete powered by Radar.io API
- ZIP-to-county lookup fallback for addresses without county data
- New database table `nss_zip_county` for Ohio ZIP-county mapping
- Settings page options for Radar.io API key, enable/disable autocomplete, and Ohio restriction
- Guest access to calculator (users can calculate without logging in)
- Login/register prompt shown to guests after calculation (to save sheets/download PDFs)

### Fixed
- Removed reference to non-existent `class-nss-pdf-template.php` that prevented plugin activation
- Fixed duplicate database key errors for `county_name` and `fee_type` indexes
- Fixed `static_fee_type` field handling in security sanitizer

## [1.0.0] - 2025-01-29

### Added
- Initial release
- Property information capture (address, county, sales price, closing date)
- Loan payoff calculations (up to 3 loans plus wire fees)
- County-specific fee management (6 fee types)
- Tiered commission rate structure
- Tax proration calculations
- PDF generation with company branding
- User profile management
- REST API for external integrations
- Admin dashboard with statistics
- GitHub-based automatic updates
