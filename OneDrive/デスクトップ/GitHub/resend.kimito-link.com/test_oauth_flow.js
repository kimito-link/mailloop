const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();

  const baseUrl = 'https://resend.kimito-link.com';
  
  console.log('=== OAuth認証フローテスト開始 ===\n');

  try {
    // 1) /auth/login にアクセス
    console.log('1. /auth/login にアクセス...');
    await page.goto(`${baseUrl}/auth/login`, { waitUntil: 'networkidle' });
    
    // リダイレクトURLを確認
    const currentUrl = page.url();
    console.log(`   現在のURL: ${currentUrl}`);
    
    // stateパラメータが含まれているか確認
    if (currentUrl.includes('accounts.google.com')) {
      const urlObj = new URL(currentUrl);
      const stateParam = urlObj.searchParams.get('state');
      console.log(`   stateパラメータ: ${stateParam ? stateParam.substring(0, 20) + '...' : '見つかりません'}`);
      
      if (!stateParam) {
        console.error('   ❌ エラー: stateパラメータがURLに含まれていません！');
        console.log(`   完全なURL: ${currentUrl}`);
      } else {
        console.log('   ✅ stateパラメータが含まれています');
      }
    } else {
      console.log(`   ⚠️  Googleの認証ページにリダイレクトされていません`);
      console.log(`   ページタイトル: ${await page.title()}`);
      
      // エラーメッセージを確認
      const errorText = await page.textContent('body').catch(() => '');
      if (errorText.includes('エラー') || errorText.includes('error')) {
        console.log(`   エラー内容: ${errorText.substring(0, 200)}`);
      }
    }

    // 2) Googleの認証ページでログイン（手動操作が必要な場合は待機）
    if (currentUrl.includes('accounts.google.com')) {
      console.log('\n2. Googleの認証ページを確認...');
      console.log('   手動でログインと同意を行ってください...');
      console.log('   (最大120秒待機します)');
      
      // 認証完了を待つ（/auth/callback にリダイレクトされるまで）
      try {
        await page.waitForURL(url => url.includes('/auth/callback'), { timeout: 120000 });
        console.log('   ✅ /auth/callback にリダイレクトされました');
      } catch (e) {
        console.log('   ⚠️  /auth/callback へのリダイレクトを待機中にタイムアウトしました');
        console.log(`   現在のURL: ${page.url()}`);
      }
      
      const callbackUrl = page.url();
      console.log(`\n3. /auth/callback にリダイレクトされました`);
      console.log(`   コールバックURL: ${callbackUrl}`);
      
      // URLパラメータを確認
      const callbackUrlObj = new URL(callbackUrl);
      const codeParam = callbackUrlObj.searchParams.get('code');
      const stateParam = callbackUrlObj.searchParams.get('state');
      
      console.log(`   codeパラメータ: ${codeParam ? codeParam.substring(0, 20) + '...' : '見つかりません'}`);
      console.log(`   stateパラメータ: ${stateParam ? stateParam.substring(0, 20) + '...' : '見つかりません'}`);
      
      if (!stateParam) {
        console.error('   ❌ エラー: stateパラメータがコールバックURLに含まれていません！');
      } else {
        console.log('   ✅ stateパラメータが含まれています');
      }
      
      // ページの内容を確認
      await page.waitForTimeout(3000); // ページ読み込み待機
      const pageTitle = await page.title();
      const pageContent = await page.textContent('body').catch(() => '');
      
      console.log(`\n4. ページ内容を確認...`);
      console.log(`   タイトル: ${pageTitle}`);
      
      if (pageContent.includes('state不一致') || pageContent.includes('state mismatch')) {
        console.error('   ❌ エラー: state不一致エラーが発生しています');
        console.log('   ページのスクリーンショットを保存します...');
        await page.screenshot({ path: 'oauth_error_state_mismatch.png' });
      } else if (pageContent.includes('エラー') || pageContent.includes('error')) {
        console.error(`   ❌ エラーが発生しています: ${pageContent.substring(0, 200)}`);
        console.log('   ページのスクリーンショットを保存します...');
        await page.screenshot({ path: 'oauth_error.png' });
      } else if (callbackUrl.includes('/templates') || callbackUrl.includes('/send') || callbackUrl.includes('/logs')) {
        console.log('   ✅ 認証が成功し、アプリケーションにリダイレクトされました');
      } else {
        console.log(`   ⚠️  予期しない状態です`);
        console.log(`   現在のURL: ${callbackUrl}`);
      }
    } else {
      // Googleの認証ページにリダイレクトされなかった場合
      await page.waitForTimeout(2000);
      const pageTitle = await page.title();
      const pageContent = await page.textContent('body').catch(() => '');
      console.log(`\n2. ページ内容を確認...`);
      console.log(`   タイトル: ${pageTitle}`);
      console.log(`   URL: ${currentUrl}`);
      if (pageContent.includes('エラー')) {
        console.error(`   ❌ エラーが発生しています: ${pageContent.substring(0, 200)}`);
        await page.screenshot({ path: 'oauth_error_initial.png' });
      }
    }

    // 3) Cookieを確認
    console.log('\n5. Cookieを確認...');
    const cookies = await context.cookies();
    const sessionCookie = cookies.find(c => c.name === 'mailloop_session');
    if (sessionCookie) {
      console.log(`   ✅ mailloop_session Cookieが見つかりました`);
      console.log(`   値: ${sessionCookie.value.substring(0, 20)}...`);
      console.log(`   domain: ${sessionCookie.domain}`);
      console.log(`   secure: ${sessionCookie.secure}`);
      console.log(`   httpOnly: ${sessionCookie.httpOnly}`);
      console.log(`   sameSite: ${sessionCookie.sameSite || '未設定'}`);
    } else {
      console.error('   ❌ mailloop_session Cookieが見つかりません');
      console.log(`   すべてのCookie: ${cookies.map(c => c.name).join(', ')}`);
    }

    // 4) ネットワークリクエストを確認
    console.log('\n6. ネットワークリクエストを確認...');
    const requests = [];
    page.on('request', request => {
      if (request.url().includes('auth')) {
        requests.push({
          url: request.url(),
          method: request.method(),
          headers: request.headers()
        });
      }
    });

    await page.waitForTimeout(1000);

    console.log(`   /auth/login へのリクエスト数: ${requests.filter(r => r.url.includes('/auth/login')).length}`);
    console.log(`   /auth/callback へのリクエスト数: ${requests.filter(r => r.url.includes('/auth/callback')).length}`);

  } catch (error) {
    console.error('\n❌ エラーが発生しました:');
    console.error(error.message);
    console.error(error.stack);
  } catch (error) {
    console.error('\n❌ 予期しないエラーが発生しました:');
    console.error(error.message);
    if (error.stack) {
      console.error(error.stack);
    }
    // エラー時もスクリーンショットを保存
    try {
      await page.screenshot({ path: 'oauth_error_unexpected.png' });
    } catch (e) {
      // スクリーンショット保存に失敗しても続行
    }
  } finally {
    console.log('\n=== テスト完了 ===');
    console.log('\n結果を確認するため、30秒間ブラウザを開いたままにします...');
    console.log('(手動でブラウザを閉じることもできます)');
    try {
      await page.waitForTimeout(30000);
    } catch (e) {
      // タイムアウトやエラーが発生しても続行
    }
    console.log('\nブラウザを閉じます...');
    try {
      await browser.close();
    } catch (e) {
      // ブラウザが既に閉じられている場合
      console.log('ブラウザは既に閉じられています');
    }
  }
})();
