(function ($) {
    'use strict';
    // Auto-hide our success notices (settings saved, place disconnected)
    $(function () {
        var $autoNotice = $('.wgrr-auto-notice');
        if ($autoNotice.length) {
            setTimeout(function () {
                $autoNotice.fadeOut(400);
            }, 2000);
        }
    });
    $(document).on('click', '#wgrr-disconnect-btn-red', function (e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to disconnect?')) {
            return;
        }
        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: 'delete_place_details',
                nonce: wgrr_ajax.nonce
            },
            success: function (response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Failed to remove place details.');
                }
            },
            error: function () {
                alert('An error occurred.');
            }
        });
    });
    $(document).on('click', '.repocean-btn.repocean-tooltip', function (e) {
        var temp = document.createElement("textarea");
        document.body.appendChild(temp);
        var shortcodeElement = $(this).closest('.shortcode-container').find('.repocean-shortcode');
        temp.value = shortcodeElement.text();
        temp.select();
        document.execCommand("copy");
        document.body.removeChild(temp);
        var tooltip = this.querySelector('.repocean-tooltip-message');
        tooltip.style.opacity = 1;
        setTimeout(() => {
            tooltip.style.opacity = 0;
        }, 2000);
    });
    $(document).on('click', '.repocean-compact-btn', function (e) {
        e.preventDefault();
        var temp = document.createElement("textarea");
        document.body.appendChild(temp);
        var shortcodeElement = $(this).closest('.repocean-compact-section').find('.repocean-compact-shortcode');
        temp.value = shortcodeElement.text().trim();
        temp.select();
        document.execCommand("copy");
        document.body.removeChild(temp);
        var tooltip = this.querySelector('.repocean-tooltip-message');
        tooltip.style.opacity = 1;
        setTimeout(function () {
            tooltip.style.opacity = 0;
        }, 2000);
    });
    // Show a spinner and block re-clicks while the settings form saves (full page submit)
    $(document).on('submit', 'form', function () {
        var $btn = $(this).find('button[name="repocean_submit"]');
        if ($btn.length) {
            $btn.addClass('is-loading');
        }
    });
    window.addEventListener('message', function (event) {
        if (event.data.type === 'placeDetails') {
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wgrr_save_place_details',
                    place: event.data,
                    nonce: wgrr_ajax.nonce
                },
                success: function (response) {
                    window.location.href = response.return_url;
                },
                error: function (xhr, status, error) {
                    console.error('Error saving place ID:', error);
                }
            });
        }
    });
})(jQuery);