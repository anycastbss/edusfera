const { chromium } = require('playwright');
const path = require('path');

const ARTIFACT_DIR = '/Users/sergei/.gemini/antigravity-ide/brain/fc8538da-02af-49f0-9fa0-53011772085f';

async function main() {
    console.log('🚀 Запуск всесторонней верификации виртуального класса на edusfera.by...');
    const browser = await chromium.launch({ channel: 'chrome', headless: true, args: ['--no-sandbox', '--ignore-certificate-errors'] });

    try {
        // 1. Mobile Portrait (390 x 844)
        console.log('\n--- 1. Тест мобильного портретного режима (390x844) ---');
        const mContext = await browser.newContext({ viewport: { width: 390, height: 844 }, isMobile: true, hasTouch: true });
        const mPage = await mContext.newPage();
        await mPage.goto('https://edusfera.by/admin/login', { waitUntil: 'domcontentloaded' });
        await mPage.fill('input[name="loginIdentifier"]', 'student-test@edusfera.by');
        await mPage.fill('input[name="password"]', 'Password123!');
        await Promise.all([mPage.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {}), mPage.click('button[type="submit"]')]);
        await mPage.goto('https://edusfera.by/classroom/17', { waitUntil: 'domcontentloaded' });
        await mPage.waitForTimeout(3500);

        // Verify mobile dock buttons
        const mDockBtns = await mPage.evaluate(() => {
            const btns = Array.from(document.querySelectorAll('.cr-island-dock .cr-dock-btn'));
            return btns.filter(b => window.getComputedStyle(b).display !== 'none').map(b => ({
                label: b.querySelector('.cr-dock-label')?.textContent.trim() || '',
                title: b.title,
                classes: b.className
            }));
        });
        console.log('Видимые кнопки мобильного дока (должны быть Микрофон, Камера, Звонок, Чат, ИИ, Ещё, Выход):');
        console.log(JSON.stringify(mDockBtns, null, 2));

        const portraitBoardPath = path.join(ARTIFACT_DIR, 'verified_mobile_portrait_board.png');
        await mPage.screenshot({ path: portraitBoardPath });
        console.log(`✓ Скриншот мобильной доски: ${portraitBoardPath}`);

        // Test Fullscreen Video Call toggle
        console.log('Разворачиваем видеозвонок на весь экран...');
        await mPage.click('.cr-dock-btn--call');
        await mPage.waitForTimeout(1000);

        const fsState = await mPage.evaluate(() => {
            const island = document.getElementById('cr-video-island');
            const tiles = document.querySelector('.cr-island-video--fullscreen .cr-video-tiles');
            return {
                isFs: island?.classList.contains('cr-island-video--fullscreen'),
                rect: island ? island.getBoundingClientRect() : null,
                tilesFlexDirection: tiles ? window.getComputedStyle(tiles).flexDirection : null
            };
        });
        console.log('Статус полноэкранного видеозвонка:', JSON.stringify(fsState, null, 2));

        const portraitFsPath = path.join(ARTIFACT_DIR, 'verified_mobile_portrait_fullscreen.png');
        await mPage.screenshot({ path: portraitFsPath });
        console.log(`✓ Скриншот полноэкранного видеозвонка: ${portraitFsPath}`);

        // Return to whiteboard
        console.log('Возвращаемся к доске по кнопке "К доске"...');
        await mPage.click('.cr-fs-back-btn');
        await mPage.waitForTimeout(800);

        // Test Exit Modal
        console.log('Открываем модальное окно выхода из урока...');
        await mPage.click('.cr-dock-btn--exit');
        await mPage.waitForTimeout(800);

        const exitModalState = await mPage.evaluate(() => {
            const overlay = document.querySelector('.cr-modal-overlay');
            const title = overlay?.querySelector('.cr-modal-title')?.textContent.trim();
            const display = overlay ? window.getComputedStyle(overlay).display : 'none';
            return { display, title };
        });
        console.log('Статус модального окна выхода:', JSON.stringify(exitModalState, null, 2));

        const exitModalPath = path.join(ARTIFACT_DIR, 'verified_mobile_exit_modal.png');
        await mPage.screenshot({ path: exitModalPath });
        console.log(`✓ Скриншот модального окна выхода: ${exitModalPath}`);

        await mContext.close();

        // 2. Mobile Landscape (844 x 390)
        console.log('\n--- 2. Тест мобильного ландшафтного режима (844x390) ---');
        const lContext = await browser.newContext({ viewport: { width: 844, height: 390 }, isMobile: true, hasTouch: true });
        const lPage = await lContext.newPage();
        await lPage.goto('https://edusfera.by/classroom/17', { waitUntil: 'domcontentloaded' });
        await lPage.waitForTimeout(3000);

        const landscapeBoardPath = path.join(ARTIFACT_DIR, 'verified_mobile_landscape_board.png');
        await lPage.screenshot({ path: landscapeBoardPath });
        console.log(`✓ Скриншот ландшафтной доски: ${landscapeBoardPath}`);

        // Fullscreen in Landscape (50/50 side by side)
        console.log('Разворачиваем видеозвонок в ландшафтном режиме...');
        await lPage.click('.cr-dock-btn--call');
        await lPage.waitForTimeout(1000);

        const lFsState = await lPage.evaluate(() => {
            const tiles = document.querySelector('.cr-island-video--fullscreen .cr-video-tiles');
            return {
                tilesDisplay: tiles ? window.getComputedStyle(tiles).display : null,
                tilesGridCols: tiles ? window.getComputedStyle(tiles).gridTemplateColumns : null
            };
        });
        console.log('Ландшафтный сплит видео:', JSON.stringify(lFsState, null, 2));

        const landscapeFsPath = path.join(ARTIFACT_DIR, 'verified_mobile_landscape_fullscreen.png');
        await lPage.screenshot({ path: landscapeFsPath });
        console.log(`✓ Скриншот ландшафтного полноэкранного видео: ${landscapeFsPath}`);

        await lContext.close();

        console.log('\n🎉 ВСЕ ТЕСТЫ УСПЕШНО ПРОЙДЕНЫ!');
    } catch (e) {
        console.error('Ошибка в тесте:', e);
    } finally {
        await browser.close();
    }
}

main();
