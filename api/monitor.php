<?php
// 启动指令标注：copilot-debug php /www/wwwroot/keepget.com/api/monitor.php?action=check&notify=1
// 命令行启动示例：
// curl "http://localhost/api/monitor.php?action=check&notify=1"
// 或使用浏览器访问上述URL

// PHP兼容性函数
if (!function_exists('str_ends_with')) {
    function str_ends_with($haystack, $needle) {
        return substr($haystack, -strlen($needle)) === $needle;
    }
}

if (!function_exists('endsWith')) {
    function endsWith($haystack, $needle) {
        return substr($haystack, -strlen($needle)) === $needle;
    }
}

/**
 * KeepGet.com 域名监控API
 * 监控推荐域名状态并通过Telegram机器人发送报告
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// 处理预检请求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 配置文件
$config = [
    'telegram' => [
        'bot_token' => getenv('TELEGRAM_TOKEN') ?: '8005042122:AAExvHlkQ3R4tH4IEt1BvKgiUJqXA9wfjg0',
        'chat_id' => getenv('TELEGRAM_CHAT_ID') ?: '-4943598430',
    ],
    'domains' => [
        '8y369.com',
        '8y258.com',
        '8y68.com',
        '8yapp.com',
        '8y520.com',
        '8y668.com',
        '818by.com',
        '518by.com',
        '81svip.com',
        '8y98.com',
        'wj88.818by.com',
        'dl.518by.com',
        'by.518by.com',
        'cdn.8y98.com',
        'web.8y98.com',
        'vpn.8y98.com',
        'ssl.8y98.com',
        'speed.8y98.com',
        'server.8y98.com',
        'secure.8y98.com',
        'proxy.8y98.com',
        'node.8y98.com',
        'mobile.8y98.com',
        'link.8y98.com',
        'hub.8y98.com',
        'file.8y98.com',
        'fast.8y98.com',
        'download.8y98.com',
        'dl.8y98.com',
        'data.8y98.com',
        'cloud.8y98.com',
        '98.8y98.com',
        '8y.8y98.com',
        '81yx.8y98.com',
        '81.8y98.com',
        '8188.8y98.com',
        'aa.8y98.com',
        'ss.8y98.com',
        'start.8y98.com',
        'one.8y98.com',
        'byg.8y98.com',
        'byy.8y98.com',
        'byd.8y98.com',
        'byc.8y98.com',
        'byb.8y98.com',
        'bya.8y98.com',
        'game.8y98.com',
        'by.8y98.com',
        'app.8y98.com',
        'dns.8y98.com',
        'boooyugame2vip.com',
        'openjy8199.com',
        'bygame2vip.com',
        'bygame1vip.com',
        '8y88.top',
        '8y521.com',
        'b826419xp.com',
        '8y718.com',
        'opg00993rd.com',
        '120431sf8y.com',
        'ghkla3499.com',
        '8y84279hhd.com',
        '81yx.vip',
        '81yx.cc',
        '8y88.vip',
        '8y188.com',
        '8y168.com',
        'viphyy866887.com',
        'boyuyouxi9.com',
        'boyuyouxi8.com',
        'boyuyouxi7.com',
        'boyuyouxi6.com',
        'boyuyouxi5.com',
        'boyuyouxi4.com',
        'boyuyouxi3.com',
        'boyuyouxi2.com',
        'boyuyouxi1.com',
        'boyuyouxi.com',
        'boyu08.com',
        'boyu8888.vip',
        'boyuyx.top',
        'boyu004.com',
        'boyu003.com',
        'boyu006.com',
        'boyu005.com',
        'boyu002.com',
        'bomao666.vip',
        'yhzvip.vip',
        'yhzvip.com',
        'n2hz01.com',
        '2hzsvip.com',
        '1hzvip.com',
        '1hzsvip.com',
        '2hzvip.com',
        'fhzz2024.com',
    ],
    'check_interval' => 21600, // 6个小时检查一次
];

/**
 * 检查域名状态
 */
function checkDomainStatus($domain) {
    // 主要检查HTTPS访问性
    $httpsUrl = "https://$domain";
    
    // 检查HTTPS可访问性和获取页面内容
    $httpsStatus = checkUrlWithContent($httpsUrl);
    
    // SSL证书检查
    $sslInfo = checkSSLCertificate($domain);
    
    // DNS解析检查
    $dnsInfo = checkDNSResolution($domain);
    
    // 关键词验证
    $keywordValidation = validateKeywords($httpsStatus['content'] ?? '');
    
    return [
        'domain' => $domain,
        'timestamp' => date('Y-m-d H:i:s'),
        'https' => $httpsStatus,
        'ssl' => $sslInfo,
        'dns' => $dnsInfo,
        'keyword_validation' => $keywordValidation,
        'status' => determineOverallStatus($httpsStatus, $sslInfo, $dnsInfo, $keywordValidation),
    ];
}

