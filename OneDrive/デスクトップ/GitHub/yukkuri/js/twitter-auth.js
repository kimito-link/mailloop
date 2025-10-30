// Twitter認証管理
(function() {
    'use strict';
    
    // 認証状態をチェック
    async function checkAuthStatus() {
        try {
            const response = await fetch('/api/twitter-check-auth.php', {
                credentials: 'include'
            });
            const data = await response.json();
            
            if (data.authenticated && data.user) {
                // LocalStorageに保存
                const userToStore = {
                    loggedIn: true,
                    name: data.user.name,
                    username: data.user.username || data.user.screen_name,
                    profileImage: data.user.profile_image_url,
                    followers_count: data.user.followers_count || 0,
                    avatar: data.user.profile_image_url
                };
                localStorage.setItem('twitter_user', JSON.stringify(userToStore));
                
                updateUIForAuthenticatedUser(data.user);
            } else {
                localStorage.removeItem('twitter_user');
                updateUIForGuest();
            }
            
            return data;
        } catch (error) {
            console.error('認証チェックエラー:', error);
            localStorage.removeItem('twitter_user');
            updateUIForGuest();
            return { authenticated: false };
        }
    }
    
    // 認証済みユーザー用のUI更新
    function updateUIForAuthenticatedUser(user) {
        const authSection = document.querySelector('.twitter-auth-section');
        if (!authSection) return;
        
        authSection.innerHTML = `
            <div class="user-profile-section">
                <div class="user-info">
                    <img src="${user.profile_image_url || '/images/default-avatar.png'}" 
                         alt="${user.name}" 
                         class="user-avatar"
                         onerror="this.src='/images/default-avatar.png'">
                    <div class="user-details">
                        <h3>${user.name || 'ユーザー'}</h3>
                        <p>@${user.screen_name || user.username}</p>
                        ${user.followers_count ? `<p class="followers-count">${user.followers_count.toLocaleString()} フォロワー</p>` : ''}
                    </div>
                </div>
                <button onclick="logout()" class="logout-btn">ログアウト</button>
            </div>
        `;
    }
    
    // ゲスト用のUI更新
    function updateUIForGuest() {
        const authSection = document.querySelector('.twitter-auth-section');
        if (!authSection) return;
        
        authSection.innerHTML = `
            <div class="login-prompt">
                <button onclick="loginWithTwitter()" class="twitter-login-btn">
                    <i class="fab fa-twitter"></i> Twitterでログイン
                </button>
            </div>
        `;
    }
    
    // Twitterログイン
    window.loginWithTwitter = async function() {
        try {
            const response = await fetch('/api/twitter-auth.php');
            const data = await response.json();
            
            if (data.auth_url) {
                window.location.href = data.auth_url;
            } else {
                console.error('認証URLの取得に失敗しました');
            }
        } catch (error) {
            console.error('ログインエラー:', error);
        }
    };
    
    // ログアウト
    window.logout = async function() {
        try {
            await fetch('/api/twitter-logout.php', {
                credentials: 'include'
            });
            // LocalStorageをクリア
            localStorage.removeItem('twitter_user');
            localStorage.removeItem('userProfile');
            window.location.reload();
        } catch (error) {
            console.error('ログアウトエラー:', error);
        }
    };
    
    // 配信者・アイドル紹介のTwitter情報を取得
    async function loadStreamersTwitterInfo() {
        const streamers = [
            { id: 'chibichan', username: 'chibi_chan' },
            { id: 'ponko', username: 'ponko_2' },
            { id: 'vitamayu', username: 'vitamayu' }
        ];
        
        for (const streamer of streamers) {
            try {
                const response = await fetch(`/api/get-twitter-profiles.php?username=${streamer.username}`);
                const data = await response.json();
                
                if (data.success && data.user) {
                    updateStreamerCard(streamer.id, data.user);
                }
            } catch (error) {
                console.error(`${streamer.username}の情報取得エラー:`, error);
            }
        }
    }
    
    // 配信者カードを更新
    function updateStreamerCard(streamerId, twitterUser) {
        const card = document.querySelector(`[data-streamer-id="${streamerId}"]`);
        if (!card) return;
        
        const twitterInfo = card.querySelector('.twitter-info');
        if (twitterInfo) {
            twitterInfo.innerHTML = `
                <img src="${twitterUser.profile_image_url || '/images/default-avatar.png'}" 
                     alt="${twitterUser.name}"
                     class="twitter-avatar"
                     onerror="this.src='/images/default-avatar.png'">
                <div class="twitter-details">
                    <span class="twitter-name">${twitterUser.name}</span>
                    <span class="twitter-handle">@${twitterUser.screen_name}</span>
                    <span class="twitter-followers">
                        <i class="fab fa-twitter"></i>
                        ${(twitterUser.followers_count || 0).toLocaleString()}
                    </span>
                </div>
            `;
        }
    }
    
    // ページ読み込み時の初期化
    document.addEventListener('DOMContentLoaded', async function() {
        console.log('Twitter Auth: 初期化開始');
        
        // 認証状態をチェック
        const authData = await checkAuthStatus();
        console.log('Twitter Auth: 認証状態', authData);
        
        // 既存のupdateUserDisplay関数と連携
        if (authData.authenticated && authData.user) {
            // LocalStorageに保存して既存のコードと連携
            const userToStore = {
                loggedIn: true,
                name: authData.user.name,
                username: authData.user.screen_name || authData.user.username,
                profileImage: authData.user.profile_image_url,
                followers_count: authData.user.followers_count,
                avatar: authData.user.profile_image_url
            };
            localStorage.setItem('twitter_user', JSON.stringify(userToStore));
            
            // 既存のupdateUserDisplay関数を呼び出す
            if (typeof updateUserDisplay === 'function') {
                console.log('Twitter Auth: updateUserDisplay呼び出し');
                updateUserDisplay();
            }
        }
        
        // 配信者のTwitter情報を読み込み
        await loadStreamersTwitterInfo();
    });
    
    // グローバルに公開
    window.checkAuthStatus = checkAuthStatus;
    window.updateUIForAuthenticatedUser = updateUIForAuthenticatedUser;
    window.updateUIForGuest = updateUIForGuest;
})();