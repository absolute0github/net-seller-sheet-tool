/**
 * Net Seller Sheet Address Autocomplete
 *
 * Provides address suggestions using Radar.io with ZIP-to-county fallback
 */

(function($) {
    'use strict';

    var debounceTimer = null;
    var minChars = 3;
    var debounceDelay = 300;
    var $addressInput = null;
    var $dropdown = null;

    $(document).ready(function() {
        initAddressAutocomplete();
    });

    /**
     * Initialize address autocomplete
     */
    function initAddressAutocomplete() {
        $addressInput = $('#property_address');

        if (!$addressInput.length || !nssAutocomplete.enabled) {
            return;
        }

        // Create dropdown container
        $dropdown = $('<div class="nss-autocomplete-dropdown"></div>');
        $addressInput.after($dropdown);

        // Wrap input and dropdown for positioning
        $addressInput.wrap('<div class="nss-autocomplete-wrapper"></div>');
        $addressInput.after($dropdown);

        // Input event listener with debounce
        $addressInput.on('input', function() {
            var query = $(this).val().trim();

            clearTimeout(debounceTimer);

            if (query.length < minChars) {
                hideDropdown();
                return;
            }

            debounceTimer = setTimeout(function() {
                searchAddresses(query);
            }, debounceDelay);
        });

        // Handle keyboard navigation
        $addressInput.on('keydown', function(e) {
            if (!$dropdown.is(':visible')) {
                return;
            }

            var $items = $dropdown.find('.nss-autocomplete-item');
            var $active = $dropdown.find('.nss-autocomplete-item.active');
            var index = $items.index($active);

            switch (e.keyCode) {
                case 40: // Down arrow
                    e.preventDefault();
                    if (index < $items.length - 1) {
                        $items.removeClass('active');
                        $items.eq(index + 1).addClass('active');
                    }
                    break;

                case 38: // Up arrow
                    e.preventDefault();
                    if (index > 0) {
                        $items.removeClass('active');
                        $items.eq(index - 1).addClass('active');
                    }
                    break;

                case 13: // Enter
                    e.preventDefault();
                    if ($active.length) {
                        selectAddress($active.data('address'));
                    }
                    break;

                case 27: // Escape
                    hideDropdown();
                    break;
            }
        });

        // Click on suggestion
        $(document).on('click', '.nss-autocomplete-item', function() {
            selectAddress($(this).data('address'));
        });

        // Click outside to close
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.nss-autocomplete-wrapper').length) {
                hideDropdown();
            }
        });

        // Handle blur
        $addressInput.on('blur', function() {
            // Delay to allow click on dropdown
            setTimeout(function() {
                hideDropdown();
            }, 200);
        });
    }

    /**
     * Search addresses via AJAX proxy
     */
    function searchAddresses(query) {
        $.ajax({
            url: nssAutocomplete.ajax_url,
            type: 'POST',
            data: {
                action: 'nss_address_autocomplete',
                nonce: nssAutocomplete.nonce,
                query: query
            },
            beforeSend: function() {
                showLoading();
            },
            success: function(response) {
                if (response.success && response.data.addresses && response.data.addresses.length) {
                    displaySuggestions(response.data.addresses);
                } else {
                    hideDropdown();
                }
            },
            error: function() {
                hideDropdown();
            }
        });
    }

    /**
     * Display address suggestions in dropdown
     */
    function displaySuggestions(addresses) {
        var html = '';

        $.each(addresses, function(i, address) {
            var displayText = formatDisplayAddress(address);
            html += '<div class="nss-autocomplete-item" data-address=\'' + JSON.stringify(address).replace(/'/g, '&#39;') + '\'>';
            html += '<span class="nss-autocomplete-address">' + escapeHtml(displayText) + '</span>';
            if (address.county) {
                html += '<span class="nss-autocomplete-county">' + escapeHtml(address.county) + ' County</span>';
            }
            html += '</div>';
        });

        $dropdown.html(html).show();
    }

    /**
     * Format address for display
     */
    function formatDisplayAddress(address) {
        var parts = [];

        if (address.street) {
            parts.push(address.street);
        }
        if (address.city) {
            parts.push(address.city);
        }
        if (address.state) {
            parts.push(address.state);
        }
        if (address.zip) {
            parts.push(address.zip);
        }

        return parts.join(', ');
    }

    /**
     * Select an address and populate form fields
     */
    function selectAddress(address) {
        if (typeof address === 'string') {
            address = JSON.parse(address);
        }

        // Populate fields
        $addressInput.val(address.street || address.formatted_address || '');
        $('#property_city').val(address.city || '');
        $('#property_state').val(address.state || '');
        $('#property_zip').val(address.zip || '');

        // Handle county - may need fallback lookup
        if (address.county) {
            $('#property_county').val(address.county);
            hideDropdown();
        } else if (address.zip) {
            // Fallback: lookup county by ZIP
            lookupCountyByZip(address.zip);
            hideDropdown();
        } else {
            hideDropdown();
        }
    }

    /**
     * Lookup county by ZIP code (fallback)
     */
    function lookupCountyByZip(zip) {
        $.ajax({
            url: nssAutocomplete.ajax_url,
            type: 'POST',
            data: {
                action: 'nss_lookup_county',
                nonce: nssAutocomplete.nonce,
                zip: zip
            },
            success: function(response) {
                if (response.success && response.data.county) {
                    $('#property_county').val(response.data.county);
                }
            }
        });
    }

    /**
     * Show loading state
     */
    function showLoading() {
        $dropdown.html('<div class="nss-autocomplete-loading">Searching...</div>').show();
    }

    /**
     * Hide dropdown
     */
    function hideDropdown() {
        if ($dropdown) {
            $dropdown.hide().empty();
        }
    }

    /**
     * Escape HTML entities
     */
    function escapeHtml(text) {
        if (!text) return '';
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

})(jQuery);
