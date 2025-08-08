// グローバル変数
let challenges = [];
let selectedChallenge = null;
let typeFilter = 'all';
let regionFilter = 'all';
let autoUpdateInterval = null;

// 友人入力フィールドを管理する変数
let friendsCount = 0;
const maxFriends = 10;

// 現在のカテゴリ情報を取得
// URLパラメータからカテゴリを取得
const urlParams = new URLSearchParams(window.location.search);
let currentCategorySlug = urlParams.get('category') || window.currentCategorySlug || '';
let categories = [];

// HTMLエスケープ関数
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// 地域区分
const regions = {
  '北海道・東北': ['北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県'],
  '関東': ['茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県'],
  '中部': ['新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県', '静岡県', '愛知県'],
  '関西': ['三重県', '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県'],
  '中国・四国': ['鳥取県', '島根県', '岡山県', '広島県', '山口県', '徳島県', '香川県', '愛媛県', '高知県'],
  '九州・沖縄': ['福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県']
};

// 都道府県リスト
const prefectures = [
  '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
  '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
  '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
  '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
  '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
  '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
  '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
];

// 名前からスラッグを生成する関数
function generateSlug(name) {
    return name.toLowerCase()
        .replace(/☆/g, '')
        .replace(/★/g, '')
        .replace(/\s+/g, '-')
        .replace(/[^a-z0-9\-]/g, '');
}

// ユーティリティ関数
function formatDate(dateString) {
  const date = new Date(dateString);
  const month = date.getMonth() + 1;
  const day = date.getDate();
  const weekDay = ['日', '月', '火', '水', '木', '金', '土'][date.getDay()];
  return `${month}月${day}日(${weekDay})`;
}

function getDaysUntil(dateString) {
  // 日付が無効な場合の処理を追加
  if (!dateString || dateString === '0000-00-00') {
    return '日付未定';
  }
  
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const eventDate = new Date(dateString);
  
  // 日付が無効な場合の処理
  if (isNaN(eventDate.getTime())) {
    return '日付エラー';
  }
  
  eventDate.setHours(0, 0, 0, 0);
  const diffTime = eventDate - today;
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
  
  if (diffDays < 0) return '終了';
  if (diffDays === 0) return '今日！';
  if (diffDays === 1) return '明日！';
  return `あと${diffDays}日`;
}

function getProgress(current, target) {
  return Math.min((current / target) * 100, 100);
}

function getColorClass(color) {
  const colors = {
    purple: 'gradient-purple',
    blue: 'gradient-blue',
    pink: 'gradient-pink',
    green: 'gradient-green',
    orange: 'gradient-orange'
  };
  return colors[color] || 'gradient-purple';
}

function isTicketOnSale(saleDate) {
  if (!saleDate) return true;
  return new Date() >= new Date(saleDate);
}

// API関数
async function fetchChallenges() {
  try {
    let url = 'https://doin-challenge.com/api.php?action=getChallenges';
    
    // カテゴリページの場合、カテゴリでフィルタリング
    if (currentCategorySlug) {
      url += '&category=' + encodeURIComponent(currentCategorySlug);
    }
    
    // タイムアウト設定
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10秒タイムアウト
    
    const response = await fetch(url, {
      signal: controller.signal
    });
    clearTimeout(timeoutId);
    
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    
    // データ検証
    if (data.error) {
      throw new Error(data.message || 'データの取得に失敗しました');
    }
    
    if (!Array.isArray(data)) {
      throw new Error('不正なレスポンス形式');
    }
    
    // 重複を除去（同じIDのチャレンジを除外）
    const uniqueChallenges = data.filter((challenge, index, self) =>
      index === self.findIndex((c) => c.id === challenge.id)
    );
    
    challenges = uniqueChallenges;
    renderChallengeList();
  } catch (error) {
    console.error('Error fetching challenges:', error);
    
    // エラー時にローディング表示をクリア
    const challengeList = document.getElementById('challengeList');
    if (challengeList) {
      challengeList.innerHTML = `
        <div class="text-center text-gray-500" style="grid-column: 1 / -1;">
          <p class="mt-4">データの取得に失敗しました</p>
          <button onclick="location.reload()" class="mt-4 px-4 py-2 bg-primary text-white rounded-lg hover:bg-opacity-80">
            ページを再読み込み
          </button>
        </div>
      `;
    }
    
    if (error.name === 'AbortError') {
      showError('接続がタイムアウトしました。リロードしてください。');
    } else if (error.message.includes('HTTP error')) {
      showError('サーバーエラーが発生しました。しばらくしてから再度お試しください。');
    } else {
      showError('データの取得に失敗しました。リロードしてください。');
    }
  }
}

// カテゴリ一覧を取得
async function fetchCategories() {
  try {
    const response = await fetch('https://doin-challenge.com/api.php?action=getCategories');
    const data = await response.json();
    if (!data.error) {
      categories = data;
    }
  } catch (error) {
    console.error('Error fetching categories:', error);
  }
}

async function fetchChallengeDetail(id, slug) {
  try {
    let url = `https://doin-challenge.com/api.php?action=getChallenge`;
    if (id) {
      url += `&id=${id}`;
    } else if (slug) {
      url += `&slug=${encodeURIComponent(slug)}`;
    }
    
    const response = await fetch(url);
    const data = await response.json();
    if (data.error) {
      showError('詳細データの取得に失敗しました');
      return;
    }
    selectedChallenge = data;
    renderChallengeDetail();
  } catch (error) {
    console.error('Error fetching challenge detail:', error);
    showError('サーバーへの接続に失敗しました');
  }
}

async function createChallenge(challengeData) {
  try {
    // カテゴリが指定されていない場合のチェック
    if (!challengeData.category_id) {
      console.error('カテゴリが選択されていません');
      showError('カテゴリを選択してください');
      return;
    }
    
    // 現在のカテゴリを追加
    if (currentCategorySlug) {
      challengeData.category_slug = currentCategorySlug;
    }
    
    const response = await fetch('https://doin-challenge.com/api.php?action=createChallenge', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(challengeData)
    });
    
    // レスポンスが正常か確認
    if (!response.ok) {
      throw new Error(`HTTP error! status: ${response.status}`);
    }
    
    const data = await response.json();
    
    if (data.error) {
      // エラーメッセージを詳細に表示
      showError(`チャレンジの作成に失敗しました: ${data.message || '不明なエラー'}`);
      console.error('Error details:', data);
      return;
    }
    
    // モーダルを閉じる
    const createModal = document.getElementById('createModal');
    if (createModal) {
      createModal.style.display = 'none';
    }
    
    // フォームをリセット
    const form = document.getElementById('createForm');
    if (form) {
      form.reset();
    }
    
    await fetchChallenges();
    showSuccess('チャレンジを作成しました！');
  } catch (error) {
    console.error('Error creating challenge:', error);
    showError(`エラーが発生しました: ${error.message}`);
  }
}

async function addSupporter(supporterData) {
  try {
    const response = await fetch('https://doin-challenge.com/api.php?action=addSupporter', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(supporterData)
    });
    const data = await response.json();
    if (data.error) {
      showError('参加表明の登録に失敗しました');
      return;
    }
    // フォームをリセット
    document.getElementById('userName').value = '';
    document.getElementById('userMessage').value = '';
    document.getElementById('friendCount').value = '0';
    // 詳細を再取得
    await fetchChallengeDetail(selectedChallenge.id);
    showSuccess('参加表明を登録しました！');
  } catch (error) {
    console.error('Error adding supporter:', error);
    showError('サーバーへの接続に失敗しました');
  }
}

// UI更新関数
function renderChallengeList() {
  const filteredChallenges = challenges.filter(challenge => {
    if (typeFilter !== 'all' && challenge.type !== typeFilter) return false;
    
    if (regionFilter !== 'all') {
      const prefectureRegion = Object.entries(regions).find(([region, prefs]) => 
        prefs.includes(challenge.prefecture)
      )?.[0];
      if (prefectureRegion !== regionFilter) return false;
    }
    
    return true;
  });

  const container = document.getElementById('challengeList');
  if (!container) return;
  
  container.innerHTML = '';

  if (filteredChallenges.length === 0) {
    container.innerHTML = `
      <div class="text-center text-gray-500 py-8">
        <p>該当するチャレンジがありません</p>
      </div>
    `;
    return;
  }

  filteredChallenges.forEach(challenge => {
    const progress = getProgress(challenge.current, challenge.target);
    const daysLeft = getDaysUntil(challenge.event_date);
    
    const card = document.createElement('div');
    card.className = 'card cursor-pointer';
    card.onclick = () => selectChallenge(challenge);
    
    card.innerHTML = `
      <div class="flex items-center gap-2 mb-3">
        <svg width="20" height="20" fill="currentColor" class="${challenge.type === 'solo' ? 'text-pink-400' : 'text-blue-400'}">
          <use href="#icon-${challenge.type === 'solo' ? 'user' : 'users'}"></use>
        </svg>
        <span class="text-xs text-gray-400">${challenge.type === 'solo' ? 'ソロ' : 'グループ'}</span>
        ${challenge.ticket_url ? '<svg width="16" height="16" fill="currentColor" class="text-green-400 ml-auto"><use href="#icon-ticket"></use></svg>' : ''}
      </div>
      
      <div class="progress-bar mb-4" style="height: 0.5rem;">
        <div class="progress-fill ${getColorClass(challenge.color)}" style="width: ${progress}%"></div>
      </div>
      
      <h3 class="text-xl font-bold mb-1">${challenge.name}</h3>
      ${challenge.type === 'group' && challenge.members ? `
        <p class="text-xs text-gray-500 mb-2">
          ${challenge.members.slice(0, 3).join('、')}
          ${challenge.members.length > 3 ? ` 他${challenge.members.length - 3}名` : ''}
        </p>
      ` : ''}
      <p class="text-sm text-gray-400 mb-3">${challenge.event_name}</p>
      
      <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
          <svg width="20" height="20" fill="currentColor" class="text-purple-400">
            <use href="#icon-users"></use>
          </svg>
          <span class="text-2xl font-bold">${challenge.current}</span>
          <span class="text-gray-400">/ ${challenge.target}人</span>
        </div>
        <span class="text-lg font-bold ${progress >= 100 ? 'text-green-400' : 'text-yellow-400'}">${progress.toFixed(0)}%</span>
      </div>
      
      <div class="flex items-center justify-between text-sm text-gray-400">
        <span class="flex items-center gap-1">
          <svg width="14" height="14" fill="currentColor"><use href="#icon-map-pin"></use></svg>
          ${challenge.venue.length > 10 ? challenge.prefecture : `${challenge.venue}（${challenge.prefecture}）`}
        </span>
        <span>${daysLeft}</span>
      </div>
    `;
    
    container.appendChild(card);
  });
}

// チャレンジ詳細画面用の都道府県マップ表示
function displayChallengeMap(challengeId) {
  // チャレンジの参加者データから都道府県を集計
  if (!selectedChallenge || !selectedChallenge.messages) return;
  
  const prefectureCount = {};
  
  // 参加者の都道府県をカウント
  selectedChallenge.messages.forEach(supporter => {
    if (supporter.prefecture) {
      prefectureCount[supporter.prefecture] = (prefectureCount[supporter.prefecture] || 0) + 1;
    }
  });
  
  // 地域別に都道府県を表示
  Object.entries(mapRegions).forEach(([regionId, prefectures]) => {
    const container = document.getElementById('challengeMapRegions');
    if (!container) return;
    
    const regionDiv = document.createElement('div');
    regionDiv.className = `map-region ${regionId}`;
    regionDiv.style = 'margin-bottom: 10px;';
    
    regionDiv.innerHTML = `
      <div style="display: flex; flex-wrap: wrap; gap: 4px; justify-content: center;">
        ${prefectures.map(pref => {
          const count = prefectureCount[pref] || 0;
          const hasParticipants = count > 0;
          const color = hasParticipants ? 
            (selectedChallenge.color === 'purple' ? '#a855f7' :
             selectedChallenge.color === 'blue' ? '#3b82f6' :
             selectedChallenge.color === 'pink' ? '#ec4899' :
             selectedChallenge.color === 'green' ? '#10b981' : '#f97316') : '#4a4a5e';
          
          return `
            <div class="prefecture-chip" style="
              background: ${color};
              color: ${hasParticipants ? '#fff' : '#999'};
              padding: 4px 8px;
              border-radius: 4px;
              font-size: 11px;
              font-weight: ${hasParticipants ? 'bold' : 'normal'};
              opacity: ${hasParticipants ? '1' : '0.6'};
            ">
              ${pref.replace('県', '').replace('府', '').replace('都', '')}
              ${hasParticipants ? `(${count})` : ''}
            </div>
          `;
        }).join('')}
      </div>
    `;
    
    container.appendChild(regionDiv);
  });
}

