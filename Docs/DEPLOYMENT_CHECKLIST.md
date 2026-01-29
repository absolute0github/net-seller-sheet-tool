# Net Seller Sheet - Deployment Checklist

Complete these steps before going live with your WordPress plugin.

## Pre-Deployment (Development Environment)

- [ ] Clone/download plugin to `wp-content/plugins/net-seller-sheet/`
- [ ] Run `composer install` to install PHP dependencies
- [ ] Activate plugin in WordPress Admin
- [ ] Verify database tables created:
  - [ ] wp_nss_sheets
  - [ ] wp_nss_conveyance_fees
  - [ ] wp_nss_tax_rates
  - [ ] wp_nss_property_value_rates
  - [ ] wp_nss_title_closing_fees
  - [ ] wp_nss_title_exam_fees
  - [ ] wp_nss_static_title_fees
- [ ] Test admin menu appears: Admin → Net Seller Sheet
- [ ] Test all admin pages load without errors:
  - [ ] Dashboard
  - [ ] All Sheets
  - [ ] Fee Configuration
  - [ ] Settings

## Configuration

- [ ] Update GitHub repository URL in `includes/class-nss-updater.php`
  ```php
  const GITHUB_REPO = 'yourusername/net-seller-sheet';
  ```
- [ ] Set default company name in Settings
- [ ] Add at least one fee configuration:
  - [ ] Add test county for Conveyance Fees
  - [ ] Add test county for Tax Rates
  - [ ] Add test county for Title Fees
  - [ ] Verify Property Value Tiers are seeded (4 default tiers)

## Frontend Testing

- [ ] Create page with `[nss_calculator]` shortcode
- [ ] Create page with `[nss_my_sheets]` shortcode
- [ ] Create page with `[nss_profile]` shortcode
- [ ] Test as logged-in user:
  - [ ] Calculator loads and displays correctly
  - [ ] Can fill in form fields
  - [ ] Calculate button works
  - [ ] Results display correctly
  - [ ] Can save sheet
  - [ ] Can download PDF
  - [ ] My Sheets page shows saved sheets
  - [ ] Profile page displays user info
  - [ ] Can update company name
  - [ ] Can upload company logo
- [ ] Test as non-logged-in user:
  - [ ] Calculator redirects to login
  - [ ] My Sheets redirects to login
  - [ ] Profile redirects to login

## Calculations Verification

- [ ] Test calculation with known data
- [ ] Verify calculations match expected results
- [ ] Test with different property values (tests tiered commission)
- [ ] Test with different closing dates (tests tax proration)
- [ ] Test with multiple loan payoffs
- [ ] Test with optional fees (HOA, wire)
- [ ] Download PDF and verify:
  - [ ] Company logo appears
  - [ ] All line items display
  - [ ] Calculations are accurate
  - [ ] Professional formatting

## Security Review

- [ ] Verify nonces work on all AJAX calls
- [ ] Verify capability checks work:
  - [ ] Non-users can't access calculator
  - [ ] Regular users can't access other users' sheets
  - [ ] Non-admins can't access fee configuration
- [ ] Test input sanitization:
  - [ ] Special characters in address handled correctly
  - [ ] HTML in company name escaped properly
  - [ ] Large numbers handled without issues
- [ ] Verify data validation:
  - [ ] Invalid dates rejected
  - [ ] Negative numbers rejected where applicable
  - [ ] Missing required fields show errors
- [ ] Check SQL injection prevention:
  - [ ] Use database query checker tool
  - [ ] All queries use prepared statements

## Performance Testing

- [ ] Page load time < 2 seconds (non-PDF pages)
- [ ] Calculator calculation < 500ms
- [ ] PDF generation < 5 seconds
- [ ] No JavaScript errors in console
- [ ] No PHP warnings in logs
- [ ] CSS loads without issues
- [ ] Images load correctly

## Browser Compatibility

Test in the following browsers:
- [ ] Chrome (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)
- [ ] Edge (latest)
- [ ] Mobile Chrome (iOS/Android)
- [ ] Mobile Safari (iOS)

## API Testing (Optional)

If using REST API:
- [ ] Test GET /wp-json/nss/v1/sheets
- [ ] Test POST /wp-json/nss/v1/sheets
- [ ] Test GET /wp-json/nss/v1/sheets/{id}
- [ ] Test PATCH /wp-json/nss/v1/sheets/{id}
- [ ] Test DELETE /wp-json/nss/v1/sheets/{id}
- [ ] Test GET /wp-json/nss/v1/profile
- [ ] Test PATCH /wp-json/nss/v1/profile

