const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    console.log('=== OAuth認可URLのstate詳細確認 ===\n');

    // 1. デバッグ画面にアクセス
    const debugUrl = 'https://resend.kimito-link.com/auth/login?debug=1';
    console.log(`1. デバッグ画面にアクセス: ${debugUrl}`);
    
    // リダイレクトを追跡
    const response = await page.goto(debugUrl, { 
      waitUntil: 'networkidle',
      timeout: 30000 
    });

    console.log(`   ステータスコード: ${response.status()}`);
    console.log(`   最終URL: ${page.url()}`);

    // 2. ページがリダイレクトされたか確認
    if (page.url().includes('accounts.google.com')) {
      console.log('\n❌ 問題: デバッグ画面が表示されず、Googleにリダイレクトされました');
      console.log('   これは、デバッグモードが動作していないか、サーバー側のファイルが古い可能性があります');
      
      // リダイレクト前のURLを確認
      console.log('\n   リダイレクトチェーンを確認...');
      const redirectChain = response.request().redirectChain();
      if (redirectChain.length > 0) {
        console.log(`   リダイレクト数: ${redirectChain.length}`);
        redirectChain.forEach((req, i) => {
          console.log(`   ${i + 1}. ${req.url()}`);
        });
      }
      
      // 現在のURLからstateパラメータを抽出
      const currentUrl = page.url();
      const urlObj = new URL(currentUrl);
      const stateParam = urlObj.searchParams.get('state');
      
      console.log('\n   現在のGoogle URLからstateを確認:');
      if (stateParam) {
        console.log(`   ✅ stateパラメータが見つかりました: ${stateParam.substring(0, 32)}...`);
      } else {
        console.log('   ❌ stateパラメータが見つかりませんでした');
      }
      
      // URL全体を表示（最初の500文字）
      console.log('\n   現在のURL（最初の500文字）:');
      console.log(currentUrl.substring(0, 500));
      
    } else {
      // デバッグ画面が表示された場合
      console.log('\n✅ デバッグ画面が表示されました');
      
      // ページの内容を取得
      const content = await page.content();
      const bodyText = await page.textContent('body').catch(() => '');
      
      console.log('\n2. デバッグ画面の内容:');
      console.log(bodyText.substring(0, 1000));
      
      // 認可URLを抽出（複数の方法で試す）
      let authUrl = null;
      
      // 方法1: HTMLから直接抽出
      const urlMatch1 = content.match(/認可URL:[\s\S]*?<a[^>]+href=['"]([^'"]+)['"]/);
      if (urlMatch1) {
        authUrl = urlMatch1[1];
        console.log('\n✅ 方法1で認可URLを抽出しました');
      }
      
      // 方法2: テキストから抽出
      if (!authUrl) {
        const urlMatch2 = bodyText.match(/https:\/\/accounts\.google\.com\/o\/oauth2\/v2\/auth[^\s<>"]+/);
        if (urlMatch2) {
          authUrl = urlMatch2[0];
          console.log('\n✅ 方法2で認可URLを抽出しました');
        }
      }
      
      // 方法3: ページ内のすべてのリンクを確認
      if (!authUrl) {
        const links = await page.$$eval('a', links => links.map(a => a.href));
        const googleAuthLink = links.find(href => href.includes('accounts.google.com/o/oauth2/v2/auth'));
        if (googleAuthLink) {
          authUrl = googleAuthLink;
          console.log('\n✅ 方法3で認可URLを抽出しました');
        }
      }
      
      if (authUrl) {
        console.log(`\n3. 抽出された認可URL:`);
        console.log(authUrl.substring(0, 300) + '...');
        
        // stateパラメータの確認
        const hasState = authUrl.includes('state=');
        console.log(`\n4. State検証:`);
        if (hasState) {
          console.log('✅ stateパラメータが含まれています');
          
          // stateの値を抽出
          const stateMatch = authUrl.match(/[&?]state=([^&]+)/);
          if (stateMatch) {
            const stateValue = decodeURIComponent(stateMatch[1]);
            console.log(`   State値（先頭32文字）: ${stateValue.substring(0, 32)}...`);
            console.log(`   State値（全体）: ${stateValue}`);
            console.log(`   State値の長さ: ${stateValue.length}文字`);
          }
        } else {
          console.log('❌ stateパラメータが含まれていません！');
        }
        
        // URLパラメータを解析
        try {
          const urlObj = new URL(authUrl);
          console.log(`\n5. URLパラメータ解析:`);
          console.log(`   client_id: ${urlObj.searchParams.get('client_id') ? 'あり' : 'なし'}`);
          console.log(`   redirect_uri: ${urlObj.searchParams.get('redirect_uri') ? 'あり' : 'なし'}`);
          console.log(`   response_type: ${urlObj.searchParams.get('response_type')}`);
          console.log(`   scope: ${urlObj.searchParams.get('scope') ? 'あり' : 'なし'}`);
          console.log(`   access_type: ${urlObj.searchParams.get('access_type')}`);
          console.log(`   prompt: ${urlObj.searchParams.get('prompt')}`);
          console.log(`   state: ${urlObj.searchParams.get('state') ? 'あり' : 'なし'}`);
        } catch (e) {
          console.log(`   URL解析エラー: ${e.message}`);
        }
      } else {
        console.log('\n❌ 認可URLを抽出できませんでした');
        console.log('   ページのHTMLを保存します...');
        await page.screenshot({ path: 'debug_page_screenshot.png', fullPage: true });
      }
      
      // デバッグ画面のState検証結果も確認
      if (bodyText.includes('✅ stateパラメータが含まれています')) {
        console.log('\n✅ デバッグ画面でもstateが確認されました');
      } else if (bodyText.includes('❌ stateパラメータが含まれていません')) {
        console.log('\n❌ デバッグ画面でstateが確認されませんでした');
      }
    }

    // スクリーンショットを保存
    await page.screenshot({ path: 'state_check_result.png', fullPage: true });
    console.log('\nスクリーンショットを保存しました: state_check_result.png');

    // 3秒待機してからブラウザを閉じる
    console.log('\n3秒後にブラウザを閉じます...');
    await page.waitForTimeout(3000);

  } catch (error) {
    console.error('\n❌ エラーが発生しました:');
    console.error(error.message);
    if (error.stack) {
      console.error(error.stack);
    }
    await page.screenshot({ path: 'error_state_check.png', fullPage: true });
    console.log('エラー時のスクリーンショットを保存しました: error_state_check.png');
  } finally {
    await browser.close();
  }
})();
