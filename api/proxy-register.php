<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 只允许POST请求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => '方法不允许']);
    exit();
}

// 获取请求数据
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['targetSite']) || !isset($data['registrationData'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => '请求数据无效']);
    exit();
}

$targetSite = $data['targetSite'];
$registrationData = $data['registrationData'];

// 验证目标网站是否在允许列表中
$allowedSites = [
    '8y369.com',
    '8y258.com', 
    '8y68.com',
    '8yapp.com',
    '8y520.com',
    '8y668.com',
    '818by.com',
    '518by.com',
    '81svip.com',
    '8y98.com'
];

if (!in_array($targetSite, $allowedSites)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => '目标网站不在允许列表中']);
    exit();
}

// 构建目标API URL
if ($targetSite === 'test.keepget.com') {
    // 使用本地测试API
    $targetApiUrl = "http://keepget.com/api/test-register.php";
} else {
    $targetApiUrl = "https://{$targetSite}/api/register";
}

// 准备cURL请求
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $targetApiUrl,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($registrationData),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'User-Agent: KeepGet-Sync-Register/1.0',
        'X-Forwarded-For: ' . $_SERVER['REMOTE_ADDR']
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 3
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

if ($error) {
    // cURL错误
    curl_close($ch);
    
    // 记录错误日志
    error_log("KeepGet Proxy Register Error: {$error} for {$targetSite}");
    
    echo json_encode([
        'success' => false,
        'message' => '连接目标网站失败',
        'error' => $error,
        'suggestion' => 'manual_register'
    ]);
    exit();
}

curl_close($ch);

// 检查HTTP状态码
if ($httpCode >= 200 && $httpCode < 300) {
    // 成功响应
    $responseData = json_decode($response, true);
    
    if ($responseData && isset($responseData['success'])) {
        // 目标网站返回了标准格式
        echo json_encode($responseData);
    } else {
        // 目标网站返回了非标准格式，假设注册成功
        echo json_encode([
            'success' => true,
            'message' => '注册成功',
            'loginToken' => generateLoginToken($registrationData['email'], $targetSite),
            'userId' => time() . rand(1000, 9999)
        ]);
    }
} else if ($httpCode === 409 || $httpCode === 400) {
    // 客户端错误（如邮箱已存在）
    $responseData = json_decode($response, true);
    echo json_encode([
        'success' => false,
        'message' => $responseData['message'] ?? '注册失败',
        'errorCode' => $responseData['errorCode'] ?? 'REGISTRATION_FAILED',
        'httpCode' => $httpCode
    ]);
} else if ($httpCode === 404) {
    // API端点不存在
    echo json_encode([
        'success' => false,
        'message' => '目标网站暂不支持自动注册',
        'errorCode' => 'API_NOT_FOUND',
        'suggestion' => 'manual_register'
    ]);
} else {
    // 其他服务器错误
    echo json_encode([
        'success' => false,
        'message' => '目标网站暂时无法访问',
        'errorCode' => 'SERVER_ERROR',
        'httpCode' => $httpCode,
        'suggestion' => 'retry_later'
    ]);
}

// 生成登录令牌的辅助函数
function generateLoginToken($email, $targetSite) {
    $timestamp = time();
    $randomStr = bin2hex(random_bytes(8));
    $hashBase = "{$email}_{$targetSite}_{$timestamp}_{$randomStr}";
    return substr(base64_encode($hashBase), 0, 32);
}

// 记录访问日志
function logRegistrationAttempt($targetSite, $email, $success, $error = null) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'target_site' => $targetSite,
        'email' => hash('sha256', $email), // 哈希邮箱保护隐私
        'success' => $success,
        'error' => $error,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ];
    
    $logFile = __DIR__ . '/logs/proxy_register.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
}
?>