<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Super Fruit Machine 3D</title>
    <style>
        :root {
            --primary-red: #d32f2f;
            --primary-blue: #0277bd;
            --led-bg: #000;
            --led-red: #ff3333;
            --led-green: #76ff03;
            --glass-sheen: rgba(255,255,255,0.3);
            --shadow-depth: 4px;
        }

        * { box-sizing: border-box; -webkit-tap-highlight-color: transparent; user-select: none; }

        body {
            margin: 0; padding: 0;
            background: #111;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            overflow: hidden;
        }

        /* === 游戏机外壳 === */
        #app-root {
            width: 100%;
            max-width: 450px;
            background: linear-gradient(180deg, #b71c1c 0%, #880e4f 100%);
            border-radius: 20px;
            padding: 10px;
            box-shadow: 0 0 30px rgba(255, 0, 0, 0.3);
            position: relative;
            border: 2px solid #555;
        }

        /* === 顶部栏 === */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 10px;
            background: rgba(0,0,0,0.2);
            border-radius: 10px 10px 0 0;
            margin-bottom: 5px;
        }
        .status-badge { color: gold; font-weight: bold; text-shadow: 0 0 5px gold; font-size: 14px; }
        .icon-btn { font-size: 18px; cursor: pointer; filter: drop-shadow(0 2px 2px rgba(0,0,0,0.5)); }

        /* === 数码显示区 === */
        .score-board {
            display: flex;
            gap: 10px;
            margin-bottom: 8px;
            background: #01579b;
            padding: 5px;
            border-radius: 8px;
            border: 2px solid #4fc3f7;
        }
        .lcd-panel {
            flex: 1;
            background: #000;
            border-radius: 4px;
            padding: 4px;
            box-shadow: inset 0 0 10px rgba(255,255,255,0.1);
            position: relative;
            overflow: hidden;
        }
        .lcd-panel::after { /* 玻璃反光 */
            content: ''; position: absolute; top:0; left:0; width:100%; height:50%;
            background: linear-gradient(to bottom, rgba(255,255,255,0.1), transparent);
            pointer-events: none;
        }
        .lcd-label { color: #4fc3f7; font-size: 9px; text-align: center; margin-bottom: 2px; letter-spacing: 1px; }
        .lcd-value {
            color: var(--led-red);
            font-family: 'Courier New', monospace;
            font-size: 22px;
            text-align: center;
            font-weight: bold;
            text-shadow: 0 0 8px var(--led-red);
            letter-spacing: 2px;
        }

        /* === 主游戏盘面 (Grid) === */
        .game-board {
            background: #002f6c;
            padding: 8px;
            border-radius: 8px;
            border: 2px solid #4fc3f7;
            position: relative;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            grid-template-rows: repeat(6, 1fr);
            gap: 4px;
            aspect-ratio: 1/1.05;
        }

        /* 格子样式 */
        .cell {
            background: #fff8e1;
            border-radius: 6px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.3), 0 2px 2px rgba(0,0,0,0.3);
            position: relative;
            transition: transform 0.1s;
        }
        .cell .icon { font-size: 22px; z-index: 2; }
        .cell .multi { font-size: 9px; font-weight: 800; color: #333; margin-top: -2px; z-index: 2; }

        /* 跑灯激活状态 */
        .cell.active {
            background: #fff !important;
            box-shadow: 0 0 15px gold, inset 0 0 10px #ffeb3b;
            z-index: 10;
            transform: scale(1.1);
            border: 2px solid red;
        }
        /* 中奖状态 */
        .cell.winner {
            animation: blinkWinner 0.5s infinite alternate;
        }

        /* 特殊颜色格子 */
        .cell.type-apple { background: #ffebee; }
        .cell.type-bar { background: #e0f7fa; }
        .cell.type-orange { background: #fff3e0; }

        /* === 中心大图区域 === */
        .center-stage {
            grid-column: 2 / 6;
            grid-row: 2 / 6;
            background: radial-gradient(circle, #800000 30%, #3e2723 100%);
            border-radius: 10px;
            border: 2px solid gold;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            padding-bottom: 10px;
            box-shadow: inset 0 0 20px #000;
        }

        .god-image {
            position: absolute;
            top: 10px; left: 50%;
            transform: translateX(-50%);
            font-size: 80px;
            text-shadow: 0 0 20px gold;
            animation: floatGod 3s ease-in-out infinite;
        }

        .center-hud {
            display: flex;
            align-items: center;
            gap: 10px;
            background: rgba(0,0,0,0.6);
            padding: 5px 15px;
            border-radius: 20px;
            border: 1px solid #4fc3f7;
            z-index: 5;
            margin-bottom: 50px;
        }
        .hud-btn { width: 24px; height: 24px; border-radius: 50%; background: #039be5; color: white; border: none; font-weight: bold; box-shadow: 0 2px 0 #01579b; }
        .hud-btn:active { transform: translateY(2px); box-shadow: none; }

        .countdown-led {
            position: absolute;
            bottom: 15px;
            background: #000;
            color: var(--led-red);
            padding: 2px 12px;
            border: 2px solid #333;
            border-radius: 4px;
            font-family: monospace;
            font-size: 18px;
            box-shadow: 0 0 5px var(--led-red);
        }

        /* === 控制台按钮 === */
        .control-panel {
            background: #0277bd;
            padding: 8px;
            display: flex;
            gap: 6px;
            border-top: 3px solid #4fc3f7;
            border-bottom: 3px solid #01579b;
        }

        .btn-3d {
            border: none;
            color: white;
            font-weight: bold;
            text-shadow: 1px 1px 0 rgba(0,0,0,0.5);
            cursor: pointer;
            position: relative;
            transition: all 0.1s;
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 var(--shadow-depth) 0 rgba(0,0,0,0.4), 0 calc(var(--shadow-depth) + 2px) 5px rgba(0,0,0,0.3);
        }
        .btn-3d:active {
            transform: translateY(var(--shadow-depth));
            box-shadow: 0 0 0 rgba(0,0,0,0.4);
        }
        .btn-3d:disabled { filter: grayscale(0.8); cursor: not-allowed; }

        .btn-rect { flex: 1; height: 35px; border-radius: 6px; font-size: 12px; }
        .btn-circle { width: 45px; height: 45px; border-radius: 50%; font-size: 10px; line-height: 1.1; }
        .btn-go {
            flex: 2;
            background: linear-gradient(#ffeb3b, #fbc02d);
            color: #b71c1c;
            font-size: 20px;
            border-radius: 8px;
            height: 45px;
            box-shadow: 0 5px 0 #f57f17, 0 8px 5px rgba(0,0,0,0.3);
        }
        .btn-go:active { box-shadow: 0 0 0 #f57f17; }

        .bg-green { background: linear-gradient(#76ff03, #33691e); }
        .bg-blue { background: linear-gradient(#29b6f6, #01579b); }
        .bg-purple { background: linear-gradient(#ab47bc, #4a148c); }

        /* === 押注区 === */
        .bet-row {
            display: flex;
            background: #002d52;
            padding: 5px 2px;
            gap: 2px;
        }
        .bet-cell {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .odds-tag { font-size: 10px; text-align: center; color: #000; padding: 1px; font-weight: bold; border-radius: 2px; }
        .led-small {
            background: #000; color: var(--led-green);
            font-family: monospace; font-size: 11px; text-align: center;
            border: 1px solid #333; height: 16px; line-height: 14px;
        }

        /* === 底部水果按钮 === */
        .footer-btns {
            display: flex;
            justify-content: space-between;
            padding: 10px 5px;
            background: #0277bd;
            border-radius: 0 0 20px 20px;
        }
        .fruit-btn-wrap {
            position: relative;
            width: 11.5%;
            padding-bottom: 11.5%; /* Square aspect ratio */
            height: 0;
        }
        .fruit-btn {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, #a5d6a7, #1b5e20);
            border: 2px solid #1b5e20;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
            box-shadow: 0 3px 5px rgba(0,0,0,0.5);
            cursor: pointer;
            transition: transform 0.05s;
        }
        .fruit-btn:active { transform: scale(0.9); background: radial-gradient(circle at 30% 30%, #81c784, #1b5e20); }

        /* 动画关键帧 */
        @keyframes blinkWinner {
            0% { background: #fff; box-shadow: 0 0 10px red; }
            100% { background: gold; box-shadow: 0 0 30px gold; }
        }
        @keyframes floatGod {
            0%, 100% { transform: translate(-50%, 0); }
            50% { transform: translate(-50%, -10px); }
        }

        /* 颜色辅助类 */
        .tag-red { background: #ef5350; color: white; }
        .tag-orange { background: #ffa726; }
        .tag-blue { background: #42a5f5; color: white; }
        .tag-grey { background: #bdbdbd; }

        /* Toast 提示 */
        #toast {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.8); color: white; padding: 10px 20px; border-radius: 20px;
            pointer-events: none; opacity: 0; transition: opacity 0.3s; z-index: 100;
            font-weight: bold; border: 1px solid gold;
        }
    </style>
</head>
<body>

<div id="app-root">
    <div id="toast"></div>

    <!-- 顶部 -->
    <header class="header">
        <div class="icon-btn">⚙️</div>
        <div class="status-badge">💰 JACKPOT</div>
        <div class="icon-btn">🛒</div>
    </header>

    <!-- 显示屏 -->
    <div class="score-board">
        <div class="lcd-panel">
            <div class="lcd-label">WIN SCORE</div>
            <div class="lcd-value" id="win-display">0</div>
        </div>
        <div class="lcd-panel">
            <div class="lcd-label">CREDIT</div>
            <div class="lcd-value" id="credit-display">5000</div>
        </div>
    </div>

    <!-- 游戏主板 -->
    <div class="game-board">
        <div class="grid-container" id="grid-box">
            <!-- 动态生成格子 -->
        </div>
    </div>

    <!-- 控制按钮 -->
    <div class="control-panel">
        <button class="btn-3d btn-circle bg-green" onclick="game.autoBet()">ALL<br>+/</button>
        <button class="btn-3d btn-rect bg-blue">⬅️</button>
        <button class="btn-3d btn-rect bg-blue">➡️</button>
        <button class="btn-3d btn-rect bg-purple">1-6</button>
        <button class="btn-3d btn-rect bg-purple">8-13</button>
        <button class="btn-3d btn-go" id="start-btn" onclick="game.spin()">GO</button>
    </div>

    <!-- 赔率/押注显示 -->
    <div class="bet-row" id="odds-row">
        <!-- 动态生成 -->
    </div>

    <!-- 底部按钮 -->
    <div class="footer-btns" id="bet-btns">
        <!-- 动态生成 -->
    </div>
</div>

<script>
    // === 游戏配置 ===
    const ITEMS = [
        { id: 0, name: 'BAR', icon: '💎', odds: 100, color: 'tag-blue' },
        { id: 1, name: '77',  icon: '7️⃣', odds: 40,  color: 'tag-red' },
        { id: 2, name: 'STR', icon: '⭐', odds: 30,  color: 'tag-grey' },
        { id: 3, name: 'WTR', icon: '🍉', odds: 20,  color: 'tag-grey' },
        { id: 4, name: 'BEL', icon: '🔔', odds: 20,  color: 'tag-red' },
        { id: 5, name: 'LEM', icon: '🍋', odds: 15,  color: 'tag-grey' },
        { id: 6, name: 'ORG', icon: '🍊', odds: 10,  color: 'tag-grey' },
        { id: 7, name: 'APP', icon: '🍎', odds: 5,   color: 'tag-blue' }
    ];

    // 盘面布局 (顺时针顺序, 对应 ITEMS 的索引，99代表LUCK/JP)
    // 0=BAR, 1=77, 2=STR, 3=WTR, 4=BEL, 5=LEM, 6=ORG, 7=APP
    const BOARD_LAYOUT_IDS = [
        6, 4, 0, 0, 7, 7, // Top row (0-5)
        4, 99, 3,         // Right col (6-8) (99 is center filler logic, handled separately)
        3, 7,             // Right col cont.
        99, 99,           // Bottom (11,12) - Wait, Grid is 6x6.
        // Let's map indices manually to the visual 6x6 ring.
        // Top: (0,0) to (0,5)
        // Right: (1,5) to (5,5)
        // Bottom: (5,4) to (5,0)
        // Left: (4,0) to (1,0)
    ];

    // 24格的实际物品定义 (索引顺序：左上角开始顺时针)
    // 简化版布局配置
    const GRID_ITEMS = [
        {type: 6, sub:''}, {type: 4, sub:'x3'}, {type: 0, sub:'x50'}, {type: 0, sub:'x100'}, {type: 7, sub:''}, {type: 7, sub:'x3'}, // Top
        {type: 4, sub:'x3'}, {type: 99, sub:'R1'}, {type: 99, sub:'R2'}, {type: 3, sub:''}, {type: 3, sub:'x3'}, // Right
        {type: 99, sub:'L1'}, {type: 99, sub:'L2'}, // LUCK spots on right bottom
        {type: 2, sub:'x3'}, {type: 7, sub:''}, // Bottom Right continued...

        // 其实直接定义24个格子的数据结构更容易
        // 0-5 (Top), 6-10 (Right), 11-16 (Bottom Rev), 17-23 (Left Rev)
    ];

    // 重写更清晰的环形数据结构
    const RING_DATA = [
        // Top Row (Left to Right)
        {i:6, m:1}, {i:4, m:3}, {i:0, m:50}, {i:0, m:100}, {i:7, m:1}, {i:7, m:3},
        // Right Col (Top to Bottom)
        {i:4, m:3}, {i:3, m:1}, {i:3, m:3}, {i:98, m:0}, {i:7, m:1},
        // Bottom Row (Right to Left)
        {i:4, m:1}, {i:1, m:3}, {i:1, m:1}, {i:5, m:3}, {i:5, m:1}, {i:2, m:1},
        // Left Col (Bottom to Top)
        {i:7, m:1}, {i:2, m:3}, {i:98, m:0}, {i:3, m:3}, {i:7, m:1}, {i:6, m:1}
    ];
    // Correction: Standard machine has 24 slots. 6x6 perimeter is 20 slots.
    // The image has 6 cols, 6 rows.
    // Perimeter = 6 + 6 + 6 + 6 - 4 corners = 20.
    // Wait, the image counts:
    // Row 1: 6 cells.
    // Row 2: 1 cell (left), Center(4wide), 1 cell (right).
    // ...
    // Total perimeter cells = 6 (top) + 6 (bottom) + 4 (left mid) + 4 (right mid) = 20 cells?
    // Let's count image:
    // Top: 6
    // Bottom: 6
    // Left side vertical between top/bot: 4
    // Right side vertical between top/bot: 4
    // Total = 20.
    // But standard data is usually 24. I will adjust to 20 for visual accuracy to the 6x6 grid.

    const GAME_RING = [
        // Top 0-5
        {id:6, l:'🍊'}, {id:4, l:'🔔', s:'x3'}, {id:0, l:'💎', s:'50'}, {id:0, l:'💎', s:'100'}, {id:7, l:'🍎'}, {id:7, l:'🍎', s:'x3'},
        // Right 6-9
        {id:3, l:'🍉'}, {id:3, l:'🍉', s:'x3'}, {id:99, l:'LUCK'}, {id:7, l:'🍎'},
        // Bottom 10-15 (Reversed in logic, but let's list linearly for array)
        {id:6, l:'🍊', s:'x3'}, {id:1, l:'77', s:'x3'}, {id:1, l:'77'}, {id:5, l:'🍒', s:'x3'}, {id:5, l:'🍋', s:'x3'}, {id:2, l:'⭐'},
        // Left 16-19
        {id:2, l:'⭐', s:'x3'}, {id:99, l:'LUCK'}, {id:4, l:'🔔', s:'x3'}, {id:6, l:'🍊'}
    ];
    // This is 20 slots.

    // === 音效管理器 ===
    const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
    const Sound = {
        playTone: (freq, type, duration) => {
            if(audioCtx.state === 'suspended') audioCtx.resume();
            const osc = audioCtx.createOscillator();
            const gain = audioCtx.createGain();
            osc.type = type;
            osc.frequency.setValueAtTime(freq, audioCtx.currentTime);
            gain.gain.setValueAtTime(0.1, audioCtx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + duration);
            osc.connect(gain);
            gain.connect(audioCtx.destination);
            osc.start();
            osc.stop(audioCtx.currentTime + duration);
        },
        click: () => Sound.playTone(800, 'square', 0.1),
        bet: () => Sound.playTone(1200, 'sine', 0.1),
        step: () => Sound.playTone(600, 'triangle', 0.05),
        win: () => {
            [400, 500, 600, 800, 1000].forEach((f, i) => setTimeout(() => Sound.playTone(f, 'square', 0.2), i*100));
        }
    };

    // === 游戏控制器 ===
    const game = {
        balance: 5000,
        bets: {},
        isSpinning: false,
        activeIndex: 0, // 当前亮灯位置

        init: function() {
            this.renderGrid();
            this.renderControls();
            this.updateDisplay();
            ITEMS.forEach(i => this.bets[i.id] = 0);
        },

        renderGrid: function() {
            const container = document.getElementById('grid-box');
            let html = '';

            // Grid 6x6.
            // Indices mapping:
            // 0-5: Row 1
            // 6: Row 2 Col 1 (Index 19 in Ring)
            // Center: Row 2 Col 2-5
            // 7: Row 2 Col 6 (Index 6 in Ring)
            // ...

            // Construct visual grid cells
            // Top Row
            for(let i=0; i<6; i++) html += this.createCell(i);

            // Row 2
            html += this.createCell(19);
            html += `<div class="center-stage">
                        <div class="god-image">👺</div>
                        <div class="center-hud">
                            <button class="hud-btn">-</button>
                            <span style="color:white;font-weight:bold;font-size:14px">10</span>
                            <button class="hud-btn">+</button>
                        </div>
                        <div class="countdown-led">JP: 8888</div>
                     </div>`; // Spans 4 cols
            html += this.createCell(6);

            // Row 3
            html += this.createCell(18);
            html += this.createCell(7);

            // Row 4
            html += this.createCell(17);
            html += this.createCell(8);

            // Row 5
            html += this.createCell(16);
            html += this.createCell(9);

            // Bottom Row (Row 6) - Ring indices 15 down to 10
            for(let i=15; i>=10; i--) html += this.createCell(i);

            container.innerHTML = html;
        },

        createCell: function(ringIndex) {
            const data = GAME_RING[ringIndex];
            const isSpec = data.id === 99;
            const cls = isSpec ? 'cell' : `cell type-${ITEMS.find(x=>x.id===data.id)?.name.toLowerCase() || 'common'}`;
            return `<div class="cell ${cls}" id="cell-${ringIndex}" data-id="${data.id}">
                        <div class="icon">${data.l}</div>
                        ${data.s ? `<div class="multi">${data.s}</div>` : ''}
                    </div>`;
        },

        renderControls: function() {
            const oddsRow = document.getElementById('odds-row');
            const betBtns = document.getElementById('bet-btns');

            ITEMS.forEach(item => {
                // Odds Row
                oddsRow.innerHTML += `
                    <div class="bet-cell">
                        <div class="odds-tag ${item.color}">${item.odds}</div>
                        <div class="led-small" id="bet-display-${item.id}">0</div>
                    </div>
                `;

                // Footer Buttons
                betBtns.innerHTML += `
                    <div class="fruit-btn-wrap">
                        <div class="fruit-btn" onclick="game.placeBet(${item.id})">${item.icon}</div>
                    </div>
                `;
            });
        },

        placeBet: function(id) {
            if(this.isSpinning) return;
            if(this.balance < 10) return this.toast("余额不足!");

            this.balance -= 10;
            this.bets[id] += 10;
            Sound.bet();
            this.updateDisplay();

            // Button animation effect
            const btn = document.getElementById(`bet-display-${id}`);
            btn.style.color = '#fff';
            setTimeout(() => btn.style.color = '#76ff03', 100);
        },

        autoBet: function() {
            if(this.isSpinning) return;
            // Simple logic: bet 10 on everything
            ITEMS.forEach(i => {
                if(this.balance >= 10) {
                    this.balance -= 10;
                    this.bets[i.id] += 10;
                }
            });
            Sound.bet();
            this.updateDisplay();
        },

        updateDisplay: function() {
            document.getElementById('credit-display').innerText = this.balance;
            ITEMS.forEach(i => {
                document.getElementById(`bet-display-${i.id}`).innerText = this.bets[i.id];
            });
        },

        toast: function(msg) {
            const t = document.getElementById('toast');
            t.innerText = msg;
            t.style.opacity = 1;
            setTimeout(()=> t.style.opacity = 0, 2000);
        },

        // === 核心转动逻辑 ===
        spin: function() {
            const totalBet = Object.values(this.bets).reduce((a,b)=>a+b, 0);
            if(totalBet === 0) return this.toast("请先下注!");
            if(this.isSpinning) return;

            this.isSpinning = true;
            document.getElementById('start-btn').disabled = true;
            document.getElementById('win-display').innerText = "0";

            // 移除之前的赢家特效
            document.querySelectorAll('.winner').forEach(el => el.classList.remove('winner'));

            // 决定结果 (简单的伪随机，实际应由后端决定)
            // 这里为了演示，随便随机一个格子
            const stopIndex = Math.floor(Math.random() * 20);
            const loops = 3; // 至少转3圈
            const totalSteps = (20 * loops) + (stopIndex - this.activeIndex + 20) % 20;

            let currentStep = 0;

            const run = () => {
                // 移除上一个高亮
                document.getElementById(`cell-${this.activeIndex}`).classList.remove('active');

                // 移动到下一个
                this.activeIndex = (this.activeIndex + 1) % 20;
                const el = document.getElementById(`cell-${this.activeIndex}`);
                el.classList.add('active');
                Sound.step();

                currentStep++;

                if(currentStep < totalSteps) {
                    // 物理变速逻辑
                    let speed = 50; // 最快速度
                    const remaining = totalSteps - currentStep;

                    // 起步阶段
                    if(currentStep < 10) speed = 300 - (currentStep * 20);
                    // 减速阶段
                    else if(remaining < 15) speed = 50 + ((15 - remaining) * 20); // 线性增加延迟
                    else if(remaining < 5) speed = 400; // 最后几步非常慢

                    setTimeout(run, speed);
                } else {
                    this.gameEnd(stopIndex);
                }
            };

            run();
        },

        gameEnd: function(stopIdx) {
            this.isSpinning = false;
            document.getElementById('start-btn').disabled = false;

            const resultItem = GAME_RING[stopIdx];
            const el = document.getElementById(`cell-${stopIdx}`);
            el.classList.add('winner'); // 闪烁特效

            // 计算奖励
            if(resultItem.id !== 99) {
                const betAmount = this.bets[resultItem.id];
                const odds = ITEMS.find(i=>i.id===resultItem.id).odds;
                // 如果格子上有倍率 (如 x3)，还要乘
                let multi = 1;
                if(resultItem.s === 'x3') multi = 3;
                if(resultItem.s === '50') multi = 50; // Special case for Bar

                const win = betAmount * odds * multi;

                if(win > 0) {
                    Sound.win();
                    this.animateWin(win);
                }
            } else {
                // Luck 逻辑 (简化)
                this.toast("LUCKY!");
                Sound.win();
            }

            // 清空押注 (可选)
            ITEMS.forEach(i => this.bets[i.id] = 0);
            this.updateDisplay();
        },

        animateWin: function(amount) {
            const display = document.getElementById('win-display');
            let current = 0;
            const step = Math.ceil(amount / 20);
            const tm = setInterval(() => {
                current += step;
                if(current >= amount) {
                    current = amount;
                    clearInterval(tm);
                    this.balance += amount;
                    this.updateDisplay();
                }
                display.innerText = current;
                display.style.color = (current % 2 === 0) ? '#fff' : 'red';
            }, 50);
        }
    };

    // 启动
    game.init();

</script>
</body>
</html>
