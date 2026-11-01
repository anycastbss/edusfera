<script>
(function() {
    if (typeof window === 'undefined' || !window.navigator) return;

    function sendTelemetry(eventName, data) {
        try {
            var payload = JSON.stringify({
                event_name: eventName,
                url: window.location.href,
                duration_ms: data && data.duration ? Math.round(data.duration) : 0,
                error_message: data && data.error ? String(data.error) : null,
                metadata: data || {}
            });

            if (navigator.sendBeacon) {
                var blob = new Blob([payload], { type: 'application/json' });
                navigator.sendBeacon('/api/telemetry/event', blob);
            } else {
                fetch('/api/telemetry/event', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: payload,
                    keepalive: true
                }).catch(function() {});
            }
        } catch(e) {}
    }

    // 1. Capture Client-Side JavaScript Errors
    window.addEventListener('error', function(e) {
        if (!e) return;
        sendTelemetry('client_js_error', {
            error: e.message || 'Unknown JS Error',
            filename: e.filename,
            lineno: e.lineno,
            colno: e.colno
        });
    });

    window.addEventListener('unhandledrejection', function(e) {
        if (!e) return;
        sendTelemetry('client_promise_rejection', {
            error: e.reason ? (e.reason.message || String(e.reason)) : 'Unhandled Promise'
        });
    });

    // 2. Performance & Web Vitals (FCP / LCP)
    if ('PerformanceObserver' in window) {
        try {
            var fcpObserver = new PerformanceObserver(function(entryList) {
                var entries = entryList.getEntriesByName('first-contentful-paint');
                if (entries.length > 0) {
                    sendTelemetry('web_vital_fcp', {
                        duration: entries[0].startTime,
                        metric: 'FCP'
                    });
                    fcpObserver.disconnect();
                }
            });
            fcpObserver.observe({ type: 'paint', buffered: true });
        } catch(e) {}
    }
})();
</script>
