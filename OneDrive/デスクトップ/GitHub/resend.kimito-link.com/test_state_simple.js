const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    console.log('=== OAuth認可URLのstate確認（簡易版） ===\n');

    // 通常のログインURLにアクセス（リダイレクトを追跡）
    const loginUrl = 'https://resend.kimito-link.com/auth/login';
    console.log(`アクセス中: ${loginUrl}`);
    
    // リダイレクトを待機
    await page.goto(loginUrl, { 
      waitUntil: 'networkidle',
      timeout: 30000 
    });

    const finalUrl = page.url();
    console.log(`\n最終URL: ${finalUrl.substring(0, 200)}...`);

    // stateパラメータを抽出
    try {
      const urlObj = new URL(finalUrl);
      const stateParam = urlObj.searchParams.get('state');
      
      console.log('\n=== State検証結果 ===');
      if (stateParam) {
        console.log('✅ stateパラメータが含まれています！');
        console.log(`State値: ${stateParam.substring(0, 32)}...`);
        console.log(`State値（全体）: ${stateParam}`);
        console.log(`State値の長さ: ${stateParam.length}文字`);
      } else {
        console.log('❌ stateパラメータが含まれていません！');
      }

      // その他のパラメータも確認
      console.log('\n=== URLパラメータ一覧 ===');
      console.log(`client_id: ${urlObj.searchParams.get('client_id') ? 'あり' : 'なし'}`);
      console.log(`redirect_uri: ${urlObj.searchParams.get('redirect_uri') || 'なし'}`);
      console.log(`response_type: ${urlObj.searchParams.get('response_type') || 'なし'}`);
      console.log(`scope: ${urlObj.searchParams.get('scope') ? 'あり' : 'なし'}`);
      console.log(`access_type: ${urlObj.searchParams.get('access_type') || 'なし'}`);
      console.log(`prompt: ${urlObj.searchParams.get('prompt') || 'なし'}`);
      console.log(`state: ${stateParam ? 'あり' : 'なし'}`);

      // 認可URLの形式を確認
      const isCorrectFormat = finalUrl.includes('accounts.google.com/o/oauth2/v2/auth') || 
                               finalUrl.includes('accounts.google.com/v3/signin');
      console.log(`\n=== URL形式確認 ===`);
      if (isCorrectFormat) {
        console.log('✅ Google認証URLの形式です');
      } else {
        console.log('❌ Google認証URLの形式ではありません');
      }

    } catch (e) {
      console.error(`URL解析エラー: ${e.message}`);
    }

    // スクリーンショットを保存
    await page.screenshot({ path: 'state_check_simple.png', fullPage: true });
    console.log('\nスクリーンショットを保存しました: state_check_simple.png');

    console.log('\n5秒後にブラウザを閉じます...');
    await page.waitForTimeout(5000);

  } catch (error) {
    console.error('\n❌ エラーが発生しました:');
    console.error(error.message);
    if (error.stack) {
      console.error(error.stack);
    }
    await page.screenshot({ path: 'error_state_check_simple.png', fullPage: true });
  } finally {
    await browser.close();
  }
})();
