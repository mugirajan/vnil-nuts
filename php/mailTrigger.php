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
        $mail->setFrom("soundarya.ramesh0712@gmail.com", "Vnil Nuts");
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

Warm greetings from Vnil Nuts!
We are delighted to hear from you. Your enquiry has been safely received, and we will make sure our team connects with you as soon as possible.
Thank you for choosing Vnil Nuts — where every nut is Pure, Premium, and Handpicked just for you.

With warm regards,
The Vnil Nuts Team
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

Thank you for being a part of the Vnil Nuts community!
Your comment on our blog post \"{$data['blog_title']}\" has been received. We love hearing thoughts and opinions from our readers, and we will get back to you shortly.
Keep reading, keep sharing!

With warm regards,
The Vnil Nuts Team
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