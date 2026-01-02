<?php $view='send/confirm'; ?>
<h1>送信確認</h1>

<?php if (!empty($warnings)): ?>
  <div class="alert warning">
    <?php foreach ($warnings as $w): ?>
      <div><?=htmlspecialchars($w)?></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<div class="card">
  <div class="card-title">宛先件数</div>
  <div class="card-sub">To <?= (int)$counts['to'] ?> / CC <?= (int)$counts['cc'] ?> / BCC <?= (int)$counts['bcc'] ?>（合計 <?= (int)$counts['total'] ?>）</div>
</div>

<div class="card">
  <div class="card-title">件名</div>
  <div class="card-sub"><?=htmlspecialchars($subject)?></div>
</div>

<div class="card">
  <div class="card-title">本文プレビュー</div>
  <pre class="preview"><?=htmlspecialchars($body)?></pre>
</div>

<form method="post" action="/send/execute" class="row">
  <input type="hidden" name="template_id" value="<?=htmlspecialchars((string)$t['id'])?>">
  <input type="hidden" name="group_id" value="<?=htmlspecialchars((string)$g['id'])?>">
  <input type="hidden" name="subject" value="<?=htmlspecialchars($subject)?>">
  <input type="hidden" name="body_text" value="<?=htmlspecialchars($body)?>">
  <input type="hidden" name="mode" value="test">
  <button class="btn" type="submit">テスト送信（自分宛）</button>
</form>

<form method="post" action="/send/execute" onsubmit="return confirm('この内容で送信します。よろしいですか？')" class="row">
  <input type="hidden" name="template_id" value="<?=htmlspecialchars((string)$t['id'])?>">
  <input type="hidden" name="group_id" value="<?=htmlspecialchars((string)$g['id'])?>">
  <input type="hidden" name="subject" value="<?=htmlspecialchars($subject)?>">
  <input type="hidden" name="body_text" value="<?=htmlspecialchars($body)?>">
  <input type="hidden" name="mode" value="send">
  <button class="btn primary" type="submit">送信する</button>
  <a class="btn" href="/send?template_id=<?=urlencode((string)$t['id'])?>">戻る</a>
</form>
