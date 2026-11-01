import React, { Suspense } from 'react';
import { createRoot } from 'react-dom/client';
import ErrorBoundary from './components/ErrorBoundary';

/**
 * Universal dynamic mount helper for React Islands.
 * Defers loading and parsing of heavy components until their DOM container is present.
 */
function mountLazy(containerId, importComponent, props = {}) {
  const container = document.getElementById(containerId);
  if (!container || container.__reactRoot) return;

  const root = createRoot(container);
  container.__reactRoot = root;

  importComponent()
    .then((mod) => {
      const Component = mod.default || mod;
      root.render(
        <ErrorBoundary>
          <Suspense fallback={null}>
            <Component {...props} />
          </Suspense>
        </ErrorBoundary>
      );
    })
    .catch((err) => {
      console.error(`[Edusfera] Failed to mount island #${containerId}:`, err);
    });
}

function mountHomepage() {
  mountLazy('app', () => import('./components/NexumHero'));
}

function mountAuth() {
  mountLazy('aurora-auth', () => import('./components/AuroraSignUp'));
}

function mountForTutors() {
  mountLazy('for-tutors-app', () => import('./components/ForTutorsPage'));
}

function mountAiAssistant() {
  mountLazy('tutor-ai-assistant-root', () => import('./components/TutorAiAssistant/TutorAiAssistant'));
}

function mountOnboarding() {
  const container = document.getElementById('aurora-onboarding');
  if (container && !container.__reactRoot) {
    const initialData = window.EDUSFERA_ONBOARDING_INITIAL || null;
    const isStudent = initialData?.role === 'student' || initialData?.user?.role === 'student';

    if (isStudent) {
      mountLazy('aurora-onboarding', () => import('./components/StudentOnboarding/StudentOnboardingFlow'), { initialData });
    } else {
      mountLazy('aurora-onboarding', () => import('./components/TutorOnboarding/TutorOnboardingFlow'), { initialData });
    }
  }
}

function mountFloatingCopilot() {
  const container = document.getElementById('floating-ai-copilot-root');
  if (container && !container.__reactRoot) {
    const config = window.EDUSFERA_COPILOT_CONFIG || {};
    mountLazy('floating-ai-copilot-root', () => import('./components/FloatingAiCopilot/FloatingAiCopilot'), config);
  }
}

function mountMessenger() {
  const container = document.getElementById('edusfera-messenger-root');
  if (container && !container.__reactRoot) {
    const initialData = window.EDUSFERA_CHAT_INITIAL || null;
    mountLazy('edusfera-messenger-root', () => import('./components/Messenger/EdusferaMessenger'), { initialData });
  }
}

function mountMobileCabinet() {
  mountLazy('mobile-cabinet', () => import('./components/MobileCabinet/MobileCabinetApp'));
  mountLazy('cabinet-app', () => import('./components/MobileCabinet/MobileCabinetApp'));
}

function initMounts() {
  mountHomepage();
  mountAuth();
  mountForTutors();
  mountAiAssistant();
  mountFloatingCopilot();
  mountOnboarding();
  mountMessenger();
  mountMobileCabinet();
}

if (typeof document !== 'undefined') {
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMounts);
  } else {
    initMounts();
  }
  document.addEventListener('livewire:navigated', initMounts);
}