function renderChallengeDetail() {
  if (!selectedChallenge) return;
  
  const progress = getProgress(selectedChallenge.current, selectedChallenge.target);
  const daysLeft = getDaysUntil(selectedChallenge.event_date);
  const remaining = selectedChallenge.target - selectedChallenge.current;
  const capacityRate = (selectedChallenge.target / selectedChallenge.capacity * 100).toFixed(0);
  const ticketOnSale = isTicketOnSale(selectedChallenge.ticket_sale_date);
  
  const challengeList = document.getElementById('challengeList');
  if (challengeList) {
    challengeList.style.display = 'none';
  }
  
  const detailContainer = document.getElementById('challengeDetail');
  if (!detailContainer) return;
  
  detailContainer.style.display = 'block';
  
  // 現在のユーザーが参加済みかチェック
  const isCurrentUserParticipating = window.isLoggedIn && selectedChallenge.messages && selectedChallenge.messages.find(
    supporter => supporter.twitter_username === window.loginUser.screen_name
  );
  
  detailContainer.innerHTML = `
    <div class="${getColorClass(selectedChallenge.color)} p-6">
      <div class="container">
        <button onclick="backToList()" class="text-white/80 hover:text-white mb-4">
          ← 一覧に戻る
        </button>
        
        <div class="flex items-start justify-between mb-2">
          <div class="flex items-start gap-3">
            <svg width="24" height="24" fill="currentColor" class="mt-1">
              <use href="#icon-${selectedChallenge.type === 'solo' ? 'user' : 'users'}"></use>
            </svg>
            <div>
              <h1 class="text-3xl font-bold">${selectedChallenge.name}</h1>
              ${selectedChallenge.type === 'group' && selectedChallenge.members ? `
                <p class="text-white/80 text-sm mt-1">${selectedChallenge.members.join('、')}</p>
              ` : ''}
              <!-- 場所情報をここに移動 -->
              <div class="flex items-center gap-1 mt-2" style="color: white; font-size: 1.1rem;">
                <svg width="20" height="20" fill="currentColor"><use href="#icon-map-pin"></use></svg>
                <span style="font-weight: bold;">${selectedChallenge.venue}（${selectedChallenge.prefecture}）</span>
              </div>
            </div>
          </div>
          
          <!-- 参加状態・ボタンエリア -->
          <div style="display: flex; gap: 1rem; align-items: center;">
            ${isCurrentUserParticipating ? `
              <div style="background: rgba(16,185,129,0.2); border: 2px solid #10b981; border-radius: 2rem; padding: 0.75rem 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg width="20" height="20" fill="#10b981">
                  <use href="#icon-check-circle"></use>
                </svg>
                <span style="color: #10b981; font-weight: bold;">参加中</span>
              </div>
            ` : ''}
            
            <button onclick="navigateToOtherChallenges()" 
                    style="background: rgba(255,255,255,0.2); color: white; padding: 0.75rem 1.5rem; border-radius: 2rem; border: 2px solid white; font-weight: bold; cursor: pointer; transition: all 0.3s;"
                    onmouseover="this.style.background='rgba(255,255,255,0.3)';" 
                    onmouseout="this.style.background='rgba(255,255,255,0.2)';">
              他のチャレンジに参加
            </button>
            
            <button onclick="showCreateOwnChallenge()" 
                    style="background: #ff6b9d; color: white; padding: 0.75rem 1.5rem; border-radius: 2rem; font-weight: bold; cursor: pointer; transition: all 0.3s; border: 2px solid #ff6b9d;"
                    onmouseover="this.style.opacity='0.8';" 
                    onmouseout="this.style.opacity='1';">
              主催する
            </button>
          </div>
        </div>
        
        <div class="flex flex-wrap items-center gap-4 text-white/90">
          <span class="flex items-center gap-1">
            <svg width="20" height="20" fill="currentColor"><use href="#icon-calendar"></use></svg>
            ${selectedChallenge.event_name}
          </span>
          <span class="flex items-center gap-1">
            <svg width="20" height="20" fill="currentColor"><use href="#icon-music"></use></svg>
            キャパ ${selectedChallenge.capacity}人（目標${capacityRate}%）
          </span>
        </div>
      </div>
    </div>
    
    <div class="container p-6">
      ${selectedChallenge.ticket_url ? `
        <div class="ticket-banner">
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-xl font-bold mb-2 flex items-center gap-2">
                <svg width="24" height="24" fill="currentColor"><use href="#icon-ticket"></use></svg>
                チケット情報
              </h3>
              <div class="text-sm">
                ${selectedChallenge.ticket_price ? `<p>料金: ${selectedChallenge.ticket_price}</p>` : ''}
                ${selectedChallenge.ticket_sale_date ? `
                  <p>販売開始: ${formatDate(selectedChallenge.ticket_sale_date)}
                  ${!ticketOnSale ? ' (まもなく！)' : ''}</p>
                ` : ''}
              </div>
            </div>
            <a href="${selectedChallenge.ticket_url}" target="_blank" rel="noopener noreferrer"
               class="btn ${ticketOnSale ? 'btn-primary' : 'btn-secondary cursor-not-allowed'}"
               ${!ticketOnSale ? 'onclick="return false;"' : ''}>
              ${ticketOnSale ? 'チケット購入' : '販売前'}
              <svg width="18" height="18" fill="currentColor"><use href="#icon-external-link"></use></svg>
            </a>
          </div>
        </div>
      ` : ''}
      
      <div class="card mb-6">
        <div class="text-center mb-6">
          <div class="text-6xl font-bold mb-2">
            ${selectedChallenge.current}
            <span class="text-3xl text-gray-400"> / ${selectedChallenge.target}人</span>
          </div>
          <div class="text-2xl font-bold text-yellow-400">
            あと${remaining}人で目標達成！
          </div>
        </div>
        
        <div class="progress-bar mb-6">
          <div class="progress-fill ${getColorClass(selectedChallenge.color)}" style="width: ${progress}%">
            ${progress >= 10 ? `<span class="text-sm font-bold">${progress.toFixed(0)}%</span>` : ''}
          </div>
        </div>
        
        <!-- 日本地図ヒートマップ表示 -->
        <div class="japan-map-mini mb-6" style="background: #2a2a3e; padding: 20px; border-radius: 15px;">
          <div id="challengeMapRegions">
            <!-- 動的に地域別都道府県が挿入される -->
          </div>
          <div class="legend" style="display: flex; gap: 10px; margin-top: 15px; justify-content: center; flex-wrap: wrap; font-size: 11px;">
            <div style="display: flex; align-items: center; gap: 5px;">
              <div style="width: 16px; height: 16px; border-radius: 4px; background: ${getColorClass(selectedChallenge.color).includes('purple') ? '#a855f7' : 
                getColorClass(selectedChallenge.color).includes('blue') ? '#3b82f6' :
                getColorClass(selectedChallenge.color).includes('pink') ? '#ec4899' :
                getColorClass(selectedChallenge.color).includes('green') ? '#10b981' : '#f97316'};"></div>
              <span>参加あり</span>
            </div>
            <div style="display: flex; align-items: center; gap: 5px;">
              <div style="width: 16px; height: 16px; border-radius: 4px; background: #4a4a5e;"></div>
              <span>参加なし</span>
            </div>
          </div>
        </div>
        
        <div class="text-center">
          <div class="inline-flex items-center gap-2 bg-gray-700 rounded-full px-6 py-3">
            <svg width="24" height="24" fill="currentColor" class="text-yellow-400">
              <use href="#icon-zap"></use>
            </svg>
            <span class="text-xl font-bold">
              開催まであと<span class="text-3xl text-yellow-400 mx-2">${daysLeft}</span>日
            </span>
          </div>
        </div>
      </div>
      
      <div class="card mb-6">
        <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
          <svg width="20" height="20" fill="currentColor" class="text-pink-400">
            <use href="#icon-heart"></use>
          </svg>
          参加表明する
        </h2>
        
        ${window.isLoggedIn ? (() => {
          // 既に参加表明済みかチェック
          const alreadySupported = selectedChallenge.messages && selectedChallenge.messages.find(
            supporter => supporter.twitter_username === window.loginUser.screen_name
          );
          
          if (alreadySupported) {
            // 参加表明済みの場合
            return `
            <!-- 参加表明済み表示 -->
            <div style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 1rem; padding: 2rem; text-align: center; margin-bottom: 1.5rem;">
              <div style="margin-bottom: 1.5rem;">
                <svg width="64" height="64" fill="white" style="margin: 0 auto;">
                  <use href="#icon-check-circle"></use>
                </svg>
              </div>
              <h3 style="color: white; font-size: 1.5rem; margin-bottom: 1rem; font-weight: bold;">
                参加表明済みです！
              </h3>
              <p style="color: rgba(255,255,255,0.9); font-size: 1rem; margin-bottom: 0.5rem;">
                ${formatDate(alreadySupported.created_at)} に参加表明しました
              </p>
              
              <!-- イベント詳細情報 -->
              <div style="background: rgba(255,255,255,0.15); border-radius: 0.75rem; padding: 1.5rem; margin-bottom: 1rem; text-align: left;">
                <h4 style="color: white; font-size: 1.1rem; font-weight: bold; margin-bottom: 0.8rem; text-align: center;">
                  <svg width="20" height="20" fill="white" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                    <use href="#icon-calendar"></use>
                  </svg>
                  イベント詳細
                </h4>
                <div style="display: grid; gap: 0.5rem; font-size: 0.95rem;">
                  <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="16" height="16" fill="rgba(255,255,255,0.8)">
                      <use href="#icon-${selectedChallenge.type === 'solo' ? 'user' : 'users'}"></use>
                    </svg>
                    <span style="color: rgba(255,255,255,0.8);">アーティスト:</span>
                    <span style="color: white; font-weight: bold;">${selectedChallenge.name}</span>
                  </div>
                  ${selectedChallenge.type === 'group' && selectedChallenge.members ? `
                    <div style="display: flex; align-items: start; gap: 0.5rem; margin-left: 1.5rem;">
                      <span style="color: rgba(255,255,255,0.7); font-size: 0.85rem;">メンバー: ${selectedChallenge.members.join('、')}</span>
                    </div>
                  ` : ''}
                  <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="16" height="16" fill="rgba(255,255,255,0.8)">
                      <use href="#icon-music"></use>
                    </svg>
                    <span style="color: rgba(255,255,255,0.8);">イベント名:</span>
                    <span style="color: white; font-weight: bold;">${selectedChallenge.event_name}</span>
                  </div>
                  <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="16" height="16" fill="rgba(255,255,255,0.8)">
                      <use href="#icon-calendar"></use>
                    </svg>
                    <span style="color: rgba(255,255,255,0.8);">開催日:</span>
                    <span style="color: white; font-weight: bold;">${formatDate(selectedChallenge.event_date)}</span>
                  </div>
                  <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <svg width="16" height="16" fill="rgba(255,255,255,0.8)">
                      <use href="#icon-map-pin"></use>
                    </svg>
                    <span style="color: rgba(255,255,255,0.8);">会場:</span>
                    <span style="color: white; font-weight: bold;">${selectedChallenge.venue}</span>
                  </div>
                  <div style="display: flex; align-items: center; gap: 0.5rem; margin-left: 1.5rem;">
                    <span style="color: rgba(255,255,255,0.7); font-size: 0.9rem;">${selectedChallenge.prefecture} / キャパ${selectedChallenge.capacity}人</span>
                  </div>
                </div>
              </div>
              
              <div style="background: rgba(255,255,255,0.2); border-radius: 0.75rem; padding: 1rem; margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-around; margin-bottom: 0.8rem;">
                  <div style="text-align: center;">
                    <div style="color: white; font-size: 1.8rem; font-weight: bold;">${selectedChallenge.current}</div>
                    <div style="color: rgba(255,255,255,0.8); font-size: 0.8rem;">現在の参加者</div>
                  </div>
                  <div style="text-align: center;">
                    <div style="color: white; font-size: 1.8rem; font-weight: bold;">${selectedChallenge.target}</div>
                    <div style="color: rgba(255,255,255,0.8); font-size: 0.8rem;">目標人数</div>
                  </div>
                  <div style="text-align: center;">
                    <div style="color: #fbbf24; font-size: 1.8rem; font-weight: bold;">${progress.toFixed(0)}%</div>
                    <div style="color: rgba(255,255,255,0.8); font-size: 0.8rem;">達成率</div>
                  </div>
                </div>
                <div style="border-top: 1px solid rgba(255,255,255,0.2); padding-top: 0.8rem;">
                  <p style="color: white; margin: 0; font-size: 0.875rem;">
                    ${alreadySupported.friends > 0 ? 
                      `友人 ${alreadySupported.friends}人 を含めて参加予定` : 
                      '1人で参加予定'}
                  </p>
                  ${alreadySupported.prefecture ? 
                    `<p style="color: rgba(255,255,255,0.8); margin: 0.3rem 0 0 0; font-size: 0.875rem;">
                      <svg width="12" height="12" fill="currentColor" style="display: inline-block; vertical-align: middle; margin-right: 0.3rem;">
                        <use href="#icon-map-pin"></use>
                      </svg>
                      ${alreadySupported.prefecture}から参加
                    </p>` : 
                    ''}
                  ${alreadySupported.message ? 
                    `<p style="color: white; margin: 0.5rem 0 0 0; font-size: 0.875rem; font-style: italic;">"${escapeHtml(alreadySupported.message)}"</p>` : 
                    ''}
                </div>
              </div>
              
              <!-- 友人情報 -->
              ${alreadySupported.friends_list && alreadySupported.friends_list.length > 0 ? `
              <div style="background: rgba(255,255,255,0.1); border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem;">
                <h5 style="color: white; font-size: 0.9rem; font-weight: bold; margin-bottom: 0.5rem;">
                  一緒に参加する友人
                </h5>
                <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;">
                  ${alreadySupported.friends_list.map(friend => `
                    <div style="background: rgba(255,255,255,0.2); border-radius: 1rem; padding: 0.3rem 0.8rem; display: flex; align-items: center; gap: 0.3rem;">
                      ${friend.twitter_username ? 
                        `<img src="https://unavatar.io/twitter/${friend.twitter_username}?fallback=https://ui-avatars.com/api/?name=${encodeURIComponent(friend.name)}&background=9ca3af&color=fff&size=20" 
                              alt="${escapeHtml(friend.name)}" 
                              style="width: 20px; height: 20px; border-radius: 50%;"
                              onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(friend.name)}&background=9ca3af&color=fff&size=20'">` :
                        `<img src="https://ui-avatars.com/api/?name=${encodeURIComponent(friend.name)}&background=9ca3af&color=fff&size=20" 
                              alt="${escapeHtml(friend.name)}" 
                              style="width: 20px; height: 20px; border-radius: 50%;">`
                      }
                      <span style="color: white; font-size: 0.85rem;">${escapeHtml(friend.name)}</span>
                    </div>
                  `).join('')}
                </div>
              </div>
              ` : ''}
              <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="https://twitter.com/intent/tweet?text=${encodeURIComponent(`🎯 ${selectedChallenge.name} ${selectedChallenge.event_name}に参加表明済み！\n\n現在の参加者：${selectedChallenge.current}人\nあと${selectedChallenge.target - selectedChallenge.current}人で目標達成！\n\n@idolfunch でみんなも参加しよう👉`)}&url=${encodeURIComponent(window.location.href)}&hashtags=${encodeURIComponent('動員チャレンジ')}" 
                   target="_blank"
                   style="display: inline-block; background: white; color: #10b981; padding: 0.75rem 2rem; border-radius: 2rem; text-decoration: none; font-weight: bold; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s;"
                   onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.3)';" 
                   onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.2)';">
                  <svg width="20" height="20" fill="currentColor" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                    <use href="#icon-share-2"></use>
                  </svg>
                  友達にシェアする
                </a>
                <button onclick="showEditSupporter(${alreadySupported.id})" 
                        style="display: inline-block; background: rgba(255,255,255,0.2); color: white; padding: 0.75rem 2rem; border-radius: 2rem; border: 2px solid white; font-weight: bold; cursor: pointer; transition: all 0.3s;"
                        onmouseover="this.style.background='rgba(255,255,255,0.3)';" 
                        onmouseout="this.style.background='rgba(255,255,255,0.2)';">
                  <svg width="20" height="20" fill="currentColor" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                    <use href="#icon-edit"></use>
                  </svg>
                  編集・削除
                </button>
              </div>
            </div>
            
            <!-- 編集フォーム（初期は非表示） -->
            <div id="editSupporterForm-${alreadySupported.id}" style="display: none; background: rgba(255,255,255,0.1); border-radius: 1rem; padding: 1.5rem; margin-top: 1rem;">
              <h4 style="color: white; font-weight: bold; margin-bottom: 1rem;">参加表明を編集・削除</h4>
              <form onsubmit="handleEditSupporter(event, ${alreadySupported.id})">
                <div class="mb-4">
                  <label style="color: white; display: block; margin-bottom: 0.5rem;">都道府県</label>
                  <select id="editPrefecture-${alreadySupported.id}" class="form-input" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);">
                    <option value="" style="color: #333;">都道府県を選択（任意）</option>
                    ${prefectures.map(pref => 
                      `<option value="${pref}" ${alreadySupported.prefecture === pref ? 'selected' : ''} style="color: #333;">${pref}</option>`
                    ).join('')}
                  </select>
                </div>
                
                <div class="mb-4">
                  <label style="color: white; display: block; margin-bottom: 0.5rem;">応援メッセージ</label>
                  <textarea id="editMessage-${alreadySupported.id}" 
                            rows="3" 
                            class="form-input" 
                            style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);"
                            placeholder="応援メッセージ（任意）">${alreadySupported.message || ''}</textarea>
                </div>
                
                <div class="mb-4">
                  <label style="color: white; display: block; margin-bottom: 0.5rem;">友達を何人連れて行きますか？</label>
                  <select id="editFriendCount-${alreadySupported.id}" class="form-input" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);">
                    <option value="0" ${alreadySupported.friends === 0 ? 'selected' : ''} style="color: #333;">自分だけ</option>
                    <option value="1" ${alreadySupported.friends === 1 ? 'selected' : ''} style="color: #333;">1人</option>
                    <option value="2" ${alreadySupported.friends === 2 ? 'selected' : ''} style="color: #333;">2人</option>
                    <option value="3" ${alreadySupported.friends === 3 ? 'selected' : ''} style="color: #333;">3人</option>
                    <option value="4" ${alreadySupported.friends === 4 ? 'selected' : ''} style="color: #333;">4人</option>
                    <option value="5" ${alreadySupported.friends >= 5 ? 'selected' : ''} style="color: #333;">5人以上</option>
                  </select>
                </div>
                
                <div class="mb-4">
                  <label style="color: white; display: block; margin-bottom: 0.5rem;">編集パスワード（必須）</label>
                  <input type="password" id="editPassword-${alreadySupported.id}" 
                         class="form-input" 
                         style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);"
                         placeholder="編集パスワードまたはPINコード"
                         required>
                  <p style="color: rgba(255,255,255,0.7); font-size: 0.75rem; margin-top: 0.25rem;">
                    ※編集パスワードを設定していない場合は、名前の最初2文字+登録月日（例：田中さんが7月6日に登録→田中0706）
                  </p>
                </div>
                
                <div style="display: flex; gap: 1rem;">
                  <button type="submit" class="btn btn-primary flex-1">保存</button>
                  <button type="button" onclick="showDeleteConfirm(${alreadySupported.id})" class="btn btn-secondary" style="background: #ef4444; border-color: #ef4444;">削除</button>
                  <button type="button" onclick="hideEditSupporter(${alreadySupported.id})" class="btn btn-secondary">キャンセル</button>
                </div>
              </form>
              
              <!-- 削除確認フォーム -->
              <div id="deleteConfirm-${alreadySupported.id}" style="display: none; background: rgba(239,68,68,0.2); border: 2px solid #ef4444; border-radius: 0.75rem; padding: 1rem; margin-top: 1rem;">
                <p style="color: white; margin-bottom: 1rem;">本当に参加表明を削除しますか？</p>
                <form onsubmit="handleDeleteSupporter(event, ${alreadySupported.id})">
                  <div class="mb-4">
                    <input type="password" id="deletePassword-${alreadySupported.id}" 
                           class="form-input" 
                           style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);"
                           placeholder="削除パスワードまたはPINコード"
                           required>
                  </div>
                  <div style="display: flex; gap: 1rem;">
                    <button type="submit" class="btn btn-primary" style="background: #ef4444; border-color: #ef4444;">削除実行</button>
                    <button type="button" onclick="hideDeleteConfirm(${alreadySupported.id})" class="btn btn-secondary">キャンセル</button>
                  </div>
                </form>
              </div>
            </div>
            `;
          } else {
            // 未参加の場合は通常のフォームを表示
            return `
        <!-- ログイン済みユーザー情報表示 -->
        <div style="background: rgba(29,161,242,0.1); border: 2px solid #1DA1F2; border-radius: 0.75rem; padding: 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 1rem;">
          <img src="${window.loginUser.profile_image_url}" 
               alt="${window.loginUser.name}" 
               style="width: 48px; height: 48px; border-radius: 50%; border: 2px solid #1DA1F2;">
          <div>
            <p style="margin: 0; font-weight: bold; color: #fff; font-size: 1rem;">
              ${window.loginUser.name}
            </p>
            <p style="margin: 0; color: #1DA1F2; font-size: 0.875rem;">
              @${window.loginUser.screen_name}
            </p>
          </div>
          <div style="margin-left: auto;">
            <svg width="24" height="24" fill="#1DA1F2">
              <use href="#icon-twitter"></use>
            </svg>
          </div>
        </div>
        
        <form onsubmit="handleSupporterSubmit(event)">
          <div class="mb-4" style="display: none;">
            <input type="text" id="userName" value="${window.loginUser.name}" class="form-input" required>
          </div>
          
          <div class="mb-4" style="display: none;">
            <input type="text" id="twitter_username" value="${window.loginUser.screen_name}" class="form-input" required>
          </div>
          
          <div class="prefecture-select-wrapper mb-4">
            <label class="prefecture-select-label" for="userPrefecture">
              🗾 どちらから参加されますか？
            </label>
            <select id="userPrefecture" class="prefecture-select">
              <option value="">都道府県を選択（任意）</option>
              ${prefectures.map(pref => `<option value="${pref}">${pref}</option>`).join('')}
            </select>
            <p class="prefecture-hint">※ 日本地図で可視化されます</p>
          </div>
          
          <div class="mb-4">
            <textarea id="userMessage" placeholder="応援メッセージ（任意）" 
                      rows="3" class="form-input"></textarea>
          </div>
          
          <div class="friends-section">
            <div class="friends-header">
              <h3 class="friends-title">一緒に参加する友人（任意）</h3>
              <button type="button" 
                      onclick="addFriendField()" 
                      class="add-friend-btn">
                <svg class="icon icon-plus" style="width: 16px; height: 16px;">
                  <use xlink:href="#icon-plus"></use>
                </svg>
                友人を追加
              </button>
            </div>
            <div id="friends-container">
              <!-- 動的に友人入力フィールドが追加される -->
            </div>
          </div>
          
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">友達を何人連れて行きますか？</label>
            <select id="friendCount" class="form-input">
              <option value="0">自分だけ</option>
              <option value="1">1人</option>
              <option value="2">2人</option>
              <option value="3">3人</option>
              <option value="4">4人</option>
              <option value="5">5人以上</option>
            </select>
          </div>
          
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">編集パスワード（任意）</label>
            <input type="password" id="editPassword" placeholder="後で編集・削除する時に使用" 
                   class="form-input">
            <p class="text-xs text-gray-400 mt-1">※設定すると後から編集・削除できます</p>
          </div>
          
          <button type="submit" class="btn btn-primary w-full text-lg">
            参加表明する！
          </button>
        </form>`;
          }
        })() : `
        <!-- 未ログイン時の表示 -->
        <div style="background: linear-gradient(135deg, #1DA1F2 0%, #1a91da 100%); border-radius: 1rem; padding: 2rem; text-align: center; margin-bottom: 1.5rem;">
          <h3 style="color: white; font-size: 1.5rem; margin-bottom: 1rem; font-weight: bold;">
            Twitterログインで参加しよう！
          </h3>
          <p style="color: rgba(255,255,255,0.9); font-size: 1rem; margin-bottom: 1.5rem;">
            参加表明にはTwitterアカウントが必要です
          </p>
          <a href="/twitter_auth.php?redirect=${encodeURIComponent(window.location.href)}" 
             style="display: inline-block; background: white; color: #1DA1F2; padding: 1rem 2.5rem; border-radius: 2rem; text-decoration: none; font-weight: bold; font-size: 1.1rem; box-shadow: 0 4px 15px rgba(0,0,0,0.2); transition: all 0.3s;"
             onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0,0,0,0.3)';" 
             onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(0,0,0,0.2)';">
            <svg width="20" height="20" fill="currentColor" style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
              <use href="#icon-twitter"></use>
            </svg>
            Twitterでログイン
          </a>
          <p style="color: rgba(255,255,255,0.8); font-size: 0.875rem; margin-top: 1rem;">
            ※ 君斗りんくのフォローが必須です
          </p>
        </div>
        
        <form onsubmit="handleSupporterSubmit(event)" style="opacity: 0.5; pointer-events: none;">
          <div class="mb-4">
            <input type="text" id="userName" placeholder="お名前（ニックネーム）" 
                   class="form-input" required disabled>
          </div>
          
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2" style="color: #ff6b9d;">Twitter <span style="color: #ff3333;">※必須</span></label>
            <input type="text" id="twitter_username" placeholder="@なしでTwitterユーザー名（必須）" 
                   class="form-input" required disabled>
            <p class="text-xs text-gray-400 mt-1">例: idolfunch（@マークは不要）</p>
          </div>
          
          <div class="prefecture-select-wrapper mb-4">
            <label class="prefecture-select-label" for="userPrefecture">
              🗾 どちらから参加されますか？
            </label>
            <select id="userPrefecture" class="prefecture-select" disabled>
              <option value="">都道府県を選択（任意）</option>
              ${prefectures.map(pref => `<option value="${pref}">${pref}</option>`).join('')}
            </select>
            <p class="prefecture-hint">※ 日本地図で可視化されます</p>
          </div>
          
          <div class="mb-4">
            <textarea id="userMessage" placeholder="応援メッセージ（任意）" 
                      rows="3" class="form-input" disabled></textarea>
          </div>
          
          <div class="friends-section">
            <div class="friends-header">
              <h3 class="friends-title">一緒に参加する友人（任意）</h3>
              <button type="button" 
                      onclick="addFriendField()" 
                      class="add-friend-btn" disabled>
                <svg class="icon icon-plus" style="width: 16px; height: 16px;">
                  <use xlink:href="#icon-plus"></use>
                </svg>
                友人を追加
              </button>
            </div>
            <div id="friends-container">
              <!-- 動的に友人入力フィールドが追加される -->
            </div>
          </div>
          
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">友達を何人連れて行きますか？</label>
            <select id="friendCount" class="form-input" disabled>
              <option value="0">自分だけ</option>
              <option value="1">1人</option>
              <option value="2">2人</option>
              <option value="3">3人</option>
              <option value="4">4人</option>
              <option value="5">5人以上</option>
            </select>
          </div>
          
          <div class="mb-4">
            <label class="block text-sm font-medium mb-2">編集パスワード（任意）</label>
            <input type="password" id="editPassword" placeholder="後で編集・削除する時に使用" 
                   class="form-input" disabled>
            <p class="text-xs text-gray-400 mt-1">※設定すると後から編集・削除できます</p>
          </div>
          
          <button type="submit" class="btn btn-primary w-full text-lg" disabled>
            参加表明する！
          </button>
        </form>`}
      </div>
      
      <div class="card">
        <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
          <svg width="20" height="20" fill="currentColor" class="text-blue-400">
            <use href="#icon-message-circle"></use>
          </svg>
          みんなの応援メッセージ
        </h2>
        
        <div class="message-list">
          ${selectedChallenge.messages && selectedChallenge.messages.length > 0 ? 
            displaySupporters(selectedChallenge.messages) : 
            '<p class="text-gray-500 text-center py-8">まだメッセージがありません。最初の参加表明者になろう！</p>'
          }
        </div>
      </div>
      
      <!-- 貢献度ランキング -->
      <div class="card mt-6">
        <h2 class="text-xl font-bold mb-4 flex items-center gap-2">
          <svg width="20" height="20" fill="currentColor" class="text-yellow-400">
            <use href="#icon-star"></use>
          </svg>
          貢献度ランキング TOP10
        </h2>
        
        <div id="contributionRanking">
          <div style="text-align: center; color: #a855f7;">
            <div class="loading"></div>
            <p style="margin-top: 0.5rem;">ランキングを読み込み中...</p>
          </div>
        </div>
      </div>
      
      ${window.isLoggedIn ? `
      <!-- 応援ボタン（モバイル最適化） -->
      <div class="card mt-6" style="background: linear-gradient(135deg, rgba(155,89,182,0.1) 0%, rgba(255,107,157,0.1) 100%); border: 2px solid rgba(155,89,182,0.3);">
        <div id="cheerButtonArea" style="text-align: center;">
          <button id="cheerActionButton" 
                  onclick="handleCheerAction()" 
                  style="background: linear-gradient(45deg, #9b59b6, #8e44ad); color: white; padding: 1.5rem 3rem; border-radius: 3rem; border: none; font-weight: bold; font-size: 1.3rem; cursor: pointer; box-shadow: 0 6px 20px rgba(155,89,182,0.4); transition: all 0.3s; width: 100%; max-width: 400px;"
                  onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 25px rgba(155,89,182,0.5)';"
                  onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 6px 20px rgba(155,89,182,0.4)';">
            <span style="font-size: 1.5rem;">🔥</span> 応援する！ <span style="font-size: 1.5rem;">🔥</span>
          </button>
          <div id="cheerStatusInfo" style="margin-top: 0.75rem; color: rgba(255,255,255,0.8); font-size: 0.9rem;"></div>
          <div id="cheerTimerInfo" style="margin-top: 0.5rem; color: rgba(255,255,255,0.7); font-size: 0.875rem;"></div>
        </div>
      </div>
      
      <!-- 応援コンテスト -->
      <div class="card mt-6" style="background: linear-gradient(135deg, rgba(255,107,157,0.1) 0%, rgba(255,218,185,0.1) 100%); border: 2px solid rgba(255,107,157,0.3);">
        <h2 class="text-xl font-bold mb-4 flex items-center gap-2" style="color: #ff6b9d;">
          🎉 応援コンテスト開催中！ 🎉
        </h2>
        
        <div style="background: white; border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.5rem;">
          <p style="color: #333; font-size: 1rem; line-height: 1.7; margin-bottom: 1rem;">
            チャレンジページで応援ボタンを押して、みんなで盛り上げよう！<br>
            応援回数が多い人にはりんくから特別なプレゼントがあるかも...？✨
          </p>
          
          <div style="display: flex; gap: 1rem; justify-content: center; margin-bottom: 1.5rem;">
            <a href="/cheer_dashboard.php" 
               style="background: linear-gradient(45deg, #ff6b9d, #ffd89b); color: white; padding: 1rem 2rem; border-radius: 2rem; text-decoration: none; font-weight: bold; box-shadow: 0 4px 15px rgba(255,107,157,0.3); transition: all 0.3s;"
               onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(255,107,157,0.4)';"
               onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 15px rgba(255,107,157,0.3)';">
              🎊 応援ダッシュボードへ
            </a>
          </div>
        </div>
        
        <!-- 応援ランキング -->
        <div id="cheerRanking">
          <h3 style="color: #ff6b9d; font-size: 1.1rem; font-weight: bold; margin-bottom: 1rem;">🏆 応援ランキング（このチャレンジ）</h3>
          <div style="text-align: center; color: #a855f7;">
            <div class="loading"></div>
            <p style="margin-top: 0.5rem;">応援データを読み込み中...</p>
          </div>
        </div>
      </div>
      ` : ''}
      
      <div class="mt-6 text-center">
        <button class="btn btn-primary">
          <svg width="20" height="20" fill="currentColor"><use href="#icon-share-2"></use></svg>
          SNSでシェアして仲間を増やす
        </button>
      </div>
    </div>
  `;
  
  // 日本地図を表示
  setTimeout(() => {
    displayChallengeMap(selectedChallenge.id);
  }, 100);
  
  // 貢献度ランキングを読み込み
  loadContributionRanking(selectedChallenge.id);
  
  // 応援ランキングを読み込み（ログイン時のみ）
  if (window.isLoggedIn) {
    loadCheerRanking(selectedChallenge.id);
  }
  
  // 応援ボタンの状態をチェック
  setTimeout(() => {
    if (window.isLoggedIn && window.checkCheerStatus) {
      window.checkCheerStatus();
    }
  }, 500);
}

