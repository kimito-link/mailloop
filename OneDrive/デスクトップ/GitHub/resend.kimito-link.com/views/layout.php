<?php ?><!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>MailLoop</title>
  <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
  <header class="topbar">
    <div class="brand">MailLoop</div>
    <?php 
    // $userが配列でidキーがある場合のみ表示
    $hasValidUser = isset($user) && is_array($user) && array_key_exists('id', $user);
    if ($hasValidUser): ?>
      <form method="post" action="/auth/logout" class="logout">
        <?php if (function_exists('csrf_token')): ?>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
        <?php endif; ?>
        <button type="submit">ログアウト</button>
      </form>
    <?php endif; ?>
  </header>

  <main class="container">
    <?php
      $contentFile = __DIR__ . '/' . $view . '.php';
      if (file_exists($contentFile)) require $contentFile;
      else echo "<p>View not found</p>";
    ?>
  </main>

  <?php 
  // $userが配列でidキーがある場合のみ表示
  $hasValidUser = isset($user) && is_array($user) && array_key_exists('id', $user);
  if ($hasValidUser): ?>
  <nav class="bottomnav">
    <a class="<?=($page==='templates'?'active':'')?>" href="/templates">テンプレ</a>
    <a class="<?=($page==='groups'?'active':'')?>" href="/groups">宛先</a>
    <a class="<?=($page==='send'?'active':'')?>" href="/send">送信</a>
    <a class="<?=($page==='logs'?'active':'')?>" href="/logs">ログ</a>
  </nav>
  <?php endif; ?>
</body>
</html>
