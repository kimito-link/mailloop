<?php $view='groups/edit'; $id = $g['id'] ?? 0;
$to = $g['to'] ?? (json_decode($g['to_json'] ?? '[]', true) ?: []);
$cc = $g['cc'] ?? (json_decode($g['cc_json'] ?? '[]', true) ?: []);
$bcc = $g['bcc'] ?? (json_decode($g['bcc_json'] ?? '[]', true) ?: []);
?>
<h1><?= $id ? 'グループ編集' : 'グループ作成' ?></h1>

<form method="post" action="/groups/save" class="form">
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