// 貢献度ランキングを取得・表示
async function loadContributionRanking(challengeId) {
    try {
        const response = await fetch(`https://doin-challenge.com/api.php?action=getContributions&challenge_id=${challengeId}`);
        const data = await response.json();
        
        if (!data.error) {
            renderContributionRanking(data);
        }
    } catch (error) {
        console.error('Error loading contribution ranking:', error);
    }
}

// 応援ランキングを取得・表示
async function loadCheerRanking(challengeId) {
    try {
        const response = await fetch(`https://doin-challenge.com/api.php?action=getCheerRanking&challenge_id=${challengeId}`);
        const data = await response.json();
        
        if (!data.error && data.rankings) {
            renderCheerRanking(data.rankings);
        } else {
            // エラーまたはデータがない場合
            const container = document.getElementById('cheerRanking');
            if (container) {
                container.innerHTML = '<p style="text-align: center; color: #6b7280;">まだ応援データがありません</p>';
            }
        }
    } catch (error) {
        console.error('Error loading cheer ranking:', error);
        const container = document.getElementById('cheerRanking');
        if (container) {
            container.innerHTML = '<p style="text-align: center; color: #ff6666;">応援ランキングの読み込みに失敗しました</p>';
        }
    }
}

// グローバルに公開（他のファイルから使用可能にする）
window.loadCheerRanking = loadCheerRanking;

// 全体の応援ランキングを取得・表示（応援コンテスト用）
async function loadOverallCheerRanking(period = 'all') {
    try {
        const response = await fetch(`https://doin-challenge.com/api.php?action=getCheerRanking&period=${period}`);
        const data = await response.json();
        
        if (!data.error && data.rankings) {
            return data.rankings;
        } else {
            console.error('応援ランキングの取得に失敗しました');
            return [];
        }
    } catch (error) {
        console.error('Error loading overall cheer ranking:', error);
        return [];
    }
}

// グローバルに公開
window.loadOverallCheerRanking = loadOverallCheerRanking;

// 応援を送信する関数
async function sendCheer(challengeId = null) {
    try {
        const response = await fetch('https://doin-challenge.com/api.php?action=addCheer', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                challenge_id: challengeId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            return {
                success: true,
                message: data.message,
                today_count: data.today_count,
                next_cheer_time: data.next_cheer_time
            };
        } else {
            throw new Error(data.message || '応援の送信に失敗しました');
        }
    } catch (error) {
        console.error('Error sending cheer:', error);
        throw error;
    }
}

// グローバルに公開
window.sendCheer = sendCheer;

// チャレンジ別の応援データを取得
async function getCheersByChallenge(challengeId) {
    try {
        const response = await fetch(`https://doin-challenge.com/api.php?action=getCheersByChallenge&challenge_id=${challengeId}`);
        const data = await response.json();
        
        if (!data.error) {
            return data;
        } else {
            console.error('応援データの取得に失敗しました');
            return { cheers: [], today_count: 0 };
        }
    } catch (error) {
        console.error('Error getting cheers by challenge:', error);
        return { cheers: [], today_count: 0 };
    }
}

// グローバルに公開
window.getCheersByChallenge = getCheersByChallenge;

