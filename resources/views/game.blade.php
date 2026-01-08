<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Fruit Machine Pro</title>

    <!-- 依赖库 -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://pixijs.download/v7.x/pixi.min.js"></script>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.3/howler.min.js"></script>

    <!-- 字体 -->
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;900&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <!-- 图标库 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            --primary-color: #f59e0b; /* 金色 */
            --accent-color: #ef4444;  /* 红色 */
            --bg-dark: #0f172a;       /* 深蓝底 */
            --panel-bg: rgba(30, 41, 59, 0.9);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --neon-glow: 0 0 10px rgba(245, 158, 11, 0.5);
        }

        * { box-sizing: border-box; user-select: none; -webkit-tap-highlight-color: transparent; }

        body {
            background-color: #050505;
            background-image: radial-gradient(circle at 50% 0%, #1e1b4b 0%, #000000 70%);
            margin: 0; padding: 0; overflow: hidden;
            height: 100vh;
            display: flex; justify-content: center; align-items: center;
            font-family: 'Roboto', sans-serif;
            color: #fff;
        }

        /* 游戏主体缩放容器 */
        #app-scaler {
            width: 480px;
            height: 100vh;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        /* --- 顶部 Header --- */
        .machine-header {
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(to bottom, rgba(0,0,0,0.8), transparent);
            z-index: 10;
        }

        .user-info {
            display: flex; align-items: center; gap: 10px;
        }
        .user-avatar {
            width: 36px; height: 36px; border-radius: 50%; background: #333;
            border: 2px solid var(--primary-color);
            display: flex; justify-content: center; align-items: center; font-size: 14px;
        }
        .app-title {
            font-family: 'Orbitron', sans-serif;
            font-weight: 900;
            color: var(--primary-color);
            text-shadow: var(--neon-glow);
            font-size: 18px;
            letter-spacing: 1px;
        }

        /* --- 奖池面板 --- */
        .jackpot-panel {
            text-align: center;
            margin: 5px 15px;
            padding: 10px;
            background: linear-gradient(135deg, #3f0909 0%, #000 100%);
            border: 1px solid #7f1d1d;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
            position: relative;
        }
        .jackpot-label {
            color: #fca5a5; font-size: 10px; letter-spacing: 3px; font-weight: bold; margin-bottom: 2px;
        }
        .jackpot-value {
            font-family: 'Orbitron', monospace;
            color: #fcd34d;
            font-size: 28px;
            font-weight: 700;
            text-shadow: 0 0 15px #d97706;
        }

        /* --- 屏幕数据显示 (Win/Credit) --- */
        .screen-panel {
            display: flex; justify-content: space-between; gap: 10px;
            padding: 0 15px; margin: 10px 0;
        }
        .lcd-box {
            flex: 1;
            background: #000;
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 5px 10px;
            position: relative;
            box-shadow: inset 0 0 10px rgba(0,0,0,0.8);
        }
        .lcd-label {
            font-size: 9px; color: #64748b; font-weight: bold; text-transform: uppercase;
        }
        .lcd-value {
            font-family: 'Orbitron', monospace;
            color: #4ade80; /* 绿色数字 */
            font-size: 18px;
            text-align: right;
            letter-spacing: 1px;
        }
        #winDisplay { color: #f87171; /* 赢分显示红色 */ }

        /* --- 游戏核心区 (PIXI Canvas) --- */
        #game-container-wrapper {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }
        #game-container {
            width: 360px; height: 360px;
            border-radius: 50%; /* 圆形游戏区 */
            background: radial-gradient(circle, #1e293b 0%, #000000 70%);
            border: 4px solid #334155;
            box-shadow: 0 0 30px rgba(0,0,0,0.5), inset 0 0 50px rgba(0,0,0,0.8);
            overflow: hidden;
            display: flex; justify-content: center; align-items: center;
        }

        /* --- 底部控制面板 --- */
        .control-panel {
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(10px);
            border-top: 1px solid #334155;
            padding: 15px;
            border-radius: 20px 20px 0 0;
            padding-bottom: 30px; /* 适配 iPhone 底部条 */
        }

        /* 赔率显示条 */
        #oddsDisplayContainer {
            display: flex; justify-content: space-between;
            padding: 0 5px 10px 5px;
            border-bottom: 1px solid #334155;
            margin-bottom: 10px;
            font-size: 10px; color: #94a3b8;
        }

        /* 下注按钮网格 */
        .bet-buttons {
            display: grid;
            grid-template-columns: repeat(8, 1fr);
            gap: 6px;
            margin-bottom: 15px;
        }
        .bet-unit {
            display: flex; flex-direction: column; align-items: center; gap: 4px;
        }
        .bet-lcd {
            font-family: 'Orbitron', monospace; font-size: 9px; color: #fff;
            background: #000; padding: 2px 4px; border-radius: 3px; min-width: 20px; text-align: center;
            border: 1px solid #333;
        }
        .btn-fruit {
            width: 100%; aspect-ratio: 1;
            border-radius: 8px; border: none;
            background: #1e293b; color: #cbd5e1;
            font-weight: bold; font-size: 10px;
            box-shadow: 0 3px 0 #0f172a;
            cursor: pointer; transition: all 0.1s;
            position: relative; overflow: hidden;
        }
        .btn-fruit:active { transform: translateY(3px); box-shadow: none; }
        /* 下注时的激活样式 */
        .bet-unit.active .btn-fruit { border: 1px solid var(--primary-color); color: var(--primary-color); }

        /* 底部大按钮组 */
        .func-buttons {
            display: flex; gap: 10px; height: 55px;
        }
        .btn-func {
            border: none; border-radius: 12px;
            font-weight: bold; font-size: 14px; color: #fff;
            cursor: pointer; display: flex; flex-direction: column; align-items: center; justify-content: center;
            transition: transform 0.1s;
            box-shadow: 0 4px 0 rgba(0,0,0,0.3);
        }
        .btn-func:active { transform: scale(0.95); box-shadow: 0 1px 0 rgba(0,0,0,0.3); }
        .btn-func i { font-size: 18px; margin-bottom: 2px; }

        /* 颜色定义 */
        .btn-wallet { flex: 1; background: linear-gradient(180deg, #3b82f6, #2563eb); }
        .btn-reset { flex: 1; background: linear-gradient(180deg, #64748b, #475569); }
        .btn-auto { flex: 1; background: linear-gradient(180deg, #a855f7, #9333ea); }

        /* 旋转按钮 (特别大) */
        .btn-spin {
            flex: 1.8;
            background: linear-gradient(180deg, #fbbf24, #d97706);
            color: #451a03; font-size: 18px; text-transform: uppercase; letter-spacing: 1px;
            animation: neon-pulse 2s infinite;
        }
        .btn-spin i { margin-bottom: 0; margin-right: 5px; }

        @keyframes neon-pulse {
            0% { box-shadow: 0 0 0 0 rgba(251, 191, 36, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(251, 191, 36, 0); }
            100% { box-shadow: 0 0 0 0 rgba(251, 191, 36, 0); }
        }

        /* --- 弹窗 (Wallet Modal) --- */
        .modal-overlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8); backdrop-filter: blur(5px);
            z-index: 9999; justify-content: center; align-items: flex-end; /* 底部弹出 */
        }
        .modal-content {
            background: #1e293b; width: 100%; max-width: 480px;
            border-radius: 20px 20px 0 0; border-top: 1px solid #475569;
            box-shadow: 0 -5px 30px rgba(0,0,0,0.5);
            animation: slideUp 0.3s ease-out;
            padding-bottom: 30px;
        }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }

        .modal-header {
            padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #334155;
        }
        .modal-header h3 { margin: 0; color: #fff; font-size: 18px; }
        .close-btn { background: none; border: none; color: #94a3b8; font-size: 24px; cursor: pointer; }

        .modal-tabs { display: flex; padding: 10px 20px 0; gap: 15px; }
        .tab-btn {
            background: none; border: none; color: #64748b; font-size: 16px; font-weight: bold;
            padding-bottom: 8px; cursor: pointer; position: relative;
        }
        .tab-btn.active { color: var(--primary-color); }
        .tab-btn.active::after {
            content: ''; position: absolute; bottom: 0; left: 0; width: 100%; height: 3px;
            background: var(--primary-color); border-radius: 2px;
        }

        .tab-panel { padding: 20px; color: #fff; }
        .hint { color: #94a3b8; font-size: 12px; margin-bottom: 8px; }

        .channel-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 20px; }
        .channel-btn {
            background: #0f172a; border: 1px solid #334155; color: #fff;
            padding: 12px; border-radius: 8px; cursor: pointer; font-size: 14px;
            transition: all 0.2s; display: flex; align-items: center; justify-content: center; gap: 5px;
        }
        .channel-btn.active {
            background: rgba(245, 158, 11, 0.2); border-color: var(--primary-color); color: var(--primary-color);
        }

        .input-group { display: flex; gap: 10px; margin-top: 10px; }
        .input-main {
            flex: 1; background: #0f172a; border: 1px solid #334155; color: #fff;
            padding: 12px; border-radius: 8px; font-size: 16px; outline: none;
        }
        .input-main:focus { border-color: var(--primary-color); }

        .btn-confirm {
            background: var(--primary-color); color: #000; border: none; padding: 0 20px;
            border-radius: 8px; font-weight: bold; cursor: pointer;
        }
        .btn-confirm:disabled { background: #64748b; cursor: not-allowed; }

        /* Loading 遮罩 */
        #loading-mask {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #050505; z-index: 10000; display: flex; flex-direction: column;
            justify-content: center; align-items: center; color: var(--primary-color);
        }
        .loader-spinner {
            width: 40px; height: 40px; border: 4px solid #333; border-top: 4px solid var(--primary-color);
            border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 15px;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

    </style>
</head>
<body>

<!-- Loading 遮罩 -->
<div id="loading-mask">
    <div class="loader-spinner"></div>
    <div style="font-family: 'Orbitron'; letter-spacing: 2px; font-size: 14px;">SYSTEM INITIALIZING...</div>
</div>

<div id="app-scaler">

    <!-- 顶部 -->
    <div class="machine-header">
        <div class="user-info">
            <div class="user-avatar"><i class="fa-solid fa-user"></i></div>
            <div class="app-title">CYBER FRUIT</div>
        </div>
        <div>
            <!-- 可以放个设置按钮或音量开关 -->
            <i class="fa-solid fa-volume-high" style="color: #64748b; font-size: 20px;"></i>
        </div>
    </div>

    <!-- 奖池 -->
    <div class="jackpot-panel">
        <div class="jackpot-label">GRAND JACKPOT</div>
        <div class="jackpot-value">
            <i class="fa-solid fa-bolt" style="font-size: 18px; color: #ef4444;"></i>
            <span id="jackpotDisplay">0.00</span>
        </div>
    </div>

    <!-- 显示屏 -->
    <div class="screen-panel">
        <div class="lcd-box">
            <div class="lcd-label">WIN</div>
            <div class="lcd-value" id="winDisplay">0</div>
        </div>
        <div class="lcd-box">
            <div class="lcd-label">CREDIT</div>
            <div class="lcd-value" id="balanceDisplay">---</div>
        </div>
    </div>

    <!-- 游戏画布容器 (PIXI) -->
    <div id="game-container-wrapper">
        <div id="game-container"></div>
    </div>

    <!-- 底部控制区 -->
    <div class="control-panel">
        <!-- 赔率条 -->
        <div id="oddsDisplayContainer">Loading...</div>

        <!-- 下注按钮 -->
        <div class="bet-buttons" id="betButtonsContainer">
            <!-- JS 动态生成 -->
        </div>

        <!-- 功能按钮组 -->
        <div class="func-buttons">
            <button class="btn-func btn-wallet" onclick="openWallet()">
                <i class="fa-solid fa-wallet"></i> 钱包
            </button>
            <button class="btn-func btn-reset" onclick="resetBets()">
                <i class="fa-solid fa-rotate-left"></i> 重置
            </button>
            <button class="btn-func btn-auto" onclick="toggleAuto()">
                <i class="fa-solid fa-robot"></i> 托管
            </button>
            <button class="btn-func btn-spin" id="startBtn" onclick="spin()">
                <i class="fa-solid fa-play"></i> SPIN
            </button>
        </div>
    </div>

</div>

<!-- 钱包弹窗 -->
<div id="walletModal" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-header">
            <h3>资金管理</h3>
            <button onclick="closeWallet()" class="close-btn"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="modal-tabs">
            <button id="tabDep" class="tab-btn active" onclick="switchTab('deposit')">充值</button>
            <button id="tabWdr" class="tab-btn" onclick="switchTab('withdraw')">提现</button>
        </div>

        <!-- 充值面板 -->
        <div id="panelDeposit" class="tab-panel">
            <p class="hint">请选择支付通道：</p>
            <div id="channelList" class="channel-grid">
                <div style="text-align:center; color:#64748b; font-size:12px; grid-column: span 2;">正在获取通道...</div>
            </div>

            <!-- 输入区域 -->
            <div id="depositInputArea" style="display:none; border-top: 1px solid #334155; padding-top: 15px;">
                <p class="hint">
                    汇率: <span id="currentRate" style="color:#f59e0b; font-weight:bold;">1.0</span>
                    | 预计到账: <span id="calcPoints" style="color:#4ade80; font-weight:bold;">0</span>
                </p>
                <div class="input-group">
                    <input type="number" id="depAmount" class="input-main" placeholder="输入金额" value="100">
                    <button class="btn-confirm" onclick="doDeposit()">支付</button>
                </div>
            </div>

            <div id="depResult" style="display:none; margin-top:15px; background:rgba(0,0,0,0.3); padding:10px; border-radius:8px; font-size: 13px;"></div>
        </div>

        <!-- 提现面板 -->
        <div id="panelWithdraw" class="tab-panel" style="display:none;">
            <p class="hint">可用余额: <span id="walletBalance" style="color:#fff; font-weight:bold;">0.00</span></p>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                <input type="number" id="wdrAmount" class="input-main" placeholder="提现金额 (最少10)">
                <input type="text" id="wdrAddress" class="input-main" placeholder="USDT TRC20 地址">
                <button class="btn-confirm" style="background: #ef4444; color: #fff; margin-top: 10px;" onclick="doWithdraw()">提交申请</button>
            </div>
        </div>
    </div>
</div>

<script>
    // --- 0. 环境初始化 ---
    const tg = window.Telegram.WebApp;
    tg.ready();
    try {
        tg.expand();
        tg.setHeaderColor('#0f172a'); // 适配顶部颜色
        tg.setBackgroundColor('#0f172a');
    } catch(e){}

    // --- 1. 全局拦截器 (处理 Token 过期) ---
    axios.interceptors.response.use(response => {
        return response;
    }, error => {
        if (error.response && error.response.status === 401) {
            console.warn("Token expired");
            // 简单处理：刷新页面重新登录
            // location.reload();
        }
        return Promise.reject(error);
    });

    // --- 2. 核心状态 ---
    let FRUIT_CONFIG = [];
    let BOARD_LAYOUT = [];
    let currentBets = {};
    let isSpinning = false;
    let autoPlay = false;
    let activeChannel = null;

    // --- 3. 登录逻辑 ---
    async function loginWithTelegram() {
        let token = localStorage.getItem('game_token');
        let isSuccess = false;

        if (tg.initData) {
            try {
                const res = await axios.post('/api/auth/telegram', {
                    init_data: tg.initData
                });
                if (res.data.token) {
                    token = res.data.token;
                    localStorage.setItem('game_token', token);
                    console.log("TG Login:", res.data.user?.name);
                    isSuccess = true;
                }
            } catch (e) { console.error("Login Error:", e); }
        }

        if (token) {
            axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
            if (!isSuccess) isSuccess = true;
        }
        return isSuccess;
    }

    // --- 4. 支付回调监听 ---
    window.addEventListener('load', () => {
        const urlParams = new URLSearchParams(window.location.search);
        const status = urlParams.get('status');
        if (status === 'success') {
            window.history.replaceState({}, document.title, window.location.pathname);
            tg.showAlert("支付成功！积分稍后到账。");
            setTimeout(refreshBalance, 3000);
        }
    });

    // --- 5. 游戏启动 ---
    (async function initGame() {
        try {
            const loginOk = await loginWithTelegram();
            if (!loginOk && !localStorage.getItem('game_token')) {
                document.getElementById('loading-mask').innerHTML = "<div style='color:red'>LOGIN FAILED</div>";
                return;
            }

            const res = await axios.get('/api/game/config');
            const data = res.data.data;
            FRUIT_CONFIG = data.fruits;
            BOARD_LAYOUT = data.layout;

            FRUIT_CONFIG.forEach(f => { if(f.id !== 9) currentBets[f.id] = 0; });

            initBoard();
            initBetButtons();
            initOddsLabels();
            await refreshBalance();

            // 隐藏 Loading，显示游戏
            document.getElementById('loading-mask').style.display = 'none';

        } catch (e) {
            console.error(e);
            document.getElementById('loading-mask').innerText = "CONNECT ERROR";
        }
    })();

    // --- 6. 屏幕适配 ---
    function resizeApp() {
        const scaler = document.getElementById('app-scaler');
        const scale = Math.min(window.innerWidth / 480, 1);
        scaler.style.transform = `scale(${scale})`;
        document.body.style.height = (scaler.offsetHeight * scale) + 'px';
    }
    window.addEventListener('resize', resizeApp);
    resizeApp();

    // --- 7. 音频 ---
    const sndClick = new Howl({ src: ['https://assets.mixkit.co/active_storage/sfx/2568/2568-preview.mp3'], volume: 0.5 });
    const sndSpin  = new Howl({ src: ['https://assets.mixkit.co/active_storage/sfx/2044/2044-preview.mp3'], loop: true, volume: 0.4 });
    const sndWin   = new Howl({ src: ['https://assets.mixkit.co/active_storage/sfx/2019/2019-preview.mp3'], volume: 0.8 });
    const sndLucky = new Howl({ src: ['https://assets.mixkit.co/active_storage/sfx/2003/2003-preview.mp3'], volume: 1.0 });

    document.body.addEventListener('click', () => { if (Howler.ctx && Howler.ctx.state !== 'running') Howler.ctx.resume(); }, { once: true });

    // --- 8. PIXI 渲染 ---
    const app = new PIXI.Application({ width: 360, height: 360, backgroundColor: 0x000000, backgroundAlpha: 0 });
    document.getElementById('game-container').appendChild(app.view);
    const squares = [];

    function initBoard() {
        const centerStyle = new PIXI.TextStyle({
            fontFamily: 'Orbitron', fill: ['#fbbf24', '#d97706'], fontSize: 36, fontWeight: '900',
            dropShadow: true, dropShadowColor: '#000000', dropShadowBlur: 5
        });
        const centerText = new PIXI.Text('LUCKY', centerStyle);
        centerText.anchor.set(0.5); centerText.x = 180; centerText.y = 180;
        app.stage.addChild(centerText);

        const size = 42; const gap = 6; const step = size + gap;
        const startX = 18; const startY = 18;
        const coords = [];

        // 环形坐标生成 (适配 360x360)
        for(let i=0; i<7; i++) coords.push({x: startX + i*step, y: startY});
        for(let i=1; i<=6; i++) coords.push({x: startX + 6*step, y: startY + i*step});
        for(let i=5; i>=0; i--) coords.push({x: startX + i*step, y: startY + 6*step});
        for(let i=5; i>=1; i--) coords.push({x: startX, y: startY + i*step});

        coords.forEach((pos, index) => {
            const container = new PIXI.Container();
            container.x = pos.x; container.y = pos.y;

            const fruitId = BOARD_LAYOUT[index];
            const config = FRUIT_CONFIG.find(f => f.id === fruitId);

            const bg = new PIXI.Graphics();
            bg.lineStyle(2, 0x475569);
            // 特殊颜色处理
            if(fruitId === 9) bg.beginFill(0xbe123c); // Lucky
            else bg.beginFill(0x1e293b); // 普通格子
            bg.drawRoundedRect(0, 0, size, size, 6); bg.endFill();
            container.addChild(bg);

            // 文字/图标
            const textStr = config ? config.name : '?';
            // 简单处理：如果是 Lucky 显示星星，否则显示文字
            const text = new PIXI.Text(textStr, {
                fontSize: 10, fill: '#fff', fontWeight: 'bold'
            });
            text.anchor.set(0.5); text.position.set(size/2, size/2);
            container.addChild(text);

            // 高亮框 (发光效果)
            const highlight = new PIXI.Graphics();
            highlight.lineStyle(3, 0xf59e0b);
            highlight.beginFill(0xfcd34d, 0.2);
            highlight.drawRoundedRect(-2, -2, size+4, size+4, 8); highlight.endFill();
            highlight.visible = false;
            container.addChild(highlight);

            squares.push({ highlight: highlight, id: fruitId });
            app.stage.addChild(container);
        });
        updateHighlight(0);
    }

    function initBetButtons() {
        const container = document.getElementById('betButtonsContainer');
        container.innerHTML = '';
        const bettable = FRUIT_CONFIG.filter(f => f.id !== 9);
        bettable.forEach(fruit => {
            const unit = document.createElement('div');
            unit.className = 'bet-unit';
            unit.id = `unit-${fruit.id}`;

            const lcd = document.createElement('div');
            lcd.className = 'bet-lcd';
            lcd.id = `bet-val-${fruit.id}`;
            lcd.innerText = '0';

            const btn = document.createElement('button');
            btn.className = `btn-fruit`;
            btn.innerText = fruit.name;
            // 简单设置一些水果颜色
            if(fruit.name === 'BAR') btn.style.color = '#4ade80';
            if(fruit.name === '77') btn.style.color = '#a855f7';

            btn.onclick = () => {
                tgTrigger('light');
                addBet(fruit.id);
                unit.classList.add('active'); // 添加激活态
            };

            unit.appendChild(lcd); unit.appendChild(btn);
            container.appendChild(unit);
        });
    }

    function initOddsLabels() {
        const container = document.getElementById('oddsDisplayContainer');
        container.innerHTML = '';
        const bettable = FRUIT_CONFIG.filter(f => f.id !== 9);
        bettable.forEach(fruit => {
            const span = document.createElement('span');
            span.innerText = `${fruit.name}:x${fruit.multiplier}`;
            container.appendChild(span);
        });
    }

    function addBet(id) {
        if(isSpinning) return;
        sndClick.play();
        currentBets[id] += 10;
        document.getElementById(`bet-val-${id}`).innerText = currentBets[id];
    }

    function resetBets() {
        if(isSpinning) return;
        sndClick.play(); tgTrigger('medium');
        for(let id in currentBets) {
            currentBets[id] = 0;
            document.getElementById(`bet-val-${id}`).innerText = 0;
            document.getElementById(`unit-${id}`).classList.remove('active');
        }
    }

    function toggleAuto() {
        autoPlay = !autoPlay;
        tgNotify(autoPlay ? 'success' : 'warning');
        const btn = document.querySelector('.btn-auto');
        btn.style.filter = autoPlay ? 'brightness(1.5)' : 'brightness(1)';
    }

    async function refreshBalance() {
        try {
            const res = await axios.get('/api/game/balance');
            const data = res.data.data;
            document.getElementById('balanceDisplay').innerText = String(data.balance).padStart(6, '0');
            document.getElementById('jackpotDisplay').innerText = parseFloat(data.jackpot).toFixed(2);
        } catch(e) { document.getElementById('balanceDisplay').innerText = "---"; }
    }

    function updateHighlight(idx) {
        squares.forEach(s => s.highlight.visible = false);
        if(squares[idx]) squares[idx].highlight.visible = true;
    }

    function runToStop(targetIndex, speedMult = 1) {
        return new Promise(resolve => {
            let currentIdx = squares.findIndex(s => s.highlight.visible);
            if(currentIdx < 0) currentIdx = 0;
            let rounds = 0;
            const minRounds = 2;
            const baseTime = 50 / speedMult;
            const timer = setInterval(() => {
                currentIdx++;
                if(currentIdx >= 24) { currentIdx = 0; rounds++; }
                updateHighlight(currentIdx);
                if(rounds >= minRounds && currentIdx === targetIndex) {
                    clearInterval(timer); resolve();
                }
            }, baseTime);
        });
    }

    async function spin() {
        const totalBet = Object.values(currentBets).reduce((a,b)=>a+b, 0);
        if (totalBet === 0) return tg.showAlert("请先点击图标下注");

        isSpinning = true;
        const startBtn = document.getElementById('startBtn');
        startBtn.disabled = true; startBtn.style.opacity = 0.5;

        tgTrigger('heavy');
        document.getElementById('winDisplay').innerText = "0";
        sndSpin.play();

        try {
            const res = await axios.post('/api/game/spin', { bets: currentBets });
            const data = res.data.data;
            const stops = data.stops || [data.stop_index];

            for(let i=0; i<stops.length; i++) {
                const target = stops[i];
                const isFinal = (i === stops.length - 1);
                const speed = (i > 0) ? 2.5 : 1.0;
                await runToStop(target, speed);
                if (!isFinal) {
                    sndSpin.pause(); sndLucky.play(); tgNotify('success');
                    const blinkTimer = setInterval(() => {
                        squares[target].highlight.visible = !squares[target].highlight.visible;
                    }, 100);
                    await new Promise(r => setTimeout(r, 1500));
                    clearInterval(blinkTimer);
                    sndSpin.play();
                }
            }

            isSpinning = false; sndSpin.stop();
            refreshBalance();
            document.getElementById('winDisplay').innerText = data.win_amount;

            if(data.win_amount > 0) {
                sndWin.play(); tgNotify('success');
                // 赢分特效：数字闪烁
                const winDis = document.getElementById('winDisplay');
                winDis.style.textShadow = "0 0 20px #ef4444";
                setTimeout(() => winDis.style.textShadow = "none", 1000);
            }

            if(autoPlay && data.balance >= totalBet) setTimeout(spin, 1500);
            else if (autoPlay) { autoPlay = false; tg.showAlert("自动停止"); }

        } catch (err) {
            isSpinning = false; sndSpin.stop();
            tg.showAlert("Error: " + (err.response?.data?.message || "Net Error"));
        } finally {
            startBtn.disabled = false; startBtn.style.opacity = 1;
        }
    }

    // --- 9. 钱包功能 ---
    async function openWallet() {
        refreshBalance();
        document.getElementById('walletBalance').innerText = document.getElementById('balanceDisplay').innerText;
        document.getElementById('walletModal').style.display = 'flex';
        await loadChannels();
    }
    function closeWallet() {
        document.getElementById('walletModal').style.display = 'none';
        document.getElementById('depResult').style.display = 'none';
    }
    function switchTab(type) {
        document.getElementById('tabDep').className = `tab-btn ${type==='deposit'?'active':''}`;
        document.getElementById('tabWdr').className = `tab-btn ${type==='withdraw'?'active':''}`;
        document.getElementById('panelDeposit').style.display = type==='deposit'?'block':'none';
        document.getElementById('panelWithdraw').style.display = type==='withdraw'?'block':'none';
    }

    async function loadChannels() {
        const container = document.getElementById('channelList');
        try {
            const res = await axios.get('/api/deposit/channels');
            container.innerHTML = '';
            if(!res.data.data.length) return container.innerHTML = '<p class="hint">暂无通道</p>';

            res.data.data.forEach(ch => {
                const btn = document.createElement('div');
                btn.className = 'channel-btn';
                // 图标判断
                let icon = '<i class="fa-solid fa-coins"></i>';
                if(ch.slug === 'stars') icon = '<i class="fa-solid fa-star" style="color:#fbbf24"></i>';
                if(ch.slug === 'nowpayments') icon = '<i class="fa-brands fa-bitcoin" style="color:#4ade80"></i>';

                btn.innerHTML = `${icon} ${ch.name}`;
                btn.onclick = () => selectChannel(ch, btn);
                container.appendChild(btn);
            });
        } catch(e) { container.innerHTML = '<p class="hint">Load Failed</p>'; }
    }

    function selectChannel(channel, el) {
        activeChannel = channel;
        document.querySelectorAll('.channel-btn').forEach(b => b.classList.remove('active'));
        el.classList.add('active');
        document.getElementById('depositInputArea').style.display = 'block';
        document.getElementById('currentRate').innerText = channel.exchange_rate;
        calcPreview();
    }

    document.getElementById('depAmount').addEventListener('input', calcPreview);
    function calcPreview() {
        if(!activeChannel) return;
        const val = parseFloat(document.getElementById('depAmount').value) || 0;
        const points = val * parseFloat(activeChannel.exchange_rate);
        document.getElementById('calcPoints').innerText = points.toFixed(0);
    }

    async function doDeposit() {
        if (!activeChannel) return tg.showAlert("请选择通道");
        const amount = document.getElementById('depAmount').value;
        const btn = document.querySelector('.btn-confirm');
        btn.disabled = true; btn.innerText = "处理中...";

        try {
            const res = await axios.post('/api/deposit', { amount, method: activeChannel.slug });
            const data = res.data.data;
            const resArea = document.getElementById('depResult');
            resArea.style.display = 'block';
            resArea.innerHTML = '';

            if (data.type === 'stars') {
                let url = data.url;
                if(url.includes('t.me/')) url = url.split('t.me/')[1];
                tg.openInvoice(url, (status) => {
                    if(status === 'paid') {
                        tg.close(); closeWallet(); tg.showAlert("充值成功"); refreshBalance();
                    }
                });
            } else if (data.type === 'url') {
                if(window.Telegram?.WebApp) tg.openLink(data.url);
                else window.location.href = data.url;
                closeWallet();
            }
        } catch(e) { tg.showAlert(e.response?.data?.message || "Error"); }
        finally { btn.disabled = false; btn.innerText = "支付"; }
    }

    // 辅助函数
    function tgTrigger(style) { if(window.Telegram?.WebApp?.HapticFeedback) window.Telegram.WebApp.HapticFeedback.impactOccurred(style); }
    function tgNotify(type) { if(window.Telegram?.WebApp?.HapticFeedback) window.Telegram.WebApp.HapticFeedback.notificationOccurred(type); }

</script>
</body>
</html>
