<?php
/**
 * Telegram 调试脚本
 * 详细测试 Telegram 连接并显示错误信息
 */

// 配置信息
$bot_token = '8005042122:AAExvHlkQ3R4tH4IEt1BvKgiUJqXA9wfjg0';
$chat_id = '-4943598430';

echo "🔧 Telegram 连接调试工具\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

echo "📋 配置信息:\n";
echo "   Bot Token: {$bot_token}\n";
echo "   Chat ID: {$chat_id}\n\n";

// 1. 测试网络连接
echo "🌐 1. 测试网络连接到 Telegram API...\n";
$testUrl = "https://api.telegram.org";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $testUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_CONNECTTIMEOUT => 5,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_VERBOSE => false,
]);

$networkTest = curl_exec($ch);
$networkError = curl_error($ch);
$networkHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($networkError) {
    echo "   ❌ 网络连接失败: {$networkError}\n\n";
} else {
    echo "   ✅ 网络连接正常 (HTTP {$networkHttpCode})\n\n";
}

// 2. 测试 Bot Token 是否有效
echo "🤖 2. 验证 Bot Token...\n";
$getMeUrl = "https://api.telegram.org/bot{$bot_token}/getMe";
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $getMeUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
]);

$getMeResponse = curl_exec($ch);
$getMeError = curl_error($ch);
$getMeHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($getMeError) {
    echo "   ❌ 请求失败: {$getMeError}\n\n";
} else {
    echo "   HTTP 状态码: {$getMeHttpCode}\n";
    $getMeData = json_decode($getMeResponse, true);
    if ($getMeData && $getMeData['ok']) {
        echo "   ✅ Bot Token 有效\n";
        echo "   Bot 用户名: @{$getMeData['result']['username']}\n";
        echo "   Bot 名称: {$getMeData['result']['first_name']}\n\n";
    } else {
        echo "   ❌ Bot Token 无效\n";
        echo "   响应: {$getMeResponse}\n\n";
    }
}

// 3. 测试发送消息
echo "💬 3. 测试发送消息...\n";
$sendUrl = "https://api.telegram.org/bot{$bot_token}/sendMessage";
$testMessage = "🧪 测试消息 - " . date('Y-m-d H:i:s');

$data = [
    'chat_id' => $chat_id,
    'text' => $testMessage,
    'parse_mode' => 'Markdown',
    'disable_web_page_preview' => true,
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $sendUrl,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_VERBOSE => false,
]);

$sendResponse = curl_exec($ch);
$sendError = curl_error($ch);
$sendHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlInfo = curl_getinfo($ch);
curl_close($ch);

echo "   请求 URL: {$sendUrl}\n";
echo "   HTTP 状态码: {$sendHttpCode}\n";

if ($sendError) {
    echo "   ❌ cURL 错误: {$sendError}\n";
} else {
    echo "   ✅ cURL 请求成功\n";
}

echo "   响应内容: {$sendResponse}\n";

$sendData = json_decode($sendResponse, true);
if ($sendData) {
    if ($sendData['ok']) {
        echo "   ✅ 消息发送成功！\n";
        echo "   消息 ID: {$sendData['result']['message_id']}\n";
    } else {
        echo "   ❌ 消息发送失败\n";
        echo "   错误代码: {$sendData['error_code']}\n";
        echo "   错误描述: {$sendData['description']}\n";
        
        // 常见错误处理建议
        switch ($sendData['error_code']) {
            case 400:
                if (strpos($sendData['description'], 'chat not found') !== false) {
                    echo "   💡 建议: Chat ID 不正确，请检查是否正确获取了聊天 ID\n";
                } elseif (strpos($sendData['description'], 'parse_mode') !== false) {
                    echo "   💡 建议: Markdown 解析错误，请检查消息格式\n";
                }
                break;
            case 401:
                echo "   💡 建议: Bot Token 无效或已过期\n";
                break;
            case 403:
                echo "   💡 建议: Bot 被用户阻止或没有权限发送消息\n";
                break;
            case 429:
                echo "   💡 建议: 请求频率过高，请稍后重试\n";
                break;
        }
    }
} else {
    echo "   ❌ 无法解析响应 JSON\n";
}

echo "\n";

// 4. 详细的 cURL 信息
echo "🔍 4. 详细的连接信息:\n";
echo "   总耗时: " . round($curlInfo['total_time'], 3) . " 秒\n";
echo "   DNS 解析耗时: " . round($curlInfo['namelookup_time'], 3) . " 秒\n";
echo "   连接耗时: " . round($curlInfo['connect_time'], 3) . " 秒\n";
echo "   SSL 握手耗时: " . round($curlInfo['appconnect_time'], 3) . " 秒\n";
echo "   重定向次数: {$curlInfo['redirect_count']}\n";

echo "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

// 5. 提供解决方案
echo "🛠️ 可能的解决方案:\n\n";
echo "1. 确保 Bot Token 正确:\n";
echo "   - 通过 @BotFather 重新生成 Token\n";
echo "   - 检查 Token 格式是否完整\n\n";

echo "2. 确保 Chat ID 正确:\n";
echo "   - 发送消息给 Bot 后，访问: https://api.telegram.org/bot{$bot_token}/getUpdates\n";
echo "   - 查找 chat.id 字段获取正确的 Chat ID\n\n";

echo "3. 检查网络和防火墙:\n";
echo "   - 确保服务器可以访问 api.telegram.org\n";
echo "   - 检查是否有防火墙阻止 HTTPS 请求\n\n";

echo "4. 检查服务器时间:\n";
echo "   - 当前服务器时间: " . date('Y-m-d H:i:s T') . "\n";
echo "   - 确保服务器时间准确\n\n";

echo "调试完成！\n";
?>
