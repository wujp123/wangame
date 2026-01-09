<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Royal Fruit - Auto Fit</title>

    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://pixijs.download/v7.x/pixi.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.3/howler.min.js"></script>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Roboto+Condensed:wght@700&display=swap');

        :root { --body-bg: #000; }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; user-select: none; }

        body, html {
            margin: 0; padding: 0;
            background-color: var(--body-bg);
            /* 强制全屏，禁止滚动 */
            height: 100%; width: 100%;
            overflow: hidden;
            font-family: 'Roboto Condensed', sans-serif;
        }

        #loading-mask {
            position: fixed; inset: 0; background: #000; z-index: 9999;
            display: flex; justify-content: center; align-items: center;
            color: #ffd700; font-family: 'Orbitron'; font-size: 20px;
        }

        #app-root {
            width: 100%;
            /* 限制最大宽度，但高度跟随屏幕 */
            max-width: 500px;
            height: 100%;
            margin: 0 auto; /* 居中 */

            background: linear-gradient(180deg, #b71c1c 0%, #880e4f 5%, #0277bd 15%, #01579b 100%);
            display: flex;
            flex-direction: column; /* 垂直排列 */
            position: relative;
            box-shadow: 0 0 50px rgba(0,0,0,0.8);
        }

        /* === 1. 顶部灯箱 (固定高度，不许缩小) === */
        .top-hood {
            flex: 0 0 auto; /* 关键：禁止被压缩 */
            height: 70px;   /* 固定高度 */
            background: radial-gradient(circle at 50% 100%, #ff5252, #b71c1c);
            border-bottom: 4px solid #ffd700;
            display: flex; justify-content: space-between; align-items: center;
            padding: 5px 15px;
            padding-top: max(5px, env(safe-area-inset-top)); /* 适配刘海屏 */
            z-index: 20; position: relative;
            box-shadow: 0 2px 10px rgba(0,0,0,0.4);
        }
        .bulb-deco { position: absolute; bottom: 5px; left: 50%; transform: translateX(-50%); width: 60%; display: flex; justify-content: space-between; pointer-events: none; }
        .bulb { width: 6px; height: 6px; background: #fff; border-radius: 50%; box-shadow: 0 0 8px #fff; }

        .lcd-group { text-align: center; }
        .lcd-label { color: #29b6f6; font-size: 10px; font-weight: bold; margin-bottom: 1px; text-shadow: 0 1px 2px rgba(0,0,0,0.8); }
        .lcd-frame { background: #000; padding: 2px; border-radius: 6px; border-bottom: 1px solid #444; box-shadow: 0 2px 5px rgba(0,0,0,0.5); }
        .lcd-screen { background: radial-gradient(#222, #000); border: 1px solid #333; border-radius: 4px; padding: 0 8px; min-width: 90px; }
        .lcd-digit { font-family: 'Orbitron', monospace; font-size: 18px; color: #ff1744; text-shadow: 0 0 8px #d50000; letter-spacing: 1px; }
        #balanceDisplay { color: #fff; text-shadow:none; }

        /* === 2. 游戏盘面 (弹性区域，自动缩放) === */
        #game-wrapper {
            /* 关键布局设置 */
            flex: 1 1 auto;  /* 占据剩余所有空间 */
            min-height: 0;   /* 允许内容无限缩小，防止溢出 */
            width: 100%;

            background: #fdf5e6;
            border-left: 2px solid #0d47a1;
            border-right: 2px solid #0d47a1;

            display: flex;
            justify-content: center;
            align-items: center; /* 让画布居中 */
            overflow: hidden;
            padding: 5px; /* 留一点呼吸感 */
        }

        /* === 3. 底部操作台 (固定高度，不许缩小) === */
        .control-deck {
            flex: 0 0 auto; /* 关键：禁止被压缩 */
            background: linear-gradient(180deg, #42a5f5 0%, #1565c0 40%, #0d47a1 100%);
            border-top: 4px solid #ffd700;
            padding: 8px;
            padding-bottom: max(10px, env(safe-area-inset-bottom)); /* 适配底部横条 */
            display: flex; flex-direction: column; gap: 6px;
            position: relative; z-index: 10;
        }

        /* UI 组件样式 (保持不变) */
        .func-row { display: flex; gap: 6px; justify-content: space-between; align-items: center; padding: 4px; background: rgba(0,0,0,0.2); border-radius: 10px; box-shadow: inset 0 2px 5px rgba(0,0,0,0.3); }
        .btn-3d { border: none; position: relative; cursor: pointer; color: #fff; font-weight: bold; display: flex; align-items: center; justify-content: center; transition: transform 0.1s; }
        .btn-3d:active { transform: translateY(3px); box-shadow: 0 0 0 transparent !important; border-bottom-width: 0 !important;}

        .b-round-green { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(180deg, #76ff03 0%, #33691e 100%); box-shadow: 0 4px 0 #1b5e20, 0 5px 5px rgba(0,0,0,0.3); border: 2px solid #b2ff59; font-size: 10px; flex-direction: column; line-height: 1; text-shadow: 0 1px 1px #000; }
        .b-sq { height: 38px; border-radius: 8px; font-size: 13px; flex: 1; box-shadow: 0 4px 0 rgba(0,0,0,0.4), 0 5px 5px rgba(0,0,0,0.2); border-top: 1px solid rgba(255,255,255,0.4); }
        .b-blue { background: linear-gradient(180deg, #29b6f6 0%, #01579b 100%); font-size: 18px; }
        .b-purp { background: linear-gradient(180deg, #ab47bc 0%, #4a148c 100%); font-size: 11px; }
        .b-go { width: 70px; height: 42px; border-radius: 10px; background: linear-gradient(180deg, #ffeb3b 0%, #ff6f00 100%); box-shadow: 0 5px 0 #e65100, 0 5px 5px rgba(0,0,0,0.3); border: 2px solid #fff; color: #b71c1c; font-family: 'Orbitron'; font-size: 20px; }
        .b-go:active { transform: translateY(4px); box-shadow: 0 1px 0 #e65100; }
        .b-go:disabled { filter: grayscale(1); cursor: not-allowed; }

        .bet-panel { display: grid; grid-template-columns: repeat(8, 1fr); gap: 3px; background: #0d47a1; padding: 4px; border-radius: 8px; box-shadow: inset 0 2px 8px rgba(0,0,0,0.6), 0 2px 0 rgba(255,255,255,0.2); }
        .bet-col { display: flex; flex-direction: column; align-items: center; gap: 1px; }

        .odds-glass { width: 100%; height: 24px; font-size: 12px; font-weight: 900; color: #fff; display: flex; align-items: center; justify-content: center; text-shadow: 0 1px 2px #000; box-shadow: inset 0 1px 0 rgba(255,255,255,0.4), 0 2px 2px rgba(0,0,0,0.3); border: 1px solid rgba(0,0,0,0.2); border-top: 1px solid rgba(255,255,255,0.6); }
        .bg-blue { background: linear-gradient(180deg, #42a5f5 0%, #1565c0 100%); }
        .bg-red { background: linear-gradient(180deg, #ef5350 0%, #b71c1c 100%); }
        .bg-grey { background: linear-gradient(180deg, #90a4ae 0%, #455a64 100%); }

        .led-window { width: 100%; height: 22px; background: #000; border: 1px solid #555; border-bottom: 1px solid #777; color: #ff1744; font-family: 'Orbitron'; font-size: 14px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 3px 5px rgba(0,0,0,0.8); margin-bottom: 2px; letter-spacing: 1px; }

        .push-btn { width: 100%; aspect-ratio: 1; border-radius: 50%; border: none; position: relative; cursor: pointer; box-shadow: 0 4px 0 rgba(0,0,0,0.3), 0 5px 5px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; border-top: 1px solid rgba(255,255,255,0.5); }
        .push-btn::after { content: ''; position: absolute; top: 10%; left: 20%; width: 60%; height: 40%; background: linear-gradient(180deg, rgba(255,255,255,0.7) 0%, rgba(255,255,255,0) 100%); border-radius: 50%; pointer-events: none; }
        .push-btn:active { transform: translateY(3px); box-shadow: 0 1px 0 rgba(0,0,0,0.3); }
        .pb-green { background: linear-gradient(180deg, #76ff03 0%, #33691e 100%); }
        .pb-purp { background: linear-gradient(180deg, #e040fb 0%, #7b1fa2 100%); }
        .pb-red { background: linear-gradient(180deg, #ff5252 0%, #b71c1c 100%); }

        /* Wallet */
        .modal-overlay { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:999; justify-content:center; align-items:center; }
        .modal-body { background: #fff; width: 300px; padding: 20px; border-radius: 10px; font-family: sans-serif; }
        .modal-tabs { display:flex; gap:10px; margin-bottom:15px; }
        .tab-btn { flex:1; padding:8px; border:1px solid #ccc; background:#eee; cursor:pointer; }
        .tab-btn.active { background:#0277bd; color:white; border-color:#0277bd; }
        .channel-btn { padding:10px; margin-bottom:5px; border:1px solid #ddd; cursor:pointer; display:flex; justify-content:space-between; }
        .channel-btn.active { border-color:#0277bd; background:#e1f5fe; }
    </style>
</head>
<body>

<div id="loading-mask">CONNECTING...</div>

<div id="app-root">
    <!-- 顶部区域 -->
    <div class="top-hood">
        <div class="bulb-deco"><div class="bulb"></div><div class="bulb"></div><div class="bulb"></div><div class="bulb"></div></div>
        <div class="lcd-group">
            <div class="lcd-label">WIN / JP</div>
            <div class="lcd-frame"><div class="lcd-screen"><div id="winDisplay" class="lcd-digit">0</div></div></div>
        </div>
        <div class="lcd-group">
            <div class="lcd-label">CREDIT</div>
            <div class="lcd-frame"><div class="lcd-screen"><div id="balanceDisplay" class="lcd-digit" style="color:#fff">---</div></div></div>
        </div>
    </div>

    <!-- 游戏盘面区域 (自适应) -->
    <div id="game-wrapper">
        <!-- Canvas 将注入到这里 -->
    </div>

    <!-- 底部区域 -->
    <div class="control-deck">
        <div class="func-row">
            <button class="btn-3d b-round-green" onclick="openWallet()">$<br>ADD</button>
            <div style="display:flex; gap:6px; flex:1.5">
                <button class="btn-3d b-sq b-blue">⬅</button>
                <button class="btn-3d b-sq b-blue">➡</button>
            </div>
            <div style="display:flex; gap:6px; flex:1.5">
                <button class="btn-3d b-sq b-purp btn-auto" onclick="toggleAuto()">AUTO</button>
                <button class="btn-3d b-sq b-purp">8-13</button>
            </div>
            <button class="btn-3d b-go" id="startBtn" onclick="spin()">GO</button>
        </div>
        <div class="bet-panel" id="betButtonsContainer"></div>
    </div>
</div>

<!-- Wallet Modal -->
<div id="walletModal" class="modal-overlay">
    <div class="modal-body">
        <div style="display:flex;justify-content:space-between;margin-bottom:10px;">
            <h3>钱包</h3><button onclick="closeWallet()">X</button>
        </div>
        <div class="modal-tabs">
            <button id="tabDep" class="tab-btn active" onclick="switchTab('deposit')">充值</button>
            <button id="tabWdr" class="tab-btn" onclick="switchTab('withdraw')">提现</button>
        </div>
        <div id="panelDeposit">
            <div id="channelList">Loading...</div>
            <div id="depositInputArea" style="display:none; margin-top:10px; border-top:1px solid #eee; padding-top:10px;">
                <p style="font-size:12px">汇率: <span id="currentRate"></span></p>
                <input type="number" id="depAmount" placeholder="金额" style="width:100%;padding:8px;margin-bottom:5px;">
                <p style="font-size:12px;color:green">预计: <span id="calcPoints">0</span> 积分</p>
                <button class="btn-3d b-sq b-blue btn-confirm" style="width:100%" onclick="doDeposit()">支付</button>
            </div>
            <div id="depResult" style="margin-top:10px; font-size:12px;"></div>
        </div>
        <div id="panelWithdraw" style="display:none">
            <p>余额: <span id="walletBalance">0</span></p>
            <input id="wdrAddr" placeholder="TRC20 地址" style="width:100%;padding:8px;margin-bottom:5px;">
            <input type="number" id="wdrAmt" placeholder="金额" style="width:100%;padding:8px;margin-bottom:5px;">
            <button class="btn-3d b-sq b-purp" style="width:100%" onclick="alert('提现申请已提交')">提现</button>
        </div>
    </div>
</div>

<script>
    // === 环境配置 ===
    const tg = window.Telegram.WebApp;
    tg.ready(); tg.expand();
    try { tg.setHeaderColor('#0d47a1'); } catch(e){}

    axios.interceptors.response.use(r => r, e => {
        if (e.response && e.response.status === 401) location.reload();
        return Promise.reject(e);
    });

    const VISUAL_MAP = {
        1: { icon: '💎', color: 'pb-green', tag: 'bg-blue' },
        2: { icon: '7️⃣', color: 'pb-purp', tag: 'bg-red' },
        3: { icon: '⭐', color: 'pb-green', tag: 'bg-grey' },
        4: { icon: '🍉', color: 'pb-green', tag: 'bg-grey' },
        5: { icon: '🔔', color: 'pb-green', tag: 'bg-red' },
        6: { icon: '🍋', color: 'pb-green', tag: 'bg-grey' },
        7: { icon: '🍊', color: 'pb-green', tag: 'bg-grey' },
        8: { icon: '🍎', color: 'pb-red', tag: 'bg-blue' },
        9: { icon: '❓', color: '', tag: '' }
    };

    let FRUIT_CONFIG = [], BOARD_LAYOUT = [], currentBets = {}, isSpinning = false, autoPlay = false, activeChannel = null, squares = [], app;
    const sndClick = new Howl({ src: ['https://assets.mixkit.co/active_storage/sfx/2568/2568-preview.mp3'] });
    const sndSpin  = new Howl({ src: ['https://assets.mixkit.co/active_storage/sfx/2044/2044-preview.mp3'], loop:true, volume:0.5 });
    const sndWin   = new Howl({ src: ['https://assets.mixkit.co/active_storage/sfx/2019/2019-preview.mp3'] });
    const sndLucky = new Howl({ src: ['https://assets.mixkit.co/active_storage/sfx/2003/2003-preview.mp3'] });

    async function initGame() {
        if (await loginWithTelegram()) {
            try {
                // Config API
                const res = await axios.get('/api/game/config');
                const data = res.data.data;
                FRUIT_CONFIG = data.fruits;
                BOARD_LAYOUT = data.layout;
                FRUIT_CONFIG.forEach(f => { if(f.id !== 9) currentBets[f.id] = 0; });

                initPixi();
                initUI();
                await refreshBalance();
                document.getElementById('loading-mask').style.display = 'none';

                // Trigger an initial resize to fit the screen perfectly
                window.dispatchEvent(new Event('resize'));
            } catch(e) {
                document.getElementById('loading-mask').innerText = "CONNECT ERROR: " + e.message;
            }
        } else {
            document.getElementById('loading-mask').innerText = "LOGIN FAILED";
        }
    }

    async function loginWithTelegram() {
        let token = localStorage.getItem('game_token');
        if (tg.initData) {
            try {
                const res = await axios.post('/api/auth/telegram', { init_data: tg.initData });
                if (res.data.token) { token = res.data.token; localStorage.setItem('game_token', token); }
            } catch (e) { return false; }
        }
        if (token) { axios.defaults.headers.common['Authorization'] = `Bearer ${token}`; return true; }
        return false;
    }

    function initUI() {
        const div = document.getElementById('betButtonsContainer');
        div.innerHTML = '';
        FRUIT_CONFIG.filter(f => f.id !== 9).forEach(f => {
            const style = VISUAL_MAP[f.id] || VISUAL_MAP[8];
            const col = document.createElement('div');
            col.className = 'bet-col';
            col.innerHTML = `
                <div class="odds-glass ${style.tag}">x${f.multiplier}</div>
                <div class="led-window" id="bet-val-${f.id}">0</div>
                <button class="push-btn ${style.color}" onclick="addBet(${f.id})">
                    <span style="font-size:20px; color:#fff; text-shadow:1px 1px 2px #000; z-index:2">${style.icon}</span>
                </button>
            `;
            div.appendChild(col);
        });
    }

    function initPixi() {
        const wrapper = document.getElementById('game-wrapper');
        // 初始逻辑尺寸
        const W = 520, H = 520;
        app = new PIXI.Application({ width:W, height:H, backgroundAlpha:0, resolution:2 });
        wrapper.appendChild(app.view);

        // ★★★ 核心修复：自适应正方形 ★★★
        const resize = () => {
            // 获取容器实际可用尺寸
            const w = wrapper.clientWidth;
            const h = wrapper.clientHeight;
            // 取两者较小值，确保是正方形且不溢出
            const size = Math.min(w, h);

            app.view.style.width = size + 'px';
            app.view.style.height = size + 'px';
        };
        window.addEventListener('resize', resize);
        // 立即执行一次
        resize();

        // 绘制内容 (基于 520x520 逻辑坐标)
        const bg = new PIXI.Graphics();
        bg.beginFill(0xfdf5e6); bg.drawRect(0,0,W,H);
        app.stage.addChild(bg);

        const center = new PIXI.Container();
        const cBg = new PIXI.Graphics();
        cBg.beginFill(0xb71c1c); cBg.drawRoundedRect(0,0, 330, 330, 20);
        cBg.beginFill(0xffecb3); cBg.drawCircle(165, 165, 155);
        cBg.lineStyle(2, 0xffd54f);
        for(let i=0; i<12; i++) {
            cBg.moveTo(165,165);
            cBg.lineTo(165 + 155*Math.cos(i*Math.PI/6), 165 + 155*Math.sin(i*Math.PI/6));
        }
        center.addChild(cBg);

        const txt = new PIXI.Text("CAISHEN", {
            fontFamily: 'Orbitron', fontSize: 45, fill: ['#d50000', '#ff6f00'],
            stroke: '#fff', strokeThickness: 5, dropShadow: true, dropShadowDistance: 4
        });
        txt.anchor.set(0.5); txt.position.set(165, 130);
        center.addChild(txt);

        const midUI = new PIXI.Container();
        midUI.position.set(45, 240);
        const capsule = new PIXI.Graphics();
        capsule.beginFill(0x0d47a1); capsule.lineStyle(2, 0x42a5f5);
        capsule.drawRoundedRect(0,0, 240, 50, 25);
        midUI.addChild(capsule);
        const ledBox = new PIXI.Graphics();
        ledBox.beginFill(0x000); ledBox.drawRoundedRect(70, 8, 100, 34, 5);
        midUI.addChild(ledBox);
        const ledNum = new PIXI.Text("JP", { fontFamily: 'Orbitron', fontSize: 24, fill: '#ff5252' });
        ledNum.anchor.set(0.5); ledNum.position.set(120, 25);
        midUI.addChild(ledNum);
        center.addChild(midUI);

        center.position.set(95, 95);
        app.stage.addChild(center);

        // Grid (Full Gapless)
        const step = 73; const start = 5; const boxSize = 71;
        const POS = [];
        for(let i=0; i<7; i++) POS.push({x:start+i*step, y:start});
        for(let i=1; i<=6; i++) POS.push({x:start+6*step, y:start+i*step});
        for(let i=5; i>=0; i--) POS.push({x:start+i*step, y:start+6*step});
        for(let i=5; i>=1; i--) POS.push({x:start, y:start+i*step});

        squares = [];
        POS.forEach((p, idx) => {
            const id = BOARD_LAYOUT[idx];
            const fConfig = FRUIT_CONFIG.find(x => x.id === id);
            const style = VISUAL_MAP[id] || VISUAL_MAP[9];

            const g = new PIXI.Container();
            g.x=p.x; g.y=p.y;

            const box = new PIXI.Graphics();
            const fill = (id===9) ? 0xffcdd2 : 0xfff8e1;
            box.lineStyle(2, 0x3e2723); box.beginFill(fill);
            box.drawRoundedRect(0,0, boxSize, boxSize, 12);
            g.addChild(box);

            const icon = new PIXI.Text(style.icon, {fontSize:34});
            icon.anchor.set(0.5); icon.position.set(boxSize/2, boxSize/2 - 4);
            g.addChild(icon);

            const lTxt = (id===9) ? 'JP' : `x${fConfig.multiplier}`;
            const label = new PIXI.Text(lTxt, {
                fontFamily: 'Roboto Condensed', fontSize:13, fontWeight:'bold',
                fill: (id===9) ? '#d32f2f' : '#333'
            });
            label.anchor.set(0.5); label.position.set(boxSize/2, boxSize - 12);
            g.addChild(label);

            const hl = new PIXI.Graphics();
            hl.lineStyle(5, 0xff0000); hl.beginFill(0xffff00, 0.3);
            hl.drawRoundedRect(-2,-2, boxSize+4, boxSize+4, 14);
            hl.visible = false;
            g.addChild(hl);

            squares.push({highlight: hl, id: id});
            app.stage.addChild(g);
        });
        if(squares.length) squares[0].highlight.visible = true;
    }

    // Logic
    function addBet(id) {
        if(isSpinning) return;
        sndClick.play();
        currentBets[id] += 10;
        document.getElementById(`bet-val-${id}`).innerText = currentBets[id];
        tgTrigger('light');
    }

    async function refreshBalance() {
        try {
            const res = await axios.get('/api/game/balance');
            const data = res.data.data;
            document.getElementById('balanceDisplay').innerText = String(data.balance).padStart(8, '0');
        } catch(e) {}
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
                squares.forEach(s => s.highlight.visible = false);
                squares[currentIdx].highlight.visible = true;
                if(rounds >= minRounds && currentIdx === targetIndex) {
                    clearInterval(timer); resolve();
                }
            }, baseTime);
        });
    }

    async function spin() {
        const totalBet = Object.values(currentBets).reduce((a,b)=>a+b, 0);
        if (totalBet === 0) return tg.showAlert("请先下注");

        isSpinning = true;
        document.getElementById('startBtn').disabled = true;
        document.getElementById('winDisplay').innerText = "0";
        tgTrigger('medium');
        sndSpin.play();

        try {
            const res = await axios.post('/api/game/spin', { bets: currentBets });
            const data = res.data.data;
            const stops = data.stops || [data.stop_index];

            for(let i=0; i<stops.length; i++) {
                const target = stops[i];
                const speed = (i > 0) ? 2.0 : 1.0;
                await runToStop(target, speed);
                if (i < stops.length - 1) {
                    sndLucky.play();
                    await new Promise(r => setTimeout(r, 1000));
                }
            }

            isSpinning = false; sndSpin.stop();
            refreshBalance();
            document.getElementById('winDisplay').innerText = data.win_amount;
            if(data.win_amount > 0) { sndWin.play(); tgNotify('success'); }

            if(autoPlay && data.balance >= totalBet) setTimeout(spin, 1500);
            else if (autoPlay) { toggleAuto(); tg.showAlert("自动停止"); }

        } catch (err) {
            isSpinning = false; sndSpin.stop();
            tg.showAlert(err.response?.data?.message || "Error");
        } finally {
            document.getElementById('startBtn').disabled = false;
        }
    }

    // Wallet
    async function openWallet() {
        await loadChannels();
        document.getElementById('walletModal').style.display='flex';
        document.getElementById('walletBalance').innerText = document.getElementById('balanceDisplay').innerText;
    }
    function closeWallet() { document.getElementById('walletModal').style.display='none'; }
    function switchTab(t) {
        document.getElementById('tabDep').className = `tab-btn ${t==='deposit'?'active':''}`;
        document.getElementById('tabWdr').className = `tab-btn ${t==='withdraw'?'active':''}`;
        document.getElementById('panelDeposit').style.display = t==='deposit'?'block':'none';
        document.getElementById('panelWithdraw').style.display = t==='withdraw'?'block':'none';
    }
    async function loadChannels() {
        const list = document.getElementById('channelList');
        try {
            const res = await axios.get('/api/deposit/channels');
            list.innerHTML = '';
            if(!res.data.data.length) return list.innerHTML='暂无通道';
            res.data.data.forEach(ch => {
                const div = document.createElement('div');
                div.className='channel-btn';
                div.innerHTML=`<b>${ch.name}</b>`;
                div.onclick = () => selectCh(ch, div);
                list.appendChild(div);
            });
        } catch(e) { list.innerText='加载失败'; }
    }
    function selectCh(ch, el) {
        activeChannel = ch;
        document.querySelectorAll('.channel-btn').forEach(b=>b.classList.remove('active'));
        el.classList.add('active');
        document.getElementById('depositInputArea').style.display='block';
        document.getElementById('currentRate').innerText = ch.exchange_rate;
    }
    async function doDeposit() {
        if(!activeChannel) return;
        const amt = document.getElementById('depAmount').value;
        try {
            const res = await axios.post('/api/deposit', {amount:amt, method:activeChannel.slug});
            const d = res.data.data;
            if(d.type==='stars') {
                tg.openInvoice(d.url.split('t.me/')[1], (s)=>{ if(s==='paid') { closeWallet(); refreshBalance(); } });
            } else if(d.type==='url') {
                tg.openLink(d.url);
            }
        } catch(e) { tg.showAlert(e.message); }
    }

    function toggleAuto() { autoPlay=!autoPlay; document.querySelector('.btn-auto').style.color=autoPlay?'#76ff03':'#fff'; }
    function tgTrigger(s) { if(tg.HapticFeedback) tg.HapticFeedback.impactOccurred(s); }
    function tgNotify(t) { if(tg.HapticFeedback) tg.HapticFeedback.notificationOccurred(t); }

    // Start
    initGame();
</script>
</body>
</html>
