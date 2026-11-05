/**
 * Group Enquiry Desk — Frontend AJAX Submission Handler
 */

document.addEventListener('DOMContentLoaded', function () {
    var formWrapper = document.getElementById('ged-enquiry-form-container');
    if (!formWrapper) {
        return;
    }

    var form = formWrapper.querySelector('.ged-form');
    var alertBox = formWrapper.querySelector('.ged-alerts');
    var submitBtn = form.querySelector('.ged-submit-btn');
    var btnText = form.querySelector('.ged-btn-text');
    var spinner = form.querySelector('.ged-spinner');

    if (!form || !alertBox || !submitBtn) {
        return;
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        // Clear existing alerts
        alertBox.innerHTML = '';

        // UI Loading State
        submitBtn.disabled = true;
        if (spinner) spinner.style.display = 'inline-block';
        if (btnText) btnText.textContent = 'Submitting safely...';

        var formData = new FormData(form);
        formData.append('action', 'group_enquiry_submit');

        var ajaxUrl = (typeof groupEnquiryConfig !== 'undefined' && groupEnquiryConfig.ajaxUrl)
            ? groupEnquiryConfig.ajaxUrl
            : form.getAttribute('action');

        fetch(ajaxUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(function (response) {
            return response.json();
        })
        .then(function (res) {
            submitBtn.disabled = false;
            if (spinner) spinner.style.display = 'none';
            if (btnText) btnText.textContent = 'Submit Enquiry';

            if (res && res.success) {
                alertBox.innerHTML = '<div class="ged-alert ged-alert-success">' + escapeHtml(res.data.message) + '</div>';
                form.reset();
            } else {
                var errorMsg = (res && res.data && res.data.message)
                    ? res.data.message
                    : 'An error occurred while submitting your enquiry. Please try again.';
                alertBox.innerHTML = '<div class="ged-alert ged-alert-error">' + escapeHtml(errorMsg) + '</div>';
            }
        })
        .catch(function () {
            submitBtn.disabled = false;
            if (spinner) spinner.style.display = 'none';
            if (btnText) btnText.textContent = 'Submit Enquiry';

            alertBox.innerHTML = '<div class="ged-alert ged-alert-error">A network error occurred. Please check your connection or contact us directly.</div>';
        });
    });

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }
});

