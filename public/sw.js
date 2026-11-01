const CACHE_VERSION = 'edusfera-mobile-pwa-v2.5';
const STATIC_CACHE = `${CACHE_VERSION}-static`;
const API_CACHE = `${CACHE_VERSION}-api`;
const OFFLINE_URL = '/offline.html';

const STATIC_PRECACHE = [
  '/',
  '/m',
  '/offline.html',
  '/manifest.json',
  '/favicon.svg',
  '/apple-touch-icon.png',
  '/icons/icon-192x192.png',
  '/icons/icon-512x512.png',
  '/icons/icon-maskable-192x192.png',
  '/fonts/Rimma_sans-Bold.ttf'
];

// Helper: timeout for fetch requests with realistic mobile latency (10s)
function fetchWithTimeout(request, timeoutMs = 10000) {
  return new Promise((resolve, reject) => {
    const timer = setTimeout(() => {
      reject(new Error(`Request timed out after ${timeoutMs}ms`));
    }, timeoutMs);

    fetch(request)
      .then((response) => {
        clearTimeout(timer);
        resolve(response);
      })
      .catch((err) => {
        clearTimeout(timer);
        reject(err);
      });
  });
}

// ─── 1. Установка: кэшируем критические статические ресурсы и App Shell ───
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE).then((cache) => {
      return cache.addAll(STATIC_PRECACHE).catch((err) => {
        console.warn('[Edusfera PWA] Precache warning:', err);
      });
    }).then(() => self.skipWaiting())
  );
});

// ─── 2. Активация: удаляем устаревшие кэши и мгновенно перехватываем клиентов ───
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.map((key) => {
          if (key !== STATIC_CACHE && key !== API_CACHE) {
            return caches.delete(key);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// ─── 3. Слушаем сообщения от клиента (принудительное обновление и установка бейджей) ───
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  if (event.data && event.data.type === 'SET_BADGE') {
    const count = parseInt(event.data.count, 10) || 0;
    if (self.navigator && 'setAppBadge' in self.navigator) {
      if (count > 0) {
        self.navigator.setAppBadge(count).catch(() => {});
      } else {
        self.navigator.clearAppBadge().catch(() => {});
      }
    }
  }
});

// ─── 4. Web Push Notifications ──────────────────────────────────────────
self.addEventListener('push', (event) => {
  let data = {};
  try {
    data = event.data ? event.data.json() : {};
  } catch (e) {
    data = {
      title: 'Edusfera',
      body: event.data ? event.data.text() : 'Новое уведомление'
    };
  }

  const title = data.title || 'Edusfera';
  const options = {
    body: data.body || 'У вас новое уведомление в Edusfera',
    icon: data.icon || '/icons/icon-192x192.png',
    badge: '/icons/icon-192x192.png',
    tag: data.tag || 'edusfera-push',
    data: {
      url: data.url || '/m',
    },
    vibrate: [100, 50, 100],
    actions: data.actions || [
      { action: 'open', title: 'Открыть' }
    ]
  };

  event.waitUntil(self.registration.showNotification(title, options));
});

// Клик по нативному уведомлению
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  const targetUrl = (event.notification.data && event.notification.data.url)
    ? event.notification.data.url
    : '/m';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if (client.url.includes(targetUrl) && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
    })
  );
});

