<?php $view='logs/view'; 
$cnt = $log['recipient_counts_json'] ?? null;
$counts = $log['counts'] ?? (is_string($cnt) ? (json_decode($cnt, true) ?: []) : []);
$err = $log['error_json'] ?? null;
$error = $log['error'] ?? (is_string($err) ? (json_decode($err, true) ?: null) : null);
?>
<h1>ログ詳細</h1>

<div class="card">
  <div class="card-title">ステータス</div>
  <div class="card-sub"><?=htmlspecialchars($log['status'] ?? '')?></div>
</div>

<div class="card">
  <div class="card-title">件名</div>
  <div class="card-sub"><?=htmlspecialchars($log['subject_snapshot'] ?? '')?></div>
</div>

<div class="card">
  <div class="card-title">宛先件数</div>
  <div class="card-sub">To <?= (int)($counts['to'] ?? 0) ?> / CC <?= (int)($counts['cc'] ?? 0) ?> / BCC <?= (int)($counts['bcc'] ?? 0) ?></div>
</div>

<?php if ($error): ?>
  <div class="alert danger">
    <div><strong>エラー</strong></div>
    <pre class="preview"><?=htmlspecialchars(json_encode($error, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT))?></pre>
  </div>
<?php endif; ?>

<a class="btn" href="/logs">戻る</a>
