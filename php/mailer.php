<?php
session_start();

require_once("./mailTrigger.php");

header('Content-Type: application/json');

$res = ["success" => false, "message" => ""];

// ── 1. HONEYPOT CHECK ──────────────────────────────────────
if (!empty($_POST['website'])) {
    $res["message"] = "Bot detected.";
    echo json_encode($res);
    exit;
}

// ── 2. CSRF CHECK ──────────────────────────────────────────
if (empty($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    $res["message"] = "Invalid request token.";
    echo json_encode($res);
    exit;
}

// ── 3. RATE LIMITING (5 submissions per 10 minutes) ────────
$ip     = $_SERVER['REMOTE_ADDR'];
$key    = 'rate_' . md5($ip);
$now    = time();
$window = 600; // 10 minutes
$limit  = 5;

if (!isset($_SESSION[$key])) {
    $_SESSION[$key] = ['count' => 0, 'start' => $now];
}

if ($now - $_SESSION[$key]['start'] > $window) {
    $_SESSION[$key] = ['count' => 0, 'start' => $now];
}

$_SESSION[$key]['count']++;

if ($_SESSION[$key]['count'] > $limit) {
    $res["message"] = "Too many requests. Please try again after 10 minutes.";
    echo json_encode($res);
    exit;
}

// ── 4. TYPE CHECK ──────────────────────────────────────────
if (!isset($_POST["type"])) {
    $res["message"] = "Invalid request.";
    echo json_encode($res);
    exit;
}

// ── 5. MESSAGE VALIDATION ──────────────────────────────────
if (isset($_POST['message'])) {
    $message = trim($_POST['message']);

    if (empty($message)) {
        $res["message"] = "Message cannot be empty.";
        echo json_encode($res);
        exit;
    }

    if (strlen(preg_replace('/\s+/', '', $message)) < 3) {
        $res["message"] = "Message must contain at least 3 characters.";
        echo json_encode($res);
        exit;
    }

    $_POST['message'] = $message;
}

// ── 6. reCAPTCHA VERIFICATION ─────────────────────────────
$formsWithCaptcha = ["contactForm"];

if (in_array($_POST["type"], $formsWithCaptcha)) {
    if (empty($_POST['g-recaptcha-response'])) {
        $res["message"] = "Please complete the CAPTCHA.";
        echo json_encode($res);
        exit;
    }

$secret = getenv('RECAPTCHA_SECRET');
$ch = curl_init("https://www.google.com/recaptcha/api/siteverify");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
    'secret'   => $secret,
    'response' => $_POST['g-recaptcha-response']
]));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$verify  = curl_exec($ch);
curl_close($ch);
$captcha = json_decode($verify);

    if (!$captcha->success) {
        $res["message"] = "CAPTCHA verification failed. Please try again.";
        echo json_encode($res);
        exit;
    }
}

// ── 7. ROUTE TO HANDLER ───────────────────────────────────
$sm = new sndMail();

switch ($_POST["type"]) {
    case "contactForm":
        $res = $sm->contactEnquiry($_POST);
        break;
    case "commentForm":
        $res = $sm->blogEnquiry($_POST);
        break;
    default:
        $res["message"] = "Invalid request type.";
        break;
}

echo json_encode($res);
?>