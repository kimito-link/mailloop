<?php $view='send/prepare'; ?>
<h1>送信</h1>

<form method="post" action="/send/confirm" class="form">
  <?php if ($t): ?>
    <input type="hidden" name="template_id" value="<?=htmlspecialchars((string)$t['id'])?>">
    <div class="card">
      <div class="card-title"><?=htmlspecialchars($t['title'] ?? '')?></div>
      <div class="card-sub"><?=htmlspecialchars($t['subject'] ?? '')?></div>
    </div>
  <?php else: ?>
    <p class="muted">テンプレ一覧から「送信」を押してください。</p>
    <a class="btn" href="/templates">テンプレ一覧へ</a>
    <?php return; ?>
  <?php endif; ?>

  <label>宛先グループ</label>
  <select name="group_id" required>
    <option value="">選択してください</option>
    <?php foreach ($groups as $g): ?>
      <option value="<?=htmlspecialchars((string)$g['id'])?>"><?=htmlspecialchars($g['name'] ?? '')?></option>
    <?php endforeach; ?>
  </select>

  <label>件名（必要なら編集）</label>
  <input name="subject" value="<?=htmlspecialchars($t['subject'] ?? '')?>">

  <label>本文（必要なら編集）</label>
  <textarea name="body_text" rows="10"><?=htmlspecialchars($t['body_text'] ?? '')?></textarea>

  <button class="btn primary" type="submit">確認へ</button>
</form>
