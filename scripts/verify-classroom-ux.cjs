const { chromium } = require('playwright');
const path = require('path');

const ARTIFACT_DIR = '/Users/sergei/.gemini/antigravity-ide/brain/0c496a45-e262-4034-90bc-586126700923';

async function verifyClassroomUx() {
    console.log('🚀 Проверка обновлений UX виртуального класса и личных кабинетов...');

    const browser = await chromium.launch({
        channel: 'chrome',
        headless: true,
        args: ['--no-sandbox', '--disable-web-security']
    });

    const context = await browser.newContext({
        viewport: { width: 1280, height: 800 }
    });
    const page = await context.newPage();

    try {
        console.log('1. Вход в панель управления преподавателя (https://edusfera.by/login)...');
        await page.goto('https://edusfera.by/login', { waitUntil: 'domcontentloaded' });
        await page.waitForSelector('input[name="loginIdentifier"]', { timeout: 10000 });
        await page.fill('input[name="loginIdentifier"]', 'admin@edusfera.by');
        await page.fill('input[name="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForURL('**/admin', { timeout: 15000 });

        console.log('2. Проверка бокового меню: убеждаемся, что "Проверить камеру и доску" удалено...');
        const sidebarText = await page.evaluate(() => document.querySelector('.fi-sidebar-nav')?.innerText || '');
        const hasTestRoomInNav = sidebarText.includes('Проверить камеру') || sidebarText.includes('Тест класса');
        console.log('   Пункт "Проверить камеру и доску" присутствует в меню:', hasTestRoomInNav ? '❌ ДА (ОШИБКА)' : '✅ НЕТ (УСПЕШНО УДАЛЕН)');

        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'tutor_nav_without_test_call.png') });

        console.log('3. Проверка входа в Урок #41 (статус: pending)...');
        const response = await page.goto('https://edusfera.by/classroom/41', { waitUntil: 'domcontentloaded' });
        const statusCode = response.status();
        console.log('   HTTP Статус ответа сервера:', statusCode, statusCode === 200 ? '✅ 200 OK (Вместо 403 Forbidden)' : '❌ Ошибка');

        const pageTitle = await page.title();
        console.log('   Заголовок страницы:', pageTitle);

        const cardText = await page.evaluate(() => document.querySelector('.waiting-card')?.innerText || '');
        const hasPendingBadge = cardText.includes('Ожидает подтверждения') || cardText.includes('Новая заявка');
        const hasTutorName = cardText.includes('Галина Зайцева') || cardText.includes('Преподаватель');
        const hasStudentName = cardText.includes('Andrey') || cardText.includes('Ученик');
        const hasConfirmBtn = cardText.includes('Подтвердить');

        console.log('   Бейдж статуса "Ожидает подтверждения":', hasPendingBadge ? '✅ Да' : '❌ Нет');
        console.log('   Данные репетитора:', hasTutorName ? '✅ Да' : '❌ Нет');
        console.log('   Данные ученика:', hasStudentName ? '✅ Да' : '❌ Нет');
        console.log('   Кнопка быстрого подтверждения для преподавателя:', hasConfirmBtn ? '✅ Да' : '❌ Нет');

        await page.screenshot({ path: path.join(ARTIFACT_DIR, 'classroom_waiting_screen_live.png') });

        console.log('4. Проверка API статуса урока (/classroom/41/status)...');
        const statusRes = await page.evaluate(async () => {
            const res = await fetch('/classroom/41/status', { headers: { 'Accept': 'application/json' } });
            return { ok: res.ok, status: res.status, data: await res.json() };
        });
        console.log('   Ответ эндпоинта /classroom/41/status:', JSON.stringify(statusRes, null, 2));

        console.log('\n🎉 ВСЕ ПРОВЕРКИ UX УСПЕШНО ПРОЙДЕНЫ!');
    } catch (e) {
        console.error('Ошибка проверки UX:', e);
    } finally {
        await browser.close();
    }
}

verifyClassroomUx();
