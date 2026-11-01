import React from 'react';
import { createRoot } from 'react-dom/client';
import ClassroomApp from './components/Classroom/ClassroomApp.jsx';

document.addEventListener('DOMContentLoaded', () => {
    const rootEl = document.getElementById('classroom-app-root');
    if (!rootEl) return;

    let config = window.__classroom || {};
    if (rootEl.dataset.config) {
        try {
            config = { ...config, ...JSON.parse(rootEl.dataset.config) };
        } catch (e) {
            console.warn('[ClassroomApp] Failed to parse data-config:', e);
        }
    }

    const root = createRoot(rootEl);
    root.render(<ClassroomApp config={config} />);
});