// 応援ランキングを表示
function renderCheerRanking(rankings) {
    const container = document.getElementById('cheerRanking');
    if (!container) return;
    
    if (rankings.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #6b7280;">まだ応援データがありません</p>';
        return;
    }
    
    let html = '<h3 style="color: #ff6b9d; font-size: 1.1rem; font-weight: bold; margin-bottom: 1rem;">🏆 応援ランキング（このチャレンジ）</h3>';
    
    html += rankings.slice(0, 10).map((item, index) => {
        const medalIcon = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : '';
        const isCurrentUser = window.loginUser && item.twitter_username === window.loginUser.screen_name;
        
        return `
            <div style="background: ${isCurrentUser ? 'rgba(255,107,157,0.2)' : 'rgba(255,255,255,0.05)'}; 
                        border: ${isCurrentUser ? '2px solid #ff6b9d' : 'none'}; 
                        border-radius: 0.75rem; 
                        padding: 1rem; 
                        margin-bottom: 0.75rem; 
                        display: flex; 
                        justify-content: space-between; 
                        align-items: center;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: #ff6b9d; width: 40px; text-align: center;">
                        ${medalIcon || `#${index + 1}`}
                    </div>
                    <div>
                        ${item.twitter_username ? 
                            `<img src="https://unavatar.io/twitter/${item.twitter_username}?fallback=https://ui-avatars.com/api/?name=${encodeURIComponent(item.name)}&background=ff6b9d&color=fff&size=32" 
                                  alt="${escapeHtml(item.name)}" 
                                  style="width: 32px; height: 32px; border-radius: 50%; display: inline-block; vertical-align: middle; margin-right: 0.5rem;"
                                  onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(item.name)}&background=ff6b9d&color=fff&size=32'">` :
                            `<img src="https://ui-avatars.com/api/?name=${encodeURIComponent(item.name)}&background=9ca3af&color=fff&size=32" 
                                  alt="${escapeHtml(item.name)}" 
                                  style="width: 32px; height: 32px; border-radius: 50%; display: inline-block; vertical-align: middle; margin-right: 0.5rem;">`
                        }
                        <span style="font-weight: bold; color: #fff;">${escapeHtml(item.name)}</span>
                        ${item.twitter_username ? 
                            `<a href="https://twitter.com/${item.twitter_username}" 
                               target="_blank" 
                               style="color: #ff6b9d; font-size: 0.875rem; margin-left: 0.5rem;">@${item.twitter_username}</a>` : 
                            ''}
                        ${isCurrentUser ? '<span style="background: #ff6b9d; color: white; font-size: 0.75rem; padding: 0.125rem 0.5rem; border-radius: 1rem; margin-left: 0.5rem;">あなた</span>' : ''}
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: #ff6b9d;">${item.cheer_count}回</div>
                    <div style="color: rgba(255,255,255,0.6); font-size: 0.75rem;">応援しました</div>
                </div>
            </div>
        `;
    }).join('');
    
    // 現在のユーザーがランキングに入っていない場合、自分の順位を表示
    if (window.loginUser) {
        const userRank = rankings.findIndex(item => item.twitter_username === window.loginUser.screen_name);
        if (userRank === -1 || userRank >= 10) {
            // APIから自分の応援データを取得する必要がある場合はここに追加
            html += `
                <div style="margin-top: 1rem; text-align: center;">
                    <button onclick="window.location.href='/cheer_dashboard.php'" 
                            style="background: #ff6b9d; 
                                   color: white; 
                                   padding: 0.5rem 1.5rem; 
                                   border-radius: 2rem; 
                                   border: none; 
                                   font-weight: bold; 
                                   cursor: pointer; 
                                   transition: all 0.3s;"
                            onmouseover="this.style.opacity='0.8';" 
                            onmouseout="this.style.opacity='1';">
                        もっと応援する！
                    </button>
                </div>
            `;
        }
    }
    
    container.innerHTML = html;
}

function renderContributionRanking(contributions) {
    const container = document.getElementById('contributionRanking');
    if (!container) return;
    
    if (contributions.length === 0) {
        container.innerHTML = '<p style="text-align: center; color: #6b7280;">まだランキングデータがありません</p>';
        return;
    }
    
    container.innerHTML = contributions.slice(0, 10).map((item, index) => {
        const medalIcon = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : '';
        
        return `
            <div style="background: rgba(255,255,255,0.05); border-radius: 0.75rem; padding: 1rem; margin-bottom: 0.75rem; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: #6b7280; width: 40px; text-align: center;">
                        ${medalIcon || `#${index + 1}`}
                    </div>
                    <div>
                        <div style="font-weight: bold; color: #fff;">${escapeHtml(item.name)}</div>
                        ${item.twitter_username ? 
                            `<a href="https://twitter.com/${item.twitter_username}" target="_blank" style="color: #60a5fa; font-size: 0.875rem;">@${item.twitter_username}</a>` : 
                            ''}
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: #fbbf24;">${item.total_contribution}人</div>
                    <div style="color: rgba(255,255,255,0.6); font-size: 0.75rem;">
                        参加${item.participation_count}回・友人${item.total_friends}人
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

// URL変更とHistory API関連
function navigateToChallenge(categorySlug, challengeSlug) {
    const url = `/category/${categorySlug}/${challengeSlug}/`;
    window.location.href = url;
}

// イベントハンドラー
function selectChallenge(challenge) {
  selectedChallenge = challenge;
  // 自動更新を停止
  stopAutoUpdate();
  
  // URLにパラメータを追加（ブラウザの履歴に追加）
  const params = new URLSearchParams();
  params.set('id', challenge.id);
  if (challenge.name) {
    const slug = challenge.name.toLowerCase()
      .replace(/[☆★♪！。・　]/g, '')
      .replace(/\s+/g, '-');
    params.set('name', slug);
  }
  const newUrl = `?${params.toString()}`;
  window.history.pushState({challengeId: challenge.id}, '', newUrl);
  fetchChallengeDetail(challenge.id);
}

function backToList() {
  selectedChallenge = null;
  // URLパラメータを削除してトップページに戻る
  window.history.pushState({}, '', '/');
  document.getElementById('challengeDetail').style.display = 'none';
  document.getElementById('challengeList').style.display = 'grid';
  // 一覧を再取得
  fetchChallenges();
  // 自動更新を再開
  if (isPageVisible) {
    startAutoUpdate();
  }
}

// backToCategory関数を追加
function backToCategory() {
  selectedChallenge = null;
  // カテゴリURLの場合はカテゴリページに戻る
  if (currentCategorySlug) {
    window.location.href = `/category/${currentCategorySlug}/`;
  } else {
    // 通常のトップページに戻る
    document.getElementById('challengeDetail').style.display = 'none';
    const challengeList = document.getElementById('challengeList');
    if (challengeList) {
      challengeList.style.display = 'grid';
    }
    fetchChallenges();
    // 自動更新を再開
    if (isPageVisible) {
      startAutoUpdate();
    }
  }
}

// History APIのpopstateイベントを処理
window.addEventListener('popstate', function(event) {
  const urlParams = new URLSearchParams(window.location.search);
  const challengeId = urlParams.get('id');
  if (challengeId) {
    // 詳細ページを表示
    selectedChallenge = { id: parseInt(challengeId) }; // 仮の選択状態を設定
    fetchChallengeDetail(parseInt(challengeId));
    // 自動更新を停止
    stopAutoUpdate();
  } else {
    // 一覧ページに戻る
    backToList();
  }
});

// fetchChallengeDetailBySlug関数も不要なのでコメントアウト
// async function fetchChallengeDetailBySlug(slug) {
//     try {
//         const response = await fetch(`api.php?action=getChallenge&slug=${encodeURIComponent(slug)}`);
//         const data = await response.json();
//         if (data.error) {
//             showError('チャレンジが見つかりませんでした');
//             return;
//         }
//         selectedChallenge = data;
//         renderChallengeDetail();
//     } catch (error) {
//         console.error('Error fetching challenge by slug:', error);
//         showError('サーバーへの接続に失敗しました');
//     }
// }

function showCreateModal(categorySlug) {
  // カテゴリが指定されていれば設定
  if (categorySlug) {
    currentCategorySlug = categorySlug;
  }
  
  // モーダル要素の存在確認
  const modal = document.getElementById('createModal');
  if (!modal) {
    console.error('createModal要素が見つかりません');
    // 要素が見つからない場合のデバッグ情報
    console.log('現在のDOM状態:');
    console.log('body.children:', document.body.children.length);
    console.log('モーダル関連要素:', document.querySelectorAll('[id*="modal"]').length);
    
    alert('エラーが発生しました。ページをリロードしてください。');
    return;
  }
  
  // カテゴリセレクトボックスの更新
  const categorySelect = document.getElementById('categorySelect');
  if (categorySelect && categories.length > 0) {
    categorySelect.innerHTML = categories.map(cat => 
      `<option value="${cat.id}">${cat.icon} ${cat.name}</option>`
    ).join('');
    
    // 現在のカテゴリを選択
    if (currentCategorySlug) {
      const currentCat = categories.find(c => c.slug === currentCategorySlug);
      if (currentCat) {
        categorySelect.value = currentCat.id;
      }
    }
  }
  
  // モーダルを表示
  try {
    modal.style.display = 'flex';
    console.log('createModalを表示しました');
  } catch (error) {
    console.error('モーダル表示エラー:', error);
  }
}

function hideModal() {
  // supportModalの存在確認
  const supportModal = document.getElementById('supportModal');
  if (supportModal) {
    supportModal.style.display = 'none';
  }
  
  // createModalの存在確認
  const createModal = document.getElementById('createModal');
  if (createModal) {
    createModal.style.display = 'none';
  }
  
  // フォームをリセット
  const supportForm = document.querySelector('#supportModal form');
  if (supportForm) {
    supportForm.reset();
    
    // 友人入力フィールドをクリア
    const friendsContainer = document.getElementById('friends-container');
    if (friendsContainer) {
      friendsContainer.innerHTML = '';
      friendsCount = 0;
    }
  }
}

function handleTypeChange(type) {
  const membersSection = document.getElementById('membersSection');
  if (type === 'solo') {
    membersSection.style.display = 'none';
  } else {
    membersSection.style.display = 'block';
  }
}

function addMemberInput() {
  const membersList = document.getElementById('membersList');
  const memberCount = membersList.children.length + 1;
  const input = document.createElement('input');
  input.type = 'text';
  input.name = 'member';
  input.placeholder = `メンバー${memberCount}の名前`;
  input.className = 'form-input';
  membersList.appendChild(input);
}

function handleCreateSubmit(event) {
  event.preventDefault();
  const formData = new FormData(event.target);
  
  const challengeData = {
    category_id: parseInt(formData.get('category_id')),
    type: formData.get('type'),
    name: formData.get('name'),
    members: formData.get('type') === 'group' ? 
      Array.from(formData.getAll('member')).filter(m => m.trim()) : [],
    target: parseInt(formData.get('target')),
    event_date: formData.get('event_date'),
    event_name: formData.get('event_name'),
    venue: formData.get('venue'),
    prefecture: formData.get('prefecture'),
    capacity: parseInt(formData.get('capacity')),
    color: formData.get('color'),
    ticket_url: formData.get('ticket_url'),
    ticket_sale_date: formData.get('ticket_sale_date'),
    ticket_price: formData.get('ticket_price')
  };
  
  // デバッグ用：送信データをコンソールに表示
  console.log('Sending challenge data:', challengeData);
  
  createChallenge(challengeData);
}

async function handleSupporterSubmit(event) {
    event.preventDefault();
    
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Twitter入力チェック
    const twitterInput = document.getElementById('twitter_username');
    if (!twitterInput.value || !twitterInput.value.trim()) {
        showError('Twitterユーザー名は必須です。@なしで入力してください。');
        twitterInput.focus();
        return;
    }
    
    // 既に他のチャレンジに参加しているかチェック
    const existingChallenge = await checkExistingParticipation();
    if (existingChallenge && existingChallenge.id !== selectedChallenge.id) {
        showError(`既に「${existingChallenge.name}」のチャレンジに参加中です。\n参加は1つのチャレンジのみ可能です。`);
        return;
    }
    
    // ボタンを無効化
    submitBtn.disabled = true;
    submitBtn.textContent = '送信中...';
    
    // 友人情報を収集
    const friendsList = [];
    document.querySelectorAll('.friend-input-group').forEach(group => {
        const nameInput = group.querySelector('.friend-name-input');
        const twitterInput = group.querySelector('.friend-twitter-input');
        
        if (nameInput && nameInput.value.trim()) {
            friendsList.push({
                name: nameInput.value.trim(),
                twitter_username: twitterInput ? twitterInput.value.trim() : ''
            });
        }
    });
    
    const data = {
        challenge_id: selectedChallenge.id,
        name: document.getElementById('userName').value,
        twitter_username: document.getElementById('twitter_username').value.trim(),
        prefecture: document.getElementById('userPrefecture').value,
        message: document.getElementById('userMessage').value,
        friends: parseInt(document.getElementById('friendCount').value) || 0,
        friends_list: friendsList,  // 友人詳細情報を追加
        edit_password: document.getElementById('editPassword').value  // 編集パスワード追加
    };
    
    try {
        const response = await fetch('https://doin-challenge.com/api.php?action=addSupporter', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // 成功メッセージ
            showSuccess('参加表明ありがとうございます！');
            
            // フォームをリセット
            form.reset();
            document.getElementById('friends-container').innerHTML = '';
            friendsCount = 0;
            
            // データを再取得
            await fetchChallengeDetail(selectedChallenge.id);
        } else {
            throw new Error(result.message || '送信に失敗しました');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('エラーが発生しました。もう一度お試しください。');
    } finally {
        // ボタンを有効化
        submitBtn.disabled = false;
        submitBtn.textContent = '参加表明する！';
    }
}

function setTypeFilter(type) {
  typeFilter = type;
  // ボタンのアクティブ状態を更新
  document.querySelectorAll('[data-type-filter]').forEach(btn => {
    btn.classList.remove('btn-primary');
    btn.classList.add('btn-secondary');
  });
  document.querySelector(`[data-type-filter="${type}"]`).classList.remove('btn-secondary');
  document.querySelector(`[data-type-filter="${type}"]`).classList.add('btn-primary');
  
  renderChallengeList();
}

function setRegionFilter(region) {
  regionFilter = region;
  renderChallengeList();
}

// メッセージ表示
function showError(message) {
  const alertDiv = document.createElement('div');
  alertDiv.className = 'error-message';
  alertDiv.textContent = message;
  document.getElementById('alerts').appendChild(alertDiv);
  setTimeout(() => alertDiv.remove(), 5000);
}

function showSuccess(message) {
  const alertDiv = document.createElement('div');
  alertDiv.className = 'success-message';
  alertDiv.textContent = message;
  document.getElementById('alerts').appendChild(alertDiv);
  setTimeout(() => alertDiv.remove(), 5000);
}

// カテゴリ一覧を表示する関数（改善版）
function renderCategories() {
  const container = document.getElementById('categoryList');
  if (!container || !categories.length) return;
  
  container.innerHTML = categories.map((category, index) => {
    // ライブ動員を強調表示
    const isLive = category.slug === 'live';
    const cardClass = isLive ? 'category-card featured' : 'category-card';
    const badgeHtml = isLive ? '<span class="featured-badge">人気No.1 🔥</span>' : '';
    const hasNewChallenge = category.challenge_count > 0 && index < 3 ? '<span class="new-indicator">NEW!</span>' : '';
    
    // アニメーション遅延
    const animationDelay = index * 0.1;
    
    return `
      <div class="${cardClass}" 
           data-color="${category.color || 'purple'}"
           onclick="navigateToCategory('${category.slug}')" 
           style="cursor: pointer; animation-delay: ${animationDelay}s;">
        ${badgeHtml}
        ${hasNewChallenge}
        <div class="category-icon">${category.icon || '📌'}</div>
        <h3 class="category-name">${category.name}</h3>
        <p class="category-count">
          ${category.challenge_count || 0}件のチャレンジ
        </p>
      </div>
    `;
  }).join('');
}

// カテゴリページへ遷移
function navigateToCategory(slug) {
  window.location.href = `/category/${slug}/`;
}

// 自動更新管理用変数
let isPageVisible = true;

// 自動更新の開始
function startAutoUpdate() {
  if (!autoUpdateInterval && isPageVisible && !selectedChallenge) {
    autoUpdateInterval = setInterval(() => {
      fetchChallenges();
      // 日本地図も更新（ホームページのみ）
      if (!window.initialSlug && !window.currentCategorySlug && document.getElementById('heatmapSection')) {
        loadPrefectureStats();
      }
    }, 5000);
  }
}

// 自動更新の停止
function stopAutoUpdate() {
  if (autoUpdateInterval) {
    clearInterval(autoUpdateInterval);
    autoUpdateInterval = null;
  }
}

// ページ表示状態の監視
document.addEventListener('visibilitychange', () => {
  isPageVisible = !document.hidden;
  if (isPageVisible && !selectedChallenge) {
    startAutoUpdate();
  } else {
    stopAutoUpdate();
  }
});

// ========== 日本地図ヒートマップ機能 ==========

// 地域別の都道府県定義（地図用）
const mapRegions = {
  'hokkaido-tohoku': ['北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県'],
  'kanto': ['茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県'],
  'chubu': ['新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県', '静岡県', '愛知県'],
  'kansai': ['三重県', '滋賀県', '京都府', '大阪府', '兵庫県', '奈良県', '和歌山県'],
  'chugoku-shikoku': ['鳥取県', '島根県', '岡山県', '広島県', '山口県', '徳島県', '香川県', '愛媛県', '高知県'],
  'kyushu-okinawa': ['福岡県', '佐賀県', '長崎県', '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県']
};

// 全都道府県リスト
const allPrefectures = Object.values(mapRegions).flat();

// 都道府県統計データ
let prefectureStats = {};

// ヒートレベルを取得
function getHeatLevel(count) {
  if (count === 0) return 0;
  if (count <= 5) return 1;
  if (count <= 20) return 2;
  if (count <= 50) return 3;
  if (count <= 100) return 4;
  if (count <= 200) return 5;
  return 6;
}

// 都道府県別統計データを取得
async function loadPrefectureStats() {
  try {
    // タイムアウト設定
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 10000); // 10秒タイムアウト
    
    const response = await fetch('https://doin-challenge.com/api.php?action=getPrefectureStats', {
      signal: controller.signal
    });
    clearTimeout(timeoutId);
    
    const data = await response.json();
    
    if (data.error) {
      console.error('Error:', data.message);
      return;
    }
    
    prefectureStats = data.stats || {};
    
    // データが無い都道府県も0で初期化
    allPrefectures.forEach(pref => {
      if (!prefectureStats[pref]) {
        prefectureStats[pref] = {
          total_participants: 0,
          supporter_count: 0,
          total_friends: 0
        };
      }
    });
    
    displayMap();
    displayRanking();
    updateTotals(data);
    
    // ローディングを非表示
    const loading = document.getElementById('heatmapLoading');
    const content = document.getElementById('heatmapContent');
    if (loading && content) {
      loading.style.display = 'none';
      content.style.display = 'block';
    }
    
  } catch (error) {
    console.error('Error:', error);
    const loading = document.getElementById('heatmapLoading');
    if (loading) {
      if (error.name === 'AbortError') {
        loading.innerHTML = '<p style="color: #ff6666;">接続がタイムアウトしました</p>';
      } else {
        loading.innerHTML = '<p style="color: #ff6666;">通信エラーが発生しました</p>';
      }
    }
    // コンテンツエリアも非表示のままにしてエラーメッセージを表示
    const content = document.getElementById('heatmapContent');
    if (content) {
      content.style.display = 'none';
    }
  }
}

// 地図を表示（TOP3アイコン追加）
function displayMap() {
  Object.entries(mapRegions).forEach(([regionId, prefectures]) => {
    const container = document.getElementById(`region-${regionId}`);
    if (!container) return;
    
    container.innerHTML = '';
    
    prefectures.forEach((pref, index) => {
      const stat = prefectureStats[pref] || { total_participants: 0 };
      const heatLevel = getHeatLevel(stat.total_participants);
      
      const prefDiv = document.createElement('div');
      prefDiv.className = `prefecture-box heat-${heatLevel}`;
      
      // TOP3かどうかチェック
      const isTop3 = window.top3Prefectures && window.top3Prefectures.includes(pref);
      const rank = isTop3 ? window.top3Prefectures.indexOf(pref) : -1;
      const medalIcon = rank === 0 ? '🥇' : rank === 1 ? '🥈' : rank === 2 ? '🥉' : '';
      
      prefDiv.innerHTML = `
        ${medalIcon ? `<div class="medal-icon">${medalIcon}</div>` : ''}
        <div class="prefecture-name">${pref.replace('県', '').replace('府', '').replace('都', '')}</div>
        <div class="prefecture-count">${stat.total_participants}人</div>
      `;
      
      // TOP3にはパルスアニメーション追加
      if (isTop3) {
        prefDiv.classList.add('top3-prefecture');
        prefDiv.style.animationDelay = `${rank * 0.2}s`;
      }
      
      // ツールチップ設定（詳細情報追加）
      prefDiv.addEventListener('mouseenter', (e) => {
        const tooltip = document.getElementById('heatmap-tooltip');
        if (!tooltip) {
          const newTooltip = document.createElement('div');
          newTooltip.id = 'heatmap-tooltip';
          document.body.appendChild(newTooltip);
        }
        const tooltipEl = document.getElementById('heatmap-tooltip');
        
        // 参加率を計算
        const participationRate = ((stat.supporter_count / (stat.total_participants || 1)) * 100).toFixed(1);
        const friendRate = ((stat.total_friends / (stat.total_participants || 1)) * 100).toFixed(1);
        
        tooltipEl.innerHTML = `
          <strong>${pref} ${medalIcon}</strong><br>
          <div style="margin-top: 5px;">
            参加者: <strong>${stat.total_participants}人</strong><br>
            本人: ${stat.supporter_count}人 (${participationRate}%)<br>
            友人: ${stat.total_friends}人 (${friendRate}%)
          </div>
          ${stat.total_participants > 0 ? '<div style="margin-top: 5px; font-size: 11px; opacity: 0.8;">クリックで詳細を表示</div>' : ''}
        `;
        tooltipEl.style.display = 'block';
        
        // エフェクト追加
        prefDiv.style.transform = 'scale(1.1)';
      });
      
      prefDiv.addEventListener('mousemove', (e) => {
        const tooltip = document.getElementById('heatmap-tooltip');
        if (tooltip) {
          tooltip.style.left = e.pageX + 10 + 'px';
          tooltip.style.top = e.pageY - 40 + 'px';
        }
      });
      
      prefDiv.addEventListener('mouseleave', () => {
        const tooltip = document.getElementById('heatmap-tooltip');
        if (tooltip) {
          tooltip.style.display = 'none';
        }
        // エフェクト解除
        prefDiv.style.transform = 'scale(1)';
      });
      
      // クリックイベント（詳細モーダル表示）
      if (stat.total_participants > 0) {
        prefDiv.style.cursor = 'pointer';
        prefDiv.addEventListener('click', () => {
          showPrefectureDetail(pref);
        });
      }
      
      container.appendChild(prefDiv);
    });
  });
}

// ランキングを表示（TOP3にアイコン追加）
function displayRanking() {
  const ranking = Object.entries(prefectureStats)
    .filter(([pref, stat]) => stat.total_participants > 0)
    .sort((a, b) => b[1].total_participants - a[1].total_participants)
    .slice(0, 10);
    
  const rankingContainer = document.getElementById('prefectureRanking');
  if (!rankingContainer) return;
  
  rankingContainer.innerHTML = '';
  
  if (ranking.length === 0) {
    rankingContainer.innerHTML = '<div style="text-align: center; opacity: 0.6;">まだ参加表明がありません</div>';
    return;
  }
  
  // TOP3の都道府県を記録（地図にアイコン表示用）
  window.top3Prefectures = ranking.slice(0, 3).map(([pref]) => pref);
  
  ranking.forEach(([pref, stat], index) => {
    const heatLevel = getHeatLevel(stat.total_participants);
    const rankDiv = document.createElement('div');
    rankDiv.className = `stat-item heat-${heatLevel}`;
    
    // TOP3にはアイコンを追加
    const rankIcon = index === 0 ? '🥇' : index === 1 ? '🥈' : index === 2 ? '🥉' : '';
    
    rankDiv.innerHTML = `
      <div style="display: flex; align-items: center;">
        <span class="stat-rank">
          ${rankIcon ? `<span style="font-size: 28px;">${rankIcon}</span>` : index + 1}
        </span>
        <div>
          <div style="font-weight: bold;">${pref}</div>
          <div style="font-size: 11px; opacity: 0.8;">
            本人${stat.supporter_count} + 友人${stat.total_friends}
          </div>
        </div>
      </div>
      <div style="font-size: 20px; font-weight: bold;">
        ${stat.total_participants}人
      </div>
    `;
    
    // アニメーション追加
    rankDiv.style.animation = `fadeInUp 0.5s ${index * 0.1}s both`;
    
    rankingContainer.appendChild(rankDiv);
  });
}

// 総計を更新
function updateTotals(data) {
  const totalCount = document.getElementById('totalCountMap');
  const prefectureCount = document.getElementById('prefectureCountMap');
  
  if (totalCount) {
    totalCount.textContent = data.total || 0;
  }
  if (prefectureCount) {
    prefectureCount.textContent = `${data.prefectures_with_data || 0}/47`;
  }
}

// 編集機能用の関数
function showEditSupporter(supporterId) {
  const editForm = document.getElementById(`editSupporterForm-${supporterId}`);
  if (editForm) {
    editForm.style.display = 'block';
  }
}

function hideEditSupporter(supporterId) {
  const editForm = document.getElementById(`editSupporterForm-${supporterId}`);
  if (editForm) {
    editForm.style.display = 'none';
  }
  // 削除確認も非表示
  hideDeleteConfirm(supporterId);
}

// 削除確認フォームの表示・非表示
function showDeleteConfirm(supporterId) {
  const deleteForm = document.getElementById(`deleteConfirm-${supporterId}`);
  if (deleteForm) {
    deleteForm.style.display = 'block';
  }
}

function hideDeleteConfirm(supporterId) {
  const deleteForm = document.getElementById(`deleteConfirm-${supporterId}`);
  if (deleteForm) {
    deleteForm.style.display = 'none';
  }
}

async function handleEditSupporter(event, supporterId) {
  event.preventDefault();
  
  const form = event.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  
  // ボタンを無効化
  submitBtn.disabled = true;
  submitBtn.textContent = '更新中...';
  
  const data = {
    supporter_id: supporterId,
    prefecture: document.getElementById(`editPrefecture-${supporterId}`).value,
    message: document.getElementById(`editMessage-${supporterId}`).value,
    friends: parseInt(document.getElementById(`editFriendCount-${supporterId}`).value) || 0,
    edit_password: document.getElementById(`editPassword-${supporterId}`).value
  };
  
  try {
    const response = await fetch('https://doin-challenge.com/api.php?action=updateSupporter', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    
    const result = await response.json();
    
    if (result.success) {
      showSuccess('参加表明を更新しました！');
      
      // 編集フォームを非表示
      hideEditSupporter(supporterId);
      
      // データを再取得して表示を更新
      await fetchChallengeDetail(selectedChallenge.id);
    } else {
      throw new Error(result.message || '更新に失敗しました');
    }
  } catch (error) {
    console.error('Error:', error);
    showError('エラーが発生しました。もう一度お試しください。');
  } finally {
    // ボタンを有効化
    submitBtn.disabled = false;
    submitBtn.textContent = '保存';
  }
}

// 削除処理
async function handleDeleteSupporter(event, supporterId) {
  event.preventDefault();
  
  const form = event.target;
  const submitBtn = form.querySelector('button[type="submit"]');
  
  // ボタンを無効化
  submitBtn.disabled = true;
  submitBtn.textContent = '削除中...';
  
  const data = {
    supporter_id: supporterId,
    delete_password: document.getElementById(`deletePassword-${supporterId}`).value
  };
  
  try {
    const response = await fetch('https://doin-challenge.com/api.php?action=deleteSupporter', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });
    
    const result = await response.json();
    
    if (result.success) {
      showSuccess('参加表明を削除しました');
      
      // 詳細を再取得して表示を更新
      await fetchChallengeDetail(selectedChallenge.id);
    } else {
      throw new Error(result.message || '削除に失敗しました');
    }
  } catch (error) {
    console.error('Error:', error);
    showError('エラーが発生しました。もう一度お試しください。');
  } finally {
    // ボタンを有効化
    submitBtn.disabled = false;
    submitBtn.textContent = '削除実行';
  }
}

// 初期化
document.addEventListener('DOMContentLoaded', async function() {
  try {
    renderBreadcrumb(); // パンくずリスト初期表示
    
    // カテゴリ一覧を取得して表示
    fetchCategories().then(() => {
      renderCategories();
    }).catch(error => {
      console.error('カテゴリの取得に失敗:', error);
    });
    
    // 日本地図ヒートマップを表示（ホームページのみ）
    if (!window.initialSlug && !window.currentCategorySlug) {
      loadPrefectureStats();
    }
    
    // URLパラメータを取得
    const urlParams = new URLSearchParams(window.location.search);
    const challengeId = urlParams.get('id');
    if (challengeId) {
      // チャレンジIDがあれば詳細を表示（自動更新なし）
      await fetchChallengeDetail(parseInt(challengeId));
      stopAutoUpdate();
    } else {
      // なければ一覧を表示
      await fetchChallenges();
      // ページが表示中の時のみ自動更新
      if (isPageVisible) {
        startAutoUpdate();
      }
    }
  } catch (error) {
    console.error('初期化エラー:', error);
    // エラーが発生してもローディング表示をクリア
    const challengeList = document.getElementById('challengeList');
    if (challengeList && challengeList.innerHTML.includes('読み込み中')) {
      challengeList.innerHTML = `
        <div class="text-center text-gray-500" style="grid-column: 1 / -1;">
          <p class="mt-4">初期化エラーが発生しました</p>
          <button onclick="location.reload()" class="mt-4 px-4 py-2 bg-primary text-white rounded-lg hover:bg-opacity-80">
            ページを再読み込み
          </button>
        </div>
      `;
    }
  }
  
  // ログイン済みの場合、ダッシュボードを表示
  if (window.isLoggedIn && window.loginUser) {
    console.log('ログイン中:', window.loginUser.screen_name);
    await loadMyDashboard();
  }
  
  // ページ離脱時に自動更新を停止
  window.addEventListener('beforeunload', () => {
    stopAutoUpdate();
    // 応援タイマーもクリア
    if (cheerActionTimer) clearInterval(cheerActionTimer);
  });
});

// 応援タイマー機能（グローバル）
let cheerActionTimer = null;
let nextCheerTime = 0;

async function checkCheerStatus() {
    if (!selectedChallenge) return;
    
    const url = selectedChallenge.ticket_url || window.location.href;
    
    try {
        const response = await fetch(`/api.php?action=cheer_status&url=${encodeURIComponent(url)}`);
        const data = await response.json();
        
        const button = document.getElementById('cheerActionButton');
        const statusInfo = document.getElementById('cheerStatusInfo');
        const timerInfo = document.getElementById('cheerTimerInfo');
        
        if (!button) return;
        
        if (data.error) {
            button.textContent = '🔄 ログインが必要です';
            button.disabled = true;
            if (statusInfo) statusInfo.textContent = '';
            if (timerInfo) timerInfo.textContent = '';
            return;
        }
        
        if (data.can_vote) {
            button.innerHTML = '<span style="font-size: 1.5rem;">🗳️</span> 投票する！ <span style="font-size: 1.5rem;">🗳️</span>';
            button.disabled = false;
            button.style.background = 'linear-gradient(45deg, #ff6b9d, #ffd89b)';
            if (statusInfo) statusInfo.textContent = '61分投票が可能です！';
            if (timerInfo) timerInfo.textContent = '';
        } else if (data.can_view) {
            button.innerHTML = '<span style="font-size: 1.5rem;">👁️</span> 閲覧する！ <span style="font-size: 1.5rem;">👁️</span>';
            button.disabled = false;
            button.style.background = 'linear-gradient(45deg, #60a5fa, #06b6d4)';
            if (statusInfo) statusInfo.textContent = '30分閲覧が可能です！';
            if (timerInfo) timerInfo.textContent = '';
        } else {
            button.disabled = true;
            button.style.background = '#6b7280';
            
            const action = data.next_action === 'vote' ? '投票' : '閲覧';
            const minutes = Math.ceil(data.wait_time / 60);
            const seconds = data.wait_time % 60;
            
            button.innerHTML = `⏳ 次の${action}まで待機中 ⏳`;
            if (statusInfo) statusInfo.textContent = `あと ${minutes}分${seconds > 0 ? seconds + '秒' : ''}`;
            if (timerInfo) timerInfo.textContent = '少々お待ちください...';
            
            // タイマー更新
            nextCheerTime = Date.now() + (data.wait_time * 1000);
            startCheerTimer();
        }
        
    } catch (error) {
        console.error('Status check error:', error);
        const statusInfo = document.getElementById('cheerStatusInfo');
        if (statusInfo) statusInfo.textContent = 'エラーが発生しました';
    }
}

function startCheerTimer() {
    if (cheerActionTimer) clearInterval(cheerActionTimer);
    
    cheerActionTimer = setInterval(() => {
        const now = Date.now();
        const remaining = Math.max(0, nextCheerTime - now);
        
        if (remaining === 0) {
            clearInterval(cheerActionTimer);
            checkCheerStatus();
        } else {
            const minutes = Math.floor(remaining / 60000);
            const seconds = Math.floor((remaining % 60000) / 1000);
            const statusInfo = document.getElementById('cheerStatusInfo');
            if (statusInfo) statusInfo.textContent = `あと ${minutes}分${seconds}秒`;
        }
    }, 1000);
}

async function handleCheerAction() {
    const button = document.getElementById('cheerActionButton');
    if (!button || button.disabled) return;
    
    const url = selectedChallenge.ticket_url || window.location.href;
    
    // アクションタイプを判定
    let action = null;
    if (button.textContent.includes('投票')) {
        action = 'vote';
    } else if (button.textContent.includes('閲覧')) {
        action = 'view';
    }
    
    if (!action) {
        checkCheerStatus();
        return;
    }
    
    button.disabled = true;
    const originalHTML = button.innerHTML;
    button.textContent = '送信中...';
    
    try {
        const response = await fetch('/api.php?action=perform_cheer', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ url, action })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // 成功メッセージ
            button.style.background = '#10b981';
            button.innerHTML = '<span style="font-size: 1.5rem;">✅</span> ' + data.message + ' <span style="font-size: 1.5rem;">✅</span>';
            const statusInfo = document.getElementById('cheerStatusInfo');
            if (statusInfo) statusInfo.textContent = '成功しました！';
            
            // 2秒後に状態を再チェック
            setTimeout(() => {
                checkCheerStatus();
            }, 2000);
        } else {
            throw new Error(data.message || 'エラーが発生しました');
        }
        
    } catch (error) {
        console.error('Action error:', error);
        alert(error.message || 'エラーが発生しました');
        button.disabled = false;
        button.innerHTML = originalHTML;
    }
}

// グローバルに公開
window.checkCheerStatus = checkCheerStatus;
window.handleCheerAction = handleCheerAction;

// 友人入力フィールドを追加する関数
function addFriendField() {
    if (friendsCount >= maxFriends) {
        alert(`最大${maxFriends}人まで追加できます`);
        return;
    }
    
    friendsCount++;
    const friendsContainer = document.getElementById('friends-container');
    
    const friendDiv = document.createElement('div');
    friendDiv.className = 'friend-input-group';
    friendDiv.id = `friend-${friendsCount}`;
    friendDiv.innerHTML = `
        <div class="input-row">
            <input type="text" 
                   name="friends[${friendsCount}][name]" 
                   placeholder="友人の名前" 
                   class="friend-name-input">
            <input type="text" 
                   name="friends[${friendsCount}][twitter]" 
                   placeholder="@なしでユーザー名（任意）" 
                   class="friend-twitter-input">
            <button type="button" 
                    onclick="removeFriendField(${friendsCount})" 
                    class="remove-friend-btn">
                <svg class="icon icon-x"><use xlink:href="#icon-x"></use></svg>
            </button>
        </div>
    `;
    
    friendsContainer.appendChild(friendDiv);
}

// 友人入力フィールドを削除する関数
function removeFriendField(id) {
    const friendDiv = document.getElementById(`friend-${id}`);
    if (friendDiv) {
        friendDiv.remove();
    }
}

// 友人情報を表示する関数
function displayFriends(friends) {
    if (!friends || friends.length === 0) {
        return '';
    }
    
    let html = `
        <div class="friends-list">
            <p class="friends-label">一緒に参加する友人：</p>
            <div class="friends-grid">
    `;
    
    friends.forEach(friend => {
        html += `
            <div class="friend-chip">
                ${friend.twitter_username ? 
                    `<img src="https://unavatar.io/twitter/${friend.twitter_username}?fallback=https://ui-avatars.com/api/?name=${encodeURIComponent(friend.name)}&background=9ca3af&color=fff&size=24" 
                          alt="${friend.name}" 
                          class="friend-avatar"
                          onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(friend.name)}&background=9ca3af&color=fff&size=24'">` :
                    `<img src="https://ui-avatars.com/api/?name=${encodeURIComponent(friend.name)}&background=9ca3af&color=fff&size=24" 
                          alt="${friend.name}" 
                          class="friend-avatar">`
                }
                <span class="friend-name">${escapeHtml(friend.name)}</span>
                ${friend.twitter_username ? 
                    `<a href="https://twitter.com/${friend.twitter_username}" 
                       target="_blank" 
                       class="friend-twitter">@${friend.twitter_username}</a>` : 
                    ''}
            </div>
        `;
    });
    
    html += `
            </div>
        </div>
    `;
    
    return html;
}

function displaySupporters(supporters) {
    const supportersHtml = supporters.map(supporter => {
        // 既存のサポーター表示部分を以下に置き換え
        return `
            <div class="message-card">
                <div class="supporter-header">
                    ${supporter.twitter_username ? 
                        `<img src="https://unavatar.io/twitter/${supporter.twitter_username}?fallback=https://ui-avatars.com/api/?name=${encodeURIComponent(supporter.name)}&background=60a5fa&color=fff&size=40" 
                              alt="${escapeHtml(supporter.name)}" 
                              class="supporter-avatar"
                              onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(supporter.name)}&background=60a5fa&color=fff&size=40'">` :
                        `<img src="https://ui-avatars.com/api/?name=${encodeURIComponent(supporter.name)}&background=9ca3af&color=fff&size=40" 
                              alt="${escapeHtml(supporter.name)}" 
                              class="supporter-avatar">`
                    }
                    <div class="supporter-info">
                        <strong>${escapeHtml(supporter.name)}</strong>
                        ${supporter.twitter_username ? 
                            `<a href="https://twitter.com/${supporter.twitter_username}" 
                               target="_blank" 
                               class="twitter-link">@${supporter.twitter_username}</a>` : 
                            ''}
                    </div>
                </div>
                ${supporter.message ? `<p>${escapeHtml(supporter.message)}</p>` : ''}
                ${displayFriends(supporter.friends_list)}
            </div>
        `;
    }).join('');
    
    return supportersHtml;
}

// ========== ハンバーガーメニュー JavaScript ==========
function toggleMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    if (mobileMenu) {
        mobileMenu.classList.toggle('active');
        if (mobileMenu.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }
    }
}

function closeMobileMenu() {
    const mobileMenu = document.getElementById('mobileMenu');
    if (mobileMenu) {
        mobileMenu.classList.remove('active');
        document.body.style.overflow = '';
    }
}

document.addEventListener('click', function(event) {
    const mobileMenu = document.getElementById('mobileMenu');
    const hamburgerBtn = document.querySelector('.mobile-menu-toggle');
    if (mobileMenu && mobileMenu.classList.contains('active')) {
        if (!mobileMenu.contains(event.target) && !hamburgerBtn.contains(event.target)) {
            closeMobileMenu();
        }
    }
});

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeMobileMenu();
    }
});

// ========== パンくずリスト機能 ==========
function renderBreadcrumb() {
    const breadcrumbContainer = document.getElementById('breadcrumb');
    if (!breadcrumbContainer) return;
    const urlParams = new URLSearchParams(window.location.search);
    const currentCategory = urlParams.get('category');
    const challengeId = urlParams.get('id');
    let breadcrumbHTML = '<a href="/" class="breadcrumb-item">ホーム</a>';
    if (currentCategory) {
        const categoryName = getCategoryDisplayName(currentCategory);
        if (challengeId) {
            breadcrumbHTML += `<span class="breadcrumb-separator">›</span><a href="/?category=${currentCategory}" class="breadcrumb-item">${categoryName}</a><span class="breadcrumb-separator">›</span><span class="breadcrumb-current">詳細</span>`;
        } else {
            breadcrumbHTML += `<span class="breadcrumb-separator">›</span><span class="breadcrumb-current">${categoryName}</span>`;
        }
    } else if (challengeId) {
        breadcrumbHTML += `<span class="breadcrumb-separator">›</span><span class="breadcrumb-current">詳細</span>`;
    }
    breadcrumbContainer.innerHTML = breadcrumbHTML;
}

function getCategoryDisplayName(categorySlug) {
    const categoryNames = {
        'live': 'ライブ動員',
        'like': 'いいね動員', 
        'follower': 'フォロワー動員',
        'repost': 'リポスト動員',
        'view': '再生回数動員',
        'comment': 'コメント動員',
        'subscribe': 'チャンネル登録動員',
        'retweet': 'リツイート動員',
        'share': 'シェア動員',
        'other': 'その他'
    };
    return categoryNames[categorySlug] || 'その他';
}

// ========== 都道府県クリック機能 ==========

// 都道府県詳細を表示
async function showPrefectureDetail(prefecture) {
    try {
        // 都道府県詳細データを取得
        const response = await fetch(`https://doin-challenge.com/api.php?action=getPrefectureDetails&prefecture=${encodeURIComponent(prefecture)}`);
        const data = await response.json();
        
        if (data.error) {
            showError('データの取得に失敗しました');
            return;
        }
        
        // モーダルタイトル更新
        document.getElementById('prefectureModalTitle').textContent = `${prefecture}の参加者詳細`;
        
        // 参加者フロー分析表示
        const flowContent = document.getElementById('flowAnalysisContent');
        flowContent.innerHTML = '';
        
        if (data.participationFlow && data.participationFlow.length > 0) {
            data.participationFlow.forEach(flow => {
                const flowDiv = document.createElement('div');
                flowDiv.className = 'bg-gray-800 rounded-lg p-4 mb-3';
                flowDiv.innerHTML = `
                    <div class="flex justify-between items-center">
                        <div>
                            <h4 class="font-bold">${flow.eventName}</h4>
                            <p class="text-sm text-gray-400">${flow.eventPrefecture}で開催</p>
                        </div>
                        <div class="text-right">
                            <div class="text-2xl font-bold text-purple-400">${flow.supporterCount}人</div>
                            <div class="text-sm text-gray-400">${flow.percentage.toFixed(1)}%</div>
                        </div>
                    </div>
                `;
                flowContent.appendChild(flowDiv);
            });
        } else {
            flowContent.innerHTML = '<p class="text-gray-500 text-center">まだ参加データがありません</p>';
        }
        
        // ライブ開催提案
        const suggestionText = document.getElementById('suggestionText');
        suggestionText.textContent = data.suggestion || `${prefecture}からの参加者が多い場合、${prefecture}でのライブ開催を検討することをおすすめします。`;
        
        // 貢献度ランキングを取得して表示
        await loadContributorRanking(prefecture);
        
        // モーダルを表示
        document.getElementById('prefectureModal').style.display = 'flex';
        
    } catch (error) {
        console.error('Error:', error);
        showError('通信エラーが発生しました');
    }
}

