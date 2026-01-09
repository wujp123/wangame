<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="format-detection" content="telephone=no">
    <title>Universal Slot Machine</title>
    <style>
        :root {
            --design-width: 480px; /* 设计稿逻辑宽度 */
            --design-height: 850px; /* 设计稿逻辑高度 */
            --bg-body: #000;
            --primary-shadow: rgba(0,0,0,0.5);
            --led-on: #ff0000;
            --led-off: #550000;
        }

        /* === 全局重置与容器 === */
        body, html {
            margin: 0; padding: 0; width: 100%; height: 100%;
            background-color: var(--bg-body);
            overflow: hidden; /* 禁止滚动 */
            font-family: 'Segoe UI', system-ui, sans-serif;
            display: flex; justify-content: center; align-items: center;
        }

        /* 游戏舞台容器 - 核心适配层 */
        #game-stage {
            width: var(--design-width);
            height: var(--design-height);
            position: absolute;
            background: linear-gradient(180deg, #d32f2f 0%, #880e4f 100%);
            transform-origin: center center; /* 从中心缩放 */
            box-shadow: 0 0 50px rgba(0,0,0,0.8);
            border-radius: 24px;
            overflow: hidden;
            display: flex; flex-direction: column;
            border: 4px solid #333;
        }

        /* === 顶部 === */
        .header-bar {
            height: 50px;
            background: rgba(0,0,0,0.3);
            display: flex; justify-content: space-between; align-items: center;
            padding: 0 15px;
            border-bottom: 2px solid rgba(255,255,255,0.1);
        }
        .header-icon { font-size: 24px; filter: drop-shadow(0 2px 2px #000); }
        .jackpot-tag {
            color: #ffd700; font-weight: 900; font-size: 18px;
            text-shadow: 0 0 10px #ff8f00; animation: pulse 1s infinite;
        }

        /* === 数码显示屏 === */
        .lcd-section {
            display: flex; justify-content: space-between; padding: 10px;
            background: #01579b; border-bottom: 4px solid #0277bd;
        }
        .lcd-box {
            width: 48%; background: #000;
            border: 2px solid #81d4fa; border-radius: 8px;
            padding: 5px; position: relative;
        }
        .lcd-title { color: #81d4fa; font-size: 12px; text-align: center; margin-bottom: 2px; }
        .lcd-num {
            color: #ff3333; font-family: 'Courier New', monospace;
            font-size: 28px; font-weight: bold; text-align: center;
            text-shadow: 0 0 5px red; letter-spacing: 2px;
        }

        /* === 中间盘面 (Grid) === */
        .main-board {
            flex: 1;
            background: #0d47a1;
            padding: 10px;
            position: relative;
            display: flex; justify-content: center; align-items: center;
        }
        .grid-wrap {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            grid-template-rows: repeat(6, 1fr);
            gap: 6px;
            width: 100%; height: 100%;
        }

        /* 格子单元 */
        .slot-cell {
            background: #fff8e1;
            border-radius: 8px;
            border: 1px solid #ccc;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.3), 0 3px 0 rgba(0,0,0,0.2);
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            position: relative;
            transition: transform 0.1s;
        }
        .slot-icon { font-size: 32px; line-height: 1; z-index: 2; }
        .slot-tag {
            font-size: 10px; font-weight: bold; color: #333;
            background: rgba(255,255,255,0.8); padding: 0 4px; border-radius: 4px;
            margin-top: -5px; z-index: 2;
        }

        /* 激活/高亮样式 */
        .slot-cell.active {
            background: #fff !important;
            box-shadow: 0 0 20px #ffeb3b, inset 0 0 0 2px red;
            z-index: 10; transform: scale(1.1);
        }
        .slot-cell.winner-flash { animation: flashWin 0.2s infinite; }

        /* 特殊颜色 */
        .c-orange { background: #fff3e0; }
        .c-bar { background: #e0f7fa; }
        .c-apple { background: #ffebee; }

        /* 中心大图区域 */
        .center-area {
            grid-column: 2 / span 4;
            grid-row: 2 / span 4;
            background: radial-gradient(circle, #b71c1c 0%, #3e2723 100%);
            border-radius: 12px;
            border: 3px solid #ffd700;
            box-shadow: inset 0 0 20px #000;
            position: relative;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
        }
        .center-img { font-size: 100px; filter: drop-shadow(0 0 10px gold); animation: float 3s ease-in-out infinite; }
        .center-ui {
            background: rgba(0,0,0,0.6); padding: 5px 15px;
            border-radius: 20px; border: 1px solid #4fc3f7;
            display: flex; align-items: center; gap: 10px; margin-top: 10px;
        }
        .mini-btn { width: 30px; height: 30px; border-radius: 50%; border: none; background: #0288d1; color: white; font-weight: bold; font-size: 18px; }

        /* === 按钮控制台 === */
        .control-deck {
            background: #0277bd; padding: 10px;
            border-top: 4px solid #4fc3f7;
            display: flex; gap: 8px; height: 80px;
        }
        .game-btn {
            border: none; border-radius: 8px;
            color: white; font-weight: bold; font-size: 14px;
            box-shadow: 0 4px 0 rgba(0,0,0,0.3);
            position: relative; flex: 1;
            display: flex; align-items: center; justify-content: center;
            text-shadow: 1px 1px 0 rgba(0,0,0,0.5);
        }
        .game-btn:active { transform: translateY(4px); box-shadow: none; }
        .btn-green { background: linear-gradient(#76ff03, #33691e); border-radius: 50%; width: 60px; flex: unset; }
        .btn-blue { background: linear-gradient(#29b6f6, #01579b); }
        .btn-purple { background: linear-gradient(#ab47bc, #4a148c); }
        .btn-go {
            background: linear-gradient(#ffeb3b, #f57f17);
            color: #b71c1c; font-size: 24px; flex: 1.5;
            box-shadow: 0 6px 0 #e65100;
        }

        /* === 底部水果押注 === */
        .bet-bar {
            background: #01579b; padding: 10px 15px 20px 15px;
            display: flex; justify-content: space-between;
        }
        .bet-item {
            width: 48px; height: 48px;
            background: radial-gradient(circle at 30% 30%, #a5d6a7, #1b5e20);
            border-radius: 50%; border: 3px solid #1b5e20;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; box-shadow: 0 4px 6px rgba(0,0,0,0.4);
            position: relative;
        }
        .bet-item:active { transform: scale(0.9); }
        .bet-badge {
            position: absolute; top: -5px; right: -5px;
            background: red; color: white; font-size: 10px;
            padding: 2px 5px; border-radius: 10px; border: 1px solid white;
            display: none;
        }

        /* 动画关键帧 */
        @keyframes pulse { 0%{transform:scale(1);} 50%{transform:scale(1.1);} 100%{transform:scale(1);} }
        @keyframes float { 0%,100%{transform:translateY(0);} 50%{transform:translateY(-10px);} }
        @keyframes flashWin { 0%{background:#fff;} 50%{background:gold;} 100%{background:#fff;} }

        /* Canvas 金币层 */
        #coin-canvas {
            position: absolute; top:0; left:0; width:100%; height:100%;
            pointer-events: none; z-index: 999;
        }
    </style>
</head>
<body>

<!-- 画布用于金币特效 -->
<canvas id="coin-canvas"></canvas>

<!-- 游戏舞台 (逻辑尺寸 480x850) -->
<div id="game-stage">

    <!-- 头部 -->
    <div class="header-bar">
        <div class="header-icon">⚙️</div>
        <div class="jackpot-tag">AUTO SPIN</div>
        <div class="header-icon">📶</div>
    </div>

    <!-- 分数板 -->
    <div class="lcd-section">
        <div class="lcd-box">
            <div class="lcd-title">WIN</div>
            <div class="lcd-num" id="txt-win">0</div>
        </div>
        <div class="lcd-box">
            <div class="lcd-title">CREDITS</div>
            <div class="lcd-num" id="txt-credit">1000</div>
        </div>
    </div>

    <!-- 6x6 盘面 -->
    <div class="main-board">
        <div class="grid-wrap" id="grid-container">
            <!-- JS 动态生成格子 -->
        </div>
    </div>

    <!-- 按钮组 -->
    <div class="control-deck">
        <button class="game-btn btn-green">ALL</button>
        <button class="game-btn btn-blue">◀</button>
        <button class="game-btn btn-blue">▶</button>
        <button class="game-btn btn-purple">AUTO</button>
        <button class="game-btn btn-go" id="btn-spin" onclick="Game.spin()">GO</button>
    </div>

    <!-- 底部押注按钮 -->
    <div class="bet-bar" id="bet-container">
        <!-- JS 动态生成 -->
    </div>
</div>

<script>
    /**
     * 核心适配逻辑：任何机型完美对齐
     */
    function resizeGame() {
        const stage = document.getElementById('game-stage');
        const designW = 480; // 我们的设计稿宽度
        const designH = 850; // 我们的设计稿高度

        const winW = window.innerWidth;
        const winH = window.innerHeight;

        // 计算缩放比例：取宽和高的最小缩放比，确保完全放入屏幕
        const scale = Math.min(winW / designW, winH / designH);

        // 应用缩放
        stage.style.transform = `scale(${scale})`;

        // 如果是 Telegram 环境，额外处理
        if(window.Telegram?.WebApp) {
            window.Telegram.WebApp.expand();
        }
    }
    // 启动时和窗口变化时调整
    window.addEventListener('resize', resizeGame);
    window.addEventListener('load', resizeGame);
    resizeGame(); // 立即执行一次

    /**
     * 游戏配置数据
     */
    const CONFIG = {
        // 水果定义
        items: [
            { id: 0, icon: '🍊', color: 'c-orange', odds: 10 },
            { id: 1, icon: '🔔', color: 'c-orange', odds: 20 },
            { id: 2, icon: '💎', color: 'c-bar', odds: 50, tag:'BAR' },
            { id: 3, icon: '💎', color: 'c-bar', odds: 100, tag:'BAR' }, // Big Bar
            { id: 4, icon: '🍎', color: 'c-apple', odds: 5 },
            { id: 5, icon: '🍎', color: 'c-apple', odds: 15, tag:'x3' },
            { id: 6, icon: '🍉', color: 'c-orange', odds: 20 },
            { id: 7, icon: '⭐', color: 'c-bar', odds: 30 },
            { id: 8, icon: '7️⃣', color: 'c-apple', odds: 40 },
            { id: 9, icon: '🍒', color: 'c-orange', odds: 10 },
            { id: 99, icon: 'LUCK', color: 'c-bar', odds: 0 } // Luck/JP
        ],
        // 环形轨道映射 (对应 Grid 6x6 的视觉顺序)
        // 0-5: Top Row, 6-10: Right Col, 11-15: Bottom Row (Rev), 16-19: Left Col (Rev)
        ring: [
            {id:0}, {id:1, t:'x3'}, {id:2}, {id:3}, {id:4}, {id:5}, // Top
            {id:1}, {id:6}, {id:99}, {id:4}, {id:6, t:'x3'}, // Right
            {id:4}, {id:7}, {id:8}, {id:9}, {id:1}, // Bottom (Visually Right to Left)
            {id:1}, {id:99}, {id:7, t:'x3'}, {id:0}  // Left (Visually Bottom to Top)
        ]
    };

    /**
     * 游戏引擎
     */
    const Game = {
        activeIndex: 0,
        isRunning: false,
        credit: 1000,
        bets: {},

        init() {
            this.renderGrid();
            this.renderBetButtons();

            // 默认押注
            CONFIG.items.forEach(i => { if(i.id!==99 && i.id!==3 && i.id!==5) this.bets[i.id] = 0; });
        },

        // 渲染 6x6 网格
        renderGrid() {
            const container = document.getElementById('grid-container');
            let html = '';

            // 辅助函数：根据 ringIndex 获取数据并生成 HTML
            const cellHtml = (ringIdx) => {
                const d = CONFIG.ring[ringIdx % CONFIG.ring.length];
                const item = CONFIG.items.find(x => x.id === d.id);
                const tag = d.t || item.tag || '';
                return `
                    <div class="slot-cell ${item.color}" id="cell-${ringIdx}">
                        <div class="slot-icon">${item.icon}</div>
                        ${tag ? `<div class="slot-tag">${tag}</div>` : ''}
                    </div>
                `;
            };

            // 按照 Grid 顺序填充 DOM
            // 这是一个比较繁琐的映射，为了保持代码简单，我们手动按 Grid 顺序(Row 1-6) 放置
            // Ring Indices:
            // Row 1: 0, 1, 2, 3, 4, 5
            // Row 2: 20, Center, 6
            // Row 3: 19, Center, 7
            // Row 4: 18, Center, 8
            // Row 5: 17, Center, 9
            // Row 6: 16, 15, 14, 13, 12, 11 (注意 Bottom 是逆序) (Wait logic below)

            // Top Row
            for(let i=0; i<=5; i++) html += cellHtml(i);

            // Mid Rows (Left + Center + Right)
            const leftIndices = [20, 19, 18, 17];
            const rightIndices = [6, 7, 8, 9];

            for(let row=0; row<4; row++) {
                html += cellHtml(leftIndices[row]); // Left Col
                if(row===0) {
                    // Center Big Block (spanning 4x4)
                    html += `
                    <div class="center-area">
                        <div class="center-img">👺</div>
                        <div class="center-ui">
                            <button class="mini-btn">-</button>
                            <span style="color:#fff;font-weight:bold;font-size:16px">BET: 10</span>
                            <button class="mini-btn">+</button>
                        </div>
                    </div>`;
                }
                html += cellHtml(rightIndices[row]); // Right Col
            }

            // Bottom Row (16 down to 11) - Wait, previous config length is 21?
            // Let's count: Top(6) + Right(5) + Bottom(5) + Left(4) = 20 total.
            // My ring array has 21 items. Let's trim to 20 for perfect 6x6.
            // Adjusted logic:
            // Top: 0-5
            // Right: 6-9 (4 items)
            // Bottom: 10-15 (6 items, reversed)
            // Left: 16-19 (4 items, reversed)
            // Total 20.

            // Let's use simpler manual mapping for the HTML to be safe.
            // Grid flow is top-left to bottom-right.

            // Correction for Bottom Row: Indices 15 down to 10
            for(let i=15; i>=10; i--) html += cellHtml(i);

            container.innerHTML = html;
        },

        renderBetButtons() {
            const div = document.getElementById('bet-container');
            // 只渲染主要可押注水果
            const bettable = [0, 1, 8, 7, 6, 9, 4]; // Orange, Bell, 77, Star, Melon, Cherry, Apple
            div.innerHTML = bettable.map(id => {
                const item = CONFIG.items.find(x=>x.id===id);
                return `<div class="bet-item" onclick="Game.addBet(${id})">
                    ${item.icon}
                    <div class="bet-badge" id="badge-${id}">0</div>
                </div>`;
            }).join('');
        },

        addBet(id) {
            if(this.isRunning) return;
            // 简单的扣费加注演示
            if(this.credit >= 10) {
                this.credit -= 10;
                this.bets[id] = (this.bets[id]||0) + 10;
                this.updateUI();
                this.sound('click');
            }
        },

        updateUI() {
            document.getElementById('txt-credit').innerText = this.credit;
            for(let id in this.bets) {
                const el = document.getElementById(`badge-${id}`);
                if(el) {
                    el.style.display = this.bets[id]>0 ? 'block' : 'none';
                    el.innerText = this.bets[id];
                }
            }
        },

        sound(type) {
            // 简单的 Web Audio 合成音效，无需加载文件
            const ctx = window.AudioContext ? new window.AudioContext() : null;
            if(!ctx) return;
            const osc = ctx.createOscillator();
            const g = ctx.createGain();
            osc.connect(g); g.connect(ctx.destination);

            const now = ctx.currentTime;
            if(type==='click') {
                osc.frequency.setValueAtTime(800, now);
                g.gain.exponentialRampToValueAtTime(0.01, now+0.1);
                osc.start(); osc.stop(now+0.1);
            } else if(type==='step') {
                osc.frequency.setValueAtTime(400, now);
                osc.type = 'square';
                g.gain.exponentialRampToValueAtTime(0.01, now+0.05);
                osc.start(); osc.stop(now+0.05);
            } else if(type==='win') {
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(600, now);
                osc.frequency.linearRampToValueAtTime(1200, now+0.3);
                g.gain.linearRampToValueAtTime(0, now+0.5);
                osc.start(); osc.stop(now+0.5);
                Coins.burst(); // 触发金币
            }
        },

        spin() {
            if(this.isRunning) return;
            this.isRunning = true;
            document.getElementById('txt-win').innerText = 0;
            document.querySelectorAll('.active').forEach(e=>e.classList.remove('active', 'winner-flash'));

            let loops = 0;
            let currentIdx = this.activeIndex;
            let speed = 50; // 初始速度
            let maxLoops = 3;
            // 随机结果 (0-19)
            let targetIdx = Math.floor(Math.random() * 20);

            const runStep = () => {
                // 清除上一个
                document.getElementById(`cell-${currentIdx}`).classList.remove('active');

                // 移动到下一个
                currentIdx++;
                if(currentIdx >= 20) {
                    currentIdx = 0;
                    loops++;
                }

                // 点亮
                document.getElementById(`cell-${currentIdx}`).classList.add('active');
                this.sound('step');

                if(loops >= maxLoops && currentIdx === targetIdx) {
                    // 停止
                    this.isRunning = false;
                    this.activeIndex = currentIdx;
                    this.checkWin(targetIdx);
                } else {
                    // 物理变速
                    if(loops < maxLoops - 1) {
                        // 加速阶段
                        if(speed > 30) speed -= 2;
                    } else {
                        // 减速阶段
                        speed += 15;
                    }
                    setTimeout(runStep, speed);
                }
            };
            runStep();
        },

        checkWin(idx) {
            const cell = document.getElementById(`cell-${idx}`);
            cell.classList.add('winner-flash');

            const d = CONFIG.ring[idx];
            const item = CONFIG.items.find(x=>x.id===d.id);

            // 简单演示：只要不是 Luck 就根据赔率算分
            if(item.id !== 99) {
                // 这里为了演示效果，假设用户总是下了注
                // 实际应该 check this.bets[item.id]
                const win = item.odds * 10;
                document.getElementById('txt-win').innerText = win;
                this.credit += win;
                this.updateUI();
                this.sound('win');
            }
        }
    };

    /**
     * Canvas 金币爆炸特效
     */
    const Coins = {
        burst() {
            const cvs = document.getElementById('coin-canvas');
            const ctx = cvs.getContext('2d');
            cvs.width = window.innerWidth;
            cvs.height = window.innerHeight;

            let coins = [];
            for(let i=0; i<50; i++) {
                coins.push({
                    x: cvs.width/2, y: cvs.height/2,
                    vx: (Math.random()-0.5)*15, vy: (Math.random()-1)*15,
                    g: 0.5, // 重力
                });
            }

            function draw() {
                ctx.clearRect(0,0,cvs.width,cvs.height);
                let active = false;
                ctx.fillStyle = 'gold';
                coins.forEach(c => {
                    c.x += c.vx;
                    c.y += c.vy;
                    c.vy += c.g;
                    if(c.y < cvs.height) {
                        active = true;
                        ctx.beginPath();
                        ctx.arc(c.x, c.y, 8, 0, Math.PI*2);
                        ctx.fill();
                        ctx.stroke();
                    }
                });
                if(active) requestAnimationFrame(draw);
            }
            draw();
        }
    };

    // 启动
    Game.init();
</script>
</body>
</html>
