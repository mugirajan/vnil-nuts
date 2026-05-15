<?php

require_once("./vendor/autoload.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ── Load .env file ─────────────────────────────────────────
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);
            putenv($key . '=' . $value);
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
}

class sndMail
{
    private $valid = array("success" => false, "message" => "");

    public function __construct()
    {
        // session started by caller (mailer.php)
    }

    // ── Helper: read env with fallback ────────────────────
    private function env($key)
    {
        return $_ENV[$key] ?? getenv($key) ?? '';
    }

    // ── Configure PHPMailer ───────────────────────────────
    private function configureMailer()
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = "smtp.gmail.com";
        $mail->Port       = 587;
        $mail->SMTPAuth   = true;
        $mail->Username   = $this->env('GMAIL_USER');
        $mail->Password   = $this->env('GMAIL_PASS');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->setFrom($this->env('GMAIL_USER'), "Vnil Nuts");
        $mail->isHTML(false);
        return $mail;
    }

    // ── Contact form enquiry ───────────────────────────────
    public function contactEnquiry($data)
    {
        // ── Input validation ──────────────────────────────
        if (empty($data['email']) || empty($data['name']) || empty($data['message'])) {
            $this->valid['success'] = false;
            $this->valid['message'] = "Required fields are missing.";
            return $this->valid;
        }

        // ── Email sanitization & validation ───────────────
        $data['email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->valid['success'] = false;
            $this->valid['message'] = "Invalid email address.";
            return $this->valid;
        }

        $mail          = $this->configureMailer();
        $userMailSent  = false;
        $adminMailSent = false;

        // ── Send confirmation to user ─────────────────────
        try {
            $mail->clearAllRecipients();
            $mail->addAddress($data['email']);
            $mail->Subject = "Your enquiry is received - " . $data['name'];
            $mail->Body    = "
Dear {$data['name']},

Warm greetings from Vnil Nuts!
We are delighted to hear from you. Your enquiry has been safely received, and we will make sure our team connects with you as soon as possible.
Thank you for choosing Vnil Nuts — where every nut is Pure, Premium, and Handpicked just for you.

With warm regards,
The Vnil Nuts Team
            ";
            $mail->send();
            $userMailSent = true;
        } catch (Exception $e) {
            error_log("User mail failed: " . $mail->ErrorInfo);
        }

        // ── Send notification to admin ────────────────────
        try {
            $mail->clearAllRecipients();
            $mail->addAddress($this->env('GMAIL_USER'));
            $mail->Subject = "New Contact Enquiry - " . $data['name'];
            $mail->Body    = "
Hello Admin,

A new enquiry has been submitted through the Vnil Nuts website by {$data['name']} who can be reached at {$data['email']} or {$data['phone']}. They have shared the following message — {$data['message']}
Kindly follow up with the customer at the earliest.

- Vnil Nuts System
            ";
            $mail->send();
            $adminMailSent = true;
        } catch (Exception $e) {
            error_log("Admin mail failed: " . $mail->ErrorInfo);
        }

        // ── Final response ────────────────────────────────
        if ($userMailSent && $adminMailSent) {
            $this->valid['success'] = true;
            $this->valid['message'] = "Mails sent successfully.";
        } else {
            $this->valid['success'] = false;
            $this->valid['message'] = "Mail delivery issue. Please try again.";
        }

        return $this->valid;
    }

    // ── Blog comment enquiry ───────────────────────────────
    public function blogEnquiry($data)
    {
        // ── Input validation ──────────────────────────────
        if (empty($data['email']) || empty($data['name']) || empty($data['message'])) {
            $this->valid['success'] = false;
            $this->valid['message'] = "Required fields are missing.";
            return $this->valid;
        }

        // ── Email sanitization & validation ───────────────
        $data['email'] = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->valid['success'] = false;
            $this->valid['message'] = "Invalid email address.";
            return $this->valid;
        }

        $mail          = $this->configureMailer();
        $userMailSent  = false;
        $adminMailSent = false;

        // ── Send confirmation to user ─────────────────────
        try {
            $mail->clearAllRecipients();
            $mail->addAddress($data['email']);
            $mail->Subject = "Your comment is received - " . $data['name'];
            $mail->Body    = "
Dear {$data['name']},

Thank you for being a part of the Vnil Nuts community!
Your comment on our blog post \"{$data['blog_title']}\" has been received. We love hearing thoughts and opinions from our readers, and we will get back to you shortly.
Keep reading, keep sharing!

With warm regards,
The Vnil Nuts Team
            ";
            $mail->send();
            $userMailSent = true;
        } catch (Exception $e) {
            error_log("User mail failed: " . $mail->ErrorInfo);
        }

        // ── Send notification to admin ────────────────────
        try {
            $mail->clearAllRecipients();
            $mail->addAddress($this->env('GMAIL_USER'));
            $mail->Subject = "New Blog Comment - " . $data['name'];
            $mail->Body    = "
Hello Admin,

A new comment has been posted on the Vnil Nuts blog post titled \"{$data['blog_title']}\" by {$data['name']} who can be reached at {$data['email']}. They have shared the following message — {$data['message']}
Kindly review and approve the comment at the earliest.

- Vnil Nuts System
            ";
            $mail->send();
            $adminMailSent = true;
        } catch (Exception $e) {
            error_log("Admin mail failed: " . $mail->ErrorInfo);
        }

        // ── Final response ────────────────────────────────
        if ($userMailSent && $adminMailSent) {
            $this->valid['success'] = true;
            $this->valid['message'] = "Mails sent successfully.";
        } else {
            $this->valid['success'] = false;
            $this->valid['message'] = "Mail delivery issue. Please try again.";
        }

        return $this->valid;
    }
}
?>