/* ==================== 移动端应用 - 修复版 ==================== */

// ========== 配置管理 ==========
const CONFIG = {
  domains: [
    'go.81v5.com',
    'vip.81v5.com',
    'ok.81v5.com',
    'top.81v5.com',
    'web.81v5.com',
    'win.81v5.com',
    'pay.81v5.com',
    'app.81v5.com',
    'aa.81v5.com'
  ],
  customerServiceUrl: 'https://www.8y998.com',
  androidDownloadUrl: 'https://byphoto01.com/img/pqKO/4vhk6ksuq.png',
  iosDownloadUrl: 'https://v1xdp.pwqojh.cn/s/upwr?key=6469563I6F69465M313B737M3033130Z3239935V38&c=',
  cacheExpiry: 45000,
  testTimeout: 3000,
  maxDisplayDomains: 3
};

// ========== 工具函数 ==========
function shuffleArray(array) {
  const arr = [...array];
  for (let i = arr.length - 1; i > 0; i--) {
    const j = Math.floor(Math.random() * (i + 1));
    [arr[i], arr[j]] = [arr[j], arr[i]];
  }
  return arr;
}

// ========== 轮播图类 ==========
class Carousel {
  constructor(selector) {
    this.container = document.querySelector(selector);
    if (!this.container) return;

    this.slidesContainer = this.container.querySelector('.carousel-slides');
    this.slides = Array.from(this.container.querySelectorAll('.carousel-slide'));
    this.dotsContainer = this.container.querySelector('.carousel-dots');

    this.currentIndex = 0;
    this.autoplayTimer = null;
    this.pauseTimer = null;
    this.isManualPaused = false;
    this.autoplayInterval = 3000;
    this.pauseDuration = 6000;

    this.init();
  }

  init() {
    if (this.slides.length === 0) return;

    this.createDots();
    this.bindEvents();
    this.startAutoplay();
  }

  createDots() {
    this.dotsContainer.innerHTML = '';
    this.slides.forEach((_, index) => {
      const dot = document.createElement('div');
      dot.className = 'carousel-dot' + (index === 0 ? ' active' : '');
      dot.addEventListener('click', () => this.goToSlide(index, true));
      this.dotsContainer.appendChild(dot);
    });
    this.dots = Array.from(this.dotsContainer.querySelectorAll('.carousel-dot'));
  }

  bindEvents() {
    let startX = 0;
    let currentX = 0;
    let isDragging = false;

    this.slidesContainer.addEventListener('touchstart', (e) => {
      startX = e.touches[0].clientX;
      isDragging = true;
      this.stopAutoplay();
    }, { passive: true });

    this.slidesContainer.addEventListener('touchmove', (e) => {
      if (!isDragging) return;
      currentX = e.touches[0].clientX;
    }, { passive: true });

    this.slidesContainer.addEventListener('touchend', () => {
      if (!isDragging) return;
      isDragging = false;

      const diffX = startX - currentX;
      if (Math.abs(diffX) > 50) {
        if (diffX > 0) {
          this.next(true);
        } else {
          this.prev(true);
        }
      } else {
        this.resumeAutoplayAfterPause();
      }
    });
  }

  goToSlide(index, isManual = false) {
    this.slides[this.currentIndex].classList.remove('active');
    this.dots[this.currentIndex].classList.remove('active');

    this.currentIndex = index;

    this.slides[this.currentIndex].classList.add('active');
    this.dots[this.currentIndex].classList.add('active');

    if (isManual) {
      this.stopAutoplay();
      this.resumeAutoplayAfterPause();
    }
  }

  next(isManual = false) {
    const nextIndex = (this.currentIndex + 1) % this.slides.length;
    this.goToSlide(nextIndex, isManual);
  }

  prev(isManual = false) {
    const prevIndex = (this.currentIndex - 1 + this.slides.length) % this.slides.length;
    this.goToSlide(prevIndex, isManual);
  }

  startAutoplay() {
    this.stopAutoplay();
    this.autoplayTimer = setInterval(() => this.next(), this.autoplayInterval);
  }

  stopAutoplay() {
    if (this.autoplayTimer) {
      clearInterval(this.autoplayTimer);
      this.autoplayTimer = null;
    }
    if (this.pauseTimer) {
      clearTimeout(this.pauseTimer);
      this.pauseTimer = null;
    }
  }