/**
 * 检查URL可访问性并获取内容
 */
function checkUrlWithContent($url) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HEADER => true,
    ]);
    
    $start = microtime(true);
    $response = curl_exec($ch);
    $responseTime = round((microtime(true) - $start) * 1000, 2);
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    
    curl_close($ch);
    
    // 分离头部和内容
    $headers = substr($response, 0, $headerSize);
    $content = substr($response, $headerSize);
    
    // 检查不安全头部
    $unsafeHeaders = checkUnsafeHeaders($headers);
    
    return [
        'status_code' => $httpCode,
        'response_time' => $responseTime,
        'error' => $error,
        'accessible' => ($httpCode >= 200 && $httpCode < 400 && !$error),
        'content' => $content,
        'unsafe_headers' => $unsafeHeaders,
        'headers' => $headers,
    ];
}

/**
 * 检查不安全头部
 */
function checkUnsafeHeaders($headers) {
    $unsafeHeaders = [];
    $dangerousHeaders = [
        'X-Powered-By' => '暴露技术栈信息',
        'Server' => '暴露服务器信息', 
        'X-AspNet-Version' => '暴露ASP.NET版本',
        'X-AspNetMvc-Version' => '暴露ASP.NET MVC版本',
        'X-Generator' => '暴露生成器信息',
        'X-Drupal-Cache' => '暴露Drupal信息',
        'X-Varnish' => '暴露Varnish缓存信息',
        'Via' => '暴露代理信息',
        'X-Forwarded-Server' => '暴露转发服务器信息',
        'X-Runtime' => '暴露运行时信息',
        'X-Version' => '暴露版本信息',
    ];
    
    foreach ($dangerousHeaders as $header => $risk) {
        if (stripos($headers, $header . ':') !== false) {
            // 提取头部值
            $pattern = '/' . preg_quote($header, '/') . ':\s*([^\r\n]+)/i';
            if (preg_match($pattern, $headers, $matches)) {
                $unsafeHeaders[] = [
                    'header' => $header,
                    'value' => trim($matches[1]),
                    'risk' => $risk
                ];
            }
        }
    }
    
    return $unsafeHeaders;
}

/**
 * 关键词验证
 */
function validateKeywords($content) {
    $keywords = ['博鱼', '游戏', '客服', '登录', '用户名'];
    $foundKeywords = [];
    
    foreach ($keywords as $keyword) {
        if (strpos($content, $keyword) !== false) {
            $foundKeywords[] = $keyword;
        }
    }
    
    return [
        'found_keywords' => $foundKeywords,
        'keyword_count' => count($foundKeywords),
        'validation_passed' => count($foundKeywords) > 0,
        'content_length' => strlen($content),
    ];
}

/**
 * 检查DNS解析
 */
function checkDNSResolution($domain) {
    $records = @dns_get_record($domain, DNS_A);
    
    if (!$records) {
        return [
            'resolved' => false,
            'error' => 'DNS解析失败',
            'ips' => [],
            'record_count' => 0,
        ];
    }
    
    $ips = array_column($records, 'ip');
    
    return [
        'resolved' => true,
        'ips' => $ips,
        'record_count' => count($ips),
        'error' => null,
    ];
}

/**
 * 检查SSL证书
 */
