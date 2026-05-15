$(document).ready(function () {

    // ── Fetch CSRF token on page load ──────────────────────
    $.get("php/csrf.php", function(token) {
        $("#csrf_token").val(token);
    });

    // ── Contact Form Submit ────────────────────────────────
    $("#contactForm").on("submit", function (e) {
        e.preventDefault();

        const name    = $("#contact-name").val().trim();
        const phone   = $("#contact-phone").val().trim();
        const email   = $("#contact-email").val().trim();
        const message = $("#contact-message").val().trim();

        // Required fields check
        if (!name || !email || !message) {
            showMessage("contactMsg", false, "Please fill in all required fields.");
            return;
        }

        // Email format check
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            showMessage("contactMsg", false, "Please enter a valid email address.");
            return;
        }

        // Phone format check (optional but filled)
        if (phone && !/^[+]?[0-9\s\-]{7,15}$/.test(phone)) {
            showMessage("contactMsg", false, "Please enter a valid phone number.");
            return;
        }

        // reCAPTCHA check
        if (!grecaptcha.getResponse()) {
            showMessage("contactMsg", false, "Please complete the CAPTCHA.");
            return;
        }

        const formData = {
            type                 : "contactForm",
            name                 : name,
            phone                : phone,
            email                : email,
            message              : message,
            csrf_token           : $("#csrf_token").val(),
            'g-recaptcha-response': grecaptcha.getResponse()
        };

        submitForm(formData, "contactMsg", "#contactForm");
    });

    // ── Submit Form via AJAX ───────────────────────────────
    function submitForm(data, msgId, formSelector) {
        showMessage(msgId, null, "Sending...");

        // Disable submit button to prevent double click
        $(formSelector).find("button[type='submit']").prop("disabled", true);

        $.ajax({
            url     : "php/mailer.php",
            type    : "POST",
            data    : data,
            dataType: "json",
            success : function (res) {
                if (res.success) {
                    showMessage(msgId, true, "✅ Message sent successfully! We will get back to you within 24 hours.");
                    $(formSelector)[0].reset();
                    grecaptcha.reset();

                    // Auto-hide message after 10 seconds
                    setTimeout(function () {
                        $("#" + msgId).text("");
                    }, 10000);

                } else {
                    showMessage(msgId, false, "❌ " + res.message);
                    grecaptcha.reset();
                }
            },
            error: function () {
                showMessage(msgId, false, "❌ Something went wrong. Please try again.");
                grecaptcha.reset();
            },
            complete: function () {
                // Re-enable submit button
                $(formSelector).find("button[type='submit']").prop("disabled", false);
            }
        });
    }

    // ── Show Message Helper ────────────────────────────────
    function showMessage(id, success, msg) {
        const el = $("#" + id);
        el.removeClass("text-success text-danger text-secondary");
        if (success === true)       el.addClass("text-success");
        else if (success === false) el.addClass("text-danger");
        else                        el.addClass("text-secondary");
        el.text(msg);
    }

});