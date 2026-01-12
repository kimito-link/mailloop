<?php $view='home'; ?>
<div style="text-align: center; margin-bottom: 50px; padding-top: 20px;">
  <h1 style="font-size: 2.2rem; margin-bottom: 15px; font-weight: 800; background: linear-gradient(45deg, var(--primary), var(--secondary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">君斗りんくのメール送信ツール</h1>
  <p class="muted" style="font-size: 1.1rem;">Gmailを最大限に活用する、シンプルで強力なメールツール</p>
</div>

<div style="display: flex; flex-direction: column; gap: 10px;">
    <!-- りんく (Left, Blue) -->
    <div class="guide-bubble left bubble-blue">
      <img src="/assets/img/characters/link/link-yukkuri-normal-mouth-open.png" alt="りんく">
      <div class="text">
        <span class="char-name" style="color: var(--primary);">りんく</span>
        「こんにちは！君斗りんくのメール送信ツールへようこそ！<br>
        ここは、Gmailを使ってテンプレート送信や一括送信が簡単にできる場所だよ！」
      </div>
    </div>

    <!-- こん太 (Right, Yellow) -->
    <div class="guide-bubble right bubble-yellow">
      <img src="/assets/img/characters/konta/kitsune-yukkuri-smile-mouth-open.png" alt="こん太">
      <div class="text">
        <span class="char-name" style="color: var(--warn);">こん太</span>
        「過去に送ったメールをベースに、宛先を書き換えて『再送信』することもできるコン！<br>
        まずは下のボタンからログインして始めてみるコン！」
      </div>
    </div>

    <!-- たぬ姉 (Left, Green) -->
    <div class="guide-bubble left bubble-green">
      <img src="/assets/img/characters/tanunee/tanuki-yukkuri-normal-mouth-open.png" alt="たぬ姉">
      <div class="text">
        <span class="char-name" style="color: var(--secondary);">たぬ姉</span>
        「使い方がわからなくなったら、いつでも『使い方ガイド』を見てねぇ。<br>
        私たちが優しく教えるから安心しておくれ。」
      </div>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr; gap:20px; margin-top:40px;">
  <?php if (empty($user['id'])): ?>
    <a class="btn primary" href="/auth/login" style="padding: 25px; font-size: 1.3rem; border-radius: 24px;">
      <span>Googleでログインして始める</span>
    </a>
    <div style="margin-top: 10px; padding: 15px; background: rgba(255, 107, 107, 0.1); border: 1px solid var(--danger); border-radius: 12px; font-size: 0.85rem; color: var(--text); text-align: left;">
        <strong style="color: var(--danger);">【重要】Google認証画面での警告について</strong><br>
        現在、Googleによる審査中のため、ログイン時に「このアプリは確認されていません」という警告画面が表示される場合があります。<br>
        その場合は、画面左下の<strong>「詳細」</strong>をクリックし、<strong>「resend.kimito-link.com（安全ではない）に移動」</strong>を選択して進めてください。開発中のツールですが、セキュリティには配慮しておりますのでご安心ください。
    </div>
  <?php else: ?>
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
      <a class="btn primary" href="/send" style="padding: 20px; border-radius: 20px;">
        <span>メールを作成・送信</span>
      </a>
      <a class="btn" href="/logs" style="padding: 20px; border-radius: 20px;">
        <span>送信ログを確認</span>
      </a>
    </div>
  <?php endif; ?>
  
  <a class="btn" href="/guide" style="padding: 18px; background: rgba(255, 255, 255, 0.03); border-radius: 20px;">
    <span>詳しい使い方ガイドを見る</span>
  </a>
</div>

<div class="exp-card" style="margin-top: 60px;">
  <div class="exp-title">MailLoopでできること</div>
  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; align-items: center;">
    <ul style="list-style: none; padding: 0; display: grid; gap: 20px;">
      <li style="display: flex; align-items: center; gap: 15px;">
        <div style="width: 32px; height: 32px; background: rgba(77, 171, 247, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--primary); font-weight: 800;">1</div>
        <span>テンプレートを使って素早くメール作成</span>
      </li>
      <li style="display: flex; align-items: center; gap: 15px;">
        <div style="width: 32px; height: 32px; background: rgba(105, 219, 124, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--secondary); font-weight: 800;">2</div>
        <span>宛先グループの一括管理</span>
      </li>
      <li style="display: flex; align-items: center; gap: 15px;">
        <div style="width: 32px; height: 32px; background: rgba(255, 212, 59, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--warn); font-weight: 800;">3</div>
        <span>送信ログから「編集して再送信」</span>
      </li>
    </ul>
    <img src="/assets/img/demo/send.png?v=<?= time() ?>" style="width: 100%; border-radius: 15px; box-shadow: 0 10px 20px rgba(0,0,0,0.2);" alt="デモ画面">
  </div>
</div>
