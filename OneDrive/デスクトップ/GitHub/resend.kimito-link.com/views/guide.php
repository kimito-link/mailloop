<?php $view='guide'; ?>
<div class="guide-container" style="display: flex; flex-direction: column; gap: 60px; padding-top: 40px;">
    <div style="text-align: center;">
        <h1 style="margin-bottom:20px; font-weight: 800; font-size: 2.5rem; background: linear-gradient(45deg, var(--primary), var(--secondary)); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;">君斗りんくのメール送信ツール<br>使い方ガイド</h1>
        <p class="muted">5つのステップで、誰でも簡単にプロ級のメール管理が始められます！</p>
    </div>

    <div style="display: flex; justify-content: center; gap: 15px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="#step1" class="btn primary" style="font-size: 0.9rem; padding: 10px 20px; border-radius: 20px;">1. ログイン</a>
        <a href="#step2" class="btn primary" style="font-size: 0.9rem; padding: 10px 20px; border-radius: 20px;">2. テンプレ</a>
        <a href="#step3" class="btn primary" style="font-size: 0.9rem; padding: 10px 20px; border-radius: 20px;">3. 宛先</a>
        <a href="#step4" class="btn primary" style="font-size: 0.9rem; padding: 10px 20px; border-radius: 20px;">4. 送信</a>
        <a href="#step5" class="btn primary" style="font-size: 0.9rem; padding: 10px 20px; border-radius: 20px;">5. 再送信</a>
    </div>

    <!-- Step 1: Login -->
    <div id="step1" class="exp-card">
        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
            <div style="width: 50px; height: 50px; background: var(--primary); color: #0b0f14; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800;">1</div>
            <h2 style="margin: 0; font-size: 1.8rem;">Googleアカウントでログイン</h2>
        </div>
        
        <div class="guide-bubble left bubble-blue" style="margin-bottom: 30px;">
            <img src="/assets/img/characters/link/link-yukkuri-normal-mouth-open.png?v=<?= time() ?>" alt="りんく">
            <div class="text">
                <span class="char-name" style="color: var(--primary);">りんく</span>
                「まずはここからスタート！Googleアカウントでログインするだけで、あなたのGmailと連携できるよ。<br>
                面倒な設定は一切なし！ボタン一つで準備完了だよ！」
            </div>
        </div>

        <div style="margin-bottom: 20px; padding: 15px; background: rgba(255, 107, 107, 0.1); border: 1px solid var(--danger); border-radius: 12px; font-size: 0.85rem; color: var(--text);">
            <strong style="color: var(--danger);">※Google認証時の警告について</strong><br>
            現在Googleの審査中のため、ログイン時に警告画面が出ることがあるよ。<br>
            左下の<strong>「詳細」</strong>→<strong>「resend.kimito-link.com（安全ではない）に移動」</strong>の順にクリックすれば進めるから安心してね！
        </div>

        <div style="border-radius: 20px; overflow: hidden; border: 1px solid var(--border); box-shadow: 0 20px 50px rgba(0,0,0,0.4);">
            <img src="/assets/img/demo/step1.png?v=<?= time() ?>" style="width: 100%; display: block;" alt="ログイン画面のデモ">
        </div>
    </div>

    <!-- Step 2: Templates -->
    <div id="step2" class="exp-card">
        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
            <div style="width: 50px; height: 50px; background: var(--warn); color: #0b0f14; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800;">2</div>
            <h2 style="margin: 0; font-size: 1.8rem;">テンプレートを作成</h2>
        </div>

        <div class="guide-bubble right bubble-yellow" style="margin-bottom: 30px;">
            <img src="/assets/img/characters/konta/kitsune-yukkuri-smile-mouth-open.png?v=<?= time() ?>" alt="こん太">
            <div class="text">
                <span class="char-name" style="color: var(--warn);">こん太</span>
                「よく使うメールの本文を『テンプレート』として保存しておくコン！<br>
                件名も本文も自由自在。一度作れば、次からは選ぶだけで一瞬でメールが完成するコン！」
            </div>
        </div>

        <div style="border-radius: 20px; overflow: hidden; border: 1px solid var(--border); box-shadow: 0 20px 50px rgba(0,0,0,0.4);">
            <img src="/assets/img/demo/step2.png?v=<?= time() ?>" style="width: 100%; display: block;" alt="テンプレート作成のデモ">
        </div>
    </div>

    <!-- Step 3: Groups -->
    <div id="step3" class="exp-card">
        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
            <div style="width: 50px; height: 50px; background: var(--secondary); color: #0b0f14; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800;">3</div>
            <h2 style="margin: 0; font-size: 1.8rem;">宛先グループを登録</h2>
        </div>

        <div class="guide-bubble left bubble-green" style="margin-bottom: 30px;">
            <img src="/assets/img/characters/tanunee/tanuki-yukkuri-normal-mouth-open.png?v=<?= time() ?>" alt="たぬ姉">
            <div class="text">
                <span class="char-name" style="color: var(--secondary);">たぬ姉</span>
                「送りたい相手のメールアドレスをグループにまとめておこうねぇ。<br>
                Toだけじゃなく、CCやBCCも一緒に登録できるから、一括送信も楽ちんだよぉ。」
            </div>
        </div>

        <div style="border-radius: 20px; overflow: hidden; border: 1px solid var(--border); box-shadow: 0 20px 50px rgba(0,0,0,0.4);">
            <img src="/assets/img/demo/step3.png?v=<?= time() ?>" style="width: 100%; display: block;" alt="宛先グループ登録のデモ">
        </div>
    </div>

    <!-- Step 4: Send -->
    <div id="step4" class="exp-card">
        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
            <div style="width: 50px; height: 50px; background: var(--primary); color: #0b0f14; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800;">4</div>
            <h2 style="margin: 0; font-size: 1.8rem;">メールを送信</h2>
        </div>

        <div class="guide-bubble right bubble-blue" style="margin-bottom: 30px;">
            <img src="/assets/img/characters/link/link-yukkuri-smile-mouth-open.png?v=<?= time() ?>" alt="りんく">
            <div class="text">
                <span class="char-name" style="color: var(--primary);">りんく</span>
                「準備ができたら送信！テンプレートとグループを選ぶだけで、内容が自動でセットされるよ。<br>
                送信前にしっかり確認できるから、ミスも防げて安心だね！」
            </div>
        </div>

        <div style="border-radius: 20px; overflow: hidden; border: 1px solid var(--border); box-shadow: 0 20px 50px rgba(0,0,0,0.4);">
            <img src="/assets/img/demo/step4.png?v=<?= time() ?>" style="width: 100%; display: block;" alt="メール送信のデモ">
        </div>
    </div>

    <!-- Step 5: Resend -->
    <div id="step5" class="exp-card">
        <div style="display: flex; align-items: center; gap: 20px; margin-bottom: 30px;">
            <div style="width: 50px; height: 50px; background: #ff6b6b; color: #0b0f14; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; font-weight: 800;">5</div>
            <h2 style="margin: 0; font-size: 1.8rem;">送信ログから賢く再送信</h2>
        </div>

        <div class="guide-bubble left bubble-yellow" style="margin-bottom: 30px;">
            <img src="/assets/img/characters/konta/kitsune-yukkuri-normal.png?v=<?= time() ?>" alt="こん太">
            <div class="text">
                <span class="char-name" style="color: var(--warn);">こん太</span>
                「過去に送ったメールはすべて『ログ』に残っているコン！<br>
                『この内容で再送信（編集）』ボタンを押せば、同じ内容で宛先だけ変えて送ることもできるコン。これが最強の時短術だコン！」
            </div>
        </div>

        <div style="border-radius: 20px; overflow: hidden; border: 1px solid var(--border); box-shadow: 0 20px 50px rgba(0,0,0,0.4);">
            <img src="/assets/img/demo/step5.png?v=<?= time() ?>" style="width: 100%; display: block;" alt="再送信機能のデモ">
        </div>
    </div>

    <div class="char-bubble" style="background: rgba(105, 219, 124, 0.1); border-color: var(--secondary); align-self: center; max-width: 800px;">
        <img src="/assets/img/characters/tanunee/tanuki-yukkuri-smile-mouth-open.png?v=<?= time() ?>" alt="たぬ姉">
        <div class="text">
            <span class="char-name" style="color: var(--secondary);">たぬ姉</span>
            「使い方はバッチリかな？<br>
            もし分からないことがあっても、各ページで私たちが案内しているから大丈夫だよぉ。<br>
            さあ、新しいメール体験を始めておくれ。」
        </div>
    </div>

    <div style="text-align:center; margin-bottom: 80px;">
        <a href="/" class="btn primary" style="padding: 20px 80px; font-size: 1.2rem; border-radius: 30px; box-shadow: 0 10px 30px rgba(77, 171, 247, 0.4);">トップページへ戻る</a>
    </div>
</div>
