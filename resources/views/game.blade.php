<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Royal Fruit - Square</title>

    <script src="https://pixijs.download/v7.x/pixi.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/howler/2.2.3/howler.min.js"></script>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@700&family=Roboto+Condensed:wght@700&display=swap');

        :root {
            --body-bg: #000;
        }

        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; user-select: none; }

        body {
            margin: 0; padding: 0;
            background-color: var(--body-bg);
            height: 100vh; width: 100vw;
            overflow: hidden;
            font-family: 'Roboto Condensed', sans-serif;
            display: flex; justify-content: center;
            align-items: center; /* 垂直居中 */
        }

        #app-root {
            width: 100%;
            max-width: 520px;
            /* 移除固定高度，改为自适应内容，确保紧凑 */
            height: auto;
            max-height: 100vh;
            background: linear-gradient(180deg, #b71c1c 0%, #880e4f 5%, #0277bd 15%, #01579b 100%);
            display: flex; flex-direction: column;
            position: relative;
            box-shadow: 0 0 50px rgba(0,0,0,0.8);
            overflow: hidden;
        }

        /* 1. 顶部灯箱 */
        .top-hood {
            flex: 0 0 70px;
            background: radial-gradient(circle at 50% 100%, #ff5252, #b71c1c);
            border-bottom: 4px solid #ffd700;
            display: flex; justify-content: space-between; align-items: center;
            padding: 5px 20px; z-index: 20;
            position: relative;
            box-shadow: 0 2px 10px rgba(0,0,0,0.4);
        }
        .bulb-deco { position: absolute; top: 4px; left: 50%; transform: translateX(-50%); width: 80%; display: flex; justify-content: space-between; }
        .bulb { width: 8px; height: 8px; background: #fff; border-radius: 50%; box-shadow: 0 0 8px #fff; }

        .lcd-group { text-align: center; margin-top: 5px; }
        .lcd-label { color: #29b6f6; font-size: 10px; font-weight: bold; margin-bottom: 2px; text-shadow: 0 1px 2px rgba(0,0,0,0.8); letter-spacing: 1px; }
        .lcd-frame { background: #000; padding: 3px; border-radius: 8px; border-bottom: 1px solid #444; box-shadow: 0 2px 5px rgba(0,0,0,0.5); }
        .lcd-screen { background: radial-gradient(#222, #000); border: 1px solid #333; border-radius: 4px; padding: 0 10px; min-width: 110px; }
        .lcd-digit { font-family: 'Orbitron', monospace; font-size: 22px; color: #ff1744; text-shadow: 0 0 8px #d50000; letter-spacing: 2px; }
        #credit-val { color: #fff; text-shadow:none; }

        /* === 2. 游戏盘面 (核心修改：宽高一致) === */
        #game-frame {
            /* 移除 flex: 1，防止拉伸 */
            width: 100%;
            /* 强制正方形比例 */
            aspect-ratio: 1 / 1;

            background: #fdf5e6; /* 米色 */
            margin: 0; padding: 0;
            border: none;
            /* 左右加一点边框装饰，上下无 */
            border-left: 2px solid #0d47a1;
            border-right: 2px solid #0d47a1;

            position: relative;
            display: flex; justify-content: center; align-items: center;
            overflow: hidden;
        }

        /* 3. 底部操作台 */
        .control-deck {
            flex: 0 0 auto;
            background: linear-gradient(180deg, #42a5f5 0%, #1565c0 40%, #0d47a1 100%);
            border-top: 4px solid #ffd700;
            padding: 8px;
            padding-bottom: max(12px, env(safe-area-inset-bottom));
            display: flex; flex-direction: column; gap: 8px;
            position: relative; z-index: 10;
        }

        .func-row { display: flex; gap: 8px; justify-content: space-between; align-items: center; padding: 5px; background: rgba(0,0,0,0.2); border-radius: 10px; box-shadow: inset 0 2px 5px rgba(0,0,0,0.3); }

        .btn-3d { border: none; position: relative; cursor: pointer; color: #fff; font-weight: bold; display: flex; align-items: center; justify-content: center; transition: transform 0.1s; }
        .btn-3d:active { transform: translateY(4px); box-shadow: 0 0 0 transparent !important; border-bottom-width: 0 !important;}

        .b-round-green { width: 48px; height: 48px; border-radius: 50%; background: linear-gradient(180deg, #76ff03 0%, #33691e 100%); box-shadow: 0 5px 0 #1b5e20, 0 10px 10px rgba(0,0,0,0.3); border: 2px solid #b2ff59; font-size: 11px; flex-direction: column; line-height: 1; text-shadow: 0 1px 1px #000; }
        .b-sq { height: 42px; border-radius: 10px; font-size: 14px; flex: 1; box-shadow: 0 5px 0 rgba(0,0,0,0.4), 0 8px 5px rgba(0,0,0,0.2); border-top: 1px solid rgba(255,255,255,0.4); }
        .b-blue { background: linear-gradient(180deg, #29b6f6 0%, #01579b 100%); font-size: 20px; }
        .b-purp { background: linear-gradient(180deg, #ab47bc 0%, #4a148c 100%); font-size: 12px; }
        .b-go { width: 80px; height: 48px; border-radius: 12px; background: linear-gradient(180deg, #ffeb3b 0%, #ff6f00 100%); box-shadow: 0 6px 0 #e65100, 0 10px 10px rgba(0,0,0,0.3); border: 2px solid #fff; color: #b71c1c; font-family: 'Orbitron'; font-size: 24px; }
        .b-go:active { transform: translateY(5px); box-shadow: 0 1px 0 #e65100; }

        .bet-panel { display: grid; grid-template-columns: repeat(8, 1fr); gap: 4px; background: #0d47a1; padding: 5px; border-radius: 8px; box-shadow: inset 0 2px 8px rgba(0,0,0,0.6), 0 2px 0 rgba(255,255,255,0.2); }
        .bet-col { display: flex; flex-direction: column; align-items: center; gap: 2px; }

        .odds-glass { width: 100%; height: 28px; font-size: 14px; font-weight: 900; color: #fff; display: flex; align-items: center; justify-content: center; text-shadow: 0 1px 2px #000; box-shadow: inset 0 1px 0 rgba(255,255,255,0.4), 0 2px 2px rgba(0,0,0,0.3); border: 1px solid rgba(0,0,0,0.2); border-top: 1px solid rgba(255,255,255,0.6); }
        .bg-blue { background: linear-gradient(180deg, #42a5f5 0%, #1565c0 100%); }
        .bg-red { background: linear-gradient(180deg, #ef5350 0%, #b71c1c 100%); }
        .bg-grey { background: linear-gradient(180deg, #90a4ae 0%, #455a64 100%); }

        .led-window { width: 100%; height: 26px; background: #000; border: 1px solid #555; border-bottom: 1px solid #777; color: #ff1744; font-family: 'Orbitron'; font-size: 16px; display: flex; align-items: center; justify-content: center; box-shadow: inset 0 3px 5px rgba(0,0,0,0.8); margin-bottom: 3px; letter-spacing: 1px; }

        .push-btn { width: 100%; aspect-ratio: 1; border-radius: 50%; border: none; position: relative; cursor: pointer; box-shadow: 0 5px 0 rgba(0,0,0,0.3), 0 8px 5px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center; border-top: 1px solid rgba(255,255,255,0.5); }
        .push-btn::after { content: ''; position: absolute; top: 10%; left: 20%; width: 60%; height: 40%; background: linear-gradient(180deg, rgba(255,255,255,0.7) 0%, rgba(255,255,255,0) 100%); border-radius: 50%; pointer-events: none; }
        .push-btn:active { transform: translateY(4px); box-shadow: 0 1px 0 rgba(0,0,0,0.3); }

        .pb-green { background: linear-gradient(180deg, #76ff03 0%, #33691e 100%); }
        .pb-purp { background: linear-gradient(180deg, #e040fb 0%, #7b1fa2 100%); }
        .pb-red { background: linear-gradient(180deg, #ff5252 0%, #b71c1c 100%); }

        #msg-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 100; align-items: center; justify-content: center; }
        .msg-box { background: #fff; padding: 20px; border-radius: 10px; text-align: center; font-weight: bold; border: 4px solid #0d47a1;}
    </style>
</head>
<body>

<div id="app-root">
    <div class="top-hood">
        <div class="bulb-deco"><div class="bulb"></div><div class="bulb"></div><div class="bulb"></div><div class="bulb"></div></div>
        <div class="lcd-group">
            <div class="lcd-label">BONUS-WIN</div>
            <div class="lcd-frame"><div class="lcd-screen"><div id="win-display" class="lcd-digit">00000000</div></div></div>
        </div>
        <div class="lcd-group">
            <div class="lcd-label">CREDIT</div>
            <div class="lcd-frame"><div class="lcd-screen"><div id="credit-display" class="lcd-digit" style="color:#fff">00005000</div></div></div>
        </div>
    </div>

    <div id="game-frame"></div>

    <div class="control-deck">
        <div class="func-row">
            <button class="btn-3d b-round-green" onclick="allIn()">ALL<br>+1</button>
            <div style="display:flex; gap:6px; flex:1.5">
                <button class="btn-3d b-sq b-blue">⬅</button>
                <button class="btn-3d b-sq b-blue">➡</button>
            </div>
            <div style="display:flex; gap:6px; flex:1.5">
                <button class="btn-3d b-sq b-purp">1-6</button>
                <button class="btn-3d b-sq b-purp">8-13</button>
            </div>
            <button class="btn-3d b-go" onclick="spin()">GO</button>
        </div>

        <div class="bet-panel" id="bet-container"></div>
    </div>
</div>

<div id="msg-modal" onclick="this.style.display='none'">
    <div class="msg-box"><h3 id="msg-text"></h3></div>
</div>

<script>
    const FRUITS = [
        { id: 1, name: 'BAR', tagColor: 'bg-blue', btnColor:'pb-green', icon: '💎', odds: 100 },
        { id: 2, name: '77',  tagColor: 'bg-red',  btnColor:'pb-purp',  icon: '7️⃣', odds: 40 },
        { id: 3, name: 'STAR',tagColor: 'bg-grey', btnColor:'pb-green', icon: '⭐', odds: 30 },
        { id: 4, name: 'WTR', tagColor: 'bg-grey', btnColor:'pb-green', icon: '🍉', odds: 20 },
        { id: 5, name: 'BELL',tagColor: 'bg-red',  btnColor:'pb-green', icon: '🔔', odds: 20 },
        { id: 6, name: 'LEM', tagColor: 'bg-grey', btnColor:'pb-green', icon: '🍋', odds: 15 },
        { id: 7, name: 'ORG', tagColor: 'bg-grey', btnColor:'pb-green', icon: '🍊', odds: 10 },
        { id: 8, name: 'APP', tagColor: 'bg-blue', btnColor:'pb-red',   icon: '🍎', odds: 5 },
        { id: 9, name: 'LUCKY', odds: 0 }
    ];

    const LAYOUT = [
        7, 5, 1, 1, 8, 8, 6,
        4, 4, 9, 8, 8, 7,
        5, 2, 2, 8, 8, 6,
        3, 3, 9, 8, 8
    ];

    let balance = 5000;
    let bets = {};
    let isRunning = false;
    let app, blocks = [];

    const sfx = {
        click: new Howl({ src: ['https://assets.mixkit.co/active_storage/sfx/2568/2568-preview.mp3'] }),
        spin: new Howl({ src: ['https://assets.mixkit.co/active_storage/sfx/2044/2044-preview.mp3'], loop:true, volume:0.5 }),
        win: new Howl({ src: ['https://assets.mixkit.co/active_storage/sfx/2019/2019-preview.mp3'] }),
    };

    window.onload = () => {
        initUI();
        initPixi();
        updateUI();
    };

    function initUI() {
        const div = document.getElementById('bet-container');
        FRUITS.filter(f => f.id !== 9).forEach(f => {
            bets[f.id] = 0;
            const col = document.createElement('div');
            col.className = 'bet-col';
            col.innerHTML = `
                <div class="odds-glass ${f.tagColor}">${f.odds}</div>
                <div class="led-window" id="led-${f.id}">00</div>
                <button class="push-btn ${f.btnColor}" onclick="addBet(${f.id})">
                    <span style="font-size:20px; color:#fff; text-shadow:1px 1px 2px #000; z-index:2">${f.icon}</span>
                </button>
            `;
            div.appendChild(col);
        });
    }

    function initPixi() {
        const div = document.getElementById('game-frame');
        const W = 520, H = 520;
        app = new PIXI.Application({ width:W, height:H, backgroundAlpha:0, resolution:2 });
        div.appendChild(app.view);

        const resize = () => {
            // 保持 1:1 比例
            app.view.style.width = '100%';
            app.view.style.height = '100%';
        };
        window.addEventListener('resize', resize);
        resize();

        // 1. 米色背景 (铺满)
        const bg = new PIXI.Graphics();
        bg.beginFill(0xfdf5e6);
        bg.drawRect(0,0,W,H);
        app.stage.addChild(bg);

        // 2. 中间区域
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
        const ledNum = new PIXI.Text("11", { fontFamily: 'Orbitron', fontSize: 24, fill: '#ff5252' });
        ledNum.anchor.set(0.5); ledNum.position.set(120, 25);
        midUI.addChild(ledNum);

        center.addChild(midUI);
        center.position.set(95, 95);
        app.stage.addChild(center);

        // 3. 绘制格子 (极限填充)
        const step = 74;
        const start = 2;
        const boxSize = 72;

        const POS = [];
        for(let i=0; i<7; i++) POS.push({x:start+i*step, y:start});
        for(let i=1; i<=6; i++) POS.push({x:start+6*step, y:start+i*step});
        for(let i=5; i>=0; i--) POS.push({x:start+i*step, y:start+6*step});
        for(let i=5; i>=1; i--) POS.push({x:start, y:start+i*step});

        POS.forEach((p, idx) => {
            const id = LAYOUT[idx];
            const f = FRUITS.find(x => x.id === id);
            const g = new PIXI.Container();
            g.x=p.x; g.y=p.y;

            const box = new PIXI.Graphics();
            const stroke = 0x3e2723;
            const fill = (id===9) ? 0xffcdd2 : 0xfff8e1;

            box.lineStyle(2, stroke);
            box.beginFill(fill);
            box.drawRoundedRect(0,0, boxSize, boxSize, 12);
            g.addChild(box);

            const icon = new PIXI.Text(f.icon, {fontSize:34});
            icon.anchor.set(0.5); icon.position.set(boxSize/2, boxSize/2 - 4);
            g.addChild(icon);

            const lTxt = (id===9) ? 'JP' : `x${f.odds}`;
            const label = new PIXI.Text(lTxt, {
                fontFamily: 'Roboto Condensed', fontSize:13, fontWeight:'bold',
                fill: (id===9) ? '#d32f2f' : '#333'
            });
            label.anchor.set(0.5); label.position.set(boxSize/2, boxSize - 12);
            g.addChild(label);

            const hl = new PIXI.Graphics();
            hl.lineStyle(5, 0xff0000);
            hl.beginFill(0xffff00, 0.3);
            hl.drawRoundedRect(-2,-2, boxSize+4, boxSize+4, 14);
            hl.visible = false;
            g.addChild(hl);

            blocks.push({hl, id});
            app.stage.addChild(g);
        });
        if(blocks.length) blocks[0].hl.visible = true;
    }

    // 逻辑
    window.addBet = (id) => {
        if(isRunning) return;
        if(balance < 1) return;
        balance--; bets[id]++;
        sfx.click.play();
        updateUI();
    };

    window.allIn = () => {
        if(isRunning) return;
        const list = FRUITS.filter(f=>f.id!==9);
        if(balance < list.length) return;
        sfx.click.play();
        list.forEach(f=>{ balance--; bets[f.id]++; });
        updateUI();
    };

    function updateUI() {
        document.getElementById('credit-display').innerText = String(balance).padStart(8,'0');
        FRUITS.filter(f=>f.id!==9).forEach(f=>{
            const el = document.getElementById(`led-${f.id}`);
            const v = bets[f.id];
            el.innerText = v<10 ? `0${v}` : v;
        });
    }

    window.spin = () => {
        if(isRunning) return;
        if(Object.values(bets).reduce((a,b)=>a+b,0)===0) return;
        isRunning = true;
        document.getElementById('win-display').innerText = "00000000";
        sfx.spin.play();

        const target = Math.floor(Math.random()*24);
        let curr = blocks.findIndex(b=>b.hl.visible);
        if(curr===-1) curr=0;

        let rounds=0, speed=40;
        const run = () => {
            blocks[curr].hl.visible = false;
            curr++; if(curr>=24){curr=0; rounds++;}
            blocks[curr].hl.visible = true;

            if(rounds>=3 && curr===target) {
                sfx.spin.stop();
                isRunning = false;
                checkWin(target);
            } else {
                if(rounds<2){ if(speed>30) speed-=2; }
                else speed+=8;
                setTimeout(run, speed);
            }
        };
        run();
    };

    function checkWin(idx) {
        const id = LAYOUT[idx];
        let win = 0;
        if(id!==9) {
            const f = FRUITS.find(x=>x.id===id);
            if(bets[id]>0) { win=bets[id]*f.odds; sfx.win.play(); }
        } else {
            win = 200; sfx.win.play();
        }

        if(win>0) {
            balance += win;
            let d = 0;
            const t = setInterval(()=>{
                d+=Math.ceil(win/20);
                if(d>=win){ d=win; clearInterval(t); updateUI(); }
                document.getElementById('win-display').innerText = String(d).padStart(8,'0');
            }, 30);

            let c=0, bt=setInterval(()=>{
                blocks[idx].hl.visible = !blocks[idx].hl.visible;
                if(++c>8){ clearInterval(bt); blocks[idx].hl.visible=true; }
            },100);
        } else {
            updateUI();
        }
    }
</script>
</body>
</html>
