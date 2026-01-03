<?php $view='home'; ?>
<h1>MailLoop</h1>

<div style="display:flex;gap:10px;flex-wrap:wrap;margin:16px 0;">
  <a class="btn" href="/send">送信する</a>
  <a class="btn" href="/logs">ログを見る</a>
  <?php if (!empty($_SESSION['user_id'])): ?>
    <a class="btn" href="/auth/logout">ログアウト</a>
  <?php else: ?>
    <a class="btn" href="/auth/login">ログイン</a>
  <?php endif; ?>
</div>
