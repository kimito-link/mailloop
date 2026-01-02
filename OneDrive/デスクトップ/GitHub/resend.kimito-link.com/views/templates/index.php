<?php $view='templates/index'; ?>
<h1>テンプレ一覧</h1>

<form method="get" action="/templates" class="row">
  <input name="q" value="<?=htmlspecialchars($q??'')?>" placeholder="検索（件名/タイトル）">
  <button class="btn" type="submit">検索</button>
</form>

<div class="row">
  <a class="btn primary" href="/templates/new">＋ 新規テンプレ</a>
</div>

<?php if (empty($templates)): ?>
  <p class="muted">テンプレがありません。</p>
<?php else: ?>
  <div class="cards">
    <?php foreach ($templates as $t): ?>
      <div class="card">
        <div class="card-title"><?=htmlspecialchars($t['title'] ?? '')?></div>
        <div class="card-sub"><?=htmlspecialchars($t['subject'] ?? '')?></div>
        <div class="card-actions">
          <a class="btn" href="/send?template_id=<?=urlencode((string)$t['id'])?>">送信</a>
          <a class="btn" href="/templates/edit?id=<?=urlencode((string)$t['id'])?>">編集</a>
          <form method="post" action="/templates/delete" onsubmit="return confirm('削除しますか？')" style="display:inline">
            <input type="hidden" name="id" value="<?=htmlspecialchars((string)$t['id'])?>">
            <button class="btn danger" type="submit">削除</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
