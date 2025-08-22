import chokidar from 'chokidar';
import fs from 'fs';

const styleFile = './src/scss/style.scss';

chokidar.watch('./src/scss', {ignored: (path, stats) => stats?.isFile() && !path.endsWith('.scss'), persistent: true}).on('change', (path) => {
  console.log(`File changed: ${path}`);
  touchStyleFile();
});

function touchStyleFile() {
  const now = new Date();
  fs.utimes(styleFile, now, now, (err) => {
    if (err) {
      console.error(`[watch-scss] ❌ Failed to touch ${styleFile}:`, err);
    } else {
      console.log(`[watch-scss] ✔ Touched style.scss @ ${now.toLocaleTimeString()}`);
    }
  });
}