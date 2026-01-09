<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Royal Fruit - Auto Mode</title>

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
            height: 100%; width: 100%;
            overflow: hidden;
            font-family: 'Roboto Condensed', sans-serif;
        }

        #loading-mask {
            position: fixed; inset: 0; background: #000; z-index: 9999;
            display: flex; justify-content: center; align-items: center;
            color: #ffd700; font-family: 'Orbitron'; font-size: 20px;
            transition: opacity 0.5s;
        }

        #app-root {
            width: 100%; max-width: 500px; height: 100%; margin: 0 auto;
            background: linear-gradient(180deg, #b71c1c 0%, #880e4f 5%, #0277bd 15%, #01579b 100%);
            display: flex; flex-direction: column; position: relative;
            box-shadow: 0 0 50px rgba(0,0,0,0.8);
        }

        /* 1. 顶部灯箱 (固定) */
        .top-hood {
            flex: 0 0 70px; height: 70px;
            background: radial-gradient(circle at 50% 100%, #ff5252, #b71c1c);
            border-bottom: 4px solid #ffd700;
            display: flex; justify-content: space-between; align-items: center;
            padding: 5px 15px;
            padding-top: max(5px, env(safe-area-inset-top));
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

        /* 2. 游戏盘面 (弹性自适应) */
        #game-wrapper {
            flex: 1 1 auto; min-height: 0; width: 100%;
            background: #fdf5e6;
            border-left: 2px solid #0d47a1; border-right: 2px solid #0d47a1;
            display: flex; justify-content: center; align-items: center;
            overflow: hidden; padding: 0;
        }

        /* 3. 底部操作台 (固定) */
        .control-deck {
            flex: 0 0 auto;
            background: linear-gradient(180deg, #42a5f5 0%, #1565c0 40%, #0d47a1 100%);
            border-top: 4px solid #ffd700;
            padding: 8px;
            padding-bottom: max(10px, env(safe-area-inset-bottom));
            display: flex; flex-direction: column; gap: 6px;
            position: relative; z-index: 10;
        }

        .func-row { display: flex; gap: 6px; justify-content: space-between; align-items: center; padding: 4px; background: rgba(0,0,0,0.2); border-radius: 10px; box-shadow: inset 0 2px 5px rgba(0,0,0,0.3); }
        .btn-3d { border: none; position: relative; cursor: pointer; color: #fff; font-weight: bold; display: flex; align-items: center; justify-content: center; transition: transform 0.1s; }
        .btn-3d:active { transform: translateY(3px); box-shadow: 0 0 0 transparent !important; border-bottom-width: 0 !important;}

        .b-round-green { width: 42px; height: 42px; border-radius: 50%; background: linear-gradient(180deg, #76ff03 0%, #33691e 100%); box-shadow: 0 4px 0 #1b5e20, 0 5px 5px rgba(0,0,0,0.3); border: 2px solid #b2ff59; font-size: 10px; flex-direction: column; line-height: 1; text-shadow: 0 1px 1px #000; }
        .b-sq { height: 38px; border-radius: 8px; font-size: 13px; flex: 1; box-shadow: 0 4px 0 rgba(0,0,0,0.4), 0 5px 5px rgba(0,0,0,0.2); border-top: 1px solid rgba(255,255,255,0.4); }
        .b-blue { background: linear-gradient(180deg, #29b6f6 0%, #01579b 100%); font-size: 18px; }
        .b-purp { background: linear-gradient(180deg, #ab47bc 0%, #4a148c 100%); font-size: 11px; }
        .b-go { width: 70px; height: 42px; border-radius: 10px; background: linear-gradient(180deg, #ffeb3b 0%, #ff6f00 100%); box-shadow: 0 5px 0 #e65100, 0 5px 5px rgba(0,0,0,0.3); border: 2px solid #fff; color: #b71c1c; font-family: 'Orbitron'; font-size: 20px; }
        .b-go:active { transform: translateY(4px); box-shadow: 0 1px 0 #e65100; }

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
        #msg-toast { position:absolute; bottom: 100px; left:50%; transform:translateX(-50%); background:rgba(0,0,0,0.8); color:#fff; padding:10px 20px; border-radius:20px; display:none; z-index:100; pointer-events:none; }
    </style>
</head>
<body>

<div id="loading-mask">CONNECTING...</div>
<div id="msg-toast"></div>

<div id="app-root">
    <!-- 顶部 -->
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

    <!-- 中间 (Canvas) -->
    <div id="game-wrapper"></div>

    <!-- 底部 -->
    <div class="control-deck">
        <div class="func-row">
            <button class="btn-3d b-round-green" onclick="showToast('充值功能暂不可用')">$<br>ADD</button>
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

<script>
    // === 0. 智能配置 (Hybrid Config) ===
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

    // 本地备用数据 (当API失败时使用)
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
    let useMock = false; // 标记当前是否使用离线模式

    const sfx = {
        click: new Howl({ src: ['https://assets.mixkit.co/active_storage/sfx/2568/2568-preview.mp3'] }),
        spin: new Howl({ src: ['https://assets.mixkit.co/active_storage/sfx/2044/2044-preview.mp3'], loop:true, volume:0.5 }),
        win: new Howl({ src: ['https://assets.mixkit.co/active_storage/sfx/2019/2019-preview.mp3'] }),
        lucky: new Howl({ src: ['https://assets.mixkit.co/active_storage/sfx/2003/2003-preview.mp3'] })
    };

    // === 1. 核心启动逻辑 (智能降级) ===
    async function initGame() {
        const tg = window.Telegram?.WebApp;
        if(tg) { tg.ready(); tg.expand(); }

        try {
            // 尝试连接后端 (设置2秒超时，防止久等)
            const res = await axios.get('/api/game/config', { timeout: 2000 });
            // API 成功
            const data = res.data.data;
            FRUIT_CONFIG = data.fruits;
            BOARD_LAYOUT = data.layout;
            useMock = false;
            console.log("Online Mode");
        } catch (e) {
            // API 失败 -> 切换单机模式
            console.warn("API Error, switching to Offline Mode");
            FRUIT_CONFIG = MOCK_DATA.fruits;
            BOARD_LAYOUT = MOCK_DATA.layout;
            useMock = true;
        }

        // 初始化数据
        FRUIT_CONFIG.forEach(f => { if(f.id !== 9) currentBets[f.id] = 0; });

        initPixi();
        initUI();
        refreshBalance(); // 初次显示余额

        // 移除遮罩
        const mask = document.getElementById('loading-mask');
        mask.style.opacity = '0';
        setTimeout(()=>mask.style.display='none', 500);

        // 触发自适应
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
        const W = 520, H = 520;
        app = new PIXI.Application({ width:W, height:H, backgroundAlpha:0, resolution:2 });
        wrapper.appendChild(app.view);

        // ★ 自适应逻辑 ★
        const resize = () => {
            const w = wrapper.clientWidth;
            const h = wrapper.clientHeight;
            const size = Math.min(w, h);
            app.view.style.width = size + 'px';
            app.view.style.height = size + 'px';
        };
        window.addEventListener('resize', resize);
        resize();

        // 绘图
        const bg = new PIXI.Graphics();
        bg.beginFill(0xfdf5e6); bg.drawRect(0,0,W,H);
        app.stage.addChild(bg);

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

        // 格子
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

    // === 3. 游戏交互 (混合逻辑) ===
    function addBet(id) {
        if(isSpinning) return;
        if(useMock && MOCK_DATA.balance < 10) return showToast("余额不足");
        // 如果是API模式，余额检查在后端，前端只做简单判断

        sndClick.play();
        currentBets[id] += 10;
        document.getElementById(`bet-val-${id}`).innerText = currentBets[id];

        // 模拟扣费(仅UI)
        if(useMock) {
            MOCK_DATA.balance -= 10;
            refreshBalance();
        }
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
        sndSpin.play();

        let targetIndex = 0;
        let winAmount = 0;

        // 获取结果
        try {
            if(useMock) {
                // 本地计算结果
                targetIndex = Math.floor(Math.random() * 24);
                const winId = BOARD_LAYOUT[targetIndex];
                const fruit = FRUIT_CONFIG.find(x=>x.id==winId);
                const bet = currentBets[winId] || 0;
                winAmount = (winId===9) ? 200 : bet * fruit.multiplier;

                await new Promise(r => setTimeout(r, 500)); // 模拟网络延迟
            } else {
                // API 请求
                const res = await axios.post('/api/game/spin', { bets: currentBets });
                targetIndex = res.data.data.stop_index;
                winAmount = res.data.data.win_amount;
            }

            // 执行动画
            await runAnimation(targetIndex);

            // 结算
            isSpinning = false; sndSpin.stop();
            document.getElementById('startBtn').disabled = false;

            if(winAmount > 0) {
                sndWin.play();
                document.getElementById('winDisplay').innerText = winAmount;
                if(useMock) MOCK_DATA.balance += winAmount;
                refreshBalance();
                // 闪烁特效
                let c=0, t=setInterval(()=>{
                    squares[targetIndex].highlight.visible = !squares[targetIndex].highlight.visible;
                    if(++c>6) { clearInterval(t); squares[targetIndex].highlight.visible=true; }
                }, 150);
            }

            if(autoPlay) {
                if((useMock ? MOCK_DATA.balance : 9999) >= totalBet) setTimeout(spin, 1500);
                else { toggleAuto(); showToast("余额不足，自动停止"); }
            } else {
                // 清空下注 (可选)
                // for(let k in currentBets) { currentBets[k]=0; document.getElementById(`bet-val-${k}`).innerText=0; }
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

                if(rounds >= 3 && curr === target) {
                    clearInterval(timer);
                    resolve();
                }
            }, 50); // 匀速跑灯
        });
    }

    function toggleAuto() { autoPlay=!autoPlay; document.querySelector('.btn-auto').style.color=autoPlay?'#76ff03':'#fff'; }
    function showToast(msg) {
        const t = document.getElementById('msg-toast');
        t.innerText = msg; t.style.display='block';
        setTimeout(()=>t.style.display='none', 2000);
    }

    // 启动
    initGame();
</script>
</body>
</html>