function checkSSLCertificate($domain) {
    $context = stream_context_create([
        'ssl' => [
            'capture_peer_cert' => true,
            'verify_peer' => false,
            'verify_peer_name' => false,
        ]
    ]);
    
    $socket = @stream_socket_client(
        "ssl://$domain:443",
        $errno,
        $errstr,
        10,
        STREAM_CLIENT_CONNECT,
        $context
    );
    
    if (!$socket) {
        return [
            'valid' => false,
            'error' => "连接失败: $errstr ($errno)",
            'warnings' => [],
        ];
    }
    
    $cert = stream_context_get_params($socket)['options']['ssl']['peer_certificate'];
    fclose($socket);
    
    if (!$cert) {
        return [
            'valid' => false,
            'error' => '无法获取证书',
            'warnings' => [],
        ];
    }
    
    $certInfo = openssl_x509_parse($cert);
    $expiryDate = date('Y-m-d H:i:s', $certInfo['validTo_time_t']);
    $daysUntilExpiry = ceil(($certInfo['validTo_time_t'] - time()) / 86400);
    
    // SSL证书警告检查
    $warnings = [];
    
    // 证书即将过期警告
    if ($daysUntilExpiry <= 30) {
        $warnings[] = "证书将在{$daysUntilExpiry}天后过期";
    } elseif ($daysUntilExpiry <= 7) {
        $warnings[] = "证书即将过期！仅剩{$daysUntilExpiry}天";
    }
    
    // 自签名证书警告
    $subject = $certInfo['subject']['CN'] ?? '';
    $issuer = $certInfo['issuer']['CN'] ?? '';
    if ($subject === $issuer) {
        $warnings[] = "使用自签名证书，可能不被浏览器信任";
    }
    
    // 通配符证书检查
    if (strpos($subject, '*') !== false && $subject !== "*.$domain" && !preg_match('/\*\.(.+)/', $subject, $matches) || (isset($matches[1]) && $domain !== $matches[1] && !endsWith($domain, '.' . $matches[1]))) {
        $warnings[] = "SSL证书主机名不匹配：证书为 $subject，域名为 $domain";
    }
    
    // 弱加密算法警告
    $signatureAlgorithm = $certInfo['signatureTypeSN'] ?? '';
    if (stripos($signatureAlgorithm, 'sha1') !== false) {
        $warnings[] = "使用弱加密算法：$signatureAlgorithm";
    }
    
    return [
        'valid' => true,
        'subject' => $subject,
        'issuer' => $issuer,
        'expires_at' => $expiryDate,
        'days_until_expiry' => $daysUntilExpiry,
        'expires_soon' => $daysUntilExpiry <= 30,
        'signature_algorithm' => $signatureAlgorithm,
        'warnings' => $warnings,
        'is_self_signed' => ($subject === $issuer),
        'is_wildcard' => (strpos($subject, '*') !== false),
    ];
}


/**
 * 确定整体状态
 */
function determineOverallStatus($https, $ssl, $dns, $keywordValidation) {
    // DNS解析失败是致命问题
    if (!$dns['resolved']) {
        return 'critical';
    }
    
    // HTTPS不可访问是致命问题  
    if (!$https['accessible']) {
        return 'critical';
    }
    
    // 关键词验证失败表示页面内容异常
    if (!$keywordValidation['validation_passed']) {
        return 'critical';
    }
    
    // SSL证书无效是致命问题
    if (!$ssl['valid']) {
        return 'critical';
    }
    
    // 检查警告级别问题
    $hasWarnings = false;
    
    // SSL证书警告
    if (!empty($ssl['warnings'])) {
        $hasWarnings = true;
    }
    
    // 不安全头部警告
    if (!empty($https['unsafe_headers'])) {
        $hasWarnings = true;
    }
    
    // 响应时间过长警告
    if ($https['response_time'] > 5000) {
        $hasWarnings = true;
    }
    
    // 返回状态码非2xx的警告
    if ($https['status_code'] >= 300 && $https['status_code'] < 400) {
        $hasWarnings = true;
    }
    
    return $hasWarnings ? 'warning' : 'healthy';
}

/**
 * 发送Telegram消息
 */
function sendTelegramMessage($message, $config) {
    if (empty($config['telegram']['bot_token']) || empty($config['telegram']['chat_id'])) {
        return false;
    }
    
    $url = "https://api.telegram.org/bot{$config['telegram']['bot_token']}/sendMessage";
    
    $data = [
        'chat_id' => $config['telegram']['chat_id'],
        'text' => $message,
        'parse_mode' => 'Markdown',
        'disable_web_page_preview' => true,
    ];
    
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_TIMEOUT => 10,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return $httpCode === 200;
}

/**
 * 生成报告消息
 */
