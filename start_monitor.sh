#!/bin/bash
# KeepGet.com 域名监控启动脚本

echo "🚀 启动 KeepGet.com 域名监控服务..."
echo "时间: $(date)"
echo "=================================="

cd /www/wwwroot/keepget.com/api

echo "📡 开始检查域名状态并发送通知..."

# 方法1: 使用php直接执行带参数
php -r '
$_GET["action"] = "check";
$_GET["notify"] = "1";
$_SERVER["REQUEST_METHOD"] = "GET";
include "monitor.php";
'

echo ""
echo "=================================="
echo "✅ 监控检查已完成"
echo "📊 监控配置："
echo "   - 监控域名数量: 90+ 个"
echo "   - 检查间隔: 6小时"
echo "   - Telegram通知: 已启用"
echo "   - API端点: /api/monitor.php"
echo ""
echo "💡 如需再次检查，运行："
echo "   ./start_monitor.sh"
echo ""
