# 8yhot.com 移动端安全访问入口 - 部署文档

## 📋 项目概述

这是一个移动端单页 Web 应用，作为安全访问入口页面，具备以下功能：

- ✅ 活动图片轮播（3秒自动，手动滑动后暂停6秒）
- ✅ 智能域名测速与推荐
- ✅ 快速登录（自动选择最佳线路）
- ✅ 立即注册（跳转时增加 /register.do）
- ✅ APP 下载（Android + iOS 二维码）
- ✅ 客服咨询（iframe 集成）
- ✅ 域名排行榜（实时测速 Top 5）
- ✅ 添加到桌面指引
- ✅ 真实用户测速推荐域名
- ✅ 单域名单次异常立即告警
- ✅ 跨区域 3 次冷却机制
- ✅ 自动修复功能
- ✅ Telegram 告警集成

## 🗂️ 文件结构

```
/var/www/8yhot.com/8yhot.com/
├── index.html                    # 主页面
├── assets/
│   ├── style.css                 # 原有样式
│   ├── mobile-style.css          # 新增移动端样式
│   ├── modal.css                 # 模态框样式
│   ├── main.js                   # 原有脚本
│   └── app.js                    # 新增应用逻辑
├── api/
│   ├── domain-monitor.js         # 域名监控服务
│   ├── start.sh                  # 启动脚本
│   └── data/                     # 数据目录（自动创建）
│       └── monitor-data.json     # 监控数据
└── DEPLOYMENT.md                 # 本文档

/etc/nginx/
├── 8yhot.com/
│   └── domains.json              # 域名池配置
└── sites-enabled/
    └── 8yhot.com.conf            # Nginx 配置
```

## 🔧 域名池配置

当前域名池（已更新）：

```json
{
  "domains": [
    "go.81v5.com",
    "vip.81v5.com",
    "ok.81v5.com",
    "top.81v5.com",
    "web.81v5.com",
    "win.81v5.com",
    "pay.81v5.com",
    "app.81v5.com",
    "aa.81v5.com"
  ]
}
```

## 🚀 部署步骤

### 1. 安装 Node.js（如未安装）

```bash
# CentOS/RHEL
curl -sL https://rpm.nodesource.com/setup_18.x | sudo bash -
sudo yum install -y nodejs

# 验证安装
node --version
npm --version
```

### 2. 启动域名监控服务

```bash
cd /var/www/8yhot.com/8yhot.com/api

# 启动服务
./start.sh start

# 查看状态
./start.sh status

# 查看日志
tail -f monitor.log
```

### 3. 配置 Nginx 反代（可选）

如果需要通过 Nginx 反代 API 服务：

```nginx
# 在 /etc/nginx/sites-enabled/8yhot.com.conf 的 HTTPS server 块中添加：

location /api/domain-report {
    proxy_pass http://127.0.0.1:3999;
    proxy_http_version 1.1;
    proxy_set_header Host $host;
    proxy_set_header X-Real-IP $remote_addr;
    proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
    proxy_set_header X-Forwarded-Proto $scheme;
}
```

重载 Nginx：

```bash
nginx -t && systemctl reload nginx
```

### 4. 验证部署

访问页面：
```
https://8yhot.com
```

检查功能：
- ✅ 轮播图是否正常显示和切换
- ✅ 点击"立即注册"是否能测速并跳转（带 /register.do）
- ✅ 点击"一键登录"是否能测速并跳转
- ✅ APP 下载弹窗是否正常显示
- ✅ 客服咨询是否能打开对话框
- ✅ 域名排行榜是否显示数据
- ✅ 页脚"添加至桌面"按钮是否有指引

## 📊 监控与告警

### Telegram 配置

已配置的 Telegram Bot：
- **Bot Token**: `8005042122:AAExvHlkQ3R4tH4IEt1BvKgiUJqXA9wfjg0`
- **Chat ID**: `-4943598430`

### 告警类型

1. **单次异常告警**（立即发送）
   ```
   ⚠️ 单次异常（真实用户）
   区域：Asia-China
   域名：go.81v5.com
   原因：响应时间过长 (1200ms)
   时间：2026-02-03 15:30:00
   ```

2. **冷却状态告警**（跨区域 3 次异常）
   ```
   🧊 域名进入冷却状态
   域名：go.81v5.com
   异常次数：5次（跨区域）
   状态：不再推荐，自动修复中
   时间：2026-02-03 15:35:00
   ```

3. **修复成功告警**
   ```
   ✅ 域名自动修复成功
   域名：go.81v5.com
   状态：已恢复推荐资格
   时间：2026-02-03 16:00:00
   ```

4. **人工介入告警**（修复失败）
   ```
   🆘 域名无法自动修复
   域名：go.81v5.com
   尝试次数：5次
   状态：持续冷却
   说明：需要人工介入
   时间：2026-02-03 16:30:00
   ```

### 异常判定规则

