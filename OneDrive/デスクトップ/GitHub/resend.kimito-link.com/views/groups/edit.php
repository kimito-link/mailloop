<?php $view='groups/edit'; $id = $g['id'] ?? 0;
// JSON形式から配列に変換（[{"email":"...","name":"..."}]形式に対応）
$to = $g['to'] ?? [];
$cc = $g['cc'] ?? [];
$bcc = $g['bcc'] ?? [];
if (empty($to) && !empty($g['to_json'])) {
  $toJson = json_decode($g['to_json'] ?? '[]', true) ?: [];
  $to = array_column($toJson, 'email');
}
if (empty($cc) && !empty($g['cc_json'])) {
  $ccJson = json_decode($g['cc_json'] ?? '[]', true) ?: [];
  $cc = array_column($ccJson, 'email');
}
if (empty($bcc) && !empty($g['bcc_json'])) {
  $bccJson = json_decode($g['bcc_json'] ?? '[]', true) ?: [];
  $bcc = array_column($bccJson, 'email');
}
?>
<h1><?= $id ? 'グループ編集' : 'グループ作成' ?></h1>

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

<?php if (!empty($warn)): ?>
  <div style="background: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
    <strong style="color: #856404;">注意:</strong>
    <ul style="margin: 5px 0 0 20px; padding: 0;">
      <?php foreach ($warn as $w): ?>
        <li style="color: #856404;"><?= htmlspecialchars($w) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="post" action="/groups/save" class="form">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
  <input type="hidden" name="id" value="<?=htmlspecialchars((string)$id)?>">
  <label>グループ名</label>
  <input name="name" value="<?=htmlspecialchars($g['name'] ?? '')?>" required>

  <label>To（改行/カンマ区切りで貼り付けOK）</label>
  <textarea name="to_list" rows="5"><?=htmlspecialchars(implode("\n", $to))?></textarea>

  <label>CC</label>
  <textarea name="cc_list" rows="5"><?=htmlspecialchars(implode("\n", $cc))?></textarea>

  <label>BCC（100人など）</label>
  <textarea name="bcc_list" rows="7"><?=htmlspecialchars(implode("\n", $bcc))?></textarea>

  <div class="row">
    <button class="btn primary" type="submit">保存</button>
    <a class="btn" href="/groups">戻る</a>
  </div>
</form>

<p class="muted">※ To/CC/BCCをまたいだ重複は自動で整理（To優先→CC→BCC）します。</p>
