module.exports = {
  apps: [
    {
      name: 'edusfera-exam-generator',
      script: 'src/server.js',
      cwd: __dirname,
      instances: 1,
      autorestart: true,
      watch: false,
      max_memory_restart: '600M',
      env: {
        NODE_ENV: 'production',
        PORT: 3005,
        HOST: '127.0.0.1',
        RENDER_CONCURRENCY: 3,
        BROWSER_RESTART_THRESHOLD: 400,
        CHROME_EXECUTABLE_PATH: '/usr/bin/chromium'
      }
    }
  ]
};

