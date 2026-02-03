<?php
// 启动命令（终端运行）:
//   php /www/wwwroot/keepget.com/api/check_domains.php
// 说明:
//   - 请在命令行环境下运行。
//   - 确保 monitor.php 已正确配置。
/**
 * 域名检测脚本 - 显示检测进度
 */

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 包含监控配置
include 'monitor.php';

// 清屏并显示标题
echo "\033[2J\033[H";
echo "🔍 KeepGet.com 域名监控系统\n";
echo "📅 检测时间: " . date('Y-m-d H:i:s') . "\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$results = [];
$total = count($config['domains']);
$current = 0;
$healthy = 0;
$warning = 0;
$critical = 0;

foreach ($config['domains'] as $domain) {
    $current++;
    
    // 显示进度
    $progress = round(($current / $total) * 100, 1);
    $progressBar = str_repeat('█', floor($progress / 2)) . str_repeat('░', 50 - floor($progress / 2));
    
    echo "\r进度: [{$progressBar}] {$progress}% ({$current}/{$total}) 正在检测: {$domain}";
    echo str_repeat(' ', 20); // 清除之前的域名显示
    
    // 检测域名
    $result = checkDomainStatus($domain);
    $results[] = $result;
    
    // 统计状态
    switch ($result['status']) {
        case 'healthy':
            $healthy++;
            break;
        case 'warning':
            $warning++;
            break;
        case 'critical':
            $critical++;
            break;
    }
    
    // 短暂延迟避免过于频繁的请求
    usleep(100000); // 0.1秒
}

echo "\n\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "✅ 检测完成！\n\n";

// 显示统计结果
echo "📊 检测统计:\n";
echo "   总计域名: {$total} 个\n";
echo "   ✅ 正常: {$healthy} 个\n";
echo "   ⚠️  警告: {$warning} 个\n";
echo "   ❌ 异常: {$critical} 个\n\n";

// 显示异常域名详情
$criticalDomains = [];
$warningDomains = [];

foreach ($results as $result) {
    if ($result['status'] === 'critical') {
        $issues = [];
        if (!$result['dns']['resolved']) {
            $issues[] = 'DNS解析失败';
        }
        if (!$result['https']['accessible']) {
            $issues[] = 'HTTPS不可访问';
        }
        $criticalDomains[] = $result['domain'] . ' - ' . implode(', ', $issues);
    } elseif ($result['status'] === 'warning') {
        $issues = [];
        if ($result['ssl']['valid'] && $result['ssl']['expires_soon']) {
            $days = $result['ssl']['days_until_expiry'];
            $issues[] = "SSL证书{$days}天后过期";
        }
        if (!$result['ssl']['valid']) {
            $issues[] = 'SSL证书无效';
        }
        if ($result['https']['response_time'] > 5000) {
            $time = $result['https']['response_time'];
            $issues[] = "响应时间过长({$time}ms)";
        }
        $warningDomains[] = $result['domain'] . ' - ' . implode(', ', $issues);
    }
}

// 显示关键问题
if (!empty($criticalDomains)) {
    echo "🚨 关键问题域名:\n";
    foreach ($criticalDomains as $domain) {
        echo "   ❌ {$domain}\n";
    }
    echo "\n";
}

// 显示警告
if (!empty($warningDomains)) {
    echo "⚠️  警告域名:\n";
    foreach ($warningDomains as $domain) {
        echo "   ⚠️  {$domain}\n";
    }
    echo "\n";
}

// 保存结果到JSON文件
$reportData = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total' => $total,
    'healthy' => $healthy,
    'warning' => $warning,
    'critical' => $critical,
    'results' => $results,
];

file_put_contents('domain_check_result.json', json_encode($reportData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "📄 详细结果已保存到: domain_check_result.json\n\n";

// 如果有问题，询问是否发送Telegram通知
if ($critical > 0 || $warning > 0) {
    echo "❓ 检测到 {$critical} 个异常和 {$warning} 个警告，是否发送Telegram通知？(y/n): ";
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($line) === 'y' || strtolower($line) === 'yes') {
        echo "📤 正在发送Telegram通知...\n";
        $message = generateReportMessage($results);
        $success = sendTelegramMessage($message, $config);
        
        if ($success) {
            echo "✅ Telegram通知发送成功！\n";
        } else {
            echo "❌ Telegram通知发送失败！\n";
        }
    }
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "🔍 KeepGet.com 域名监控检测完成\n";
?>
