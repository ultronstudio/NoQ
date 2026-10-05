import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { fileURLToPath } from 'node:url';
import sharp from 'sharp';

const output = new URL('../public/assets/branding/', import.meta.url);
const symbol = await readFile(new URL('../public/assets/logo/noq-symbol-light.svg', import.meta.url));
const logo = await readFile(new URL('../public/assets/logo/noq-logo-light.svg', import.meta.url));
await mkdir(output, { recursive: true });

async function icon(filename, size, scale = 0.85) {
    const artwork = await sharp(symbol).resize(Math.round(size * scale)).png().toBuffer();
    await sharp({ create: { width: size, height: size, channels: 4, background: '#ffffff' } })
        .composite([{ input: artwork, gravity: 'centre' }])
        .png()
        .toFile(fileURLToPath(new URL(filename, output)));
}

await Promise.all([
    icon('icon-192.png', 192),
    icon('icon-512.png', 512),
    icon('icon-maskable-512.png', 512, 0.64),
    icon('apple-touch-icon.png', 180),
    icon('favicon-32.png', 32),
]);

const artwork = await sharp(logo).resize(960, 560).png().toBuffer();
const social = await sharp({ create: { width: 1200, height: 630, channels: 4, background: '#ffffff' } })
    .composite([{ input: artwork, gravity: 'centre' }])
    .png()
    .toBuffer();
await writeFile(new URL('social.png', output), social);
await writeFile(new URL('../public/favicon.svg', import.meta.url), symbol);
await writeFile(new URL('../public/apple-touch-icon.png', import.meta.url), await readFile(new URL('apple-touch-icon.png', output)));

// ICO containing PNG data is supported by current browsers and Windows.
const favicon = await sharp(symbol).resize(32, 32).png().toBuffer();
const header = Buffer.alloc(22);
header.writeUInt16LE(1, 2);
header.writeUInt16LE(1, 4);
header[6] = 32;
header[7] = 32;
header.writeUInt16LE(1, 10);
header.writeUInt16LE(32, 12);
header.writeUInt32LE(favicon.length, 14);
header.writeUInt32LE(22, 18);
await writeFile(new URL('../public/favicon.ico', import.meta.url), Buffer.concat([header, favicon]));
console.log('Brand icons and social preview generated from public/assets/logo.');
