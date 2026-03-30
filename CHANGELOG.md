# Changelog

All notable changes to the Net Seller Sheet plugin will be documented in this file.

## [Unreleased]

### Added
- Sheet preview page: after clicking Calculate, the form is replaced by a full-page preview that mirrors the PDF layout (ATG header, Debit/Credit table, section groupings). Users can click "Edit" to return to the form or "Download PDF" to save and download in one step.
- "Download PDF" in the preview auto-saves the sheet before downloading, so no separate Save step is required.
- Guest users see a login/register prompt in place of the PDF button.

### Changed
- Tax calculation overhauled: users now enter annual property tax amount directly; system calculates two deduction line items — prorated amount (Jan 1 to closing date) and a 50% property tax hold
- Conveyance fee formula corrected from percentage of price to dollars per $1,000 (e.g., Medina $3/k)
- Title insurance (Owner's Policy) now uses cumulative Ohio OTIRB tiered rates effective Jan 1, 2026 ($5.80/k down to $2.60/k) — always calculated, no longer gated by a checkbox
- Agent commission now user-entered percentage on the calculator form instead of a property value tier lookup
- Calculator form: replaced wire fee and owner's policy checkbox with annual taxes and agent commission % fields
- Admin fee configuration: removed Tax Rates and Title Exam Fees tabs (no longer needed); renamed Property Value Tiers to "Title Insurance Rates" with $/k rate labels
- DB version bumped to 3; property value rates table re-seeded with 6 Ohio OTIRB Standard Owner's Policy tiers
- PDF template updated: removed Wire Fee and Title Exam Fee rows; added Property Tax Hold row; Owner's Policy always shown with OTIRB label

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