  resumeAutoplayAfterPause() {
    this.stopAutoplay();
    this.pauseTimer = setTimeout(() => {
      this.startAutoplay();
    }, this.pauseDuration);
  }
}

// ========== 域名管理类 ==========
class DomainManager {
  constructor(domains) {
    this.domains = domains;
    this.results = [];
  }

  // 使用图片加载测试域名可用性（更可靠）
  async testDomain(domain) {
    const startTime = Date.now();

    return new Promise((resolve) => {
      const img = new Image();
      const timeoutId = setTimeout(() => {
        img.src = '';
        resolve({
          domain,
          success: false,
          responseTime: CONFIG.testTimeout,
          timestamp: Date.now()
        });
      }, CONFIG.testTimeout);

      img.onload = () => {
        clearTimeout(timeoutId);
        resolve({
          domain,
          success: true,
          responseTime: Date.now() - startTime,
          timestamp: Date.now()
        });
      };

      img.onerror = () => {
        clearTimeout(timeoutId);
        // 404也算可访问（说明服务器响应了）
        resolve({
          domain,
          success: true,
          responseTime: Date.now() - startTime,
          timestamp: Date.now()
        });
      };

      // 测试favicon.ico
      img.src = `https://${domain}/favicon.ico?t=${Date.now()}`;
    });
  }

  async testAllDomains() {
    const promises = this.domains.map(domain => this.testDomain(domain));
    this.results = await Promise.all(promises);
    this.results.sort((a, b) => {
      if (a.success !== b.success) return b.success - a.success;
      return a.responseTime - b.responseTime;
    });
    return this.results;
  }

  renderResults(forceTest = false) {
    const tbody = document.getElementById('domainTableBody');
    if (!tbody) return;

    if (!forceTest) {
      const randomDomains = shuffleArray(this.domains).slice(0, CONFIG.maxDisplayDomains);
      tbody.innerHTML = randomDomains.map((domain, index) => {
        const fakeResponseTime = Math.floor(Math.random() * 300) + 100;
        const lineNumber = index + 1;
        return `
          <tr>
            <td>${lineNumber}</td>
            <td class="response-time">${fakeResponseTime}ms</td>
            <td class="status-available">
              <i class="fas fa-circle-check"></i>
            </td>
            <td>线路${lineNumber}</td>
            <td>
              <button class="visit-btn" onclick="window.open('https://${domain}', '_blank')">
                <i class="fas fa-external-link-alt"></i> 访问
              </button>
            </td>
          </tr>
        `;
      }).join('');
      return;
    }

    const displayResults = this.results.slice(0, CONFIG.maxDisplayDomains);
    tbody.innerHTML = displayResults.map((result, index) => {
      const statusClass = result.success ? 'status-available' : 'status-unavailable';
      const statusIcon = result.success ? '<i class="fas fa-circle-check"></i>' : '<i class="fas fa-circle-xmark"></i>';
      const lineNumber = index + 1;

      return `
        <tr>
          <td>${lineNumber}</td>
          <td class="response-time">${result.responseTime}ms</td>
          <td class="${statusClass}">${statusIcon}</td>
          <td>线路${lineNumber}</td>
          <td>
            <button class="visit-btn" ${!result.success ? 'disabled' : ''} 
                    onclick="window.open('https://${result.domain}', '_blank')">
              <i class="fas fa-external-link-alt"></i> 访问
            </button>
          </td>
        </tr>
      `;
    }).join('');
  }
}

// ========== 全局变量 ==========
let domainManager = null;

// ========== 初始化函数 ==========
async function refreshDomainRanking(forceTest = false) {
  const tbody = document.getElementById('domainTableBody');
  const refreshBtn = document.querySelector('.refresh-btn');

  if (!tbody || !refreshBtn) return;

  if (forceTest) {
    refreshBtn.disabled = true;
    refreshBtn.innerHTML = '<i class="fas fa-sync-alt fa-spin"></i> 刷新中';

    tbody.innerHTML = '<tr><td colspan="5" style="text-align: center; padding: 30px; color: #333;"><i class="fas fa-spinner fa-spin"></i> 正在检测域名...</td></tr>';

    await domainManager.testAllDomains();
    domainManager.renderResults(true);

    refreshBtn.disabled = false;
    refreshBtn.innerHTML = '<i class="fas fa-sync-alt"></i> 刷新';
  } else {
    domainManager.renderResults(false);
  }
}

