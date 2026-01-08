const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    console.log('=== OAuth認可URLのstate確認 ===\n');

    // デバッグ画面にアクセス
    const debugUrl = 'https://resend.kimito-link.com/auth/login?debug=1';
    console.log(`アクセス中: ${debugUrl}`);
    await page.goto(debugUrl, { waitUntil: 'networkidle' });

    // ページの内容を取得
    const content = await page.content();
    
    // 認可URLを抽出
    const urlMatch = content.match(/認可URL:[\s\S]*?<a[^>]+href=['"]([^'"]+)['"]/);
    if (!urlMatch) {
      console.log('❌ 認可URLが見つかりません');
      console.log('\nページの内容:');
      console.log(await page.textContent('body'));
      return;
    }

    const authUrl = urlMatch[1];
    console.log(`\n✅ 認可URLを取得しました:`);
    console.log(authUrl.substring(0, 200) + '...');

    // stateパラメータの確認
    const hasState = authUrl.includes('state=');
    console.log(`\n=== State検証 ===`);
    if (hasState) {
      console.log('✅ stateパラメータが含まれています');
      
      // stateの値を抽出
      const stateMatch = authUrl.match(/[&?]state=([^&]+)/);
      if (stateMatch) {
        const stateValue = stateMatch[1];
        console.log(`State値（先頭32文字）: ${stateValue.substring(0, 32)}...`);
        console.log(`State値（全体）: ${stateValue}`);
      }
    } else {
      console.log('❌ stateパラメータが含まれていません！');
    }

    // 認可URLが正しい形式か確認
    const isCorrectFormat = authUrl.startsWith('https://accounts.google.com/o/oauth2/v2/auth?');
    console.log(`\n=== URL形式確認 ===`);
    if (isCorrectFormat) {
      console.log('✅ 正しい認可URL形式です');
    } else {
      console.log('❌ 認可URLの形式が正しくありません');
      console.log(`実際のURL: ${authUrl.substring(0, 100)}...`);
    }

    // デバッグ画面のState検証結果も確認
    const stateCheckText = await page.textContent('body');
    if (stateCheckText.includes('✅ stateパラメータが含まれています')) {
      console.log('\n✅ デバッグ画面でもstateが確認されました');
    } else if (stateCheckText.includes('❌ stateパラメータが含まれていません')) {
      console.log('\n❌ デバッグ画面でstateが確認されませんでした');
    }

    // スクリーンショットを保存
    await page.screenshot({ path: 'debug_state_check.png', fullPage: true });
    console.log('\nスクリーンショットを保存しました: debug_state_check.png');

  } catch (error) {
    console.error('エラーが発生しました:', error);
    await page.screenshot({ path: 'error_state_check.png', fullPage: true });
  } finally {
    await browser.close();
  }
})();
