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

<?php
$attemptId = (string)($log['send_attempt_id'] ?? '');
$attemptShort = $attemptId !== '' ? substr($attemptId, 0, 12) . '…' : '';
?>
<?php if ($attemptId !== ''): ?>
  <div class="card">
    <div class="card-title">問い合わせID</div>
    <div class="card-sub" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
      <code id="attemptText"><?=htmlspecialchars($attemptShort)?></code>
      <button class="btn" type="button" id="copyAttemptBtn" data-full="<?=htmlspecialchars($attemptId)?>">コピー</button>
      <span class="muted" id="copyAttemptMsg" style="display:none;">コピーしました</span>
    </div>
  </div>

  <script>
  (function(){
    const btn = document.getElementById('copyAttemptBtn');
    const msg = document.getElementById('copyAttemptMsg');
    if (!btn) return;
    const full = btn.getAttribute('data-full') || '';
    async function copyText(text){
      if (navigator.clipboard && window.isSecureContext) {
        await navigator.clipboard.writeText(text);
        return true;
      }
      // fallback
      const ta = document.createElement('textarea');
      ta.value = text;
      ta.style.position = 'fixed';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);
      ta.focus();
      ta.select();
      let ok = false;
      try { ok = document.execCommand('copy'); } catch(e) {}
      document.body.removeChild(ta);
      return ok;
    }
    btn.addEventListener('click', async function(){
      try {
        await copyText(full);
        if (msg) {
          msg.style.display = 'inline';
          setTimeout(()=>{ msg.style.display='none'; }, 1200);
        }
      } catch(e) {}
    });
  })();
  </script>
<?php endif; ?>

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
