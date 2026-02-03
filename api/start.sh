#!/bin/bash

# 域名监控服务启动脚本

API_DIR="/var/www/8yhot.com/8yhot.com/api"
PID_FILE="$API_DIR/monitor.pid"
LOG_FILE="$API_DIR/monitor.log"

cd "$API_DIR"

case "$1" in
  start)
    if [ -f "$PID_FILE" ]; then
      PID=$(cat "$PID_FILE")
      if ps -p "$PID" > /dev/null 2>&1; then
        echo "✅ 服务已在运行中 (PID: $PID)"
        exit 0
      fi
    fi
    
    echo "🚀 启动域名监控服务..."
    nohup node domain-monitor.js >> "$LOG_FILE" 2>&1 &
    echo $! > "$PID_FILE"
    echo "✅ 服务启动成功 (PID: $(cat $PID_FILE))"
    echo "📋 日志文件: $LOG_FILE"
    ;;
    
  stop)
    if [ ! -f "$PID_FILE" ]; then
      echo "❌ 服务未运行"
      exit 1
    fi
    
    PID=$(cat "$PID_FILE")
    echo "🛑 停止域名监控服务 (PID: $PID)..."
    kill "$PID"
    rm -f "$PID_FILE"
    echo "✅ 服务已停止"
    ;;
    
  restart)
    $0 stop
    sleep 2
    $0 start
    ;;
    
  status)
    if [ -f "$PID_FILE" ]; then
      PID=$(cat "$PID_FILE")
      if ps -p "$PID" > /dev/null 2>&1; then
        echo "✅ 服务运行中 (PID: $PID)"
        exit 0
      else
        echo "❌ 服务未运行（PID文件存在但进程不存在）"
        exit 1
      fi
    else
      echo "❌ 服务未运行"
      exit 1
    fi
    ;;
    
  *)
    echo "用法: $0 {start|stop|restart|status}"
    exit 1
    ;;
esac
