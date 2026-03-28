<?php
// ============================
// ⚙️ إعدادات البوت
// ============================
define('PAGE_ACCESS_TOKEN', 'EAAhgZBdcLCbcBRDR3BMNZB3mNkYTP90P7xjtUB9FyjDVjnZCdQ6b6Hax7n78DEglacXOZCzkAgdNZBvG2ypSZAN9G3CZCIn0pdM0c5NToi9qP9Jaof9PhFObuPyLfZCeMXm2QL687YOd2oTocfqAW5plB4Pa1eQFdnbRZArTZBGScIAQnSPeQMFW2vhaEDe1ERnU92XorbjgZDZD');
$VERIFY_TOKEN = "WolfBotVerify2026!";

// ============================
// 🗂️ العروض
// ============================
$OFFER_NAMES = [
    "imt190" => "✨ إمتياز 190 (10GB)",
    "imt70" => "🔥 إمتياز 70 (4GB)",
    "30gb" => "🚀 إنترنت 30GB شهري",
    "d1" => "📅 إنترنت 1GB يومي",
    "walk2gb" => "🌟 الهدية الأسبوعية 2GB"
];

// ============================
// 📝 تخزين جلسات المستخدمين
// ============================
$user_sessions = [];

// ============================
// 🔑 التحقق من Webhook
// ============================
if($_SERVER['REQUEST_METHOD'] === 'GET'){
    $token_sent = $_GET['hub_verify_token'] ?? '';
    echo ($token_sent === $VERIFY_TOKEN) ? ($_GET['hub_challenge'] ?? '') : 'Invalid token';
    exit;
}

// ============================
// 📩 استقبال الرسائل
// ============================
$input = json_decode(file_get_contents('php://input'), true);
if(isset($input['entry'][0]['messaging'][0])){
    $messaging = $input['entry'][0]['messaging'][0];
    $senderId = $messaging['sender']['id'];
    $text = strtolower(trim($messaging['message']['text'] ?? ''));

    // --- تسجيل الدخول بالـ OTP ---
    if($text == 'login'){
        sendMessage($senderId, "📱 أرسل رقم جيزي (07xxxxxxxx):");
        $user_sessions[$senderId]['state'] = 'await_phone';
        exit;
    }

    // --- معالجة حالة انتظار رقم الهاتف ---
    if($user_sessions[$senderId]['state'] ?? '' === 'await_phone'){
        $phone = preg_replace('/\D/', '', $text);
        if(str_starts_with($phone,'0')) $phone = "213".substr($phone,1);
        $user_sessions[$senderId]['phone'] = $phone;
        $user_sessions[$senderId]['state'] = 'await_otp';
        sendMessage($senderId, "📩 أدخل كود OTP الآن:");
        exit;
    }

    // --- معالجة حالة انتظار OTP ---
    if($user_sessions[$senderId]['state'] ?? '' === 'await_otp'){
        $phone = $user_sessions[$senderId]['phone'];
        $otp = $text;
        // --- هنا يجب الاتصال بـ API جيزي للحصول على توكن حقيقي ---
        $token = "ACCESS_TOKEN_FROM_API"; 
        $user_sessions[$senderId]['token'] = $token;
        $user_sessions[$senderId]['state'] = 'logged_in';
        sendMessage($senderId, "✅ تم تسجيل الدخول بنجاح!");
        showMainPanel($senderId);
        exit;
    }

    // --- قائمة العروض الرئيسية ---
    if($text == 'عروض'){
        $buttons = [
            ["content_type"=>"text","title"=>"2GB أسبوعية","payload"=>"walk2gb"],
            ["content_type"=>"text","title"=>"إمتياز 70","payload"=>"imt70"],
            ["content_type"=>"text","title"=>"إمتياز 190","payload"=>"imt190"],
            ["content_type"=>"text","title"=>"30GB شهري","payload"=>"30gb"],
            ["content_type"=>"text","title"=>"1GB يومي","payload"=>"d1"]
        ];
        sendMessage($senderId, "🌟 اختر العرض الذي تريد تفعيله:", $buttons);
        exit;
    }

    // --- تفعيل العروض ---
    $payload = $messaging['message']['quick_reply']['payload'] ?? $text;

    if(in_array($payload,['walk2gb','imt70','imt190','30gb','d1'])){
        $phone = $user_sessions[$senderId]['phone'] ?? "213xxxxxxxxx";
        $token = $user_sessions[$senderId]['token'] ?? "TOKEN_ACCESS";
        $h_auth = ['Authorization'=>"Bearer $token", 'User-Agent'=>"MobileApp/3.0.0"];

        $codes = [
            "walk2gb"=>"GIFTWALKWIN2GO",
            "imt70"=>"BTLINTSPEEDDAY2Go",
            "imt190"=>"BTL4GBDAY",
            "30gb"=>"DOVINTSPEEDMONTH30GoPRE",
            "d1"=>"DOVINTSPEEDDAY1GoPRE"
        ];

        $url = $payload === 'walk2gb' 
            ? "https://apim.djezzy.dz/mobile-api/api/v1/services/walk/activate-reward/$phone"
            : "https://apim.djezzy.dz/mobile-api/api/v1/subscribers/activate-product/$phone";

        $post_data = ["packageCode"=>$codes[$payload]];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($h_auth, ['Content-Type: application/json']));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if(in_array($status,[200,201,202])){
            sendMessage($senderId, "✅ تم تفعيل ".$OFFER_NAMES[$payload]." بنجاح!");
        }else{
            sendMessage($senderId, "❌ فشل التفعيل، تأكد من الشروط.");
        }
    }
}

// ============================
// 🖥️ لوحة التحكم
// ============================
function showMainPanel($userId){
    $buttons = [
        ["content_type"=>"text","title"=>"2GB أسبوعية","payload"=>"walk2gb"],
        ["content_type"=>"text","title"=>"كشف الرصيد","payload"=>"info"],
        ["content_type"=>"text","title"=>"سجل الاشتراكات","payload"=>"history"],
        ["content_type"=>"text","title"=>"عروض Imtiyaz","payload"=>"menu_imt"]
    ];
    sendMessage($userId, "🐺 لوحة تحكم 𝑾𝑶𝑳𝑭 مفتوحة ✅", $buttons);
}

// ============================
// 🔔 دالة إرسال الرسائل
// ============================
function sendMessage($userId, $text, $buttons = null){
    $payload = [
        "recipient" => ["id" => $userId],
        "message" => ["text" => $text]
    ];

    if($buttons){
        $payload["message"]["quick_replies"] = $buttons;
    }

    $ch = curl_init("https://graph.facebook.com/v17.0/me/messages?access_token=".PAGE_ACCESS_TOKEN);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}
?>
