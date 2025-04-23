<?php
// Simple PHP/JS Live Chat Popup (Guest only, demo)
session_start();
if (!isset($_SESSION['chat_user'])) {
    $_SESSION['chat_user'] = 'Guest_' . substr(md5(uniqid()), 0, 6);
}
$chat_user = $_SESSION['chat_user'];
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['message']) && trim($data['message']) !== '') {
        $msg = strip_tags($data['message']);
        $time = date('H:i');
        $line = json_encode([
            'user' => $chat_user,
            'message' => $msg,
            'time' => $time
        ]) . "\n";
        file_put_contents(__DIR__ . '/chat_messages.log', $line, FILE_APPEND);
        // Enhanced auto-reply bot: Syokichem
        $bot_reply = null;
        $msg_lc = strtolower($msg);
        if (strpos($msg_lc, 'hello') !== false || strpos($msg_lc, 'hi') !== false) {
            $bot_reply = 'Hello! I am Syokichem, your pharmacy assistant. How can I help you today?';
        } elseif (strpos($msg_lc, 'promotion') !== false || strpos($msg_lc, 'offer') !== false) {
            $bot_reply = 'Please check our homepage for the latest special offers and promotions!';
        } elseif (strpos($msg_lc, 'thank') !== false) {
            $bot_reply = 'You’re welcome! 😊 If you have more questions, feel free to ask.';
        } elseif (strpos($msg_lc, 'location') !== false || strpos($msg_lc, 'where') !== false) {
            $bot_reply = 'We are an online pharmacy. For contact and location details, please visit our Contact page.';
        } elseif (strpos($msg_lc, 'delivery') !== false || strpos($msg_lc, 'shipping') !== false) {
            $bot_reply = 'We offer fast and reliable medicine delivery. Orders placed before 3pm are delivered the same day within Nairobi.';
        } elseif (strpos($msg_lc, 'consult') !== false || strpos($msg_lc, 'doctor') !== false || strpos($msg_lc, 'pharmacist') !== false) {
            $bot_reply = 'We provide online consultation with licensed pharmacists. Visit our Online Consultation section for more details.';
        } elseif (strpos($msg_lc, 'payment') !== false || strpos($msg_lc, 'mpesa') !== false || strpos($msg_lc, 'pay') !== false) {
            $bot_reply = 'We accept payments via Mpesa and major credit/debit cards for your convenience and security.';
        } elseif (strpos($msg_lc, 'hours') !== false || strpos($msg_lc, 'open') !== false || strpos($msg_lc, 'close') !== false) {
            $bot_reply = 'Our online shop is open 24/7. Customer support is available 8am–8pm daily.';
        } elseif (strpos($msg_lc, 'return') !== false || strpos($msg_lc, 'refund') !== false) {
            $bot_reply = 'Please see our Terms page for details on returns and refunds. We strive for customer satisfaction!';
        } elseif (strpos($msg_lc, 'contact') !== false || strpos($msg_lc, 'support') !== false) {
            $bot_reply = 'You can reach us via the Contact page or this chat. We are here to assist you!';
        } elseif (strpos($msg_lc, 'products') !== false || strpos($msg_lc, 'medicine') !== false) {
            $bot_reply = 'We stock a wide range of genuine medicines and health products. Browse our Shop or use the search bar for specific items.';
        } else {
            $bot_reply = 'Thank you for your message. I am Syokichem, your virtual pharmacy assistant. For more help, please visit our site sections or ask another question!';
        }
        if ($bot_reply) {
            $bot_line = json_encode([
                'user' => 'Syokichem',
                'message' => $bot_reply,
                'time' => date('H:i')
            ]) . "\n";
            file_put_contents(__DIR__ . '/chat_messages.log', $bot_line, FILE_APPEND);
        }
        echo json_encode(['status' => 'ok']);
        exit;
    }
    echo json_encode(['status' => 'error', 'msg' => 'Empty message']);
    exit;
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $lines = @file(__DIR__ . '/chat_messages.log');
    $messages = [];
    foreach ($lines ?: [] as $line) {
        $row = json_decode($line, true);
        if ($row) $messages[] = $row;
    }
    echo json_encode(['messages' => $messages]);
    exit;
}
