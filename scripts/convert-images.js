const fs = require('fs');
const path = require('path');
const sharp = require('sharp');

const ROOT = path.resolve(__dirname, '../assets/images');

// ✅ Files to SKIP (logo and favicons)
const SKIP_FILES = [
  'vnil-logo-removebg-preview.png',
  'favicon.png',
  'favicon.ico'
];

const VALID_EXTS = ['.png', '.jpg', '.jpeg'];

function* walk(dir) {
  for (const f of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, f.name);
    if (f.isDirectory()) yield* walk(p);
    else if (VALID_EXTS.includes(path.extname(f.name).toLowerCase())) yield p;
  }
}

(async () => {
  for (const src of walk(ROOT)) {
    const filename = path.basename(src);

    if (SKIP_FILES.includes(filename)) {
      console.log('⏭️  Skipped:', filename);
      continue;
    }

    const base = src.replace(/\.(png|jpe?g)$/i, '');

    try {
      await sharp(src).avif({ quality: 60, effort: 6 }).toFile(base + '.avif');
      await sharp(src).webp({ quality: 78 }).toFile(base + '.webp');
      console.log('✅ Converted:', filename);
    } catch (e) {
      console.error(' Failed:', filename, e.message);
    }
  }

  console.log('\n All done!');
})();