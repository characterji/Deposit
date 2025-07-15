<?php
// deposit.php

// ब्राउज़र को यह बताने के लिए हेडर कि यह एक JSON प्रतिक्रिया है
header("Content-Type: application/json");
// किसी भी डोमेन से अनुरोध की अनुमति देने के लिए (विकास के लिए उपयोगी)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");

// सुनिश्चित करें कि यह एक POST अनुरोध है
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// जावास्क्रिप्ट से भेजे गए JSON डेटा को पढ़ें
$input = file_get_contents('php://input');
$data = json_decode($input);

// --- अपनी गुप्त कुंजियाँ यहाँ सुरक्षित रूप से डालें ---
$token_key = 'cb759a42eb4ffba2180511b98143cd8b';  // अपनी Zapupi टोकन कुंजी यहाँ डालें
$secret_key = '207b3281d00ab7b4fbf1e497204a2b5e'; // अपनी Zapupi गुप्त कुंजी यहाँ डालें

// भेजे गए डेटा से राशि और मोबाइल नंबर प्राप्त करें
$amount = $data->amount ?? null;
$customer_mobile = $data->customer_mobile ?? '';

// जाँचें कि राशि दी गई है या नहीं
if (empty($amount)) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Amount is a required field.']);
    exit;
}

// Zapupi API को भेजने के लिए डेटा तैयार करें
$postData = [
    'token_key'       => $token_key,
    'secret_key'      => $secret_key,
    'amount'          => $amount,
    'order_id'        => 'ORD-' . time() . '-' . rand(100, 999), // हर बार एक अद्वितीय ऑर्डर आईडी बनाएँ
    'customer_mobile' => $customer_mobile,
    // भुगतान के बाद उपयोगकर्ता को कहाँ वापस भेजना है। इसे अपनी वेबसाइट के URL से बदलें।
    'redirect_url'    => 'https://yourwebsite.com/payment-success.html' 
];

// cURL का उपयोग करके Zapupi API पर सुरक्षित रूप से कॉल करें
$ch = curl_init('https://api.zapupi.com/api/create-order');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));

// API से प्रतिक्रिया प्राप्त करें
$response = curl_exec($ch);

// जाँचें कि क्या cURL में कोई त्रुटि हुई है
if (curl_errno($ch)) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'API connection failed: ' . curl_error($ch)]);
    exit;
}

// cURL सत्र बंद करें
curl_close($ch);

// Zapupi से मिली प्रतिक्रिया को सीधे ब्राउज़र को वापस भेजें
echo $response;

?>