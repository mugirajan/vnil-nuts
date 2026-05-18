const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

const SIZES = [480, 768, 1024, 1440, 1920];
const HERO_DIR = path.resolve(__dirname, '../assets/images/banner');

(async () => {
  for (const f of fs.readdirSync(HERO_DIR)) {
    if (!/\.(png|jpe?g|webp)$/i.test(f)) continue;
    
    // Skip already resized files
    if (/-\d+\.(png|jpe?g|webp|avif)$/i.test(f)) continue;

    const src = path.join(HERO_DIR, f);
    const base = path.join(HERO_DIR, f.replace(/\.[^.]+$/, ''));

    console.log(`Processing: ${f}`);

    for (const w of SIZES) {
      await sharp(src).resize({ width: w }).avif({ quality: 55 }).toFile(`${base}-${w}.avif`);
      await sharp(src).resize({ width: w }).webp({ quality: 75 }).toFile(`${base}-${w}.webp`);
      console.log(`  ✅ Generated ${w}w`);
    }
  }
  console.log('\n Hero images done!');
})();