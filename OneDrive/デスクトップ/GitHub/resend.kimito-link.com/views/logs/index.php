<?php $view='logs/index'; ?>
<h1>送信ログ</h1>

<?php if (empty($logs)): ?>
  <p class="muted">ログがありません。</p>
<?php else: ?>
  <div class="cards">
    <?php foreach ($logs as $l): 
      $cnt = $l['recipient_counts_json'] ?? null;
      $counts = $l['counts'] ?? (is_string($cnt) ? (json_decode($cnt, true) ?: []) : []);
    ?>
      <a class="card" href="/logs/view?id=<?=urlencode((string)$l['id'])?>" style="text-decoration:none;color:inherit;">
        <div class="card-title"><?=htmlspecialchars($l['subject_snapshot'] ?? '')?></div>
        <div class="card-sub">
          <?=htmlspecialchars($l['status'] ?? '')?> /
          To <?= (int)($counts['to'] ?? 0) ?> CC <?= (int)($counts['cc'] ?? 0) ?> BCC <?= (int)($counts['bcc'] ?? 0) ?>
        </div>
        <div class="muted"><?=htmlspecialchars(isset($l['created_at']) ? (is_numeric($l['created_at'])?date('Y-m-d H:i', (int)$l['created_at']):$l['created_at']) : '')?></div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