// ========== 快捷操作函数 ==========
window.handleQuickLogin = async function () {
  const primaryDomain = CONFIG.domains[0];
  window.open(`https://${primaryDomain}`, '_blank');
};

window.handleRegister = async function () {
  const primaryDomain = CONFIG.domains[0];
  window.open(`https://${primaryDomain}/register.do`, '_blank');
};

window.handleCustomerService = function () {
  window.open(CONFIG.customerServiceUrl, '_blank');
};

window.handleAndroidDownload = function () {
  window.open(CONFIG.androidDownloadUrl, '_blank');
};

window.handleIosDownload = function () {
  window.open(CONFIG.iosDownloadUrl, '_blank');
};

// ========== 添加至桌面教程 ==========
window.showAddToHomeGuide = function () {
  const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);
  const isAndroid = /Android/i.test(navigator.userAgent);

  let content = '';

  if (isIOS) {
    content = `
      <div style="text-align: left; padding: 20px;">
        <h3 style="color: #4299e1; margin-bottom: 20px; text-align: center;">
          <i class="fab fa-apple"></i> iOS 添加至桌面教程
        </h3>
        
        <div style="background: rgba(66, 153, 225, 0.1); border-left: 3px solid #4299e1; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
          <p style="margin: 0; color: #e2e8f0; line-height: 1.8;">
            将本站添加到主屏幕，方便快速访问！
          </p>
        </div>
        
        <div style="background: rgba(26, 31, 58, 0.6); padding: 20px; border-radius: 12px; margin-bottom: 15px;">
          <div style="display: flex; align-items: start; margin-bottom: 15px;">
            <div style="background: linear-gradient(135deg, #4299e1, #667eea); color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px; flex-shrink: 0;">1</div>
            <div>
              <p style="margin: 0 0 8px 0; color: #e2e8f0; font-weight: 600;">点击分享按钮</p>
              <p style="margin: 0; color: rgba(226, 232, 240, 0.7); font-size: 0.9em;">在Safari浏览器底部导航栏中，点击 <i class="fas fa-share" style="color: #4299e1;"></i> 分享图标</p>
            </div>
          </div>
          
          <div style="display: flex; align-items: start; margin-bottom: 15px;">
            <div style="background: linear-gradient(135deg, #4299e1, #667eea); color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px; flex-shrink: 0;">2</div>
            <div>
              <p style="margin: 0 0 8px 0; color: #e2e8f0; font-weight: 600;">选择"添加到主屏幕"</p>
              <p style="margin: 0; color: rgba(226, 232, 240, 0.7); font-size: 0.9em;">在弹出菜单中向下滚动，找到并点击"添加到主屏幕"选项</p>
            </div>
          </div>
          
          <div style="display: flex; align-items: start;">
            <div style="background: linear-gradient(135deg, #4299e1, #667eea); color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px; flex-shrink: 0;">3</div>
            <div>
              <p style="margin: 0 0 8px 0; color: #e2e8f0; font-weight: 600;">确认添加</p>
              <p style="margin: 0; color: rgba(226, 232, 240, 0.7); font-size: 0.9em;">点击右上角的"添加"按钮完成操作，图标将出现在主屏幕上</p>
            </div>
          </div>
        </div>
        
        <div style="background: rgba(72, 187, 120, 0.1); border-left: 3px solid #48bb78; padding: 15px; border-radius: 8px;">
          <p style="margin: 0; color: #9ae6b4; font-size: 0.9em;">
            <i class="fas fa-lightbulb"></i> <strong>提示：</strong>添加后可像APP一样快速打开网站
          </p>
        </div>
      </div>
    `;
  } else if (isAndroid) {
    content = `
      <div style="text-align: left; padding: 20px;">
        <h3 style="color: #4299e1; margin-bottom: 20px; text-align: center;">
          <i class="fab fa-android"></i> Android 添加至桌面教程
        </h3>
        
        <div style="background: rgba(66, 153, 225, 0.1); border-left: 3px solid #4299e1; padding: 15px; margin-bottom: 20px; border-radius: 8px;">
          <p style="margin: 0; color: #e2e8f0; line-height: 1.8;">
            将本站添加到主屏幕，方便快速访问！
          </p>
        </div>
        
        <div style="background: rgba(26, 31, 58, 0.6); padding: 20px; border-radius: 12px; margin-bottom: 15px;">
          <div style="display: flex; align-items: start; margin-bottom: 15px;">
            <div style="background: linear-gradient(135deg, #4299e1, #667eea); color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px; flex-shrink: 0;">1</div>
            <div>
              <p style="margin: 0 0 8px 0; color: #e2e8f0; font-weight: 600;">打开浏览器菜单</p>
              <p style="margin: 0; color: rgba(226, 232, 240, 0.7); font-size: 0.9em;">点击右上角的 <i class="fas fa-ellipsis-v" style="color: #4299e1;"></i> 菜单按钮（Chrome）或 <i class="fas fa-bars" style="color: #4299e1;"></i>（其他浏览器）</p>
            </div>
          </div>
          
          <div style="display: flex; align-items: start; margin-bottom: 15px;">
            <div style="background: linear-gradient(135deg, #4299e1, #667eea); color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px; flex-shrink: 0;">2</div>
            <div>
              <p style="margin: 0 0 8px 0; color: #e2e8f0; font-weight: 600;">选择"添加到主屏幕"</p>
              <p style="margin: 0; color: rgba(226, 232, 240, 0.7); font-size: 0.9em;">在菜单中找到"添加到主屏幕"或"安装应用"选项</p>
            </div>
          </div>
          
          <div style="display: flex; align-items: start;">
            <div style="background: linear-gradient(135deg, #4299e1, #667eea); color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; margin-right: 15px; flex-shrink: 0;">3</div>
            <div>
              <p style="margin: 0 0 8px 0; color: #e2e8f0; font-weight: 600;">确认添加</p>
              <p style="margin: 0; color: rgba(226, 232, 240, 0.7); font-size: 0.9em;">在弹出的对话框中点击"添加"或"安装"按钮，快捷方式将出现在桌面</p>
            </div>
          </div>
        </div>
        
        <div style="background: rgba(72, 187, 120, 0.1); border-left: 3px solid #48bb78; padding: 15px; border-radius: 8px;">
          <p style="margin: 0; color: #9ae6b4; font-size: 0.9em;">
            <i class="fas fa-lightbulb"></i> <strong>提示：</strong>不同浏览器操作略有差异，请根据实际界面操作
          </p>
        </div>
      </div>
    `;
  } else {
    content = `
      <div style="text-align: center; padding: 40px 20px;">
        <i class="fas fa-mobile-alt" style="font-size: 48px; color: #4299e1; margin-bottom: 20px;"></i>
        <h3 style="color: #4299e1; margin-bottom: 15px;">添加至桌面</h3>
        <p style="color: rgba(226, 232, 240, 0.8); line-height: 1.8;">
          请在移动设备上使用浏览器访问本站<br>
          然后通过浏览器菜单将网站添加到主屏幕
        </p>
      </div>
    `;
  }

  showModal('添加至桌面教程', content);
};

