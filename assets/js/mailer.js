$(document).ready(function () {

    var $contactForm = $("#contactForm");
    var $submitBtn   = $contactForm.find("button[type='submit']");

    // ── Disable submit until CSRF token is loaded ──────────
    // Prevents a race where a user submits before the async token fetch returns.
    $submitBtn.prop("disabled", true);

    $.get("php/csrf.php")
        .done(function (token) {
            $("#csrf_token").val(String(token).trim());
            $submitBtn.prop("disabled", false);
        })
        .fail(function () {
            showMessage("contactMsg", false, "Couldn't initialize the form. Please refresh the page.");
        });

    // ── Contact Form Submit ────────────────────────────────
    $contactForm.on("submit", function (e) {
        e.preventDefault();

        var name    = $("#contact-name").val().trim();
        var phone   = $("#contact-phone").val().trim();
        var email   = $("#contact-email").val().trim();
        var message = $("#contact-message").val().trim();

        // Required fields check
        if (!name || !email || !message) {
            showMessage("contactMsg", false, "Please fill in all required fields.");
            return;
        }

        // Email format check
        var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            showMessage("contactMsg", false, "Please enter a valid email address.");
            return;
        }

        // Phone format check (optional but filled)
        if (phone && !/^[+]?[0-9\s\-]{7,15}$/.test(phone)) {
            showMessage("contactMsg", false, "Please enter a valid phone number.");
            return;
        }

        // CSRF token must be loaded
        if (!$("#csrf_token").val()) {
            showMessage("contactMsg", false, "Form not ready yet. Please wait a second and try again.");
            return;
        }

        // reCAPTCHA must be available AND completed
        if (typeof grecaptcha === "undefined") {
            showMessage("contactMsg", false, "CAPTCHA could not load. Disable ad-blockers and refresh.");
            return;
        }
        if (!grecaptcha.getResponse()) {
            showMessage("contactMsg", false, "Please complete the CAPTCHA.");
            return;
        }

        var formData = {
            type                   : "contactForm",
            name                   : name,
            phone                  : phone,
            email                  : email,
            message                : message,
            csrf_token             : $("#csrf_token").val(),
            "g-recaptcha-response" : grecaptcha.getResponse()
        };

        submitForm(formData, "contactMsg", "#contactForm");
    });

    // ── Submit Form via AJAX ───────────────────────────────
    function submitForm(data, msgId, formSelector) {
        showMessage(msgId, null, "Sending...");
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
                    resetCaptcha();

                    setTimeout(function () {
                        $("#" + msgId).text("");
                    }, 10000);
                } else {
                    showMessage(msgId, false, "❌ " + res.message);
                    resetCaptcha();
                }
            },
            error: function () {
                showMessage(msgId, false, "❌ Something went wrong. Please try again.");
                resetCaptcha();
            },
            complete: function () {
                $(formSelector).find("button[type='submit']").prop("disabled", false);
            }
        });
    }

    // ── Safe reCAPTCHA reset (no-op if widget didn't load) ──
    function resetCaptcha() {
        if (typeof grecaptcha !== "undefined" && typeof grecaptcha.reset === "function") {
            try { grecaptcha.reset(); } catch (err) { /* widget not ready */ }
        }
    }

    // ── Show Message Helper ────────────────────────────────
    function showMessage(id, success, msg) {
        var el = $("#" + id);
        el.removeClass("text-success text-danger text-secondary");
        if (success === true)       el.addClass("text-success");
        else if (success === false) el.addClass("text-danger");
        else                        el.addClass("text-secondary");
        el.text(msg);
    }

});