// 都道府県モーダルを閉じる
function closePrefectureModal() {
    document.getElementById('prefectureModal').style.display = 'none';
}

// 貢献度ランキングを取得
async function loadContributorRanking(prefecture) {
    try {
        const response = await fetch(`https://doin-challenge.com/api.php?action=getContributorRanking&prefecture=${encodeURIComponent(prefecture)}`);
        const data = await response.json();
        
        if (data.error) {
            console.error('Error:', data.message);
            return;
        }
        
        // デフォルトで総合ランキングを表示
        switchRankingTab('overall', data);
        
        // ランキングデータを保存（タブ切り替え用）
        window.contributorRankingData = data;
        
    } catch (error) {
        console.error('Error:', error);
    }
}

// ランキングタブ切り替え
function switchRankingTab(type, data = null) {
    // データがなければ保存済みのものを使用
    const rankingData = data || window.contributorRankingData;
    if (!rankingData) return;
    
    // タブのアクティブ状態を更新
    document.querySelectorAll('.ranking-tab').forEach(btn => {
        if (btn.getAttribute('data-tab') === type) {
            btn.classList.remove('btn-secondary');
            btn.classList.add('btn-primary');
        } else {
            btn.classList.remove('btn-primary');
            btn.classList.add('btn-secondary');
        }
    });
    
    // コンテンツを更新
    const content = document.getElementById('rankingContent');
    content.innerHTML = '';
    
    let rankings = [];
    switch (type) {
        case 'participation':
            rankings = rankingData.participationRanking || [];
            break;
        case 'friends':
            rankings = rankingData.friendsRanking || [];
            break;
        case 'overall':
            rankings = rankingData.overallRanking || [];
            break;
    }
    
    if (rankings.length === 0) {
        content.innerHTML = '<p class="text-gray-500 text-center py-4">まだランキングデータがありません</p>';
        return;
    }
    
    rankings.forEach((item, index) => {
        const rankDiv = document.createElement('div');
        rankDiv.className = 'bg-gray-800 rounded-lg p-4 mb-3 flex justify-between items-center';
        
        let rightContent = '';
        switch (type) {
            case 'participation':
                rightContent = `<span class="text-xl font-bold">${item.participationCount}回</span>`;
                break;
            case 'friends':
                rightContent = `<span class="text-xl font-bold">${item.totalFriends}人</span>`;
                break;
            case 'overall':
                rightContent = `
                    <div class="text-right">
                        <span class="text-xl font-bold text-yellow-400">${item.score}pt</span>
                        <p class="text-xs text-gray-400">参加${item.participations}回 友人${item.friends}人</p>
                    </div>
                `;
                break;
        }
        
        rankDiv.innerHTML = `
            <div class="flex items-center gap-3">
                <span class="text-2xl font-bold text-gray-500">#${index + 1}</span>
                <div>
                    ${item.twitter_username ? 
                        `<img src="https://unavatar.io/twitter/${item.twitter_username}?fallback=https://ui-avatars.com/api/?name=${encodeURIComponent(item.name)}&background=60a5fa&color=fff&size=32" 
                              alt="${item.name}" 
                              class="w-8 h-8 rounded-full inline-block mr-2"
                              onerror="this.src='https://ui-avatars.com/api/?name=${encodeURIComponent(item.name)}&background=60a5fa&color=fff&size=32'">` :
                        `<img src="https://ui-avatars.com/api/?name=${encodeURIComponent(item.name)}&background=9ca3af&color=fff&size=32" 
                              alt="${item.name}" 
                              class="w-8 h-8 rounded-full inline-block mr-2">`
                    }
                    <span class="font-bold">${escapeHtml(item.name)}</span>
                    ${item.twitter_username ? 
                        `<a href="https://twitter.com/${item.twitter_username}" 
                           target="_blank" 
                           class="text-blue-400 text-sm ml-1">@${item.twitter_username}</a>` : 
                        ''}
                </div>
            </div>
            ${rightContent}
        `;
        
        content.appendChild(rankDiv);
    });
}