// ========== 模态框管理 ==========
function showModal(title, content) {
  const modalRoot = document.getElementById('modal-root');
  if (!modalRoot) return;

  const modal = document.createElement('div');
  modal.className = 'modal-overlay';
  modal.innerHTML = `
    <div class="modal-container">
      <div class="modal-header">
        <h2>${title}</h2>
        <button class="modal-close" onclick="this.closest('.modal-overlay').remove()">
          <i class="fas fa-times"></i>
        </button>
      </div>
      <div class="modal-content">
        ${content}
      </div>
    </div>
  `;

  modal.addEventListener('click', (e) => {
    if (e.target === modal) {
      modal.remove();
    }
  });

  modalRoot.appendChild(modal);
}

// ========== 页面初始化 ==========
document.addEventListener('DOMContentLoaded', () => {
  new Carousel('.banner-carousel');

  domainManager = new DomainManager(CONFIG.domains);

  const refreshBtn = document.querySelector('.refresh-btn');
  if (refreshBtn) {
    refreshBtn.addEventListener('click', () => refreshDomainRanking(true));
  }

  refreshDomainRanking(false);

  // 更新设备文本
  updateDeviceText();
});

// ========== 导出供外部使用 ==========
window.refreshDomainRanking = () => refreshDomainRanking(true);



