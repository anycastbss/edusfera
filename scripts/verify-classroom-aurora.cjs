const { chromium } = require('playwright');
const path = require('path');

const ARTIFACT_DIR = '/Users/sergei/.gemini/antigravity-ide/brain/fe1622b8-f039-484c-ac8b-440e44fc4ddd';

async function main() {
    console.log('🚀 Запуск верификации виртуального класса Edusfera в стиле Aurora...');
    const browser = await chromium.launch({ channel: 'chrome', headless: true, args: ['--no-sandbox', '--ignore-certificate-errors'] });

    try {
        // --- 1. DESKTOP VIEWPORT (1440 x 900) ---
        console.log('\n--- 1. Тест Десктопного режима (1440x900) ---');
        const dContext = await browser.newContext({ viewport: { width: 1440, height: 900 } });
        const dPage = await dContext.newPage();
        await dPage.goto('https://edusfera.by/admin/login', { waitUntil: 'domcontentloaded' });
        await dPage.fill('input[name="loginIdentifier"]', 'student-test@edusfera.by');
        await dPage.fill('input[name="password"]', 'Password123!');
        await Promise.all([
            dPage.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {}),
            dPage.click('button[type="submit"]')
        ]);

        await dPage.goto('https://edusfera.by/classroom/test', { waitUntil: 'domcontentloaded' });
        await dPage.waitForTimeout(3000);

        const desktopScreenshot = path.join(ARTIFACT_DIR, 'classroom_desktop_aurora.png');
        await dPage.screenshot({ path: desktopScreenshot });
        console.log(`✓ Десктопный скриншот сохранен: ${desktopScreenshot}`);

        // --- 2. MOBILE PORTRAIT VIEWPORT (390 x 844 iPhone 14/15) ---
        console.log('\n--- 2. Тест Мобильного режима (390x844) ---');
        const mContext = await browser.newContext({
            viewport: { width: 390, height: 844 },
            isMobile: true,
            hasTouch: true,
            userAgent: 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1'
        });
        const mPage = await mContext.newPage();
        // Login on mobile session
        await mPage.goto('https://edusfera.by/admin/login', { waitUntil: 'domcontentloaded' });
        await mPage.fill('input[name="loginIdentifier"]', 'student-test@edusfera.by');
        await mPage.fill('input[name="password"]', 'Password123!');
        await Promise.all([
            mPage.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {}),
            mPage.click('button[type="submit"]')
        ]);

        await mPage.goto('https://edusfera.by/classroom/test', { waitUntil: 'domcontentloaded' });
        await mPage.waitForTimeout(3000);

        const mobileBoardScreenshot = path.join(ARTIFACT_DIR, 'classroom_mobile_aurora_board.png');
        await mPage.screenshot({ path: mobileBoardScreenshot });
        console.log(`✓ Мобильный скриншот доски сохранен: ${mobileBoardScreenshot}`);

        // Open Fullscreen Video Call
        console.log('Разворачиваем видеозвонок на весь экран на мобильном...');
        await mPage.click('.cr-dock-btn--call');
        await mPage.waitForTimeout(1000);

        const mobileFsScreenshot = path.join(ARTIFACT_DIR, 'classroom_mobile_aurora_fullscreen.png');
        await mPage.screenshot({ path: mobileFsScreenshot });
        console.log(`✓ Мобильный полноэкранный видеозвонок: ${mobileFsScreenshot}`);

        // Return to whiteboard
        await mPage.click('.cr-fs-back-btn');
        await mPage.waitForTimeout(600);

        // Open Drawer (Chat/Materials)
        console.log('Открываем Drawer чата на мобильном...');
        await mPage.click('button[title="Чат и материалы урока"]');
        await mPage.waitForTimeout(800);

        const mobileDrawerScreenshot = path.join(ARTIFACT_DIR, 'classroom_mobile_aurora_drawer.png');
        await mPage.screenshot({ path: mobileDrawerScreenshot });
        console.log(`✓ Мобильный Bottom Sheet Drawer: ${mobileDrawerScreenshot}`);

        console.log('\n✅ Все тесты виртуального класса успешно завершены!');
    } catch (err) {
        console.error('❌ Ошибка во время верификации:', err);
    } finally {
        await browser.close();
    }
}

main();
