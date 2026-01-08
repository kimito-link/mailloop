<?php $view='error'; ?>
<h1>エラー</h1>
<div style="background: #fee; border: 1px solid #fcc; padding: 15px; border-radius: 4px; margin: 20px 0;">
  <p style="color: #c00; font-weight: bold; margin: 0 0 10px 0;"><?= htmlspecialchars($message ?? 'エラーが発生しました') ?></p>
  <?php if (!empty($detail) && (defined('APP_DEBUG') && APP_DEBUG)): ?>
    <details style="margin-top: 10px;">
      <summary style="cursor: pointer; color: #666;">詳細（開発モード）</summary>
      <pre style="background: #fff; padding: 10px; margin-top: 10px; border: 1px solid #ccc; border-radius: 4px; overflow-x: auto; font-size: 12px;"><?= htmlspecialchars($detail) ?></pre>
    </details>
  <?php endif; ?>
</div>
<p><a href="/" class="btn">トップページに戻る</a></p>