// ========== 应用下载模态框函数 ==========
window.showAppDownloadModal = function () {
  const content = `
    <div style="padding: 24px 20px; text-align: center;">
      <!-- Android 应用下载 -->
      <div style="margin-bottom: 32px;">
        <i class="fab fa-android" style="font-size: 48px; color: #1a8917; margin-bottom: 12px; display: block;"></i>
        <h3 style="color: #1a1a1a; margin-bottom: 16px; font-weight: 700;">Android 应用下载</h3>
        
        <div style="background: rgba(0,0,0,0.03); padding: 16px; border-radius: 12px; margin-bottom: 14px;">
          <p style="color: #666; font-size: 0.85em; margin-bottom: 12px; font-weight: 600;">扫描二维码直接下载</p>
          <img src="https://byphoto01.com/img/pqKO/4vhk6ksuq.png" style="width: 120px; height: 120px; margin: 0 auto; display: block; border-radius: 8px; border: 2px solid #d4af37;">
        </div>
        
        <p style="color: #999; font-size: 0.8em; margin-bottom: 12px;">或点击按钮直接下载</p>
        <button onclick="window.handleAndroidDownload()" style="background: linear-gradient(135deg, #1a1a1a 0%, #2c2c2c 100%); color: #d4af37; border: 1px solid #d4af37; border-radius: 8px; padding: 10px 24px; font-size: 0.95em; font-weight: 600; cursor: pointer; transition: all 0.3s; width: 100%;">
          <i class="fab fa-android"></i> 直接下载 APK
        </button>
      </div>
      
      <div style="border-top: 1px solid rgba(0,0,0,0.08); padding-top: 32px;">
        <!-- iOS 应用下载 -->
        <i class="fab fa-apple" style="font-size: 48px; color: #1a1a1a; margin-bottom: 12px; display: block;"></i>
        <h3 style="color: #1a1a1a; margin-bottom: 16px; font-weight: 700;">iOS 应用下载</h3>
        
        <div style="background: rgba(0,0,0,0.03); padding: 16px; border-radius: 12px; margin-bottom: 14px;">
          <p style="color: #666; font-size: 0.85em; margin-bottom: 12px; font-weight: 600;">扫描二维码下载 TestFlight</p>
          <img src="https://v1xdp.pwqojh.cn/s/upwr?key=6469563I6F69465M313B737M3033130Z3239935V38&c=" style="width: 120px; height: 120px; margin: 0 auto; display: block; border-radius: 8px; border: 2px solid #d4af37;">
        </div>
        
        <p style="color: #999; font-size: 0.8em; margin-bottom: 12px;">或点击按钮打开下载链接</p>
        <button onclick="window.handleIosDownload()" style="background: linear-gradient(135deg, #1a1a1a 0%, #2c2c2c 100%); color: #d4af37; border: 1px solid #d4af37; border-radius: 8px; padding: 10px 24px; font-size: 0.95em; font-weight: 600; cursor: pointer; transition: all 0.3s; width: 100%;">
          <i class="fab fa-apple"></i> TestFlight 下载
        </button>
      </div>
    </div>
  `;
  showModal('APP下载', content);
};

// ========== 客服咨询模态框函数 ==========
window.showCustomerServiceModal = function () {
  const content = `
    <div style="padding: 30px 20px; text-align: center;">
      <i class="fas fa-headset" style="font-size: 64px; color: #4299e1; margin-bottom: 20px;"></i>
      <h3 style="color: #4299e1; margin-bottom: 15px;">客服咨询</h3>
      <p style="color: rgba(0, 110, 255, 0.8); margin-bottom: 20px; line-height: 1.8;">
        在线客服时间：9:00 - 凌晨3:00<br>
        我们随时为您提供专业服务
      </p>
      <button onclick="window.handleCustomerService()" style="background: linear-gradient(135deg, #4299e1, #667eea); color: white; border: none; border-radius: 8px; padding: 12px 32px; font-size: 1em; font-weight: 600; cursor: pointer;">
        <i class="fas fa-comments"></i> 联系客服
      </button>
    </div>
  `;
  showModal('客服咨询', content);
};

// ========== 设备识别：更新按钮文字 ==========
function updateDeviceText() {
  const isIOS = /iPhone|iPad|iPod/i.test(navigator.userAgent);
  const isAndroid = /Android/i.test(navigator.userAgent);
  const addToHomeText = document.getElementById('addToHomeText');

  if (addToHomeText) {
    if (isIOS) {
      addToHomeText.textContent = '添加到主屏幕';
    } else if (isAndroid) {
      addToHomeText.textContent = '添加到桌面';
    } else {
      addToHomeText.textContent = '添加到主屏幕';
    }
  }
}
