const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: false });
  const context = await browser.newContext();
  const page = await context.newPage();

  try {
    console.log('アクセス中: https://resend.kimito-link.com/auth/login');
    
    // リダイレクトを監視
    let redirectUrl = null;
    page.on('response', async (response) => {
      const url = response.url();
      if (url.includes('accounts.google.com') && url.includes('oauth2')) {
        redirectUrl = url;
        console.log('\n=== リダイレクト先URL ===');
        console.log(url);
        console.log('\n=== パラメータ解析 ===');
        const urlObj = new URL(url);
        console.log('client_id:', urlObj.searchParams.get('client_id'));
        console.log('redirect_uri:', urlObj.searchParams.get('redirect_uri'));
        console.log('scope:', urlObj.searchParams.get('scope'));
        console.log('state:', urlObj.searchParams.get('state'));
      }
    });

    // /auth/login にアクセス
    await page.goto('https://resend.kimito-link.com/auth/login', {
      waitUntil: 'networkidle',
      timeout: 30000
    });

    // 少し待機（リダイレクトが完了するまで）
    await page.waitForTimeout(3000);

    // 現在のURLを確認
    const currentUrl = page.url();
    console.log('\n=== 現在のURL ===');
    console.log(currentUrl);

    if (currentUrl.includes('accounts.google.com')) {
      const urlObj = new URL(currentUrl);
      console.log('\n=== パラメータ解析 ===');
      console.log('client_id:', urlObj.searchParams.get('client_id'));
      console.log('redirect_uri:', urlObj.searchParams.get('redirect_uri'));
      console.log('scope:', urlObj.searchParams.get('scope'));
      console.log('state:', urlObj.searchParams.get('state'));
    }

    // ブラウザを開いたままにする（確認用）
    console.log('\nブラウザを10秒間開いたままにします...');
    await page.waitForTimeout(10000);

  } catch (error) {
    console.error('エラー:', error.message);
  } finally {
    await browser.close();
  }
})();
