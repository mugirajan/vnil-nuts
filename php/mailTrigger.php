<?php

require_once("./vendor/autoload.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class sndMail
{
    private $valid = array("success" => false, "message" => "");

    public function __construct()
    {
        if (!session_id()) {
            session_start();
        }
    }

    private function configureMailer()
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = "smtp.gmail.com";
        $mail->Port       = 587;
        $mail->SMTPAuth   = true;
        $mail->Username   = "soundarya.ramesh0712@gmail.com";
        $mail->Password   = "wprxfjbrqlgpirhr";    // ← paste your password here
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->setFrom("soundarya.ramesh0712@gmail.com", "Organi");
        $mail->isHTML(false);
        return $mail;
    }

    // Contact form enquiry
    public function contactEnquiry($data)
    {
        $mail = $this->configureMailer();

        try {
            // Send confirmation to user
            $mail->addAddress($data['email']);
            $mail->Subject = "Your enquiry is received - " . $data['name'];
            $mail->Body = "
Dear {$data['name']},

Thank you for reaching out to Organi.

We have received your enquiry and our team will get back to you shortly.

Regards,
Organi Team
            ";
            $mail->send();

            $this->valid['success'] = true;
            $this->valid['message'] = "Mail sent successfully to user.";

        } catch (Exception $e) {
            $this->valid['success'] = false;
            $this->valid['message'] = "User mail failed: " . $mail->ErrorInfo;
        }

        try {
            // Send notification to admin
            $mail->clearAddresses();
            $mail->addAddress("soundarya.ramesh0712@gmail.com");

            $mail->Subject = "New Contact Enquiry - " . $data['name'];
            $mail->Body = "
New enquiry received from website contact form:

Name    : {$data['name']}
Phone   : {$data['phone']}
Email   : {$data['email']}
Message : {$data['message']}
            ";
            $mail->send();

            $this->valid['success'] = true;
            $this->valid['message'] = "Mail sent successfully to admin.";

        } catch (Exception $e) {
            $this->valid['success'] = false;
            $this->valid['message'] = "Admin mail failed: " . $mail->ErrorInfo;
        }

        return $this->valid;
    }

    // Blog comment enquiry
    public function blogEnquiry($data)
    {
        if (empty($data['email']) || empty($data['name']) || empty($data['message'])) {
            $this->valid['success'] = false;
            $this->valid['message'] = "Required fields are missing.";
            return $this->valid;
        }

        $mail = $this->configureMailer();

        try {
            // Send confirmation to user
            $mail->addAddress($data['email']);
            $mail->Subject = "Your comment is received - " . $data['name'];
            $mail->Body = "
Dear {$data['name']},

Thank you for your comment on our blog post: \"{$data['blog_title']}\".

We have received your message and will review it shortly.

Regards,
Organi Team
            ";
            $mail->send();

            $this->valid['success'] = true;
            $this->valid['message'] = "Mail sent successfully to user.";

        } catch (Exception $e) {
            $this->valid['success'] = false;
            $this->valid['message'] = "User mail failed: " . $mail->ErrorInfo;
        }

        try {
            // Send notification to admin
            $mail->clearAddresses();
            $mail->addAddress("soundarya.ramesh0712@gmail.com");

            $mail->Subject = "New Blog Comment - " . $data['name'];
            $mail->Body = "
New comment received from blog:

Blog Post : {$data['blog_title']}
Name      : {$data['name']}
Email     : {$data['email']}
Message   : {$data['message']}
            ";
            $mail->send();

            $this->valid['success'] = true;
            $this->valid['message'] = "Mails sent successfully.";

        } catch (Exception $e) {
            $this->valid['message'] .= " | Admin mail failed: " . $mail->ErrorInfo;
        }

        return $this->valid;
    }
}
?>