// ─── 5. Интеллектуальный перехват запросов (Fetch Engine) ─────────────────
self.addEventListener('fetch', (event) => {
  const req = event.request;

  // Игнорируем не-GET запросы (POST формы, платежи, Livewire mutations)
  if (req.method !== 'GET') {
    return;
  }

  const url = new URL(req.url);

  // Игнорируем внешние запросы, десктоп админку /admin, платежные шлюзы, видеопотоки WebRTC
  if (
    url.origin !== self.location.origin ||
    url.pathname.startsWith('/admin') ||
    url.pathname.startsWith('/livewire/') ||
    url.pathname.includes('/checkout/') ||
    url.pathname.includes('/payments/') ||
    url.pathname.includes('/alfa-') ||
    url.searchParams.has('livewire')
  ) {
    return;
  }

  // ── A. МОБИЛЬНЫЙ API (/api/mobile/*): Stale-While-Revalidate + Offline Resilient ──
  if (url.pathname.startsWith('/api/mobile/')) {
    event.respondWith(
      (async () => {
        const cache = await caches.open(API_CACHE);
        const cachedResponse = await cache.match(req);

        // Фоновый запрос в сеть с обновлением кэша
        const networkFetchPromise = fetchWithTimeout(req, 2500)
          .then((networkResponse) => {
            if (networkResponse && networkResponse.status === 200) {
              cache.put(req, networkResponse.clone());
              // Уведомляем открытые окна о свежих данных
              self.clients.matchAll().then((clients) => {
                clients.forEach((c) => {
                  c.postMessage({
                    type: 'MOBILE_API_UPDATED',
                    path: url.pathname,
                    timestamp: Date.now()
                  });
                });
              });
            }
            return networkResponse;
          })
          .catch((err) => {
            // Сеть недоступна или таймаут
            return null;
          });

        // Если есть сохраненный кэш — отдаем его СРАЗУ (0 мс задержки)
        if (cachedResponse) {
          networkFetchPromise.catch(() => {});
          return cachedResponse;
        }

        // Если в кэше нет — ждем сеть
        const netRes = await networkFetchPromise;
        if (netRes) {
          return netRes;
        }

        // Если и сеть упала, и кэша нет — возвращаем структурированный fallback JSON
        return new Response(JSON.stringify({
          offline: true,
          error: 'offline',
          message: 'Автономный режим: соединение отсутствует или нестабильно',
          lessons: [],
          chats: [],
          notifications: []
        }), {
          status: 200,
          headers: {
            'Content-Type': 'application/json',
            'X-Edusfera-Offline': '1'
          }
        });
      })()
    );
    return;
  }

  // ── B. НАВИГАЦИЯ ПО СТРАНИЦАМ (HTML): Network-First, Cache fallback only on true offline ──
  if (req.mode === 'navigate') {
    event.respondWith(
      (async () => {
        const staticCache = await caches.open(STATIC_CACHE);

        try {
          // Реальный сетевой запрос без агрессивных искусственных таймаутов
          const netResponse = await fetch(req);
          if (netResponse && netResponse.status === 200) {
            staticCache.put(req, netResponse.clone());
            return netResponse;
          }
          return netResponse;
        } catch (netErr) {
          // Сеть РЕАЛЬНО недоступна (TypeError: Failed to fetch / offline)
          // Проверяем точное совпадение в кэше
          const cachedDoc = await staticCache.match(req);
          if (cachedDoc) {
            return cachedDoc;
          }

          // Если это /m или любой мобильный раздел — возвращаем кэшированный App Shell /m
          if (url.pathname.startsWith('/m')) {
            const mShell = await staticCache.match('/m');
            if (mShell) {
              return mShell;
            }
          }

          // Общий fallback на страницу офлайна только при физическом отсутствии интернета
          const fallback = await staticCache.match(OFFLINE_URL);
          return fallback || new Response('Офлайн режим Edusfera', { 
            status: 503, 
            headers: { 'Content-Type': 'text/plain; charset=utf-8' } 
          });
        }
      })()
    );
    return;
  }

  // ── C. СТАТИЧЕСКИЕ РЕСУРСЫ (CSS, JS, Fonts, Icons, Vite Chunks, Images) ──
  // Cache-First: молниеносный отклик из локальной памяти без расхода трафика
  const isStatic =
    url.pathname.startsWith('/build/') ||
    url.pathname.startsWith('/icons/') ||
    url.pathname.startsWith('/fonts/') ||
    url.pathname.startsWith('/logo/') ||
    url.pathname.match(/\.(css|js|woff2?|ttf|png|jpg|jpeg|svg|ico|webp)$/i);

  if (isStatic) {
    event.respondWith(
      (async () => {
        const cache = await caches.open(STATIC_CACHE);
        const cached = await cache.match(req);
        if (cached) {
          // Фоновое обновление при необходимости
          fetch(req).then((fresh) => {
            if (fresh && fresh.status === 200) {
              cache.put(req, fresh);
            }
          }).catch(() => {});
          return cached;
        }

        try {
          const freshResponse = await fetch(req);
          if (freshResponse && freshResponse.status === 200) {
            cache.put(req, freshResponse.clone());
          }
          return freshResponse;
        } catch (err) {
          return new Response('', { status: 408, statusText: 'Resource Unavailable Offline' });
        }
      })()
    );
  }
});
