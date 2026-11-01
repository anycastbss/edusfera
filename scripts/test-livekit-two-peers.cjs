const { chromium } = require('playwright');
const path = require('path');

const ARTIFACT_DIR = '/Users/sergei/.gemini/antigravity-ide/brain/0c496a45-e262-4034-90bc-586126700923';

async function runTwoPeersTest() {
    console.log('🚀 Starting Two-Peer LiveKit Classroom Test...');

    const browser = await chromium.launch({
        channel: 'chrome',
        headless: true,
        args: [
            '--use-fake-ui-for-media-stream',
            '--use-fake-device-for-media-stream',
            '--no-sandbox',
            '--disable-web-security',
            '--ignore-certificate-errors',
        ]
    });

    // Tutor Context
    const tutorContext = await browser.newContext({
        viewport: { width: 1280, height: 800 },
        permissions: ['camera', 'microphone'],
    });
    const tutorPage = await tutorContext.newPage();
    tutorPage.on('console', msg => console.log('  [TUTOR CONSOLE]:', msg.type(), msg.text()));
    tutorPage.on('pageerror', err => console.error('  [TUTOR ERROR]:', err));

    // Student Context (simulate mobile or desktop)
    const studentContext = await browser.newContext({
        viewport: { width: 1280, height: 800 },
        permissions: ['camera', 'microphone'],
    });
    const studentPage = await studentContext.newPage();
    studentPage.on('console', msg => console.log('  [STUDENT CONSOLE]:', msg.type(), msg.text()));
    studentPage.on('pageerror', err => console.error('  [STUDENT ERROR]:', err));

    try {
        console.log('1. Logging in as Admin/Tutor...');
        await tutorPage.goto('https://edusfera.by/login', { waitUntil: 'domcontentloaded' });
        await tutorPage.waitForSelector('input[name="loginIdentifier"]', { timeout: 10000 });
        await tutorPage.fill('input[name="loginIdentifier"]', 'admin@edusfera.by');
        await tutorPage.fill('input[name="password"]', 'password');
        await tutorPage.click('button[type="submit"]');
        await tutorPage.waitForURL('**/admin', { timeout: 15000 });

        console.log('2. Opening classroom/test as Tutor...');
        await tutorPage.goto('https://edusfera.by/classroom/test', { waitUntil: 'domcontentloaded' });
        await tutorPage.waitForTimeout(3000);

        const inviteUrl = await tutorPage.evaluate(() => window.classroomApp?.config?.studentInviteUrl);
        console.log('3. Student Invite URL received:', inviteUrl);

        if (!inviteUrl) {
            throw new Error('No student invite URL found!');
        }

        console.log('4. Authenticating student via join link and opening classroom with role=student...');
        await studentPage.goto(inviteUrl, { waitUntil: 'domcontentloaded' });
        await studentPage.waitForTimeout(2000);
        // Force role=student query parameter
        const classroomUrlWithRole = `https://edusfera.by/classroom/42?role=student`;
        await studentPage.goto(classroomUrlWithRole, { waitUntil: 'domcontentloaded' });
        await studentPage.waitForTimeout(6000);

        console.log('5. Enabling cameras on both sides if not already enabled...');
        await tutorPage.evaluate(async () => {
            if (!window.classroomApp.isCameraOn) {
                await window.classroomApp.enableCamera();
            }
        });
        await studentPage.evaluate(async () => {
            if (!window.classroomApp.isCameraOn) {
                await window.classroomApp.enableCamera();
            }
        });

        await tutorPage.waitForTimeout(4000);

        // Check connection statuses
        const tutorStatus = await tutorPage.evaluate(() => {
            const remoteVid = document.getElementById('cr-student-remote-video');
            const localVid = document.getElementById('cr-tutor-local-video');
            return {
                isLiveKit: window.classroomApp.isLiveKit,
                isConnected: window.classroomApp.isConnected,
                remoteConnected: window.classroomApp.remoteConnected,
                remoteVideoOn: window.classroomApp.remoteVideoOn,
                isCameraOn: window.classroomApp.isCameraOn,
                isMicOn: window.classroomApp.isMicOn,
                remoteParticipantsCount: window.classroomApp.liveKit?.room?.remoteParticipants?.size || 0,
                localVideoSrc: !!localVid?.srcObject,
                localVideoPaused: localVid?.paused,
                remoteVideoSrc: !!remoteVid?.srcObject,
                remoteVideoPaused: remoteVid?.paused,
                remoteTracksCount: remoteVid?.srcObject?.getTracks()?.length || 0,
            };
        });

        const studentStatus = await studentPage.evaluate(() => {
            const remoteVid = document.getElementById('cr-tutor-remote-video');
            const localVid = document.getElementById('cr-student-local-video');
            return {
                isLiveKit: window.classroomApp.isLiveKit,
                isConnected: window.classroomApp.isConnected,
                remoteConnected: window.classroomApp.remoteConnected,
                remoteVideoOn: window.classroomApp.remoteVideoOn,
                isCameraOn: window.classroomApp.isCameraOn,
                isMicOn: window.classroomApp.isMicOn,
                remoteParticipantsCount: window.classroomApp.liveKit?.room?.remoteParticipants?.size || 0,
                localVideoSrc: !!localVid?.srcObject,
                localVideoPaused: localVid?.paused,
                remoteVideoSrc: !!remoteVid?.srcObject,
                remoteVideoPaused: remoteVid?.paused,
                remoteTracksCount: remoteVid?.srcObject?.getTracks()?.length || 0,
            };
        });

        console.log('\n=== STATUS IN WINDOWED MODE ===');
        console.log('Tutor status:', JSON.stringify(tutorStatus, null, 2));
        console.log('Student status:', JSON.stringify(studentStatus, null, 2));

        await tutorPage.screenshot({ path: path.join(ARTIFACT_DIR, 'test_tutor_windowed.png') });
        await studentPage.screenshot({ path: path.join(ARTIFACT_DIR, 'test_student_windowed.png') });

        // 6. Test Fullscreen Mode on Tutor
        console.log('\n6. Switching Tutor to Fullscreen Mode...');
        await tutorPage.evaluate(() => window.classroomApp.toggleFullscreenVideo());
        await tutorPage.waitForTimeout(2500);

        const tutorFullscreenStatus = await tutorPage.evaluate(() => {
            const island = document.getElementById('cr-video-island');
            const remoteVid = document.getElementById('cr-student-remote-video');
            const localVid = document.getElementById('cr-tutor-local-video');
            const rect = island?.getBoundingClientRect();
            return {
                isVideoFullscreen: window.classroomApp.isVideoFullscreen,
                hasFullscreenClass: island?.classList.contains('cr-island-video--fullscreen'),
                islandRect: rect ? { width: rect.width, height: rect.height, top: rect.top, left: rect.left } : null,
                remoteVideoVisible: window.getComputedStyle(remoteVid).display !== 'none',
                remoteVideoSrc: !!remoteVid?.srcObject,
                remoteVideoPaused: remoteVid?.paused,
                localVideoVisible: window.getComputedStyle(localVid).display !== 'none',
                localVideoSrc: !!localVid?.srcObject,
                localVideoPaused: localVid?.paused,
            };
        });
        console.log('Tutor Fullscreen status:', JSON.stringify(tutorFullscreenStatus, null, 2));

        await tutorPage.screenshot({ path: path.join(ARTIFACT_DIR, 'test_tutor_fullscreen.png') });

        // 7. Test Fullscreen Mode on Student
        console.log('\n7. Switching Student to Fullscreen Mode...');
        await studentPage.evaluate(() => window.classroomApp.toggleFullscreenVideo());
        await studentPage.waitForTimeout(2500);

        const studentFullscreenStatus = await studentPage.evaluate(() => {
            const island = document.getElementById('cr-video-island');
            const remoteVid = document.getElementById('cr-tutor-remote-video');
            const localVid = document.getElementById('cr-student-local-video');
            const rect = island?.getBoundingClientRect();
            return {
                isVideoFullscreen: window.classroomApp.isVideoFullscreen,
                hasFullscreenClass: island?.classList.contains('cr-island-video--fullscreen'),
                islandRect: rect ? { width: rect.width, height: rect.height, top: rect.top, left: rect.left } : null,
                remoteVideoVisible: remoteVid ? (window.getComputedStyle(remoteVid).display !== 'none') : false,
                remoteVideoSrc: !!remoteVid?.srcObject,
                remoteVideoPaused: remoteVid?.paused,
                localVideoVisible: localVid ? (window.getComputedStyle(localVid).display !== 'none') : false,
                localVideoSrc: !!localVid?.srcObject,
                localVideoPaused: localVid?.paused,
            };
        });
        console.log('Student Fullscreen status:', JSON.stringify(studentFullscreenStatus, null, 2));

        await studentPage.screenshot({ path: path.join(ARTIFACT_DIR, 'test_student_fullscreen.png') });

    } catch (e) {
        console.error('Test execution error:', e);
    } finally {
        await browser.close();
    }
}

runTwoPeersTest();