function generateReportMessage($results) {
    $timestamp = date('Y-m-d H:i:s');
    $message = "🔍 *KeepGet.com 域名监控报告*\n";
    $message .= "📅 检查时间: `$timestamp`\n\n";
    
    $criticalIssues = [];
    $warnings = [];
    $healthy = [];
    
    foreach ($results as $result) {
        $domain = $result['domain'];
        $status = $result['status'];
        
        $statusIcon = [
            'healthy' => '✅',
            'warning' => '⚠️',
            'critical' => '❌',
        ][$status] ?? '❓';
        
        $line = "$statusIcon *$domain*";
        
        if ($status === 'critical') {
            $issues = [];
            if (!$result['dns']['resolved']) {
                $issues[] = 'DNS解析失败';
            }
            if (!$result['https']['accessible']) {
                $issues[] = 'HTTPS不可访问';
            }
            if (!$result['keyword_validation']['validation_passed']) {
                $issues[] = '关键词验证失败';
            }
            if (!$result['ssl']['valid']) {
                $issues[] = 'SSL证书无效';
            }
            if (!empty($issues)) {
                $line .= " - " . implode(', ', $issues);
            }
            $criticalIssues[] = $line;
        } elseif ($status === 'warning') {
            $issues = [];
            if (!empty($result['ssl']['warnings'])) {
                $issues[] = 'SSL证书警告';
            }
            if (!empty($result['https']['unsafe_headers'])) {
                $issues[] = '不安全头部';
            }
            if ($result['https']['response_time'] > 5000) {
                $time = $result['https']['response_time'];
                $issues[] = "响应时间过长({$time}ms)";
            }
            if ($result['https']['status_code'] >= 300 && $result['https']['status_code'] < 400) {
                $issues[] = "重定向状态({$result['https']['status_code']})";
            }
            if (!empty($issues)) {
                $line .= " - " . implode(', ', $issues);
            }
            $warnings[] = $line;
        } else {
            $time = $result['https']['response_time'];
            $keywords = count($result['keyword_validation']['found_keywords']);
            $line .= " - 响应时间: {$time}ms, 关键词: {$keywords}个";
            $healthy[] = $line;
        }
    }

    // 添加关键问题
    if (!empty($criticalIssues)) {
        $message .= "🚨 *关键问题*:\n";
        foreach ($criticalIssues as $issue) {
            $message .= "$issue\n";
        }
        $message .= "\n";
    }
    
    // 添加警告
    if (!empty($warnings)) {
        $message .= "⚠️ *警告*:\n";
        foreach ($warnings as $warning) {
            $message .= "$warning\n";
        }
        $message .= "\n";
    }
    
    // 添加正常状态
    if (!empty($healthy)) {
        $message .= "✅ *正常运行*:\n";
        foreach ($healthy as $h) {
            $message .= "$h\n";
        }
    }
    
    // 添加总结
    $total = count($results);
    $criticalCount = count($criticalIssues);
    $warningCount = count($warnings);
    $healthyCount = count($healthy);
    
    $message .= "\n📊 *总结*: ";
    $message .= "总计 $total 个域名, ";
    $message .= "正常 $healthyCount 个, ";
    $message .= "警告 $warningCount 个, ";
    $message .= "异常 $criticalCount 个";
    
    return $message;
}

// 主要API处理逻辑
$action = $_GET['action'] ?? $_POST['action'] ?? 'status';

switch ($action) {
    case 'check':
        // 检查所有域名
        $results = [];
        foreach ($config['domains'] as $domain) {
            $results[] = checkDomainStatus($domain);
        }
        
        // 检查是否需要发送Telegram通知
        $sendNotification = $_GET['notify'] ?? $_POST['notify'] ?? false;
        $hasIssues = false;
        
        foreach ($results as $result) {
            if ($result['status'] !== 'healthy') {
                $hasIssues = true;
                break;
            }
        }
        
        $telegramSent = false;
        if ($sendNotification && $hasIssues) {
            $message = generateReportMessage($results);
            $telegramSent = sendTelegramMessage($message, $config);
        }
        
        echo json_encode([
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'results' => $results,
            'has_issues' => $hasIssues,
            'telegram_sent' => $telegramSent,
        ]);
        break;
        
    case 'status':
        // 返回API状态
        echo json_encode([
            'success' => true,
            'service' => 'KeepGet.com Domain Monitor',
            'version' => '1.0',
            'endpoints' => [
                '/api/monitor.php?action=check' => '检查所有域名状态',
                '/api/monitor.php?action=check&notify=1' => '检查域名并发送Telegram通知',
                '/api/monitor.php?action=test' => '测试Telegram连接',
                '/api/monitor.php?action=status' => '获取API状态',
            ],
            'domains' => $config['domains'],
            'telegram_configured' => !empty($config['telegram']['bot_token']) && !empty($config['telegram']['chat_id']),
        ]);
        break;
        
    case 'test':
        // 测试Telegram连接
        $message = "🤖 *KeepGet.com 监控机器人*\n测试消息 - " . date('Y-m-d H:i:s');
        $success = sendTelegramMessage($message, $config);
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Telegram消息发送成功' : 'Telegram消息发送失败',
            'telegram_configured' => !empty($config['telegram']['bot_token']) && !empty($config['telegram']['chat_id']),
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => '无效的action参数',
            'available_actions' => ['check', 'status', 'test'],
        ]);
}
?>
