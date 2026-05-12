$(document).ready(function () {

    $("#contactForm").on("submit", function (e) {
        e.preventDefault();

        const name    = $("#contact-name").val().trim();
        const phone   = $("#contact-phone").val().trim();
        const email   = $("#contact-email").val().trim();
        const website = $("#contact-website").val().trim();
        const message = $("#contact-message").val().trim();

        if (!name || !email || !message) {
            showMessage("contactMsg", false, "Please fill in all required fields.");
            return;
        }

        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(email)) {
            showMessage("contactMsg", false, "Please enter a valid email address.");
            return;
        }

        const formData = {
            type   : "contactForm",
            name   : name,
            phone  : phone,
            email  : email,
            website: website,
            message: message
        };

        submitForm(formData, "contactMsg", "#contactForm");
    });

    function submitForm(data, msgId, formSelector) {
        showMessage(msgId, null, "Sending...");

        $.ajax({
           url     : "../php/mailer.php",// ← path to mailer.php inside php/ folder
            type    : "POST",
            data    : data,
            dataType: "json",
            success : function (res) {
                if (res.success) {
                    showMessage(msgId, true, "✅ Message sent successfully!");
                    $(formSelector)[0].reset();
                } else {
                    showMessage(msgId, false, "❌ " + res.message);
                }
            },
            error: function () {
                showMessage(msgId, false, "❌ Something went wrong. Please try again.");
            }
        });
    }

    function showMessage(id, success, msg) {
        const el = $("#" + id);
        el.removeClass("text-success text-danger text-secondary");
        if (success === true)       el.addClass("text-success");
        else if (success === false) el.addClass("text-danger");
        else                        el.addClass("text-secondary");
        el.text(msg);
    }
});