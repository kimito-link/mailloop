<?php $view='send/prepare'; ?>
<h1>送信</h1>

<form method="post" action="/send/confirm" class="form">
  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
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

  <label>宛先グループ（選択すると自動入力されます）</label>
  <select name="group_id" id="groupSelect">
    <option value="">選択してください（または手動入力）</option>
    <?php foreach ($groups as $g): ?>
      <option value="<?=htmlspecialchars((string)$g['id'])?>" 
        data-to="<?=htmlspecialchars($g['to_json'] ?? '[]')?>"
        data-cc="<?=htmlspecialchars($g['cc_json'] ?? '[]')?>"
        data-bcc="<?=htmlspecialchars($g['bcc_json'] ?? '[]')?>"
      ><?=htmlspecialchars($g['name'] ?? '')?></option>
    <?php endforeach; ?>
  </select>

  <div class="card" style="margin-top:10px;">
    <div class="card-title">宛先編集</div>
    <div class="card-sub">カンマ区切りで複数のメールアドレスを入力できます。</div>
    
    <?php
      $toEmails = array_map(fn($item) => is_array($item) ? ($item['email'] ?? '') : $item, $initial_to ?? []);
      $ccEmails = array_map(fn($item) => is_array($item) ? ($item['email'] ?? '') : $item, $initial_cc ?? []);
      $bccEmails = array_map(fn($item) => is_array($item) ? ($item['email'] ?? '') : $item, $initial_bcc ?? []);
    ?>
    <label style="margin-top:10px;">To</label>
    <input name="to_list" id="inputTo" value="<?=htmlspecialchars(implode(', ', $toEmails))?>" placeholder="example@example.com, test@test.com">
    
    <label style="margin-top:10px;">CC</label>
    <input name="cc_list" id="inputCc" value="<?=htmlspecialchars(implode(', ', $ccEmails))?>" placeholder="">

    <label style="margin-top:10px;">BCC</label>
    <input name="bcc_list" id="inputBcc" value="<?=htmlspecialchars(implode(', ', $bccEmails))?>" placeholder="">
  </div>

  <label>件名（必要なら編集）</label>
  <input name="subject" value="<?=htmlspecialchars($t['subject'] ?? '')?>">

  <label>本文（必要なら編集）</label>
  <textarea name="body_text" rows="10"><?=htmlspecialchars($t['body_text'] ?? '')?></textarea>

  <button class="btn primary" type="submit">確認へ</button>
</form>

<script>
(function(){
  const sel = document.getElementById('groupSelect');
  const iTo = document.getElementById('inputTo');
  const iCc = document.getElementById('inputCc');
  const iBcc = document.getElementById('inputBcc');

  if(sel){
    sel.addEventListener('change', function(){
      const opt = sel.options[sel.selectedIndex];
      if(!opt.value) return;

      try {
        const toList = JSON.parse(opt.getAttribute('data-to') || '[]');
        const ccList = JSON.parse(opt.getAttribute('data-cc') || '[]');
        const bccList = JSON.parse(opt.getAttribute('data-bcc') || '[]');

        const extractEmails = (list) => list.map(item => typeof item === 'object' ? (item.email || '') : item).filter(e => e !== '');

        iTo.value = extractEmails(toList).join(', ');
        iCc.value = extractEmails(ccList).join(', ');
        iBcc.value = extractEmails(bccList).join(', ');
      } catch(e) {
        console.error('JSON parse error', e);
      }
    });
  }
})();
</script>
