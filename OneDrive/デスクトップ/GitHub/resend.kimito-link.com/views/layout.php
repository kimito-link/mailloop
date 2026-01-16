<?php ?><!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>君斗りんくのメール送信ツール</title>
  <link rel="stylesheet" href="/assets/app.css?v=<?= time() ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Noto+Sans+JP:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
  <header class="topbar">
    <a href="/" class="brand">
      <img src="/assets/img/logo/logo_funlink_RGB_color.png" alt="MailLoop Logo">
      <span>君斗りんくのメール送信ツール</span>
    </a>

    <?php 
    $hasValidUser = isset($user) && is_array($user) && array_key_exists('id', $user);
    if ($hasValidUser): ?>
      <div style="display:flex; align-items:center; gap:10px;">
        <div style="display:flex; flex-direction:column; align-items:flex-end; line-height:1.2;">
          <span style="font-size:0.7rem; color:var(--secondary); font-weight:bold; background:rgba(105, 219, 124, 0.1); padding:2px 6px; border-radius:4px;">● ログイン中</span>
          <span style="font-size:0.75rem; color:var(--muted);">
            <?php 
              $email = $user['email'] ?? '';
              if ($email) {
                $parts = explode('@', $email);
                echo htmlspecialchars(substr($parts[0], 0, 1) . '***@***');
              }
            ?>
          </span>
        </div>
        <nav class="desktop-nav">
          <a href="/templates" class="<?= ($page??'')==='templates'?'active':'' ?>">テンプレ</a>
          <a href="/groups" class="<?= ($page??'')==='groups'?'active':'' ?>">宛先</a>
          <a href="/send" class="<?= ($page??'')==='send'?'active':'' ?>">送信</a>
          <a href="/logs" class="<?= ($page??'')==='logs'?'active':'' ?>">ログ</a>
          <a href="/guide" class="<?= ($page??'')==='guide'?'active':'' ?>">使い方</a>
        </nav>

        <button class="menu-toggle" id="menuToggle">
          <span></span>
          <span></span>
          <span></span>
        </button>
      </div>
    <?php else: ?>
      <a href="/auth/login" class="btn primary" style="padding: 8px 16px; font-size: 0.9rem;">ログイン</a>
    <?php endif; ?>
  </header>

  <div class="nav-overlay" id="navOverlay"></div>
  <nav class="mobile-nav" id="mobileNav">
    <a href="/templates">テンプレート一覧</a>
    <a href="/groups">宛先グループ</a>
    <a href="/send">メール送信</a>
    <a href="/logs">送信ログ</a>
    <a href="/guide">使い方ガイド</a>
    <hr style="border:none; border-top:1px solid var(--border); margin: 20px 0;">
    <form method="post" action="/auth/logout">
      <?php if (function_exists('csrf_token')): ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
      <?php endif; ?>
      <button type="submit" class="btn danger" style="width:100%">ログアウト</button>
    </form>
  </nav>

  <main class="container">
    <?php
      if (!isset($page)) $page = '';
      $contentFile = __DIR__ . '/' . $view . '.php';
      if (file_exists($contentFile)) require $contentFile;
      else echo "<p>ビューが見つかりません (View not found)</p>";
    ?>
  </main>

  <?php if ($hasValidUser): ?>
  <nav class="bottomnav">
    <a href="/templates" class="<?= ($page??'')==='templates'?'active':'' ?>">
      <span>テンプレ</span>
    </a>
    <a href="/groups" class="<?= ($page??'')==='groups'?'active':'' ?>">
      <span>宛先</span>
    </a>
    <a href="/send" class="<?= ($page??'')==='send'?'active':'' ?>">
      <span>送信</span>
    </a>
    <a href="/logs" class="<?= ($page??'')==='logs'?'active':'' ?>">
      <span>ログ</span>
    </a>
    <a href="/guide" class="<?= ($page??'')==='guide'?'active':'' ?>">
      <span>使い方</span>
    </a>
  </nav>
  <?php endif; ?>

  <script>
    const menuToggle = document.getElementById('menuToggle');
    const mobileNav = document.getElementById('mobileNav');
    const navOverlay = document.getElementById('navOverlay');

    if (menuToggle) {
      menuToggle.addEventListener('click', () => {
        mobileNav.classList.toggle('open');
        navOverlay.classList.toggle('show');
      });
    }

    if (navOverlay) {
      navOverlay.addEventListener('click', () => {
        mobileNav.classList.remove('open');
        navOverlay.classList.remove('show');
      });
    }
  </script>
</body>
</html>
