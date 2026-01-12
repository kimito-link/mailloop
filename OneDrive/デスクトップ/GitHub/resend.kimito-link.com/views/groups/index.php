<?php $view='groups/index'; ?>
<h1>宛先グループ</h1>

<div class="char-bubble" style="border-color: var(--secondary);">
  <img src="/assets/img/characters/tanunee/tanuki-yukkuri-normal-mouth-open.png" alt="たぬ姉">
  <div class="text">
    <strong>たぬ姉：</strong><br>
    「送りたい相手のメールアドレスをグループにしておくと便利だよぉ。<br>
    CCやBCCも一緒に設定できるから、一括送信も楽ちんだねぇ。」
  </div>
</div>

<form method="get" action="/groups" class="row">
  <input name="q" value="<?=htmlspecialchars($q??'')?>" placeholder="検索（グループ名）">
  <button class="btn" type="submit">検索</button>
</form>

<div class="row">
  <a class="btn primary" href="/groups/new">＋ 新規グループ</a>
</div>

<?php if (empty($groups)): ?>
  <p class="muted">グループがありません。</p>
<?php else: ?>
  <div class="cards">
    <?php foreach ($groups as $g): 
      $to = $g['to'] ?? (json_decode($g['to_json'] ?? '[]', true) ?: []);
      $cc = $g['cc'] ?? (json_decode($g['cc_json'] ?? '[]', true) ?: []);
      $bcc = $g['bcc'] ?? (json_decode($g['bcc_json'] ?? '[]', true) ?: []);
    ?>
      <div class="card">
        <div class="card-title"><?=htmlspecialchars($g['name'] ?? '')?></div>
        <div class="card-sub">To <?=count($to)?> / CC <?=count($cc)?> / BCC <?=count($bcc)?></div>
        <div class="card-actions">
          <a class="btn" href="/groups/edit?id=<?=urlencode((string)$g['id'])?>">編集</a>
          <form method="post" action="/groups/delete" onsubmit="return confirm('削除しますか？')" style="display:inline">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?=htmlspecialchars((string)$g['id'])?>">
            <button class="btn danger" type="submit">削除</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