// ========== Twitter認証対応 - 参加表明フォーム拡張 ==========
// ※2025-06-29追加

// Twitter認証情報を格納するグローバル変数
let twitterAuth = {
    isLoggedIn: false,
    isFollowing: false,
    userId: null,
    username: null,
    name: null,
    avatar: null,
    followers: 0
};

/**
 * Twitter認証状態をチェック
 */
async function checkTwitterAuth() {
    try {
        const response = await fetch('https://doin-challenge.com/twitter/check_auth.php');
        const data = await response.json();
        
        if (data.isLoggedIn) {
            twitterAuth = data;
            updateTwitterUI();
        }
        
        return data.isLoggedIn;
    } catch (error) {
        console.error('Twitter認証チェックエラー:', error);
        return false;
    }
}

/**
 * Twitter認証UIを更新
 */
function updateTwitterUI() {
    // 参加表明フォームの表示を更新
    const supporterForm = document.querySelector('#supporterForm');
    if (!supporterForm) return;
    
    if (twitterAuth.isLoggedIn && twitterAuth.isFollowing) {
        // 認証済み＆フォロー済み
        showAuthenticatedForm();
    } else if (twitterAuth.isLoggedIn && !twitterAuth.isFollowing) {
        // 認証済みだがフォロー未完了
        showFollowRequiredMessage();
    } else {
        // 未認証
        showLoginRequiredMessage();
    }
}

/**
 * 認証済みフォームを表示
 */
function showAuthenticatedForm() {
    const formHtml = `
        <div class="twitter-user-info mb-4">
            <div class="flex items-center gap-3 bg-gray-700 rounded-lg p-3">
                <img src="${escapeHtml(twitterAuth.avatar)}" 
                     alt="${escapeHtml(twitterAuth.name)}" 
                     class="w-12 h-12 rounded-full">
                <div>
                    <div class="font-bold">${escapeHtml(twitterAuth.name)}</div>
                    <div class="text-sm text-gray-400">@${escapeHtml(twitterAuth.username)}</div>
                </div>
                <button onclick="twitterLogout()" class="ml-auto btn btn-secondary text-sm">
                    ログアウト
                </button>
            </div>
        </div>
        
        <div class="mb-4">
            <textarea id="userMessage" placeholder="応援メッセージ（任意）" 
                      rows="3" class="form-input"></textarea>
        </div>
        
        <div class="friends-section">
            <div class="friends-header">
                <h3 class="friends-title">一緒に参加する友人（Twitter認証必須）</h3>
                <button type="button" 
                        onclick="showInviteModal()" 
                        class="add-friend-btn">
                    <svg class="icon icon-plus" style="width: 16px; height: 16px;">
                        <use xlink:href="#icon-plus"></use>
                    </svg>
                    友人を招待
                </button>
            </div>
            <div id="invited-friends-list">
                <!-- 招待した友人のリスト -->
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary w-full text-lg">
            参加表明する！
        </button>
    `;
    
    document.getElementById('supporterFormContent').innerHTML = formHtml;
}

/**
 * フォロー必須メッセージを表示
 */
function showFollowRequiredMessage() {
    const messageHtml = `
        <div class="text-center py-8">
            <div class="mb-4">
                <img src="/images/idol-kimito-link.jpg" 
                     alt="君斗りんく" 
                     class="w-32 h-32 rounded-full mx-auto mb-4">
            </div>
            <h3 class="text-xl font-bold mb-3">@idolfunchをフォローしてね！</h3>
            <p class="text-gray-400 mb-6">
                参加表明するには、アイドル応援ちゃんねるの<br>
                公式アカウントをフォローする必要があるよ〜！
            </p>
            <a href="/twitter/follow.php" class="btn btn-primary">
                フォロー画面へ進む
            </a>
        </div>
    `;
    
    document.getElementById('supporterFormContent').innerHTML = messageHtml;
}

/**
 * ログイン必須メッセージを表示
 */
function showLoginRequiredMessage() {
    const messageHtml = `
        <div class="text-center py-8">
            <div class="mb-4">
                <svg width="64" height="64" fill="currentColor" class="mx-auto text-blue-400">
                    <use href="#icon-twitter"></use>
                </svg>
            </div>
            <h3 class="text-xl font-bold mb-3">Twitter（𝕏）でログイン</h3>
            <p class="text-gray-400 mb-6">
                参加表明にはTwitterアカウントが必要です。<br>
                ログインして、みんなで応援しよう！
            </p>
            <a href="/twitter/login.php?redirect=${encodeURIComponent(window.location.href)}" 
               class="btn btn-primary inline-flex items-center gap-2">
                <svg width="20" height="20" fill="currentColor">
                    <use href="#icon-twitter"></use>
                </svg>
                Twitter でログイン
            </a>
        </div>
    `;
    
    document.getElementById('supporterFormContent').innerHTML = messageHtml;
}

