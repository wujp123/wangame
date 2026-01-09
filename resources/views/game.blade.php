<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Royal Fruit - Stable</title>

    <!-- 更换为更稳定的 CDN 源 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/1.6.2/axios.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pixi.js/7.3.2/pixi.min.js"></script>
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
            position: fixed;
            font-family: 'Roboto Condensed', sans-serif;
        }

        #loading-mask {
            position: fixed; inset: 0; background: #000; z-index: 9999;
            display: flex; flex-direction: column; justify-content: center; align-items: center;
            color: #ffd700; font-family: 'Orbitron'; font-size: 20px; transition: opacity 0.5s;
        }
        #loading-text { margin-top: 10px; font-size: 12px; color: #666; }

        #app-root {
            width: 100%; height: 100%; max-width: 550px; margin: 0 auto;
            background: linear-gradient(180deg, #b71c1c 0%, #880e4f 5%, #0277bd 15%, #01579b 100%);
            display: flex; flex-direction: column;
            box-shadow: 0 0 50px rgba(0,0,0,0.8);
        }

        /* 1. Top Hood */
        .top-hood {
            flex: 0 0 auto; height: 70px;
            background: radial-gradient(circle at 50% 100%, #ff5252, #b71c1c);
            border-bottom: 4px solid #ffd700;
            display: flex; justify-content: space-between; align-items: center;
            padding: 5px 15px; padding-top: max(5px, env(safe-area-inset-top));
            z-index: 20; position: relative; box-shadow: 0 2px 10px rgba(0,0,0,0.4);
            transition: height 0.3s;
        }
        .bulb-deco { position: absolute; bottom: 5px; left: 50%; transform: translateX(-50%); width: 60%; display: flex; justify-content: space-between; pointer-events: none; }
        .bulb { width: 6px; height: 6px; background: #fff; border-radius: 50%; box-shadow: 0 0 8px #fff; }

        .lcd-group { text-align: center; }
        .lcd-label { color: #29b6f6; font-size: 10px; font-weight: bold; margin-bottom: 1px; }
        .lcd-frame { background: #000; padding: 2px; border-radius: 6px; border-bottom: 1px solid #444; box-shadow: 0 2px 5px rgba(0,0,0,0.5); }
        .lcd-screen { background: radial-gradient(#222, #000); border: 1px solid #333; border-radius: 4px; padding: 0 8px; min-width: 90px; }
        .lcd-digit { font-family: 'Orbitron', monospace; font-size: 18px; color: #ff1744; text-shadow: 0 0 8px #d50000; letter-spacing: 1px; }
        #balanceDisplay { color: #fff; text-shadow:none; }

        /* 2. Game Area */
        #game-wrapper {
            flex: 1 1 auto; min-height: 0; width: 100%;
            background: #fdf5e6;
            border-left: 3px solid #0d47a1; border-right: 3px solid #0d47a1;
            display: flex; justify-content: center; align-items: center;
            overflow: hidden;
        }

        /* 3. Control Deck */
        .control-deck {
            flex: 0 0 auto;
            background: linear-gradient(180deg, #42a5f5 0%, #1565c0 40%, #0d47a1 100%);
            border-top: 4px solid #ffd700;
            padding: 8px; padding-bottom: calc(10px + env(safe-area-inset-bottom));
            display: flex; flex-direction: column; gap: 6px;
            position: relative; z-index: 10; transition: padding 0.3s;
        }

        /* Responsive Logic */
        @media screen and (max-height: 700px) {
            .top-hood { height: 50px; padding: 2px 15px; }
            .lcd-digit { font-size: 14px; }
            .lcd-screen { min-width: 70px; padding: 0 4px; }
            .control-deck { gap: 3px; padding: 4px; padding-bottom: max(5px, env(safe-area-inset-bottom)); }
            .b-round-green { width: 36px; height: 36px; font-size: 9px; }
            .b-sq { height: 32px; font-size: 12px; }
            .b-blue { font-size: 16px; }
            .b-go { width: 60px; height: 36px; font-size: 16px; }
            .odds-glass { height: 20px; font-size: 10px; }
            .led-window { height: 18px; font-size: 12px; }
            .push-btn span { font-size: 16px; }
        }

        /* UI Components */
        .func-row { display: flex; gap: 6px; justify-content: space-between; align-items: center; padding: 4px; background: rgba(0,0,0,0.2); border-radius: 10px; }
        .btn-3d { border: none; position: relative; cursor: pointer; color: #fff; font-weight: bold; display: flex; align-items: center; justify-content: center; transition: transform 0.1s; }
        .btn-3d:active { transform: translateY(3px); box-shadow: 0 0 0 transparent !important; border-bottom-width: 0 !important;}

        .bet-panel { display: grid; grid-template-columns: repeat(8, 1fr); gap: 3px; background: #0d47a1; padding: 4px; border-radius: 8px; }
        .bet-col { display: flex; flex-direction: column; align-items: center; gap: 1px; }

        .b-round-green { width: 45px; height: 45px; border-radius: 50%; background: linear-gradient(180deg, #76ff03 0%, #33691e 100%); box-shadow: 0 4px 0 #1b5e20; border: 2px solid #b2ff59; font-size: 10px; flex-direction: column; }
        .b-sq { height: 40px; border-radius: 8px; font-size: 14px; flex: 1; box-shadow: 0 4px 0 rgba(0,0,0,0.4); border-top: 1px solid rgba(255,255,255,0.4); }
        .b-go { width: 75px; height: 45px; border-radius: 10px; background: linear-gradient(180deg, #ffeb3b 0%, #ff6f00 100%); box-shadow: 0 5px 0 #e65100; border: 2px solid #fff; color: #b71c1c; font-family: 'Orbitron'; font-size: 22px; }

        .odds-glass { width: 100%; height: 26px; font-size: 13px; font-weight: 900; color: #fff; display: flex; align-items: center; justify-content: center; text-shadow: 0 1px 2px #000; border: 1px solid rgba(0,0,0,0.2); border-top: 1px solid rgba(255,255,255,0.6); }
        .led-window { width: 100%; height: 24px; background: #000; border: 1px solid #555; color: #ff1744; font-family: 'Orbitron'; font-size: 15px; display: flex; align-items: center; justify-content: center; }
        .push-btn { width: 100%; aspect-ratio: 1; border-radius: 50%; border: none; position: relative; cursor: pointer; box-shadow: 0 4px 0 rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; border-top: 1px solid rgba(255,255,255,0.5); }
        .push-btn span { font-size: 22px; text-shadow: 1px 1px 2px #000; }

        .b-blue { background: linear-gradient(180deg, #29b6f6 0%, #01579b 100%); font-size: 20px; }
        .b-purp { background: linear-gradient(180deg, #ab47bc 0%, #4a148c 100%); font-size: 12px; }
        .bg-blue { background: linear-gradient(180deg, #42a5f5 0%, #1565c0 100%); }
        .bg-red { background: linear-gradient(180deg, #ef5350 0%, #b71c1c 100%); }
        .bg-grey { background: linear-gradient(180deg, #90a4ae 0%, #455a64 100%); }
        .pb-green { background: linear-gradient(180deg, #76ff03 0%, #33691e 100%); }
        .pb-purp { background: linear-gradient(180deg, #e040fb 0%, #7b1fa2 100%); }
        .pb-red { background: linear-gradient(180deg, #ff5252 0%, #b71c1c 100%); }

        #msg-toast { position:absolute; bottom: 20%; left:50%; transform:translateX(-50%); background:rgba(0,0,0,0.8); color:#fff; padding:10px 20px; border-radius:20px; display:none; z-index:100; pointer-events:none; }
        .modal-overlay { display: none; position: fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:999; justify-content:center; align-items:center; }
        .modal-body { background: #fff; width: 300px; padding: 20px; border-radius: 10px; font-family: sans-serif; }
    </style>
</head>
<body>

<div id="loading-mask">
    <div>LOADING SYSTEM...</div>
    <div id="loading-text">Connecting to server</div>
</div>
<div id="msg-toast"></div>

<div id="app-root">
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

    <div id="game-wrapper"></div>

    <div class="control-deck">
        <div class="func-row">
            <button class="btn-3d b-round-green" onclick="openWallet()">$<br>ADD</button>
            <div style="display:flex; gap:4px; flex:1.5">
                <button class="btn-3d b-sq b-blue">⬅</button>
                <button class="btn-3d b-sq b-blue">➡</button>
            </div>
            <div style="display:flex; gap:4px; flex:1.5">
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
        <h3>钱包功能</h3>
        <p>这里是充值和提现界面 (模拟)。</p>
        <button onclick="document.getElementById('walletModal').style.display='none'" style="margin-top:10px;padding:10px;width:100%">关闭</button>
    </div>
</div>

<script>
    // === 强力启动逻辑 ===
    window.addEventListener('load', () => {
        // 安全阀：3秒后无论如何强制进入游戏
        setTimeout(() => {
            const mask = document.getElementById('loading-mask');
            if(mask && mask.style.display !== 'none') {
                console.warn("Force start triggered");
                forceEnterGame();
            }
        }, 3000);
    });

    const tg = window.Telegram?.WebApp;
    if(tg) { tg.ready(); tg.expand(); try { tg.setHeaderColor('#b71c1c'); } catch(e){} }

    function adjustHeight() {
        const h = (tg && tg.viewportStableHeight) ? tg.viewportStableHeight : window.innerHeight;
        document.getElementById('app-root').style.height = h + 'px';
        if(app) resizePixi();
    }
    if(tg) tg.onEvent('viewportChanged', adjustHeight);
    window.addEventListener('resize', adjustHeight);

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

    let FRUIT_CONFIG=[], BOARD_LAYOUT=[], currentBets={}, isSpinning=false, autoPlay=false, squares=[], app, useMock=false;
    const sfx = {
        click: new Howl({src:['https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.3/howler.min.js']}), // Placeholder
        spin: null, win: null
    };

    async function initGame() {
        adjustHeight();
        try {
            // 尝试连接后端 (1秒超时)
            const res = await axios.get('/api/game/config', { timeout: 1000 });
            const data = res.data.data;
            FRUIT_CONFIG = data.fruits;
            BOARD_LAYOUT = data.layout;
            useMock = false;
        } catch (e) {
            console.log("Using Offline Data");
            FRUIT_CONFIG = MOCK_DATA.fruits;
            BOARD_LAYOUT = MOCK_DATA.layout;
            useMock = true;
        }
        startGameLogic();
    }

    function startGameLogic() {
        FRUIT_CONFIG.forEach(f=>{ if(f.id!==9) currentBets[f.id]=0; });
        initPixi(); initUI(); refreshBalance();

        // Hide Loader
        const mask = document.getElementById('loading-mask');
        mask.style.opacity='0';
        setTimeout(()=>mask.style.display='none', 500);
    }

    // Failsafe
    function forceEnterGame() {
        if(!FRUIT_CONFIG.length) {
            FRUIT_CONFIG = MOCK_DATA.fruits;
            BOARD_LAYOUT = MOCK_DATA.layout;
            useMock = true;
            startGameLogic();
        }
    }

    function initUI() {
        const div = document.getElementById('betButtonsContainer');
        div.innerHTML='';
        FRUIT_CONFIG.filter(f=>f.id!==9).forEach(f=>{
            const s = VISUAL_MAP[f.id] || VISUAL_MAP[8];
            const col = document.createElement('div');
            col.className='bet-col';
            col.innerHTML=`
                <div class="odds-glass ${s.tag}">x${f.multiplier}</div>
                <div class="led-window" id="bet-val-${f.id}">0</div>
                <button class="push-btn ${s.color}" onclick="addBet(${f.id})"><span>${s.icon}</span></button>
            `;
            div.appendChild(col);
        });
    }

    function initPixi() {
        const wrap = document.getElementById('game-wrapper');
        const LOGIC = 520;
        app = new PIXI.Application({width:LOGIC, height:LOGIC, backgroundAlpha:0, resolution:2});
        wrap.appendChild(app.view);

        window.resizePixi = () => {
            const w = wrap.clientWidth;
            const h = wrap.clientHeight;
            const s = Math.min(w, h);
            app.view.style.width = s+'px';
            app.view.style.height = s+'px';
        };
        resizePixi();

        const bg = new PIXI.Graphics(); bg.beginFill(0xfdf5e6); bg.drawRect(0,0,LOGIC,LOGIC); app.stage.addChild(bg);

        const center = new PIXI.Container();
        const cBg = new PIXI.Graphics();
        cBg.beginFill(0xb71c1c); cBg.drawRoundedRect(0,0,330,330,20);
        cBg.beginFill(0xffecb3); cBg.drawCircle(165,165,155);
        center.addChild(cBg);
        const txt = new PIXI.Text("CAISHEN", {fontFamily:'Arial', fontSize:45, fill:['#d50000','#ff6f00'], fontWeight:'bold'});
        txt.anchor.set(0.5); txt.position.set(165,130); center.addChild(txt);
        center.position.set(95,95); app.stage.addChild(center);

        const step=73, start=5, box=71;
        const POS=[];
        for(let i=0;i<7;i++) POS.push({x:start+i*step, y:start});
        for(let i=1;i<=6;i++) POS.push({x:start+6*step, y:start+i*step});
        for(let i=5;i>=0;i--) POS.push({x:start+i*step, y:start+6*step});
        for(let i=5;i>=1;i--) POS.push({x:start, y:start+i*step});

        squares=[];
        POS.forEach((p,idx)=>{
            const id=BOARD_LAYOUT[idx];
            const fc=FRUIT_CONFIG.find(x=>x.id==id)||FRUIT_CONFIG[0];
            const st=VISUAL_MAP[id]||VISUAL_MAP[9];
            const g=new PIXI.Container(); g.x=p.x; g.y=p.y;
            const b=new PIXI.Graphics(); b.lineStyle(2,0x3e2723); b.beginFill(id===9?0xffcdd2:0xfff8e1); b.drawRoundedRect(0,0,box,box,12); g.addChild(b);
            const ic=new PIXI.Text(st.icon, {fontSize:34}); ic.anchor.set(0.5); ic.position.set(box/2, box/2-4); g.addChild(ic);
            const hl=new PIXI.Graphics(); hl.lineStyle(5,0xff0000); hl.beginFill(0xffff00,0.3); hl.drawRoundedRect(-2,-2,box+4,box+4,14); hl.visible=false; g.addChild(hl);
            squares.push({highlight:hl, id:id}); app.stage.addChild(g);
        });
        if(squares.length) squares[0].highlight.visible=true;
    }

    function addBet(id) {
        if(isSpinning) return;
        currentBets[id]+=10;
        document.getElementById(`bet-val-${id}`).innerText=currentBets[id];
        if(useMock){ MOCK_DATA.balance-=10; refreshBalance(); }
    }

    async function refreshBalance() {
        if(useMock) document.getElementById('balanceDisplay').innerText=String(MOCK_DATA.balance).padStart(8,'0');
        else try{
            const r=await axios.get('/api/game/balance');
            document.getElementById('balanceDisplay').innerText=String(r.data.data.balance).padStart(8,'0');
        }catch(e){}
    }

    async function spin() {
        const total = Object.values(currentBets).reduce((a,b)=>a+b,0);
        if(total===0) return showToast("请先下注");
        isSpinning=true; document.getElementById('startBtn').disabled=true;

        let tIdx=0, win=0;
        try {
            if(useMock) {
                tIdx=Math.floor(Math.random()*24);
                const winId=BOARD_LAYOUT[tIdx];
                win=(winId===9)?200:(currentBets[winId]||0)*FRUIT_CONFIG.find(x=>x.id==winId).multiplier;
                await new Promise(r=>setTimeout(r,500));
            } else {
                const r=await axios.post('/api/game/spin', {bets:currentBets});
                tIdx=r.data.data.stop_index; win=r.data.data.win_amount;
            }
            await runAnim(tIdx);
            isSpinning=false; document.getElementById('startBtn').disabled=false;
            if(win>0) {
                document.getElementById('winDisplay').innerText=win;
                if(useMock) MOCK_DATA.balance+=win; refreshBalance();
            }
        } catch(e) { isSpinning=false; document.getElementById('startBtn').disabled=false; showToast("Error"); }
    }

    function runAnim(t) {
        return new Promise(r=>{
            let c=squares.findIndex(s=>s.highlight.visible); if(c<0)c=0; let rd=0;
            const tm=setInterval(()=>{
                c++; if(c>=24){c=0;rd++;}
                squares.forEach(s=>s.highlight.visible=false); squares[c].highlight.visible=true;
                if(rd>=2 && c===t) { clearInterval(tm); r(); }
            },50);
        });
    }

    function openWallet(){ document.getElementById('walletModal').style.display='flex'; }
    function toggleAuto(){autoPlay=!autoPlay; document.querySelector('.btn-auto').style.color=autoPlay?'#76ff03':'#fff';}
    function showToast(m){const t=document.getElementById('msg-toast'); t.innerText=m; t.style.display='block'; setTimeout(()=>t.style.display='none',2000);}

    initGame();
</script>
</body>
</html>
