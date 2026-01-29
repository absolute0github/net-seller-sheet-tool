<?php
/**
 * User profile template
 */

$user_id = get_current_user_id();
$user = get_user_by('id', $user_id);
$company_name = get_user_meta($user_id, 'nss_company', true);
$company_logo_id = get_user_meta($user_id, 'nss_company_logo_id', true);
$company_logo_url = get_user_meta($user_id, 'nss_company_logo_url', true);
?>

<div class="nss-profile-container">
    <div class="nss-profile-header">
        <h2>My Profile</h2>
    </div>

    <form id="nss-profile-form" class="nss-form" method="POST">
        <input type="hidden" name="user_id" value="<?php echo esc_attr($user_id); ?>">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('nss_profile_nonce'); ?>">

        <div class="nss-form-section">
            <h3>Account Information</h3>

            <div class="nss-form-group">
                <label>Email</label>
                <p><?php echo esc_html($user->user_email); ?></p>
            </div>

            <div class="nss-form-group">
                <label>Username</label>
                <p><?php echo esc_html($user->user_login); ?></p>
            </div>
        </div>

        <div class="nss-form-section">
            <h3>Company Information</h3>

            <div class="nss-form-group">
                <label for="company_name">Company Name</label>
                <input type="text" id="company_name" name="company_name" value="<?php echo esc_attr($company_name); ?>">
            </div>

            <div class="nss-form-group">
                <label for="company_logo">Company Logo</label>
                <div class="nss-logo-upload">
                    <?php if ($company_logo_url): ?>
                        <div class="nss-logo-preview">
                            <img src="<?php echo esc_url($company_logo_url); ?>" alt="Company Logo" style="max-width: 200px; max-height: 200px;">
                        </div>
                    <?php endif; ?>
                    <input type="file" id="company_logo" name="company_logo" accept="image/*">
                    <p class="description">Upload your company logo (max 2MB, PNG/JPG)</p>
                </div>
            </div>
        </div>

        <div class="nss-form-actions">
            <button type="submit" id="nss-profile-submit" class="nss-button nss-button-primary">Save Profile</button>
        </div>
    </form>

    <div id="nss-profile-message" class="nss-message" style="display:none;"></div>
</div>

<script>
(function($) {
    $('#nss-profile-form').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        formData.append('action', 'nss_update_profile');

        $.ajax({
            url: nssData.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#nss-profile-message')
                        .removeClass('error')
                        .addClass('success')
                        .text('Profile updated successfully!')
                        .show();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    $('#nss-profile-message')
                        .removeClass('success')
                        .addClass('error')
                        .text('Error: ' + response.data)
                        .show();
                }
            },
            error: function() {
                $('#nss-profile-message')
                    .removeClass('success')
                    .addClass('error')
                    .text('Error saving profile')
                    .show();
            }
        });
    });
})(jQuery);
</script>