/**
 * 友人招待モーダルを表示
 */
function showInviteModal() {
    const modal = document.createElement('div');
    modal.className = 'modal-backdrop';
    modal.id = 'inviteModal';
    
    modal.innerHTML = `
        <div class="modal-content" style="max-width: 500px;">
            <h2 class="text-2xl font-bold mb-4">友人を招待</h2>
            
            <p class="text-gray-400 mb-4">
                招待リンクを友人に送って、一緒に参加してもらおう！<br>
                友人もTwitter認証が必要です。
            </p>
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">招待リンク</label>
                <div class="flex gap-2">
                    <input type="text" 
                           id="inviteLink" 
                           readonly 
                           class="form-input flex-1" 
                           value="">
                    <button onclick="copyInviteLink()" class="btn btn-secondary">
                        コピー
                    </button>
                </div>
            </div>
            
            <div class="mb-4">
                <button onclick="shareInviteOnTwitter()" 
                        class="btn btn-primary w-full flex items-center justify-center gap-2">
                    <svg width="20" height="20" fill="currentColor">
                        <use href="#icon-twitter"></use>
                    </svg>
                    Twitter でシェア
                </button>
            </div>
            
            <div class="text-center">
                <button onclick="closeInviteModal()" class="btn btn-secondary">
                    閉じる
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // 招待リンクを生成
    generateInviteLink();
}

/**
 * 招待リンクを生成
 */
async function generateInviteLink() {
    try {
        const response = await fetch('https://doin-challenge.com/api.php?action=generateInviteLink', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                challenge_id: selectedChallenge.id,
                inviter_twitter_id: twitterAuth.userId
            })
        });
        
        const data = await response.json();
        if (data.success) {
            const inviteUrl = `${window.location.origin}/invite/${data.token}`;
            document.getElementById('inviteLink').value = inviteUrl;
        }
    } catch (error) {
        console.error('招待リンク生成エラー:', error);
        showError('招待リンクの生成に失敗しました');
    }
}

/**
 * 招待リンクをコピー
 */
function copyInviteLink() {
    const input = document.getElementById('inviteLink');
    input.select();
    document.execCommand('copy');
    showSuccess('招待リンクをコピーしました！');
}

/**
 * Twitterでシェア
 */
function shareInviteOnTwitter() {
    const inviteLink = document.getElementById('inviteLink').value;
    const text = `${selectedChallenge.name} のワンマンライブに一緒に行こう！\n\n参加表明はこちらから👇\n`;
    const hashtags = '動員チャレンジ';
    
    const shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(inviteLink)}&hashtags=${encodeURIComponent(hashtags)}`;
    
    window.open(shareUrl, '_blank', 'width=600,height=400');
}

/**
 * 招待モーダルを閉じる
 */
function closeInviteModal() {
    const modal = document.getElementById('inviteModal');
    if (modal) {
        modal.remove();
    }
}

/**
 * Twitterログアウト
 */
async function twitterLogout() {
    try {
        const response = await fetch('https://doin-challenge.com/twitter/logout.php');
        if (response.ok) {
            twitterAuth = {
                isLoggedIn: false,
                isFollowing: false,
                userId: null,
                username: null,
                name: null,
                avatar: null,
                followers: 0
            };
            updateTwitterUI();
            showSuccess('ログアウトしました');
        }
    } catch (error) {
        console.error('ログアウトエラー:', error);
    }
}

/**
 * 修正版：参加表明送信処理（Twitter対応）
 */
async function handleSupporterSubmitWithTwitter(event) {
    event.preventDefault();
    
    // Twitter認証チェック
    if (!twitterAuth.isLoggedIn || !twitterAuth.isFollowing) {
        showError('Twitter認証とフォローが必要です');
        return;
    }
    
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // ボタンを無効化
    submitBtn.disabled = true;
    submitBtn.textContent = '送信中...';
    
    const data = {
        challenge_id: selectedChallenge.id,
        twitter_id: twitterAuth.userId,
        twitter_username: twitterAuth.username,
        twitter_name: twitterAuth.name,
        twitter_avatar: twitterAuth.avatar,
        message: document.getElementById('userMessage').value,
        // 招待した友人のリストは別途処理
    };
    
    try {
        const response = await fetch('https://doin-challenge.com/api.php?action=addSupporterWithTwitter', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess('参加表明ありがとうございます！');
            
            // フォームをリセット
            form.reset();
            
            // データを再取得
            await fetchChallengeDetail(selectedChallenge.id);
            
            // ツイート画面を表示（任意）
            if (confirm('参加表明をツイートしますか？')) {
                postParticipationTweet();
            }
        } else {
            throw new Error(result.message || '送信に失敗しました');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('エラーが発生しました。もう一度お試しください。');
    } finally {
        // ボタンを有効化
        submitBtn.disabled = false;
        submitBtn.textContent = '参加表明する！';
    }
}

/**
 * 参加表明ツイートを投稿
 */
function postParticipationTweet() {
    const text = `🎯 ${selectedChallenge.name} ${selectedChallenge.event_name}に参加表明！\n\n現在の参加者：${selectedChallenge.current}人\nあと${selectedChallenge.target - selectedChallenge.current}人で目標達成！\n\n@idolfunch でみんなも参加しよう👉`;
    const url = window.location.href;
    const hashtags = `動員チャレンジ,${selectedChallenge.name.replace(/\s/g, '')}`;
    
    const tweetUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}&hashtags=${encodeURIComponent(hashtags)}`;
    
    window.open(tweetUrl, '_blank', 'width=600,height=400');
}

// 他のチャレンジへナビゲート
function navigateToOtherChallenges() {
  // カテゴリページまたはトップページへ戻る
  if (currentCategorySlug) {
    window.location.href = `/category/${currentCategorySlug}/`;
  } else {
    window.location.href = '/';
  }
}

// 主催するボタンの処理
function showCreateOwnChallenge() {
  // カテゴリが決まっている場合はそのカテゴリで、そうでない場合はライブ動員で
  const categoryToUse = currentCategorySlug || 'live';
  showCreateModal(categoryToUse);
}

// 既存の参加をチェック
async function checkExistingParticipation() {
  if (!window.isLoggedIn || !window.loginUser) {
    return null;
  }
  
  try {
    const response = await fetch(`https://doin-challenge.com/api.php?action=checkUserParticipation&twitter_username=${window.loginUser.screen_name}`);
    const data = await response.json();
    
    if (!data.error && data.participating && data.challenge) {
      return data.challenge;
    }
    return null;
  } catch (error) {
    console.error('Error checking existing participation:', error);
    return null;
  }
}

// マイダッシュボードを読み込む（もし存在しない場合）
async function loadMyDashboard() {
  if (!window.isLoggedIn || !window.loginUser) {
    return;
  }
  
  try {
    const response = await fetch(`https://doin-challenge.com/api.php?action=getUserDashboard&twitter_username=${window.loginUser.screen_name}`);
    const data = await response.json();
    
    if (!data.error) {
      // ダッシュボードデータを処理
      console.log('Dashboard data:', data);
    }
  } catch (error) {
    console.error('Error loading dashboard:', error);
  }
}        value="">
                    <button onclick="copyInviteLink()" class="btn btn-secondary">
                        コピー
                    </button>
                </div>
            </div>
            
            <div class="mb-4">
                <button onclick="shareInviteOnTwitter()" 
                        class="btn btn-primary w-full flex items-center justify-center gap-2">
                    <svg width="20" height="20" fill="currentColor">
                        <use href="#icon-twitter"></use>
                    </svg>
                    Twitter でシェア
                </button>
            </div>
            
            <div class="text-center">
                <button onclick="closeInviteModal()" class="btn btn-secondary">
                    閉じる
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // 招待リンクを生成
    generateInviteLink();
}

/**
 * 招待リンクを生成
 */
async function generateInviteLink() {
    try {
        const response = await fetch('https://doin-challenge.com/api.php?action=generateInviteLink', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                challenge_id: selectedChallenge.id,
                inviter_twitter_id: twitterAuth.userId
            })
        });
        
        const data = await response.json();
        if (data.success) {
            const inviteUrl = `${window.location.origin}/invite/${data.token}`;
            document.getElementById('inviteLink').value = inviteUrl;
        }
    } catch (error) {
        console.error('招待リンク生成エラー:', error);
        showError('招待リンクの生成に失敗しました');
    }
}

/**
 * 招待リンクをコピー
 */
function copyInviteLink() {
    const input = document.getElementById('inviteLink');
    input.select();
    document.execCommand('copy');
    showSuccess('招待リンクをコピーしました！');
}

/**
 * Twitterでシェア
 */
function shareInviteOnTwitter() {
    const inviteLink = document.getElementById('inviteLink').value;
    const text = `${selectedChallenge.name} のワンマンライブに一緒に行こう！\n\n参加表明はこちらから👇\n`;
    const hashtags = '動員チャレンジ';
    
    const shareUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(inviteLink)}&hashtags=${encodeURIComponent(hashtags)}`;
    
    window.open(shareUrl, '_blank', 'width=600,height=400');
}

/**
 * 招待モーダルを閉じる
 */
function closeInviteModal() {
    const modal = document.getElementById('inviteModal');
    if (modal) {
        modal.remove();
    }
}

/**
 * Twitterログアウト
 */
async function twitterLogout() {
    try {
        const response = await fetch('https://doin-challenge.com/twitter/logout.php');
        if (response.ok) {
            twitterAuth = {
                isLoggedIn: false,
                isFollowing: false,
                userId: null,
                username: null,
                name: null,
                avatar: null,
                followers: 0
            };
            updateTwitterUI();
            showSuccess('ログアウトしました');
        }
    } catch (error) {
        console.error('ログアウトエラー:', error);
    }
}

/**
 * 修正版：参加表明送信処理（Twitter対応）
 */
async function handleSupporterSubmitWithTwitter(event) {
    event.preventDefault();
    
    // Twitter認証チェック
    if (!twitterAuth.isLoggedIn || !twitterAuth.isFollowing) {
        showError('Twitter認証とフォローが必要です');
        return;
    }
    
    const form = event.target;
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // ボタンを無効化
    submitBtn.disabled = true;
    submitBtn.textContent = '送信中...';
    
    const data = {
        challenge_id: selectedChallenge.id,
        twitter_id: twitterAuth.userId,
        twitter_username: twitterAuth.username,
        twitter_name: twitterAuth.name,
        twitter_avatar: twitterAuth.avatar,
        message: document.getElementById('userMessage').value,
        // 招待した友人のリストは別途処理
    };
    
    try {
        const response = await fetch('https://doin-challenge.com/api.php?action=addSupporterWithTwitter', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showSuccess('参加表明ありがとうございます！');
            
            // フォームをリセット
            form.reset();
            
            // データを再取得
            await fetchChallengeDetail(selectedChallenge.id);
            
            // ツイート画面を表示（任意）
            if (confirm('参加表明をツイートしますか？')) {
                postParticipationTweet();
            }
        } else {
            throw new Error(result.message || '送信に失敗しました');
        }
    } catch (error) {
        console.error('Error:', error);
        showError('エラーが発生しました。もう一度お試しください。');
    } finally {
        // ボタンを有効化
        submitBtn.disabled = false;
        submitBtn.textContent = '参加表明する！';
    }
}

/**
 * 参加表明ツイートを投稿
 */
function postParticipationTweet() {
    const text = `🎯 ${selectedChallenge.name} ${selectedChallenge.event_name}に参加表明！\n\n現在の参加者：${selectedChallenge.current}人\nあと${selectedChallenge.target - selectedChallenge.current}人で目標達成！\n\n@idolfunch でみんなも参加しよう👉`;
    const url = window.location.href;
    const hashtags = `動員チャレンジ,${selectedChallenge.name.replace(/\s/g, '')}`;
    
    const tweetUrl = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}&url=${encodeURIComponent(url)}&hashtags=${encodeURIComponent(hashtags)}`;
    
    window.open(tweetUrl, '_blank', 'width=600,height=400');
}

// 他のチャレンジへナビゲート
function navigateToOtherChallenges() {
  // カテゴリページまたはトップページへ戻る
  if (currentCategorySlug) {
    window.location.href = `/category/${currentCategorySlug}/`;
  } else {
    window.location.href = '/';
  }
}

// 主催するボタンの処理
function showCreateOwnChallenge() {
  // カテゴリが決まっている場合はそのカテゴリで、そうでない場合はライブ動員で
  const categoryToUse = currentCategorySlug || 'live';
  showCreateModal(categoryToUse);
}

// 既存の参加をチェック
async function checkExistingParticipation() {
  if (!window.isLoggedIn || !window.loginUser) {
    return null;
  }
  
  try {
    const response = await fetch(`https://doin-challenge.com/api.php?action=checkUserParticipation&twitter_username=${window.loginUser.screen_name}`);
    const data = await response.json();
    
    if (!data.error && data.participating && data.challenge) {
      return data.challenge;
    }
    return null;
  } catch (error) {
    console.error('Error checking existing participation:', error);
    return null;
  tegorySlug}/`;
  } else {
    window.location.href = '/';
  }
}

// 現在参加中のチャレンジをチェック
async function checkExistingParticipation() {
  if (!window.isLoggedIn || !window.loginUser) return null;
  
  try {
    const response = await fetch(`https://doin-challenge.com/api.php?action=getCurrentChallenges&twitter_username=${window.loginUser.screen_name}`);
    const data = await response.json();
    
    if (!data.error && data.length > 0) {
      // 最初の参加中チャレンジを返す
      return data[0];
    }
  } catch (error) {
    console.error('Error checking participation:', error);
  }
  
  return null;
}

// 主催画面を表示
function showCreateOwnChallenge() {
  if (!requireLogin()) return;
  
  // 現在のチャレンジと同じカテゴリで新規作成画面を開く
  const categoryId = selectedChallenge.category_id || 1; // デフォルトはライブ動員
  showCreateModal(selectedChallenge.category_slug);
}

// ========== 現在参加中のチャレンジ機能 ==========
async function loadCurrentChallenges() {
    if (!window.loginUser || !window.loginUser.screen_name) return;
    
    try {
        const response = await fetch(`https://doin-challenge.com/api.php?action=getCurrentChallenges&twitter_username=${window.loginUser.screen_name}`);
        const data = await response.json();
        
        if (!data.error && data.length > 0) {
            renderCurrentChallenges(data);
            document.getElementById('currentChallenges').style.display = 'block';
        }
    } catch (error) {
        console.error('Error loading current challenges:', error);
    }
}

// ========== 主催・参加チャレンジ統合表示 ==========
async function loadMyDashboard() {
    if (!window.loginUser || !window.loginUser.screen_name) return;
    
    try {
        // 主催チャレンジを取得
        const hostedResponse = await fetch(`https://doin-challenge.com/api.php?action=getMyHostedChallenges&twitter_username=${window.loginUser.screen_name}`);
        const hostedData = await hostedResponse.json();
        
        // 参加チャレンジを取得
        const participatingResponse = await fetch(`https://doin-challenge.com/api.php?action=getCurrentChallenges&twitter_username=${window.loginUser.screen_name}`);
        const participatingData = await participatingResponse.json();
        
        // エラーチェック
        if (!hostedData.error || !participatingData.error) {
            // どちらかにデータがあれば表示
            if ((Array.isArray(hostedData) && hostedData.length > 0) || 
                (Array.isArray(participatingData) && participatingData.length > 0)) {
                renderMyDashboard(hostedData, participatingData);
                document.getElementById('myDashboard').style.display = 'block';
            }
        }
    } catch (error) {
        console.error('Error loading my dashboard:', error);
    }
}

function renderMyDashboard(hostedChallenges, participatingChallenges) {
    const container = document.getElementById('myDashboardContent');
    if (!container) return;
    
    let html = '';
    
    // 主催したチャレンジ
    if (Array.isArray(hostedChallenges) && hostedChallenges.length > 0) {
        html += `
            <div class="dashboard-section">
                <h3 class="text-lg font-bold mb-3 flex items-center gap-2">
                    <svg width="20" height="20" fill="currentColor" class="text-yellow-400">
                        <use href="#icon-star"></use>
                    </svg>
                    主催したチャレンジ
                </h3>
                <div class="grid gap-3">
                    ${hostedChallenges.map(challenge => renderDashboardChallenge(challenge, 'hosted')).join('')}
                </div>
            </div>
        `;
    }
    
    // 参加したチャレンジ
    if (Array.isArray(participatingChallenges) && participatingChallenges.length > 0) {
        html += `
            <div class="dashboard-section ${hostedChallenges.length > 0 ? 'mt-6' : ''}">
                <h3 class="text-lg font-bold mb-3 flex items-center gap-2">
                    <svg width="20" height="20" fill="currentColor" class="text-green-400">
                        <use href="#icon-check-circle"></use>
                    </svg>
                    参加しているチャレンジ
                </h3>
                <div class="grid gap-3">
                    ${participatingChallenges.map(challenge => renderDashboardChallenge(challenge, 'participating')).join('')}
                </div>
            </div>
        `;
    }
    
    container.innerHTML = html;
}

