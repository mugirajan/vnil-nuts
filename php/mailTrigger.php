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
        $mail->Password   = "wprxfjbrqlgpirhr";
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->setFrom("soundarya.ramesh0712@gmail.com", "Vnil Nuts");
        $mail->isHTML(true);
        return $mail;
    }

    // Contact form enquiry
    public function contactEnquiry($data)
    {
        $mail = $this->configureMailer();

        try {
            // Send confirmation to user
            $mail->addAddress($data['email']);
            $mail->Subject = "Your Enquiry is Received - " . $data['name'];
            $mail->Body = "
<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
    <div style='background-color: #C9956B; padding: 30px; text-align: center;'>
        <h1 style='color: white; margin: 0; letter-spacing: 2px;'>VNIL Nuts</h1>
        <p style='color: white; margin: 5px 0; font-size: 13px;'>Pure. Premium. Handpicked.</p>
    </div>
    <div style='padding: 35px; background-color: #FAEAE0;'>
        <h2 style='color: #C9956B;'>Thank You, {$data['name']}! 🌟</h2>
        <p style='color: #555; font-size: 15px; line-height: 1.7;'>We have received your enquiry and our team will get back to you shortly.</p>
        <p style='color: #555; font-size: 15px; line-height: 1.7;'>We truly appreciate you reaching out to us!</p>
        <div style='margin-top: 25px; padding: 15px; background-color: #fff; border-left: 4px solid #C9956B; border-radius: 4px;'>
            <p style='margin: 0; color: #888; font-size: 13px;'>This is an automated confirmation. Please do not reply to this email.</p>
        </div>
    </div>
    <div style='background-color: #333; padding: 20px; text-align: center;'>
        <p style='color: #aaa; margin: 0; font-size: 12px;'>© 2026 VNIL Nuts. All rights reserved.</p>
    </div>
</div>
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
<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
    <div style='background-color: #C9956B; padding: 25px; text-align: center;'>
        <h2 style='color: white; margin: 0;'>New Contact Enquiry</h2>
        <p style='color: white; margin: 5px 0; font-size: 13px;'>Received from VNIL Nuts website</p>
    </div>
    <div style='padding: 30px; background-color: #f9f9f9;'>
        <table style='width: 100%; border-collapse: collapse;'>
            <tr style='border-bottom: 1px solid #eee;'>
                <td style='padding: 12px 10px; color: #C9956B; font-weight: bold; width: 30%;'>Name</td>
                <td style='padding: 12px 10px; color: #333;'>{$data['name']}</td>
            </tr>
            <tr style='border-bottom: 1px solid #eee; background-color: #fff;'>
                <td style='padding: 12px 10px; color: #C9956B; font-weight: bold;'>Phone</td>
                <td style='padding: 12px 10px; color: #333;'>{$data['phone']}</td>
            </tr>
            <tr style='border-bottom: 1px solid #eee;'>
                <td style='padding: 12px 10px; color: #C9956B; font-weight: bold;'>Email</td>
                <td style='padding: 12px 10px; color: #333;'>{$data['email']}</td>
            </tr>
            <tr style='background-color: #fff;'>
                <td style='padding: 12px 10px; color: #C9956B; font-weight: bold;'>Message</td>
                <td style='padding: 12px 10px; color: #333;'>{$data['message']}</td>
            </tr>
        </table>
    </div>
    <div style='background-color: #333; padding: 15px; text-align: center;'>
        <p style='color: #aaa; margin: 0; font-size: 12px;'>© 2026 VNIL Nuts Admin Panel</p>
    </div>
</div>
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
            $mail->Subject = "Your Comment is Received - " . $data['name'];
            $mail->Body = "
<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
    <div style='background-color: #C9956B; padding: 30px; text-align: center;'>
        <h1 style='color: white; margin: 0; letter-spacing: 2px;'>VNIL Nuts</h1>
        <p style='color: white; margin: 5px 0; font-size: 13px;'>Pure. Premium. Handpicked.</p>
    </div>
    <div style='padding: 35px; background-color: #FAEAE0;'>
        <h2 style='color: #C9956B;'>Thank You, {$data['name']}!</h2>
        <p style='color: #555; font-size: 15px; line-height: 1.7;'>We have received your comment on our blog post: <strong>{$data['blog_title']}</strong></p>
        <p style='color: #555; font-size: 15px; line-height: 1.7;'>Our team will review it shortly.</p>
        <div style='margin-top: 25px; padding: 15px; background-color: #fff; border-left: 4px solid #C9956B; border-radius: 4px;'>
            <p style='margin: 0; color: #888; font-size: 13px;'>This is an automated confirmation. Please do not reply to this email.</p>
        </div>
    </div>
    <div style='background-color: #333; padding: 20px; text-align: center;'>
        <p style='color: #aaa; margin: 0; font-size: 12px;'>© 2026 VNIL Nuts. All rights reserved.</p>
    </div>
</div>
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
<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
    <div style='background-color: #C9956B; padding: 25px; text-align: center;'>
        <h2 style='color: white; margin: 0;'>New Blog Comment</h2>
        <p style='color: white; margin: 5px 0; font-size: 13px;'>Received from VNIL Nuts website</p>
    </div>
    <div style='padding: 30px; background-color: #f9f9f9;'>
        <table style='width: 100%; border-collapse: collapse;'>
            <tr style='border-bottom: 1px solid #eee;'>
                <td style='padding: 12px 10px; color: #C9956B; font-weight: bold; width: 30%;'>Blog Post</td>
                <td style='padding: 12px 10px; color: #333;'>{$data['blog_title']}</td>
            </tr>
            <tr style='border-bottom: 1px solid #eee; background-color: #fff;'>
                <td style='padding: 12px 10px; color: #C9956B; font-weight: bold;'>Name</td>
                <td style='padding: 12px 10px; color: #333;'>{$data['name']}</td>
            </tr>
            <tr style='border-bottom: 1px solid #eee;'>
                <td style='padding: 12px 10px; color: #C9956B; font-weight: bold;'>Email</td>
                <td style='padding: 12px 10px; color: #333;'>{$data['email']}</td>
            </tr>
            <tr style='background-color: #fff;'>
                <td style='padding: 12px 10px; color: #C9956B; font-weight: bold;'>Message</td>
                <td style='padding: 12px 10px; color: #333;'>{$data['message']}</td>
            </tr>
        </table>
    </div>
    <div style='background-color: #333; padding: 15px; text-align: center;'>
        <p style='color: #aaa; margin: 0; font-size: 12px;'>© 2026 VNIL Nuts Admin Panel</p>
    </div>
</div>
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