- **响应时间 > 1000ms**：判定为异常
- **连接失败**：判定为异常
- **单次异常**：立即告警，仅记录，不冷却
- **跨区域 3 次异常**：进入冷却，不再推荐，启动自动修复
- **修复尝试**：每 5 分钟检查一次，最多 5 次
- **修复成功**：恢复推荐资格
- **修复失败**：提交 Telegram 群组，需人工介入

### 缓存策略

- **前端缓存**：45 秒
- **后端缓存**：45 秒
- 同一请求在缓存期内直接返回缓存数据

## 🔍 日常维护

### 查看监控服务状态

```bash
cd /var/www/8yhot.com/8yhot.com/api
./start.sh status
```

### 查看监控日志

```bash
tail -f /var/www/8yhot.com/8yhot.com/api/monitor.log
```

### 查看监控数据

```bash
cat /var/www/8yhot.com/8yhot.com/api/data/monitor-data.json | jq .
```

### 重启监控服务

```bash
cd /var/www/8yhot.com/8yhot.com/api
./start.sh restart
```

### 更新域名池

编辑域名池文件：
```bash
vi /etc/nginx/8yhot.com/domains.json
```

**无需重启服务**，前端会自动读取新的域名列表。

同时更新 `app.js` 中的域名列表：
```bash
vi /var/www/8yhot.com/8yhot.com/assets/app.js
# 修改 CONFIG.domains 数组
```

## 🛠️ 故障排查

### 页面无法访问

1. 检查 Nginx 状态：
   ```bash
   systemctl status nginx
   nginx -t
   ```

2. 检查证书：
   ```bash
   ls -la /etc/nginx/ssl/8yhot.com/
   ```

### 域名测速不工作

1. 检查浏览器控制台是否有 JavaScript 错误
2. 确认域名池中的域名可访问
3. 检查浏览器是否允许跨域请求

### 监控服务未启动

1. 检查 Node.js 是否安装：
   ```bash
   node --version
   ```

2. 手动启动服务：
   ```bash
   cd /var/www/8yhot.com/8yhot.com/api
   node domain-monitor.js
   ```

3. 查看错误日志：
   ```bash
   cat /var/www/8yhot.com/8yhot.com/api/monitor.log
   ```

### Telegram 告警未收到

1. 验证 Bot Token 和 Chat ID 是否正确
2. 测试 Telegram API 连接：
   ```bash
   curl -X POST "https://api.telegram.org/bot8005042122:AAExvHlkQ3R4tH4IEt1BvKgiUJqXA9wfjg0/sendMessage" \
     -d "chat_id=-4943598430" \
     -d "text=测试消息"
   ```

3. 检查监控服务日志是否有告警发送记录

## 📝 配置说明

### 轮播配置

在 `assets/app.js` 的 `CONFIG.carousel` 中修改：

```javascript
carousel: {
  autoPlayInterval: 3000,  // 自动轮播间隔（毫秒）
  pauseDuration: 6000,     // 手动滑动后暂停时长（毫秒）
  images: [
    'https://byphoto01.com/img/pqKO/J0cMaXvvg.png',
    // ... 更多图片
  ]
}
```

### 异常阈值配置

在 `api/domain-monitor.js` 的 `CONFIG.thresholds` 中修改：

```javascript
thresholds: {
  responseTime: 1000,           // 响应时间阈值（毫秒）
  crossRegionFailures: 3        // 跨区域异常次数阈值
}
```

### 冷却配置

在 `api/domain-monitor.js` 的 `CONFIG.cooldown` 中修改：

```javascript
cooldown: {
  duration: 3600000,      // 冷却期（毫秒，1小时）
  checkInterval: 300000,  // 检查间隔（毫秒，5分钟）
  repairAttempts: 5       // 最大修复尝试次数
}
```

## 🔐 安全说明

1. **HTTPS 强制**：所有 HTTP 请求自动重定向到 HTTPS
2. **HSTS 启用**：强制浏览器使用 HTTPS
3. **Host 校验**：验证 Host Header，防止域名泄露
4. **CSP 策略**：限制资源加载来源
5. **安全响应头**：X-Frame-Options, X-Content-Type-Options 等

## 📌 注意事项

1. 域名池中的域名必须配置 `/health` 端点用于健康检查
2. 监控服务使用端口 3999，确保未被占用
3. 数据文件存储在 `/var/www/8yhot.com/8yhot.com/api/data/`
4. 建议定期备份监控数据文件
5. Telegram Bot Token 和 Chat ID 需妥善保管
6. 修改域名池后无需重启 Nginx，但需刷新前端页面

## 📞 技术支持

如遇问题，请检查：
1. Nginx 错误日志：`/var/log/nginx/error.log`
2. 监控服务日志：`/var/www/8yhot.com/8yhot.com/api/monitor.log`
3. 浏览器开发者工具控制台

---

**部署完成！** 🎉

访问地址：https://8yhot.com
监控端口：3999
Telegram 群组：-4943598430