function renderDashboardChallenge(challenge, type) {
    const progress = getProgress(challenge.current, challenge.target);
    const daysLeft = getDaysUntil(challenge.event_date);
    const statusColor = type === 'hosted' ? 'text-yellow-400' : 'text-green-400';
    const statusText = type === 'hosted' ? '主催' : '参加中';
    
    return `
        <div class="card cursor-pointer" onclick="selectChallenge({id: ${challenge.id}})">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <h4 class="font-bold">${escapeHtml(challenge.name)}</h4>
                    <p class="text-sm text-gray-400">${escapeHtml(challenge.event_name)}</p>
                </div>
                <span class="${statusColor} text-sm font-bold">${statusText}</span>
            </div>
            
            <div class="progress-bar mb-3" style="height: 0.5rem;">
                <div class="progress-fill ${getColorClass(challenge.color)}" style="width: ${progress}%"></div>
            </div>
            
            <div class="flex justify-between items-center text-sm">
                <div>
                    <span class="font-bold">${challenge.current}</span>
                    <span class="text-gray-400">/ ${challenge.target}人</span>
                    <span class="ml-2 ${progress >= 100 ? 'text-green-400' : 'text-yellow-400'}">${progress.toFixed(0)}%</span>
                </div>
                <span class="text-gray-400">${daysLeft}</span>
            </div>
        </div>
    `;
}
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

// ダッシュボードをレンダリング
function renderMyDashboard(hostedChallenges, participatingChallenges) {
    const container = document.getElementById('myDashboard');
    if (!container) return;
    
    let html = '';
    
    // 主催チャレンジ
    if (hostedChallenges.length > 0) {
        html += `
            <div class="mb-6">
                <h3 class="text-lg font-bold mb-3 flex items-center gap-2">
                    <svg width="20" height="20" fill="currentColor" class="text-yellow-400">
                        <use href="#icon-star"></use>
                    </svg>
                    主催したチャレンジ
                </h3>
                <div class="grid gap-3">
                    ${hostedChallenges.map(challenge => renderDashboardChallenge(challenge, 'hosted')).join('')}
                </div>
            </div>
        `;
    }
    
    // 参加チャレンジ
    if (participatingChallenges.length > 0) {
        html += `
            <div class="mb-6">
                <h3 class="text-lg font-bold mb-3 flex items-center gap-2">
                    <svg width="20" height="20" fill="currentColor" class="text-green-400">
                        <use href="#icon-check-circle"></use>
                    </svg>
                    参加中のチャレンジ
                </h3>
                <div class="grid gap-3">
                    ${participatingChallenges.map(challenge => renderDashboardChallenge(challenge, 'participating')).join('')}
                </div>
            </div>
        `;
    }
    
    if (hostedChallenges.length > 0 || participatingChallenges.length > 0) {
        container.innerHTML = html;
        container.style.display = 'block';
    }
}

// ダッシュボード用チャレンジカードをレンダリング
function renderDashboardChallenge(challenge, type) {
    const progress = getProgress(challenge.current, challenge.target);
    const daysLeft = getDaysUntil(challenge.event_date);
    const isHosted = type === 'hosted';
    
    return `
        <div class="card cursor-pointer" onclick="selectChallenge({id: ${challenge.id}})">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <h4 class="font-bold">${escapeHtml(challenge.name)}</h4>
                    <p class="text-sm text-gray-400">${escapeHtml(challenge.event_name)}</p>
                </div>
                <span class="text-sm ${isHosted ? 'text-yellow-400' : 'text-green-400'}">
                    ${isHosted ? '主催' : '参加中'}
                </span>
            </div>
            <div class="progress-bar mb-2" style="height: 0.25rem;">
                <div class="progress-fill ${getColorClass(challenge.color)}" style="width: ${progress}%"></div>
            </div>
            <div class="flex justify-between text-sm">
                <span>${challenge.current}/${challenge.target}人</span>
                <span>${daysLeft}</span>
            </div>
        </div>
    `;
}

// ログイン必須の関数
function requireLogin() {
    if (!window.isLoggedIn) {
        if (confirm('この機能を使うにはログインが必要です。ログインしますか？')) {
            window.location.href = '/twitter_auth.php?redirect=' + encodeURIComponent(window.location.href);
        }
        return false;
    }
    return true;
}
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

// ダッシュボードをレンダリング
function renderMyDashboard(hostedData, participatingData) {
    const dashboardElement = document.getElementById('myDashboard');
    if (!dashboardElement) return;
    
    if (hostedData.length === 0 && participatingData.length === 0) {
        dashboardElement.style.display = 'none';
        return;
    }
    
    let html = `
        <h2 style="color: #ff9cc2; font-size: 1.75rem; margin-bottom: 1.5rem; text-align: center; font-weight: bold;">
            🌟 マイダッシュボード 🌟
        </h2>
    `;
    
    // 主催チャレンジがある場合
    if (hostedData.length > 0) {
        html += `
            <div style="margin-bottom: 2rem;">
                <h3 style="font-size: 1.25rem; font-weight: bold; margin-bottom: 1rem; color: #fbbf24;">
                    🎪 主催したチャレンジ
                </h3>
                <div style="display: grid; gap: 1rem;">
                    ${hostedData.map(challenge => renderDashboardChallenge(challenge, 'hosted')).join('')}
                </div>
            </div>
        `;
    }
    
    // 参加チャレンジがある場合
    if (participatingData.length > 0) {
        html += `
            <div style="margin-bottom: 2rem;">
                <h3 style="font-size: 1.25rem; font-weight: bold; margin-bottom: 1rem; color: #60a5fa;">
                    🎯 参加中のチャレンジ
                </h3>
                <div style="display: grid; gap: 1rem;">
                    ${participatingData.map(challenge => renderDashboardChallenge(challenge, 'participating')).join('')}
                </div>
            </div>
        `;
    }
    
    dashboardElement.innerHTML = html;
    dashboardElement.style.display = 'block';
}

// ダッシュボード用チャレンジカード
function renderDashboardChallenge(challenge, type) {
    const progress = getProgress(challenge.current, challenge.target);
    const daysLeft = getDaysUntil(challenge.event_date);
    const colorClass = type === 'hosted' ? 'gradient-orange' : 'gradient-blue';
    
    return `
        <div style="background: rgba(255,255,255,0.1); border-radius: 1rem; padding: 1.5rem; cursor: pointer; transition: all 0.3s;"
             onclick="selectChallenge({id: ${challenge.id}});"
             onmouseover="this.style.background='rgba(255,255,255,0.15)'; this.style.transform='translateY(-2px)';" 
             onmouseout="this.style.background='rgba(255,255,255,0.1)'; this.style.transform='translateY(0)';">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 1rem;">
                <div>
                    <h4 style="font-size: 1.1rem; font-weight: bold; color: #fff; margin-bottom: 0.25rem;">
                        ${challenge.name}
                    </h4>
                    <p style="color: rgba(255,255,255,0.7); font-size: 0.875rem;">
                        ${challenge.event_name}
                    </p>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 0.875rem; color: ${daysLeft === '終了' ? '#ef4444' : '#fbbf24'};">
                        ${daysLeft}
                    </div>
                </div>
            </div>
            
            <div class="progress-bar mb-3" style="height: 0.4rem;">
                <div class="progress-fill ${colorClass}" style="width: ${progress}%"></div>
            </div>
            
            <div style="display: flex; justify-content: space-between; align-items: center; font-size: 0.875rem;">
                <div style="color: rgba(255,255,255,0.8);">
                    <svg width="16" height="16" fill="currentColor" style="display: inline-block; vertical-align: middle; margin-right: 0.25rem;">
                        <use href="#icon-users"></use>
                    </svg>
                    ${challenge.current} / ${challenge.target}人
                </div>
                <div style="color: rgba(255,255,255,0.8);">
                    <svg width="16" height="16" fill="currentColor" style="display: inline-block; vertical-align: middle; margin-right: 0.25rem;">
                        <use href="#icon-map-pin"></use>
                    </svg>
                    ${challenge.prefecture}
                </div>
            </div>
        </div>
    `;
}er.screen_name}`);
        const hostedData = await hostedResponse.json();
        
        // 参加チャレンジを取得
        const participatingResponse = await fetch(`https://doin-challenge.com/api.php?action=getCurrentChallenges&twitter_username=${window.loginUser.screen_name}`);
        const participatingData = await participatingResponse.json();
        
        // エラーチェック
        if (!hostedData.error || !participatingData.error) {
            renderMyDashboard(hostedData, participatingData);
        }
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

// ダッシュボードを表示
function renderMyDashboard(hostedChallenges, participatingChallenges) {
    const dashboardContainer = document.getElementById('myDashboard');
    if (!dashboardContainer) return;
    
    // 両方空の場合は非表示
    if ((!hostedChallenges || hostedChallenges.length === 0) && 
        (!participatingChallenges || participatingChallenges.length === 0)) {
        dashboardContainer.style.display = 'none';
        return;
    }
    
    let dashboardHTML = '';
    
    // 主催チャレンジ
    if (hostedChallenges && hostedChallenges.length > 0) {
        dashboardHTML += `
            <div class="hosted-challenges mb-6">
                <h3 class="text-lg font-bold mb-3 flex items-center gap-2">
                    <svg width="20" height="20" fill="currentColor" class="text-yellow-400">
                        <use href="#icon-star"></use>
                    </svg>
                    主催中のチャレンジ
                </h3>
                <div class="grid gap-3">
                    ${hostedChallenges.map(challenge => `
                        <div class="card cursor-pointer" onclick="selectChallenge({id: ${challenge.id}})">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-bold">${escapeHtml(challenge.name)}</h4>
                                    <p class="text-sm text-gray-400">${challenge.event_name}</p>
                                    <p class="text-xs text-gray-500">${challenge.venue}（${challenge.prefecture}）</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-bold">${challenge.current}/${challenge.target}人</div>
                                    <div class="text-sm text-gray-400">達成率 ${Math.round((challenge.current / challenge.target) * 100)}%</div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    // 参加チャレンジ
    if (participatingChallenges && participatingChallenges.length > 0) {
        dashboardHTML += `
            <div class="participating-challenges mb-6">
                <h3 class="text-lg font-bold mb-3 flex items-center gap-2">
                    <svg width="20" height="20" fill="currentColor" class="text-green-400">
                        <use href="#icon-check-circle"></use>
                    </svg>
                    参加中のチャレンジ
                </h3>
                <div class="grid gap-3">
                    ${participatingChallenges.map(challenge => `
                        <div class="card cursor-pointer" onclick="selectChallenge({id: ${challenge.id}})">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-bold">${escapeHtml(challenge.name)}</h4>
                                    <p class="text-sm text-gray-400">${challenge.event_name}</p>
                                    <p class="text-xs text-gray-500">${challenge.venue}（${challenge.prefecture}）</p>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-green-400">貢献度: ${challenge.contribution}人</div>
                                    <div class="text-sm text-gray-400">本人1 + 友人${challenge.friends}</div>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }
    
    dashboardContainer.innerHTML = dashboardHTML;
    dashboardContainer.style.display = 'block';
}
                    </h3>
                    <div style="display: grid; gap: 1rem;">
                        ${hostedData.map(challenge => renderDashboardChallenge(challenge, 'hosted')).join('')}
                    </div>
                </div>
            `;
        }
        
        // 参加チャレンジ
        if (!participatingData.error && participatingData.length > 0) {
            html += `
                <div style="margin-bottom: 2rem;">
                    <h3 style="font-size: 1.25rem; font-weight: bold; margin-bottom: 1rem; color: #60a5fa;">
                        ✅ 参加中のチャレンジ
                    </h3>
                    <div style="display: grid; gap: 1rem;">
                        ${participatingData.map(challenge => renderDashboardChallenge(challenge, 'participating')).join('')}
                    </div>
                </div>
            `;
        }
        
        // 何もない場合
        if (html === '') {
            html = `
                <div style="text-align: center; padding: 3rem; color: #6b7280;">
                    <p>まだチャレンジに参加・主催していません</p>
                    <button onclick="window.location.href='/'" class="btn btn-primary" style="margin-top: 1rem;">
                        チャレンジを探す
                    </button>
                </div>
            `;
        }
        
        container.innerHTML = html;
        container.style.display = 'block';
        
    } catch (error) {
        console.error('Error loading dashboard:', error);
    }
}

function renderDashboardChallenge(challenge, type) {
    const progress = getProgress(challenge.current, challenge.target);
    const daysLeft = getDaysUntil(challenge.event_date);
    const colorClass = type === 'hosted' ? 'border-yellow-400' : 'border-green-400';
    
    return `
        <div style="background: rgba(255,255,255,0.05); border: 2px solid; border-radius: 0.75rem; padding: 1rem; cursor: pointer;"
             class="${colorClass}"
             onclick="selectChallenge({id: ${challenge.id}})">
            <div style="display: flex; justify-content: space-between; align-items: start;">
                <div style="flex: 1;">
                    <h4 style="font-weight: bold; font-size: 1.1rem; margin-bottom: 0.5rem;">
                        ${escapeHtml(challenge.name)}
                    </h4>
                    <p style="color: #9ca3af; font-size: 0.875rem; margin-bottom: 0.5rem;">
                        ${escapeHtml(challenge.event_name)}
                    </p>
                    <div style="display: flex; gap: 1rem; font-size: 0.875rem; color: #d1d5db;">
                        <span>📅 ${formatDate(challenge.event_date)}</span>
                        <span>📍 ${challenge.venue}</span>
                    </div>
                </div>
                <div style="text-align: right;">
                    <div style="font-size: 1.5rem; font-weight: bold; color: ${progress >= 100 ? '#10b981' : '#fbbf24'};">
                        ${progress.toFixed(0)}%
                    </div>
                    <div style="font-size: 0.875rem; color: #9ca3af;">
                        ${challenge.current} / ${challenge.target}人
                    </div>
                    <div style="font-size: 0.75rem; color: #ef4444; margin-top: 0.25rem;">
                        ${daysLeft}
                    </div>
                </div>
            </div>
            ${type === 'participating' && challenge.contribution ? `
                <div style="margin-top: 0.75rem; padding-top: 0.75rem; border-top: 1px solid rgba(255,255,255,0.1);">
                    <span style="font-size: 0.875rem; color: #60a5fa;">
                        🏆 貢献度: ${challenge.contribution}人
                    </span>
                </div>
            ` : ''}
        </div>
    `;
}

function renderCurrentChallenges(challenges) {
    const container = document.getElementById('currentChallengesList');
    if (!container) return;
    
    container.innerHTML = challenges.map(challenge => `
        <div style="background: rgba(255,255,255,0.05); border-radius: 0.75rem; padding: 1rem; cursor: pointer;"
             onclick="selectChallenge({id: ${challenge.id}})">
            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                <div>
                    <h4 style="color: #fff; font-weight: bold; font-size: 1.1rem;">${escapeHtml(challenge.name)}</h4>
                    <p style="color: #a855f7; font-size: 0.875rem;">${escapeHtml(challenge.event_name)}</p>
                </div>
                <div style="text-align: right;">
                    <div style="color: #fbbf24; font-size: 1.5rem; font-weight: bold;">+${challenge.contribution}</div>
                    <div style="color: rgba(255,255,255,0.6); font-size: 0.75rem;">貢献度</div>
                </div>
            </div>
            <div style="display: flex; gap: 1rem; font-size: 0.875rem; color: rgba(255,255,255,0.7);">
                <span>📅 ${formatDate(challenge.event_date)}</span>
                <span>📍 ${escapeHtml(challenge.venue)}</span>
            </div>
            ${challenge.message ? `
                <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid rgba(255,255,255,0.1);">
                    <p style="color: rgba(255,255,255,0.8); font-size: 0.875rem; font-style: italic;">
                        "${escapeHtml(challenge.message)}"
                    </p>
                </div>
            ` : ''}
        </div>
    `).join('');
}

// ========== Twitter統合用の初期化処理追加 ==========


// フォーム送信イベントの上書き（Twitter認証対応）
document.addEventListener('submit', function(event) {
    if (event.target.id === 'supporterForm') {
        event.preventDefault();
        // Twitter認証が有効な場合は専用処理を使用
        if (window.twitterAuthEnabled) {
            handleSupporterSubmitWithTwitter(event);
        } else {
            // 既存の処理を使用
            handleSupporterSubmit(event);
        }
    }
});
