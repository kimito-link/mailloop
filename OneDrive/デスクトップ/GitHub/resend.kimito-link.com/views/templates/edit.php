<?php $view='templates/edit'; $id = $t['id'] ?? 0; ?>
<h1><?= $id ? 'テンプレ編集' : 'テンプレ作成' ?></h1>

<?php if (!empty($errors)): ?>
  <div style="background: #fee; border: 1px solid #fcc; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
    <strong style="color: #c00;">エラー:</strong>
    <ul style="margin: 5px 0 0 20px; padding: 0;">
      <?php foreach ($errors as $err): ?>
        <li style="color: #c00;"><?= htmlspecialchars($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" action="/templates/save" class="form">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
  <input type="hidden" name="id" value="<?=htmlspecialchars((string)$id)?>">
  <label>タイトル（自分用）</label>
  <input name="title" value="<?=htmlspecialchars($t['title'] ?? '')?>" required>

  <label>件名</label>
  <input name="subject" value="<?=htmlspecialchars($t['subject'] ?? '')?>">

  <label>本文</label>
  <textarea name="body_text" rows="10"><?=htmlspecialchars($t['body_text'] ?? '')?></textarea>

  <label>添付（MVPはリンク）</label>
  <?php
    $attUrl = '';
    if (!empty($t['attachments'])) $attUrl = $t['attachments'][0]['url'] ?? '';
    if (!empty($t['attachments_json'])) {
      $arr = json_decode($t['attachments_json'], true) ?: [];
      $attUrl = $arr[0]['url'] ?? $attUrl;
    }
  ?>
  <input name="attachment_url" value="<?=htmlspecialchars($attUrl)?>" placeholder="https://drive.google.com/...">

  <div class="row">
    <button class="btn primary" type="submit">保存</button>
    <a class="btn" href="/templates">戻る</a>
  </div>
</form>