Use tools like Postman or REST API client to test.

## Update System Testing

- [ ] Verify update checker is working:
  - [ ] Admin → Plugins page shows update notification (if new version available)
  - [ ] Check logs for GitHub API calls
- [ ] Test update installation:
  - [ ] Click "Update Now" button
  - [ ] Verify plugin updates successfully
  - [ ] Verify plugin still works after update

## Data Integrity

- [ ] Backup database before going live
- [ ] Test sheet creation/deletion:
  - [ ] Create 10+ test sheets
  - [ ] Verify all data saves correctly
  - [ ] Verify soft delete works
  - [ ] Verify deleted sheets don't appear in lists
- [ ] Test fee management:
  - [ ] Create, update, delete fees
  - [ ] Verify active/inactive toggle works
  - [ ] Verify inactive fees don't affect calculations

## Production Environment

### Pre-Upload

- [ ] Review all configuration files
- [ ] Ensure no debug code left in files
- [ ] Check plugin version in main file: `define('NSS_PLUGIN_VERSION', '1.0.0');`
- [ ] Verify all file permissions are correct (644 for files, 755 for dirs)
- [ ] Create production database backup

### Upload

- [ ] Upload plugin to production server
- [ ] Run `composer install --no-dev` (production mode)
- [ ] Set appropriate file permissions
- [ ] Verify plugin directory is readable by web server

### Activation

- [ ] Activate plugin in production WordPress
- [ ] Verify database tables created
- [ ] Check for any activation errors in logs
- [ ] Verify admin menu appears

### Post-Activation

- [ ] Add shortcodes to production pages
- [ ] Test calculator with real data
- [ ] Test PDF generation
- [ ] Monitor error logs for issues
- [ ] Set up automated backups
- [ ] Configure email notifications (optional)

## User Training

- [ ] Document how to create sheets
- [ ] Document how to download PDFs
- [ ] Document how to manage company info
- [ ] Provide screenshot guides
- [ ] Provide video tutorials (optional)
- [ ] Create FAQ document
- [ ] Set up support email

## Post-Launch Monitoring

**Day 1-3:**
- [ ] Monitor error logs daily
- [ ] Check for user support requests
- [ ] Verify calculations are accurate
- [ ] Check PDF generation is working
- [ ] Monitor database growth

**Week 1-4:**
- [ ] Weekly error log review
- [ ] Weekly backup verification
- [ ] User feedback collection
- [ ] Performance monitoring
- [ ] Update checker verification

**Ongoing:**
- [ ] Monthly security updates check
- [ ] Quarterly full system audit
- [ ] Semi-annual database optimization
- [ ] Annual penetration testing (recommended)

## Rollback Plan

If issues arise:

1. **Immediate** (within 1 hour):
   - [ ] Disable plugin: Rename plugin folder temporarily
   - [ ] Restore database from backup
   - [ ] Notify users of temporary outage

2. **Short-term** (within 24 hours):
   - [ ] Identify root cause of issue
   - [ ] Apply fix to plugin
   - [ ] Test fix thoroughly
   - [ ] Re-activate plugin

3. **Long-term**:
   - [ ] Document what went wrong
   - [ ] Update testing procedures
   - [ ] Implement preventive measures

## Success Criteria

Plugin is ready for production when:

✅ All checklist items are complete
✅ No errors in error logs
✅ All calculations verified as accurate
✅ PDFs generate successfully with company branding
✅ All users can access their sheets
✅ Security review passed
✅ Performance meets requirements
✅ Admin can manage fees
✅ Update system working

## Sign-Off

- [ ] Developer: _________________ Date: _______
- [ ] QA Tester: ________________ Date: _______
- [ ] Project Manager: __________ Date: _______
- [ ] System Admin: _____________ Date: _______

---

## Quick Reference

| Component | Status | Notes |
|-----------|--------|-------|
| Database Tables | ✅ | 7 tables created |
| Admin Interface | ✅ | Dashboard, sheets, fees, settings |
| Frontend | ✅ | 4 shortcodes, responsive |
| Calculations | ✅ | Precision math, all types |
| PDF Generation | ✅ | White-label support |
| REST API | ✅ | Full endpoints |
| Updates | ✅ | GitHub-based |
| Security | ✅ | Nonces, capabilities, sanitization |
| Documentation | ✅ | README, guides, quick start |

---

**Deployment Date**: _______________
**Plugin Version**: 1.0.0
**Environment**: Production / Staging

Last Updated: 2026-01-22
