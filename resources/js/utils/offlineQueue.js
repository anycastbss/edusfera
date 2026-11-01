import axios from 'axios';

const QUEUE_KEY = 'edusfera_offline_sync_queue';

export function getOfflineQueue() {
  try {
    const raw = localStorage.getItem(QUEUE_KEY);
    return raw ? JSON.parse(raw) : [];
  } catch (e) {
    return [];
  }
}

export function enqueueAction(action) {
  try {
    const queue = getOfflineQueue();
    queue.push({
      id: `${Date.now()}_${Math.random().toString(36).substring(2, 7)}`,
      timestamp: Date.now(),
      ...action
    });
    localStorage.setItem(QUEUE_KEY, JSON.stringify(queue));
  } catch (e) {
    console.warn('[OfflineQueue] Failed to enqueue:', e);
  }
}

export async function flushQueue() {
  if (typeof navigator !== 'undefined' && !navigator.onLine) {
    return;
  }

  const queue = getOfflineQueue();
  if (!queue.length) return;

  const remaining = [];

  for (const item of queue) {
    try {
      if (item.method === 'post') {
        await axios.post(item.url, item.data);
      } else if (item.method === 'put') {
        await axios.put(item.url, item.data);
      } else if (item.method === 'delete') {
        await axios.delete(item.url);
      }
    } catch (e) {
      // If error is 4xx client error (e.g. 404, 422), do not retry forever
      if (e.response && e.response.status >= 400 && e.response.status < 500) {
        console.warn(`[OfflineQueue] Dropping invalid action ${item.url}:`, e);
      } else {
        // Network or 5xx error: keep in queue for next retry
        remaining.push(item);
      }
    }
  }

  try {
    localStorage.setItem(QUEUE_KEY, JSON.stringify(remaining));
  } catch (e) {}
}

export async function executeResilientRequest(url, method = 'post', data = {}) {
  // If explicitly offline, enqueue immediately
  if (typeof navigator !== 'undefined' && !navigator.onLine) {
    enqueueAction({ url, method, data });
    return { queued: true, offline: true };
  }

  try {
    const res = await axios({
      url,
      method,
      data,
      timeout: 4000
    });
    return { data: res.data, success: true };
  } catch (err) {
    // If request failed due to network / timeout
    if (!err.response || err.code === 'ECONNABORTED' || !navigator.onLine) {
      enqueueAction({ url, method, data });
      return { queued: true, offline: true };
    }
    throw err;
  }
}

if (typeof window !== 'undefined') {
  window.addEventListener('online', () => {
    setTimeout(flushQueue, 1000);
  });
}
