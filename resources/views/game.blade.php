<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="format-detection" content="telephone=no">
    <title>Casino Master</title>
    <style>
        :root {
            --design-width: 480px;
            --design-height: 850px;
            --bg-body: #121212;
            --led-red: #ff3333;
            --btn-press-scale: 0.95;
        }

        /* === 基础架构 === */
        body, html {
            margin: 0; padding: 0; width: 100%; height: 100%;
            background-color: var(--bg-body);
            overflow: hidden;
            font-family: 'Segoe UI', system-ui, sans-serif;
            display: flex; justify-content: center; align-items: center;
            /* 禁用选中和触摸高亮 */
            user-select: none;
            -webkit-user-select: none;
            -webkit-tap-highlight-color: transparent;
        }

        /* 游戏机主体 (核心适配) */
        #game-stage {
            width: var(--design-width);
            height: var(--design-height);
            position: absolute;
            background: linear-gradient(180deg, #b71c1c 0%, #3e2723 100%);
            /* 硬件加速防闪烁套装 */
            transform-origin: center center;
            transform: translateZ(0);
            backface-visibility: hidden;
            box-shadow: 0 0 50px rgba(0,0,0,0.8);
            border-radius: 24px;
            overflow: hidden;
            display: flex; flex-direction: column;
            border: 4px solid #424242;
        }

        /* === 顶部栏 === */
        .header-bar {
            height: 50px;
            background: rgba(0,0,0,0.4);
            display: flex; justify-content: space-between; align-items: center;
            padding: 0 15px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            z-index: 100;
        }
        .header-btn { font-size: 24px; cursor: pointer; transition: transform 0.1s; }
        .header-btn:active { transform: scale(0.8); }
        .game-title {
            color: #ffd700; font-weight: 900; font-size: 18px;
            text-shadow: 0 0 10px #ff8f00; letter-spacing: 1px;
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
        .lcd-title { color: #81d4fa; font-size: 10px; text-align: center; margin-bottom: 2px; }
        .lcd-num {
            color: var(--led-red); font-family: 'Courier New', monospace;
            font-size: 26px; font-weight: bold; text-align: center;
            text-shadow: 0 0 5px red; letter-spacing: 2px;
        }

        /* === 主盘面 === */
        .main-board {
            flex: 1;
            background: #0d47a1;
            padding: 8px;
            position: relative;
            display: flex; justify-content: center; align-items: center;
        }
        .grid-wrap {
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            grid-template-rows: repeat(6, 1fr);
            gap: 5px;
            width: 100%; height: 100%;
        }

        /* 格子样式 */
        .slot-cell {
            background: #fff8e1;
            border-radius: 6px;
            border: 1px solid #999;
            box-shadow: inset 0 0 5px rgba(0,0,0,0.3), 0 2px 0 rgba(0,0,0,0.3);
            display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            position: relative;
            transition: transform 0.05s;
        }
        .slot-icon { font-size: 28px; line-height: 1; z-index: 2; }
        .slot-tag {
            font-size: 9px; font-weight: bold; color: #333;
            background: rgba(255,255,255,0.9); padding: 0 3px; border-radius: 4px;
            margin-top: -5px; z-index: 2;
        }

        /* 状态样式 */
        .slot-cell.active {
            background: #fff !important;
            box-shadow: 0 0 15px #ffeb3b, inset 0 0 0 2px red;
            z-index: 10; transform: scale(1.05);
        }
        .slot-cell.winner { animation: flash 0.5s infinite; background: gold !important; }
        @keyframes flash { 0%,100%{opacity:1;} 50%{opacity:0.5;} }

        .c-bar { background: #e0f7fa; } .c-apple { background: #ffebee; } .c-orange { background: #fff3e0; }

        /* 中间大图 */
        .center-area {
            grid-column: 2 / span 4;
            grid-row: 2 / span 4;
            background: radial-gradient(circle, #b71c1c 0%, #5d4037 100%);
            border-radius: 10px; border: 2px solid #ffd700;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            position: relative;
        }
        .god-icon { font-size: 90px; filter: drop-shadow(0 0 10px gold); margin-bottom: 10px; }

        /* 中间控制条 */
        .bet-adjust-ui {
            background: rgba(0,0,0,0.7); padding: 5px 10px;
            border-radius: 20px; border: 1px solid #4fc3f7;
            display: flex; align-items: center; gap: 8px;
        }
        .adjust-btn {
            width: 30px; height: 30px; border-radius: 50%; border: none;
            background: #0288d1; color: white; font-size: 20px; font-weight: bold;
            box-shadow: 0 2px 0 #01579b;
        }
        .adjust-btn:active { transform: translateY(2px); box-shadow: none; }
        .base-bet-display { color: #fff; font-size: 14px; font-weight: bold; min-width: 30px; text-align: center; }
        .base-bet-label { color: #aaa; font-size: 8px; position: absolute; bottom: 5px; }

        /* === 按钮控制台 === */
        .control-deck {
            background: #0277bd; padding: 8px;
            border-top: 3px solid #4fc3f7;
            display: flex; gap: 6px; height: 70px;
        }
        .game-btn {
            border: none; border-radius: 8px;
            color: white; font-weight: bold; font-size: 12px;
            box-shadow: 0 4px 0 rgba(0,0,0,0.3);
            position: relative; flex: 1;
            display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            text-shadow: 1px 1px 0 rgba(0,0,0,0.5);
            cursor: pointer;
        }
        .game-btn:active { transform: translateY(4px); box-shadow: none; }
        .game-btn.disabled { filter: grayscale(1); opacity: 0.7; pointer-events: none; }

        .btn-green { background: linear-gradient(#76ff03, #33691e); border-radius: 50%; width: 55px; flex: unset; }
        .btn-blue { background: linear-gradient(#29b6f6, #01579b); }
        .btn-purple { background: linear-gradient(#ab47bc, #4a148c); }
        .btn-go {
            background: linear-gradient(#ffeb3b, #f57f17);
            color: #b71c1c; font-size: 20px; flex: 1.5;
            box-shadow: 0 6px 0 #e65100;
        }
        .btn-active { box-shadow: 0 0 10px #fff, inset 0 0 10px #fff; border: 2px solid white; }

        /* === 底部水果押注 === */
        .bet-bar {
            background: #01579b; padding: 10px 10px 20px 10px;
            display: flex; justify-content: space-between;
        }
        .bet-item {
            width: 13vw; height: 13vw; max-width: 55px; max-height: 55px;
            background: radial-gradient(circle at 30% 30%, #a5d6a7, #1b5e20);
            border-radius: 50%; border: 3px solid #1b5e20;
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; box-shadow: 0 3px 5px rgba(0,0,0,0.4);
            position: relative; cursor: pointer;
        }
        .bet-item:active { transform: scale(0.9); }
        .bet-badge {
            position: absolute; top: -5px; right: -5px;
            background: #d50000; color: white; font-size: 10px;
            padding: 2px 6px; border-radius: 10px; border: 1px solid white;
            box-shadow: 0 2px 2px rgba(0,0,0,0.3);
            display: none;
        }

        /* === 充值弹窗 === */
        #modal-overlay {
            position: absolute; top:0; left:0; width:100%; height:100%;
            background: rgba(0,0,0,0.8); z-index: 999;
            display: none; justify-content: center; align-items: center;
            backdrop-filter: blur(5px);
        }
        .modal-box {
            background: #fff; padding: 20px; border-radius: 15px;
            width: 80%; text-align: center;
            box-shadow: 0 0 20px gold; border: 2px solid gold;
        }
        .charge-btn {
            background: #4caf50; color: white; border: none; padding: 10px 20px;
            font-size: 18px; border-radius: 5px; margin: 10px; width: 100%;
        }
        .close-btn {
            background: #f44336; color: white; border: none; padding: 5px 15px;
            border-radius: 5px; margin-top: 10px;
        }

        /* Toast 提示 */
        #toast {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.85); color: #fff; padding: 10px 20px;
            border-radius: 20px; border: 1px solid gold; z-index: 2000;
            display: none; pointer-events: none; font-size: 14px;
        }
    </style>
</head>
<body>

<!-- 充值弹窗 -->
<div id="modal-overlay">
    <div class="modal-box">
        <h2 style="color:#333; margin-top:0;">💰 充值中心</h2>
        <p>当前余额不足，请充值</p>
        <button class="charge-btn" onclick="Game.recharge(1000)">充值 1000 金币</button>
        <button class="charge-btn" onclick="Game.recharge(5000)">充值 5000 金币</button>
        <button class="close-btn" onclick="document.getElementById('modal-overlay').style.display='none'">关闭</button>
    </div>
</div>

<!-- 提示框 -->
<div id="toast">提示信息</div>

<!-- 游戏主舞台 -->
<div id="game-stage">

    <!-- 头部 -->
    <div class="header-bar">
        <!-- ⚙️改成大厅 -->
        <div class="header-btn" onclick="Game.toast('返回游戏大厅...')">🏠</div>
        <div class="game-title">WIN: <span id="win-display" style="color:#ffeb3b">0</span></div>
        <!-- 📶改成购物车 -->
        <div class="header-btn" onclick="Game.showRecharge()">🛒</div>
    </div>

    <!-- 分数板 -->
    <div class="lcd-section">
        <div class="lcd-box">
            <div class="lcd-title">总积分 (POOL)</div>
            <div class="lcd-num" id="credit-display">0</div>
        </div>
        <div class="lcd-box">
            <div class="lcd-title">本局押注 (BET)</div>
            <div class="lcd-num" id="total-bet-display">0</div>
        </div>
    </div>

    <!-- 6x6 盘面 -->
    <div class="main-board">
        <div class="grid-wrap" id="grid-container">
            <!-- JS生成 -->
        </div>
    </div>

    <!-- 按钮控制台 -->
    <div class="control-deck">
        <button class="game-btn btn-green" onclick="Game.betAll()">ALL<br><span style="font-size:10px">全押</span></button>
        <button class="game-btn btn-blue" onclick="Game.clearBets()">◀<br><span style="font-size:10px">清除</span></button>
        <button class="game-btn btn-blue" onclick="Game.doubleBets()">▶<br><span style="font-size:10px">翻倍</span></button>
        <button class="game-btn btn-purple" id="btn-auto" onclick="Game.toggleAuto()">AUTO<br><span style="font-size:10px">自动</span></button>
        <button class="game-btn btn-go" id="btn-spin" onclick="Game.spin()">GO</button>
    </div>

    <!-- 底部押注按钮 -->
    <div class="bet-bar" id="bet-container">
        <!-- JS生成 -->
    </div>
</div>

<script>
    /**
     * 屏幕适配：整体缩放方案
     */
    function resizeGame() {
        const stage = document.getElementById('game-stage');
        const designW = 480, designH = 850;
        const scale = Math.min(window.innerWidth / designW, window.innerHeight / designH) * 0.99; // 0.99防边缘闪烁
        stage.style.transform = `scale(${scale}) translateZ(0)`;
    }
    window.addEventListener('resize', resizeGame);
    window.addEventListener('load', resizeGame);

    /**
     * 游戏配置
     */
    const CONFIG = {
        // 水果清单
        items: [
            { id: 0, icon: '🍊', color: 'c-orange', odds: 10 },
            { id: 1, icon: '🔔', color: 'c-orange', odds: 20 },
            { id: 2, icon: '💎', color: 'c-bar', odds: 50, tag:'50' },
            { id: 3, icon: '💎', color: 'c-bar', odds: 100, tag:'100' },
            { id: 4, icon: '🍎', color: 'c-apple', odds: 5 },
            { id: 5, icon: '🍎', color: 'c-apple', odds: 10, tag:'x2' },
            { id: 6, icon: '🍉', color: 'c-orange', odds: 20 },
            { id: 7, icon: '⭐', color: 'c-bar', odds: 30 },
            { id: 8, icon: '7️⃣', color: 'c-apple', odds: 40 },
            { id: 9, icon: '🍒', color: 'c-orange', odds: 10 },
            { id: 99, icon: 'JP', color: 'c-bar', odds: 0 } // Luck
        ],
        // 环形轨道数据 (Grid布局对应)
        // 0-5 Top, 6-9 Right, 10-15 Bottom(Rev), 16-19 Left(Rev)
        ring: [
            {id:0}, {id:1, t:'x2'}, {id:2}, {id:3}, {id:4}, {id:5}, // Top
            {id:1}, {id:6}, {id:99}, {id:4}, // Right
            {id:4, t:'x2'}, {id:7}, {id:8}, {id:9}, {id:1}, {id:6, t:'x2'}, // Bottom (Visual Order: R->L)
            {id:7, t:'x2'}, {id:99}, {id:0, t:'x2'}, {id:6} // Left
        ],
        // 押注金额梯度
        betLevels: [10, 20, 50, 100, 500]
    };

    /**
     * 游戏逻辑核心
     */
    const Game = {
        credit: 5000,    // 总积分池
        bets: {},        // 当前押注
        currentBetLevelIndex: 0, // 默认押注梯度索引 (10)
        activeIndex: 0,  // 跑灯位置
        isRunning: false,
        isAuto: false,   // 自动模式状态
        autoTimer: null,

        init() {
            this.renderGrid();
            this.renderBets();

            // 初始化押注数据
            CONFIG.items.forEach(i => { if(i.id!==99 && i.id!==2 && i.id!==3 && i.id!==5) this.bets[i.id] = 0; });

            this.updateUI();
            resizeGame();
        },

        // 渲染 6x6 盘面
        renderGrid() {
            const container = document.getElementById('grid-container');
            let html = '';

            const getCell = (idx) => {
                // 安全获取数据，防止溢出
                const d = CONFIG.ring[idx % CONFIG.ring.length];
                const item = CONFIG.items.find(x => x.id === d.id);
                const tag = d.t || item.tag || '';
                return `
                    <div class="slot-cell ${item.color}" id="cell-${idx}">
                        <div class="slot-icon">${item.icon}</div>
                        ${tag ? `<div class="slot-tag">${tag}</div>` : ''}
                    </div>
                `;
            };

            // 手动映射 6x6 Grid 结构
            // Top (0-5)
            for(let i=0; i<6; i++) html += getCell(i);

            // Middle Rows (4 rows)
            const leftSide = [19, 18, 17, 16]; // Left Col indices
            const rightSide = [6, 7, 8, 9];    // Right Col indices

            for(let row=0; row<4; row++) {
                html += getCell(leftSide[row]); // Left

                // Center Area (Only on first middle row, span 4x4)
                if(row === 0) {
                    html += `
                    <div class="center-area">
                        <div class="god-icon">👺</div>
                        <div class="base-bet-label">单注金额</div>
                        <div class="bet-adjust-ui">
                            <button class="adjust-btn" onclick="Game.changeBetLevel(-1)">-</button>
                            <div class="base-bet-display" id="level-display">10</div>
                            <button class="adjust-btn" onclick="Game.changeBetLevel(1)">+</button>
                        </div>
                    </div>`;
                }

                html += getCell(rightSide[row]); // Right
            }

            // Bottom (15 down to 10)
            for(let i=15; i>=10; i--) html += getCell(i);

            container.innerHTML = html;
        },

        // 渲染底部押注按钮
        renderBets() {
            const container = document.getElementById('bet-container');
            // 只渲染可押注的普通水果
            const targets = [0, 1, 8, 7, 6, 9, 4]; // Orange, Bell, 77, Star, Melon, Cherry, Apple
            container.innerHTML = targets.map(id => {
                const item = CONFIG.items.find(i=>i.id===id);
                return `
                    <div class="bet-item" onclick="Game.addBet(${id})">
                        ${item.icon}
                        <div class="bet-badge" id="badge-${id}">0</div>
                    </div>
                `;
            }).join('');
        },

        // === 交互逻辑 ===

        // 调整单次押注额度
        changeBetLevel(dir) {
            let idx = this.currentBetLevelIndex + dir;
            if(idx < 0) idx = 0;
            if(idx >= CONFIG.betLevels.length) idx = CONFIG.betLevels.length - 1;
            this.currentBetLevelIndex = idx;
            document.getElementById('level-display').innerText = CONFIG.betLevels[idx];
            this.sound('click');
        },

        // 单个下注
        addBet(id) {
            if(this.isRunning) return;
            const amount = CONFIG.betLevels[this.currentBetLevelIndex];

            if(this.credit >= amount) {
                this.credit -= amount;
                this.bets[id] = (this.bets[id] || 0) + amount;
                this.updateUI();
                this.sound('coin');
            } else {
                this.showRecharge();
            }
        },

        // 全押 (All)
        betAll() {
            if(this.isRunning) return;
            const amount = CONFIG.betLevels[this.currentBetLevelIndex];
            const targets = [0, 1, 8, 7, 6, 9, 4];
            const totalNeeded = amount * targets.length;

            if(this.credit >= totalNeeded) {
                targets.forEach(id => {
                    this.bets[id] += amount;
                });
                this.credit -= totalNeeded;
                this.updateUI();
                this.sound('coin');
            } else {
                this.toast("余额不足以全押");
                this.showRecharge();
            }
        },

        // 清除押注 (左键)
        clearBets() {
            if(this.isRunning) return;
            // 退还积分
            let refund = 0;
            for(let k in this.bets) {
                refund += this.bets[k];
                this.bets[k] = 0;
            }
            if(refund > 0) {
                this.credit += refund;
                this.updateUI();
                this.toast("押注已清除");
                this.sound('click');
            }
        },

        // 翻倍 (右键)
        doubleBets() {
            if(this.isRunning) return;
            let totalCurrent = 0;
            for(let k in this.bets) totalCurrent += this.bets[k];

            if(totalCurrent === 0) return this.toast("请先押注");

            if(this.credit >= totalCurrent) {
                for(let k in this.bets) {
                    if(this.bets[k] > 0) this.bets[k] *= 2;
                }
                this.credit -= totalCurrent;
                this.updateUI();
                this.sound('coin');
                this.toast("押注翻倍!");
            } else {
                this.toast("余额不足以翻倍");
            }
        },

        // 自动 (Auto)
        toggleAuto() {
            this.isAuto = !this.isAuto;
            const btn = document.getElementById('btn-auto');
            if(this.isAuto) {
                btn.classList.add('btn-active');
                this.toast("自动模式开启");
                if(!this.isRunning) this.spin();
            } else {
                btn.classList.remove('btn-active');
                this.toast("自动模式关闭");
            }
        },

        // 界面更新
        updateUI() {
            document.getElementById('credit-display').innerText = this.credit;

            let totalBet = 0;
            for(let id in this.bets) {
                const val = this.bets[id];
                totalBet += val;
                const badge = document.getElementById(`badge-${id}`);
                if(badge) {
                    badge.innerText = val;
                    badge.style.display = val > 0 ? 'block' : 'none';
                }
            }
            document.getElementById('total-bet-display').innerText = totalBet;
        },

        // === 核心转动 ===
        spin() {
            // 1. 检查状态
            if(this.isRunning) return;

            // 2. 检查是否有押注
            const totalBet = Object.values(this.bets).reduce((a,b)=>a+b, 0);
            if(totalBet === 0) {
                this.isAuto = false;
                document.getElementById('btn-auto').classList.remove('btn-active');
                return this.toast("请先押注!");
            }

            // 3. 锁定状态
            this.isRunning = true;
            document.getElementById('btn-spin').classList.add('disabled');
            document.getElementById('win-display').innerText = 0;

            // 清理旧动画
            document.querySelectorAll('.slot-cell').forEach(e => {
                e.classList.remove('active', 'winner');
            });

            // 4. 计算结果 (前端随机演示)
            // 随机停止位置 0-19
            const stopIndex = Math.floor(Math.random() * 20);

            // 5. 动画参数
            let loops = 0;
            const maxLoops = 3; // 至少转几圈
            let speed = 50;
            let idx = this.activeIndex;

            // 6. 递归跑灯
            const run = () => {
                // 灭掉上一个
                document.getElementById(`cell-${idx}`).classList.remove('active');

                // 移动
                idx++;
                if(idx >= 20) {
                    idx = 0;
                    loops++;
                }

                // 亮起当前
                document.getElementById(`cell-${idx}`).classList.add('active');
                this.sound('step');

                // 判断结束
                if(loops >= maxLoops && idx === stopIndex) {
                    // 结束动画
                    this.activeIndex = idx;
                    setTimeout(() => this.gameEnd(stopIndex), 200);
                } else {
                    // 变速逻辑
                    if(loops < maxLoops - 1) {
                        if(speed > 30) speed -= 2; // 加速
                    } else {
                        speed += 10; // 减速
                    }
                    setTimeout(run, speed);
                }
            };

            run();
        },

        // 结算逻辑
        gameEnd(stopIndex) {
            const cell = document.getElementById(`cell-${stopIndex}`);
            cell.classList.add('winner'); // 闪烁效果

            const resultData = CONFIG.ring[stopIndex];
            const itemDef = CONFIG.items.find(i => i.id === resultData.id);

            // 计算赔率
            // 如果格子上有 t:'x2'，赔率翻倍；如果是普通，则用 itemDef.odds
            let multiplier = itemDef.odds;
            if(resultData.t === 'x2') multiplier *= 2;

            // Bar 的特殊处理 (格子数据里没有t，但图上有字)
            if(itemDef.tag === '50') multiplier = 50;
            if(itemDef.tag === '100') multiplier = 100;

            let winAmount = 0;

            // Luck / JP
            if(itemDef.id === 99) {
                winAmount = Math.floor(Math.random() * 500) + 100; // 随机奖励
                this.toast(`LUCKY! 获得 ${winAmount}`);
                this.sound('win');
            } else {
                // 普通中奖：押注 * 赔率
                const bet = this.bets[itemDef.id] || 0;
                if(bet > 0) {
                    winAmount = bet * multiplier;
                    this.toast(`中奖! +${winAmount}`);
                    this.sound('win');
                } else {
                    // 没押中
                    // this.sound('lose'); // 可选
                }
            }

            // 更新余额
            if(winAmount > 0) {
                this.credit += winAmount;
                document.getElementById('win-display').innerText = winAmount;
                this.updateUI();
            }

            // === 关键修复：确保状态重置 ===
            this.isRunning = false;
            document.getElementById('btn-spin').classList.remove('disabled');

            // 处理自动模式
            if(this.isAuto) {
                // 1.5秒后继续下一把，如果余额足够
                const totalBet = Object.values(this.bets).reduce((a,b)=>a+b, 0);
                if(this.credit >= totalBet) {
                    // 自动扣费逻辑
                    this.credit -= totalBet;
                    this.updateUI();
                    this.autoTimer = setTimeout(() => this.spin(), 1500);
                } else {
                    this.isAuto = false;
                    document.getElementById('btn-auto').classList.remove('btn-active');
                    this.toast("自动停止：余额不足");
                }
            }
        },

        // 充值系统
        showRecharge() {
            document.getElementById('modal-overlay').style.display = 'flex';
        },
        recharge(amount) {
            this.credit += amount;
            this.updateUI();
            this.toast(`成功充值 ${amount}`);
            document.getElementById('modal-overlay').style.display = 'none';
            this.sound('coin');
        },

        // 工具函数
        toast(msg) {
            const t = document.getElementById('toast');
            t.innerText = msg;
            t.style.display = 'block';
            setTimeout(() => t.style.display = 'none', 2000);
        },
        sound(type) {
            // 简易合成音效，无需外部文件
            if(!window.AudioContext) return;
            const ctx = new window.AudioContext();
            const osc = ctx.createOscillator();
            const g = ctx.createGain();
            osc.connect(g); g.connect(ctx.destination);

            const now = ctx.currentTime;
            if(type === 'click') {
                osc.type = 'sine'; osc.frequency.setValueAtTime(800, now);
                g.gain.exponentialRampToValueAtTime(0.01, now + 0.1);
                osc.start(); osc.stop(now + 0.1);
            } else if(type === 'step') {
                osc.type = 'square'; osc.frequency.setValueAtTime(400, now);
                g.gain.exponentialRampToValueAtTime(0.01, now + 0.05);
                osc.start(); osc.stop(now + 0.05);
            } else if(type === 'coin') {
                osc.type = 'sine'; osc.frequency.setValueAtTime(1200, now);
                g.gain.exponentialRampToValueAtTime(0.01, now + 0.2);
                osc.start(); osc.stop(now + 0.2);
            } else if(type === 'win') {
                osc.type = 'triangle';
                osc.frequency.setValueAtTime(500, now);
                osc.frequency.linearRampToValueAtTime(1000, now + 0.5);
                g.gain.setValueAtTime(0.3, now);
                g.gain.linearRampToValueAtTime(0, now + 0.5);
                osc.start(); osc.stop(now + 0.5);
            }
        }
    };

    // 启动游戏
    Game.init();
</script>
</body>
</html>
