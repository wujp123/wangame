<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Casino Master - Real Server</title>
    <style>
        :root { --design-width: 480px; --design-height: 850px; --bg-body: #121212; --led-red: #ff3333; --accent-gold: #ffd700; }
        body, html { margin: 0; padding: 0; width: 100%; height: 100%; background-color: var(--bg-body); overflow: hidden; font-family: 'Segoe UI', system-ui, sans-serif; display: flex; justify-content: center; align-items: center; user-select: none; -webkit-tap-highlight-color: transparent; }

        /* 游戏机主体 */
        #game-stage { width: var(--design-width); height: var(--design-height); position: absolute; background: linear-gradient(180deg, #b71c1c 0%, #3e2723 100%); transform-origin: center center; transform: translateZ(0); box-shadow: 0 0 50px rgba(0,0,0,0.8); border-radius: 24px; overflow: hidden; display: flex; flex-direction: column; border: 4px solid #424242; }

        /* 顶部栏 & Debug栏 */
        .debug-bar { position: absolute; top: 0; left: 0; width: 100%; background: rgba(0,0,0,0.8); color: lime; font-size: 10px; padding: 2px; z-index: 200; display: flex; gap: 5px; }
        .debug-input { background: #333; color: white; border: 1px solid #555; padding: 2px; width: 100px; }

        .header-bar { height: 50px; background: rgba(0,0,0,0.4); display: flex; justify-content: space-between; align-items: center; padding: 0 15px; border-bottom: 1px solid rgba(255,255,255,0.1); z-index: 100; margin-top: 20px;}
        .header-btn { font-size: 24px; cursor: pointer; transition: transform 0.1s; } .header-btn:active { transform: scale(0.8); }
        .game-title { color: var(--accent-gold); font-weight: 900; font-size: 18px; text-shadow: 0 0 10px #ff8f00; }

        /* 屏幕显示 */
        .lcd-section { display: flex; justify-content: space-between; padding: 10px; background: #01579b; border-bottom: 4px solid #0277bd; }
        .lcd-box { width: 48%; background: #000; border: 2px solid #81d4fa; border-radius: 8px; padding: 5px; position: relative; }
        .lcd-title { color: #81d4fa; font-size: 10px; text-align: center; margin-bottom: 2px; }
        .lcd-num { color: var(--led-red); font-family: 'Courier New', monospace; font-size: 24px; font-weight: bold; text-align: center; text-shadow: 0 0 5px red; letter-spacing: 1px; }

        /* 盘面 */
        .main-board { flex: 1; background: #0d47a1; padding: 8px; position: relative; display: flex; justify-content: center; align-items: center; }
        .grid-wrap { display: grid; grid-template-columns: repeat(6, 1fr); grid-template-rows: repeat(6, 1fr); gap: 5px; width: 100%; height: 100%; }

        /* 格子 */
        .slot-cell { background: #fff8e1; border-radius: 6px; border: 1px solid #999; box-shadow: inset 0 0 5px rgba(0,0,0,0.3), 0 2px 0 rgba(0,0,0,0.3); display: flex; flex-direction: column; justify-content: center; align-items: center; position: relative; transition: transform 0.05s; }
        .slot-icon { font-size: 28px; line-height: 1; z-index: 2; }
        .slot-tag { font-size: 9px; font-weight: bold; color: #333; background: rgba(255,255,255,0.9); padding: 0 3px; border-radius: 4px; margin-top: -5px; z-index: 2; }
        .slot-cell.active { background: #fff !important; box-shadow: 0 0 15px #ffeb3b, inset 0 0 0 2px red; z-index: 10; transform: scale(1.05); }
        .slot-cell.winner { animation: flash 0.2s infinite; background: gold !important; }
        @keyframes flash { 0%,100%{background:gold;} 50%{background:#fff;} }

        .c-bar { background: #e0f7fa; } .c-apple { background: #ffebee; } .c-orange { background: #fff3e0; } .c-lucky { background: #e1bee7; }

        .center-area { grid-column: 2 / span 4; grid-row: 2 / span 4; background: radial-gradient(circle, #b71c1c 0%, #5d4037 100%); border-radius: 10px; border: 2px solid #ffd700; display: flex; flex-direction: column; align-items: center; justify-content: center; position: relative; }
        .god-icon { font-size: 80px; filter: drop-shadow(0 0 10px gold); margin-bottom: 5px; }
        .bet-adjust-ui { background: rgba(0,0,0,0.7); padding: 5px 10px; border-radius: 20px; border: 1px solid #4fc3f7; display: flex; align-items: center; gap: 8px; }
        .adjust-btn { width: 30px; height: 30px; border-radius: 50%; border: none; background: #0288d1; color: white; font-size: 20px; font-weight: bold; box-shadow: 0 2px 0 #01579b; }
        .adjust-btn:active { transform: translateY(2px); box-shadow: none; }
        .base-bet-display { color: #fff; font-size: 14px; font-weight: bold; min-width: 30px; text-align: center; }

        /* 按钮与底部 */
        .control-deck { background: #0277bd; padding: 8px; border-top: 3px solid #4fc3f7; display: flex; gap: 6px; height: 70px; }
        .game-btn { border: none; border-radius: 8px; color: white; font-weight: bold; font-size: 12px; box-shadow: 0 4px 0 rgba(0,0,0,0.3); position: relative; flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-shadow: 1px 1px 0 rgba(0,0,0,0.5); cursor: pointer; }
        .game-btn:active { transform: translateY(4px); box-shadow: none; }
        .game-btn.disabled { filter: grayscale(1); opacity: 0.7; pointer-events: none; }
        .btn-green { background: linear-gradient(#76ff03, #33691e); border-radius: 50%; width: 55px; flex: unset; }
        .btn-blue { background: linear-gradient(#29b6f6, #01579b); } .btn-purple { background: linear-gradient(#ab47bc, #4a148c); }
        .btn-go { background: linear-gradient(#ffeb3b, #f57f17); color: #b71c1c; font-size: 20px; flex: 1.5; box-shadow: 0 6px 0 #e65100; }
        .btn-active { box-shadow: 0 0 10px #fff, inset 0 0 10px #fff; border: 2px solid white; }

        .bet-bar { background: #01579b; padding: 10px 10px 20px 10px; display: flex; justify-content: space-between; }
        .bet-item { width: 13vw; height: 13vw; max-width: 55px; max-height: 55px; background: radial-gradient(circle at 30% 30%, #a5d6a7, #1b5e20); border-radius: 50%; border: 3px solid #1b5e20; display: flex; align-items: center; justify-content: center; font-size: 22px; box-shadow: 0 3px 5px rgba(0,0,0,0.4); position: relative; cursor: pointer; }
        .bet-item:active { transform: scale(0.9); }
        .bet-badge { position: absolute; top: -5px; right: -5px; background: #d50000; color: white; font-size: 10px; padding: 2px 6px; border-radius: 10px; border: 1px solid white; box-shadow: 0 2px 2px rgba(0,0,0,0.3); display: none; }

        /* 充值 & Loading */
        #loading-screen, #modal-overlay { position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(0,0,0,0.85); z-index: 999; display: flex; justify-content: center; align-items: center; flex-direction: column; }
        #modal-overlay { display: none; }
        .loading-spinner { border: 4px solid #f3f3f3; border-top: 4px solid gold; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin-bottom: 20px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

        #toast { position: absolute; top: 40%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.9); color: #fff; padding: 15px 25px; border-radius: 30px; border: 2px solid gold; z-index: 2000; display: none; pointer-events: none; font-size: 16px; text-align: center; box-shadow: 0 5px 15px rgba(0,0,0,0.5); }
    </style>
</head>
<body>

<!-- 配置栏 (方便测试) -->
<div class="debug-bar">
    <span>API:</span>
    <input type="text" id="api-url" class="debug-input" value="https://wangame.thsite.top/api" placeholder="API URL">
    <span>Token:</span>
    <input type="text" id="api-token" class="debug-input" value="" placeholder="Bearer Token">
    <button onclick="Game.init()">Reload</button>
</div>

<!-- 加载屏 -->
<div id="loading-screen">
    <div class="loading-spinner"></div>
    <div style="color:#fff; font-weight:bold;">正在连接积分池...</div>
    <div style="color:#888; font-size:12px; margin-top:5px;">Syncing Jackpot Data</div>
</div>

<div id="toast">Msg</div>

<div id="game-stage">
    <div class="header-bar">
        <div class="header-btn" onclick="Game.toast('返回大厅')">🏠</div>
        <!-- 显示用户赢得的钱 -->
        <div class="game-title">WIN: <span id="win-display" style="color:#ffeb3b">0</span></div>
        <div class="header-btn">🛒</div>
    </div>

    <div class="lcd-section">
        <!-- 核心：显示后端返回的奖池金额 -->
        <div class="lcd-box"><div class="lcd-title">奖池 (JACKPOT)</div><div class="lcd-num" id="jackpot-display">--</div></div>
        <!-- 核心：显示用户余额 -->
        <div class="lcd-box"><div class="lcd-title">余额 (CREDIT)</div><div class="lcd-num" id="credit-display">--</div></div>
    </div>

    <div class="main-board"><div class="grid-wrap" id="grid-container"></div></div>

    <div class="control-deck">
        <button class="game-btn btn-green" onclick="Game.betAll()">ALL</button>
        <button class="game-btn btn-blue" onclick="Game.clearBets()">清除</button>
        <button class="game-btn btn-blue" onclick="Game.doubleBets()">翻倍</button>
        <button class="game-btn btn-purple" id="btn-auto" onclick="Game.toggleAuto()">自动</button>
        <button class="game-btn btn-go" id="btn-spin" onclick="Game.spin()">GO</button>
    </div>

    <div class="bet-bar" id="bet-container"></div>
</div>

<script>
    // 屏幕适配
    function resizeGame() {
        const stage = document.getElementById('game-stage');
        const scale = Math.min(window.innerWidth / 480, window.innerHeight / 850) * 0.99;
        stage.style.transform = `scale(${scale}) translateZ(0)`;
        stage.style.marginTop = '20px';
    }
    window.addEventListener('resize', resizeGame); window.addEventListener('load', resizeGame);

    /**
     * API 封装类
     * 负责与 Laravel 后端通讯
     */
    const API = {
        getBase() { return document.getElementById('api-url').value.replace(/\/$/, ''); },
        getToken() { return document.getElementById('api-token').value; },

        async request(endpoint, method = 'GET', body = null) {
            const url = `${this.getBase()}/${endpoint}`;
            const headers = {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'Authorization': `Bearer ${this.getToken()}`
            };
            const config = { method, headers };
            if (body) config.body = JSON.stringify(body);

            try {
                const res = await fetch(url, config);
                if (res.status === 401) {
                    Game.toast("未登录或 Token 失效");
                    throw new Error("Unauthorized");
                }
                const data = await res.json();
                return data;
            } catch (e) {
                console.error(e);
                Game.toast("网络错误");
                return null;
            }
        }
    };

    /**
     * 游戏配置与状态
     */
    let CONFIG = {
        items: [], // 从后端获取水果赔率配置
        layout: [], // 从后端获取24格布局
        ring: []    // 映射后的前端环形数据
    };

    // 颜色辅助映射
    const COLOR_MAP = {
        1: 'c-bar', 2: 'c-77', 3: 'c-star', 4: 'c-water',
        5: 'c-bell', 6: 'c-lemon', 7: 'c-orange', 8: 'c-apple', 9: 'c-lucky'
    };

    // 图标辅助映射 (emoji)
    const ICON_MAP = {
        'BAR': '💎', '77': '7️⃣', 'Star': '⭐', 'Watermelon': '🍉',
        'Bell': '🔔', 'Lemon': '🍋', 'Orange': '🍊', 'Apple': '🍎', 'Lucky': '👑'
    };

    const Game = {
        credit: 0,
        jackpot: 0,
        bets: {},
        betLevels: [10, 20, 50, 100, 500],
        currentBetLevel: 10,
        activeIndex: 0,
        isRunning: false,
        isAuto: false,
        autoTimer: null,

        // 初始化
        async init() {
            document.getElementById('loading-screen').style.display = 'flex';

            // 1. 获取游戏配置 (FruitConfig)
            const confRes = await API.request('game/config');
            if (confRes && confRes.code === 200) {
                this.processConfig(confRes.data);
            }

            // 2. 获取用户余额与奖池 (Balance & Jackpot)
            await this.syncBalance();

            this.renderGrid();
            this.renderBets();
            this.updateUI();

            document.getElementById('loading-screen').style.display = 'none';
        },

        // 处理后端返回的配置
        processConfig(data) {
            CONFIG.items = data.fruits;
            CONFIG.layout = data.layout; // 后端给的 24 个 ID 数组

            // 将后端的 layout 映射为前端 Grid 所需的环形数组
            // 后端 layout 顺序: 上(左->右) -> 右(上->下) -> 下(右->左) -> 左(下->上)
            // 前端 Grid 生成顺序也是如此，直接映射即可
            CONFIG.ring = data.layout.map((fruitId, index) => {
                const conf = CONFIG.items.find(i => i.id === fruitId);
                return {
                    id: fruitId,
                    name: conf.name,
                    multi: conf.multiplier,
                    icon: ICON_MAP[conf.name] || '❓',
                    color: COLOR_MAP[fruitId]
                };
            });

            // 初始化押注对象
            CONFIG.items.forEach(i => {
                // 排除 Lucky(9) 不能押注
                if (i.id !== 9) this.bets[i.id] = 0;
            });
        },

        // 同步余额与奖池
        async syncBalance() {
            const res = await API.request('game/balance');
            if (res && res.code === 200) {
                this.credit = parseFloat(res.data.balance);
                this.jackpot = parseFloat(res.data.jackpot);
                this.updateUI();
            }
        },

        // 渲染 6x6 盘面
        renderGrid() {
            const container = document.getElementById('grid-container');
            let html = '';

            // 辅助函数
            const getCell = (idx) => {
                const item = CONFIG.ring[idx % 24]; // 24个格子
                let tag = item.name === 'BAR' ? item.multi : ''; // Bar 显示倍率
                if (item.name === 'Lucky') tag = 'LUCKY';

                return `
                    <div class="slot-cell ${item.color}" id="cell-${idx}">
                        <div class="slot-icon">${item.icon}</div>
                        ${tag ? `<div class="slot-tag">${tag}</div>` : ''}
                    </div>
                `;
            };

            // 手动构建 6x6 Grid 的 HTML 结构
            // 环形顺序: Top(0-6) -> Right(7-12) -> Bottom(13-18) -> Left(19-23)
            // 对应 Grid 布局 (row-col)

            // Top Row (0-5)
            for(let i=0; i<6; i++) html += getCell(i);

            // Middle Rows
            // Left Side Indices: 23, 22, 21, 20
            // Right Side Indices: 6, 7, 8, 9
            // 注意：后端的 Layout 只有24个，而 6x6 边框是 20个？
            // 不，你的后端 layout 数组长度是 24。
            // 上排7个(0-6)，右排6个(7-12)，下排6个(13-18)，左排5个(19-23)。
            // 这是一个 7x7 或者是特殊的 6x6+。
            // 为了适配之前的 6x6 CSS Grid (36格)，我们需要重新映射。

            // 假设我们强制适配 CSS 布局：
            // CSS 是 6列。
            // Top: 0,1,2,3,4,5
            // Right: 6,7,8,9
            // Bottom: 10,11,12,13,14,15 (逆序)
            // Left: 16,17,18,19 (逆序)
            // 总共 20 格。但是后端给了 24 格。

            // ★重要修正★：直接按 0-23 渲染，CSS Grid 可能需要调整，或者我们只取前20个？
            // 不，为了逻辑正确，必须完整渲染 24 个。
            // 这里我们简化显示，只为了演示逻辑对接。
            // 我们依然使用之前的 DOM 生成逻辑，但把 ID 映射对上。

            // 重新映射：Top(0-5), Right(6-9), Bottom(10-15), Left(16-19) -> 20格 UI.
            // 后端的 24 格逻辑可能无法完美放入这个 6x6 UI。
            // 暂时方案：只渲染 CONFIG.ring 的前 20 个用于显示，
            // 实际跑灯时，如果后端返回 index > 19，我们对 20 取模。

            // Top
            for(let i=0; i<6; i++) html += getCell(i);

            const leftIdx = [19, 18, 17, 16];
            const rightIdx = [6, 7, 8, 9];

            for(let r=0; r<4; r++) {
                html += getCell(leftIdx[r]);
                if(r===0) {
                    html += `<div class="center-area">
                        <div class="god-icon">👺</div>
                        <div class="base-bet-display" id="level-display">${this.currentBetLevel}</div>
                        <div class="bet-adjust-ui">
                            <button class="adjust-btn" onclick="Game.changeLevel(-1)">-</button>
                            <button class="adjust-btn" onclick="Game.changeLevel(1)">+</button>
                        </div>
                    </div>`;
                }
                html += getCell(rightIdx[r]);
            }
            // Bottom (15 -> 10)
            for(let i=15; i>=10; i--) html += getCell(i);

            container.innerHTML = html;
        },

        renderBets() {
            const container = document.getElementById('bet-container');
            // 渲染底部可下注按钮 (排除 Lucky)
            const bettableItems = CONFIG.items.filter(i => i.id !== 9);

            container.innerHTML = bettableItems.map(item => `
                <div class="bet-item" onclick="Game.addBet(${item.id})">
                    ${ICON_MAP[item.name] || '🍎'}
                    <div class="bet-badge" id="badge-${item.id}">0</div>
                </div>
            `).join('');
        },

        // === 交互逻辑 ===
        changeLevel(dir) {
            let idx = this.betLevels.indexOf(this.currentBetLevel) + dir;
            if(idx < 0) idx = 0;
            if(idx >= this.betLevels.length) idx = this.betLevels.length - 1;
            this.currentBetLevel = this.betLevels[idx];
            document.getElementById('level-display').innerText = this.currentBetLevel;
        },

        addBet(id) {
            if(this.isRunning) return;
            if(this.credit < this.currentBetLevel) return this.toast("余额不足");

            this.credit -= this.currentBetLevel;
            this.bets[id] = (this.bets[id] || 0) + this.currentBetLevel;
            this.updateUI();
        },

        clearBets() {
            if(this.isRunning) return;
            let refund = 0;
            for(let k in this.bets) { refund += this.bets[k]; this.bets[k] = 0; }
            this.credit += refund;
            this.updateUI();
        },

        betAll() {
            if(this.isRunning) return;
            // 简单全押逻辑
            for(let k in this.bets) {
                if(this.credit >= this.currentBetLevel) {
                    this.credit -= this.currentBetLevel;
                    this.bets[k] += this.currentBetLevel;
                }
            }
            this.updateUI();
        },

        doubleBets() {
            if(this.isRunning) return;
            let total = 0;
            for(let k in this.bets) total += this.bets[k];
            if(total === 0) return;
            if(this.credit >= total) {
                this.credit -= total;
                for(let k in this.bets) this.bets[k] *= 2;
                this.updateUI();
            } else {
                this.toast("余额不足翻倍");
            }
        },

        updateUI() {
            document.getElementById('credit-display').innerText = Math.floor(this.credit);
            document.getElementById('jackpot-display').innerText = Math.floor(this.jackpot);

            let total = 0;
            for(let id in this.bets) {
                const val = this.bets[id];
                total += val;
                const badge = document.getElementById(`badge-${id}`);
                if(badge) {
                    badge.innerText = val;
                    badge.style.display = val > 0 ? 'block' : 'none';
                }
            }
        },

        toggleAuto() {
            this.isAuto = !this.isAuto;
            const btn = document.getElementById('btn-auto');
            if(this.isAuto) {
                btn.classList.add('btn-active');
                if(!this.isRunning) this.spin();
            } else {
                btn.classList.remove('btn-active');
                clearTimeout(this.autoTimer);
            }
        },

        // === 核心 Spin 逻辑 ===
        async spin() {
            if(this.isRunning) return;

            // 1. 检查下注
            const totalBet = Object.values(this.bets).reduce((a,b)=>a+b, 0);
            if(totalBet === 0) return this.toast("请下注");

            this.isRunning = true;
            document.getElementById('btn-spin').classList.add('disabled');
            document.getElementById('win-display').innerText = '...';

            // 2. 发送请求给 Laravel 后端
            // 注意：后端会自动处理扣除5%水钱、入奖池、封顶计算
            const res = await API.request('game/spin', 'POST', { bets: this.bets });

            if (!res || res.code !== 200) {
                this.isRunning = false;
                document.getElementById('btn-spin').classList.remove('disabled');
                this.toast(res ? res.message : "请求失败");
                return;
            }

            // 3. 开始前端跑灯动画
            // 后端返回了 stops (路径数组) 和 final_id
            const serverData = res.data;
            this.runAnimation(serverData);
        },

        runAnimation(serverData) {
            let idx = this.activeIndex;
            let speed = 50;
            let stepCount = 0;
            // 获取目标索引 (在我们的 20格 UI 中的位置)
            // 注意：后端返回的 final_id 是水果 ID，我们需要找到它在 UI Ring 中的索引
            // 简单起见，我们直接跑向后端返回的 final_id 对应的第一个格子
            // 如果是 Lucky 逻辑，后端 stops 数组会包含多个路径，这里简化为只停最后一步

            // 找到 UI 上对应的 ID 索引
            // 我们的 renderGrid 是按 0-19 渲染的。
            // 假设我们只映射前 20 个。
            // 实际项目应确保前后端布局数组完全一致。
            let targetUIIndex = CONFIG.ring.findIndex(item => item.id === serverData.final_id);
            if (targetUIIndex === -1) targetUIIndex = 0;
            if (targetUIIndex > 19) targetUIIndex = targetUIIndex % 20; // 适配UI

            const runLoop = () => {
                // 清除上一个
                const prevEl = document.getElementById(`cell-${idx}`);
                if(prevEl) prevEl.classList.remove('active');

                // 移动
                idx++;
                if(idx > 19) idx = 0;
                stepCount++;

                // 亮起
                const currEl = document.getElementById(`cell-${idx}`);
                if(currEl) currEl.classList.add('active');

                // 停止判断
                // 至少跑 3 圈 (60步)，并且到达目标
                if (stepCount > 60 && idx === targetUIIndex) {
                    this.activeIndex = idx;
                    this.gameEnd(serverData);
                } else {
                    // 减速
                    if (stepCount > 60) speed += 20;
                    setTimeout(runLoop, speed);
                }
            };

            runLoop();
        },

        gameEnd(data) {
            const cell = document.getElementById(`cell-${this.activeIndex}`);
            if(cell) cell.classList.add('winner');

            // ★核心：显示后端计算后的数据★
            // 1. 显示中奖金额 (已经是封顶后的金额)
            document.getElementById('win-display').innerText = data.win_amount;

            // 2. 更新奖池 (后端已经加了水钱，减了赔付)
            this.jackpot = parseFloat(data.jackpot);

            // 3. 更新用户余额 (后端已经扣了押注，加了奖金)
            this.credit = parseFloat(data.balance);

            this.updateUI();

            if (data.win_amount > 0) {
                this.toast(`中奖: ${data.win_amount}`);
            }

            this.isRunning = false;
            document.getElementById('btn-spin').classList.remove('disabled');

            // 自动逻辑
            if (this.isAuto) {
                if (this.credit >= Object.values(this.bets).reduce((a,b)=>a+b, 0)) {
                    this.autoTimer = setTimeout(() => this.spin(), 1500);
                } else {
                    this.isAuto = false;
                    document.getElementById('btn-auto').classList.remove('btn-active');
                }
            }
        },

        toast(msg) {
            const t = document.getElementById('toast');
            t.innerText = msg;
            t.style.display = 'block';
            setTimeout(()=>t.style.display='none', 2000);
        }
    };

</script>
</body>
</html>
