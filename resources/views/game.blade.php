<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Royal Fruit - Auto Scale</title>

    <!-- 依赖库 -->
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <script src="https://pixijs.download/v7.x/pixi.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.3/howler.min.js"></script>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Roboto+Condensed:wght@700&display=swap');

        :root { --body-bg: #000; }
        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; user-select: none; }

        html, body {
            margin: 0; padding: 0;
            background-color: var(--body-bg);
            height: 100%; width: 100%;
            overflow: hidden; /* 禁止滚动 */
            font-family: 'Roboto Condensed', sans-serif;
        }

        #loading-mask {
            position: fixed; inset: 0; background: #000; z-index: 9999;
            display: flex; justify-content: center; align-items: center;
            color: #ffd700; font-family: 'Orbitron'; font-size: 20px;
            transition: opacity 0.5s;
        }

        /* 主容器：铺满屏幕 */
        #app-root {
            position: absolute; top: 0; left: 0; right: 0; bottom: 0;
            width: 100%; height: 100%;
            max-width: 550px; /* 限制最大宽度，平板更舒适 */
            margin: 0 auto;
            background: linear-gradient(180deg, #b71c1c 0%, #880e4f 5%, #0277bd 15%, #01579b 100%);
            display: flex; flex-direction: column; /* 垂直排列 */
            box-shadow: 0 0 50px rgba(0,0,0,0.8);
        }

        /* === 1. 顶部区域 (固定高度) === */
        .top-hood {
            flex: 0 0 auto; /* 禁止压缩 */
            height: 70px;
            background: radial-gradient(circle at 50% 100%, #ff5252, #b71c1c);
            border-bottom: 4px solid #ffd700;
            display: flex; justify-content: space-between; align-items: center;
            padding: 5px 15px;
            padding-top: max(5px, env(safe-area-inset-top)); /* 避让刘海 */
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

        /* === 2. 中间游戏区 (弹性伸缩) === */
        #game-wrapper {
            flex: 1 1 auto;  /* 占据剩余空间 */
            min-height: 0;   /* 关键！允许压缩到 0，防止撑破屏幕 */
            min-width: 0;
            width: 100%;
            background: #fdf5e6; /* 米色背景 */
            border-left: 3px solid #0d47a1;
            border-right: 3px solid #0d47a1;
            position: relative;
            /* 居中 Canvas */
            display: flex; justify-content: center; align-items: center;
            overflow: hidden;
        }
        /* Canvas 样式由 JS 动态控制 */

        /* === 3. 底部操作台 (固定高度，内容自适应) === */
        .control-deck {
            flex: 0 0 auto; /* 禁止压缩 */
            background: linear-gradient(180deg, #42a5f5 0%, #1565c0 40%, #0d47a1 100%);
            border-top: 4px solid #ffd700;
            padding: 8px;
            padding-bottom: max(10px, env(safe-area-inset-bottom)); /* 避让底部横条 */
            display: flex; flex-direction: column; gap: 6px;
            position: relative; z-index: 10;
        }

        /* 按钮和 UI 样式 (保持高拟真) */
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

        /* Mock Toast */
        #msg-toast { position:absolute; bottom: 20%; left:50%; transform:translateX(-50%); background:rgba(0,0,0,0.8); color:#fff; padding:10px 20px; border-radius:20px; display:none; z-index:100; pointer-events:none; }

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

<div id="loading-mask">INITIALIZING...</div>
<div id="msg-toast"></div>

<div id="app-root">
    <!-- Header -->
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

    <!-- Middle Game Area (Flexible) -->
    <div id="game-wrapper">
        <!-- Canvas will be injected here -->
    </div>

    <!-- Footer -->
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
    // === 0. 智能配置 (Hybrid) ===
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

    const MOCK_DATA = {
        fruits: [
            {id:1, name:'BAR', multiplier:100}, {id:2, name:'77', multiplier:40},
            {id:3, name:'STAR', multiplier:30}, {id:4, name:'WTR', multiplier:20},
            {id:5, name:'BEL', multiplier:20}, {id:6, name:'LEM', multiplier:15},
            {id:7, name:'ORG', multiplier:10}, {id:8, name:'APP', multiplier:5},
            {id:9, name:'LUCKY', multiplier:0}
        ],
        layout: [7,5,1,1,8,8,6, 4,4,9,8,8,7, 5,2,2,8,8,6, 3,3,9,8,8],
        balance: 5000
    };

    let FRUIT_CONFIG = [], BOARD_LAYOUT = [], currentBets = {}, isSpinning = false, autoPlay = false;
    let squares = [], app;
    let useMock = false;

    const sfx = {
        click: new Howl({ src: ['https://assets.mixkit.co/active_storage/sfx/2568/2568-preview.mp3'] }),
        spin: new Howl({ src: ['https://assets.mixkit.co/active_storage/sfx/2044/2044-preview.mp3'], loop:true, volume:0.5 }),
        win: new Howl({ src: ['https://assets.mixkit.co/active_storage/sfx/2019/2019-preview.mp3'] }),
        lucky: new Howl({ src: ['https://assets.mixkit.co/active_storage/sfx/2003/2003-preview.mp3'] })
    };

    // === 1. 启动 ===
    async function initGame() {
        const tg = window.Telegram?.WebApp;
        if(tg) { tg.ready(); tg.expand(); tg.setHeaderColor('#b71c1c'); }

        try {
            // 尝试连接后端 (1秒超时)
            const res = await axios.get('/api/game/config', { timeout: 1000 });
            const data = res.data.data;
            FRUIT_CONFIG = data.fruits;
            BOARD_LAYOUT = data.layout;
            useMock = false;
        } catch (e) {
            console.warn("Offline Mode");
            FRUIT_CONFIG = MOCK_DATA.fruits;
            BOARD_LAYOUT = MOCK_DATA.layout;
            useMock = true;
        }

        FRUIT_CONFIG.forEach(f => { if(f.id !== 9) currentBets[f.id] = 0; });

        initPixi();
        initUI();
        refreshBalance();

        // 隐藏遮罩
        const mask = document.getElementById('loading-mask');
        mask.style.opacity = '0';
        setTimeout(()=>mask.style.display='none', 500);

        // 触发一次 Resize 确保对齐
        window.dispatchEvent(new Event('resize'));
    }

    // === 2. 界面构建 ===
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
        // 使用 520x520 作为基准逻辑尺寸
        const LOGIC_SIZE = 520;
        app = new PIXI.Application({ width:LOGIC_SIZE, height:LOGIC_SIZE, backgroundAlpha:0, resolution:2 });
        wrapper.appendChild(app.view);

        // ★★★ 核心适配逻辑 ★★★
        const resize = () => {
            // 获取容器当前的可用宽高
            const w = wrapper.clientWidth;
            const h = wrapper.clientHeight;
            // 计算最大正方形尺寸
            const size = Math.min(w, h);

            // 应用尺寸
            app.view.style.width = size + 'px';
            app.view.style.height = size + 'px';
        };
        window.addEventListener('resize', resize);
        // 延时执行以确保容器已渲染
        setTimeout(resize, 50);

        // 绘制内容
        const bg = new PIXI.Graphics();
        bg.beginFill(0xfdf5e6); bg.drawRect(0,0,LOGIC_SIZE,LOGIC_SIZE);
        app.stage.addChild(bg);

        // 中间
        const center = new PIXI.Container();
        const cBg = new PIXI.Graphics();
        cBg.beginFill(0xb71c1c); cBg.drawRoundedRect(0,0, 330, 330, 20);
        cBg.beginFill(0xffecb3); cBg.drawCircle(165, 165, 155);
        cBg.lineStyle(2, 0xffd54f);
        for(let i=0; i<12; i++) { cBg.moveTo(165,165); cBg.lineTo(165 + 155*Math.cos(i*Math.PI/6), 165 + 155*Math.sin(i*Math.PI/6)); }
        center.addChild(cBg);

        const txt = new PIXI.Text("CAISHEN", { fontFamily: 'Orbitron', fontSize: 45, fill: ['#d50000', '#ff6f00'], stroke: '#fff', strokeThickness: 5, dropShadow: true, dropShadowDistance: 4 });
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

        // 格子 (填满)
        const step = 73; const start = 5; const boxSize = 71;
        const POS = [];
        for(let i=0; i<7; i++) POS.push({x:start+i*step, y:start});
        for(let i=1; i<=6; i++) POS.push({x:start+6*step, y:start+i*step});
        for(let i=5; i>=0; i--) POS.push({x:start+i*step, y:start+6*step});
        for(let i=5; i>=1; i--) POS.push({x:start, y:start+i*step});

        squares = [];
        POS.forEach((p, idx) => {
            const id = BOARD_LAYOUT[idx];
            const fConfig = FRUIT_CONFIG.find(x => x.id === id) || FRUIT_CONFIG[0];
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
            const label = new PIXI.Text(lTxt, { fontFamily: 'Roboto Condensed', fontSize:13, fontWeight:'bold', fill: (id===9)?'#d32f2f':'#333' });
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

    // === 3. 交互 ===
    function addBet(id) {
        if(isSpinning) return;
        if(useMock && MOCK_DATA.balance < 10) return showToast("余额不足");
        sndClick.play();
        currentBets[id] += 10;
        document.getElementById(`bet-val-${id}`).innerText = currentBets[id];
        if(useMock) { MOCK_DATA.balance -= 10; refreshBalance(); }
        tgTrigger('light');
    }

    async function refreshBalance() {
        if(useMock) {
            document.getElementById('balanceDisplay').innerText = String(MOCK_DATA.balance).padStart(8, '0');
        } else {
            try {
                const res = await axios.get('/api/game/balance');
                document.getElementById('balanceDisplay').innerText = String(res.data.data.balance).padStart(8, '0');
            } catch(e){}
        }
    }

    async function spin() {
        const totalBet = Object.values(currentBets).reduce((a,b)=>a+b, 0);
        if (totalBet === 0) return showToast("请先下注");

        isSpinning = true;
        document.getElementById('startBtn').disabled = true;
        document.getElementById('winDisplay').innerText = "0";
        tgTrigger('medium');
        sndSpin.play();

        let targetIndex = 0, winAmount = 0;

        try {
            if(useMock) {
                targetIndex = Math.floor(Math.random() * 24);
                const winId = BOARD_LAYOUT[targetIndex];
                const fruit = FRUIT_CONFIG.find(x=>x.id==winId);
                const bet = currentBets[winId] || 0;
                winAmount = (winId===9) ? 200 : bet * fruit.multiplier;
                await new Promise(r => setTimeout(r, 500));
            } else {
                const res = await axios.post('/api/game/spin', { bets: currentBets });
                targetIndex = res.data.data.stop_index;
                winAmount = res.data.data.win_amount;
            }

            await runAnimation(targetIndex);

            isSpinning = false; sndSpin.stop();
            document.getElementById('startBtn').disabled = false;

            if(winAmount > 0) {
                sndWin.play(); tgNotify('success');
                document.getElementById('winDisplay').innerText = winAmount;
                if(useMock) MOCK_DATA.balance += winAmount;
                refreshBalance();
                let c=0, t=setInterval(()=>{
                    squares[targetIndex].highlight.visible = !squares[targetIndex].highlight.visible;
                    if(++c>6) { clearInterval(t); squares[targetIndex].highlight.visible=true; }
                }, 150);
            }

            if(autoPlay) {
                if((useMock ? MOCK_DATA.balance : 9999) >= totalBet) setTimeout(spin, 1500);
                else { toggleAuto(); showToast("余额不足，自动停止"); }
            }

        } catch (e) {
            isSpinning = false; sndSpin.stop();
            document.getElementById('startBtn').disabled = false;
            showToast("Error: " + e.message);
        }
    }

    function runAnimation(target) {
        return new Promise(resolve => {
            let curr = squares.findIndex(s => s.highlight.visible);
            if(curr < 0) curr = 0;
            let rounds = 0;
            const timer = setInterval(() => {
                curr++;
                if(curr >= 24) { curr = 0; rounds++; }
                squares.forEach(s => s.highlight.visible = false);
                squares[curr].highlight.visible = true;
                if(rounds >= 2 && curr === target) {
                    clearInterval(timer); resolve();
                }
            }, 50);
        });
    }

    // Wallet (Mock)
    async function openWallet() {
        if(useMock) { showToast("演示模式无需充值"); return; }
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
            if(d.type==='stars') tg.openInvoice(d.url.split('t.me/')[1], (s)=>{ if(s==='paid') { closeWallet(); refreshBalance(); } });
            else if(d.type==='url') tg.openLink(d.url);
        } catch(e) { showToast(e.message); }
    }

    function toggleAuto() { autoPlay=!autoPlay; document.querySelector('.btn-auto').style.color=autoPlay?'#76ff03':'#fff'; }
    function showToast(msg) {
        const t = document.getElementById('msg-toast');
        t.innerText = msg; t.style.display='block';
        setTimeout(()=>t.style.display='none', 2000);
    }
    function tgTrigger(s) { if(window.Telegram?.WebApp?.HapticFeedback) window.Telegram.WebApp.HapticFeedback.impactOccurred(s); }
    function tgNotify(t) { if(window.Telegram?.WebApp?.HapticFeedback) window.Telegram.WebApp.HapticFeedback.notificationOccurred(t); }

    // Start
    initGame();
</script>
</body>
</html>
