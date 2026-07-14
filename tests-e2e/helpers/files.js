// In-memory upload fixtures (no binary files needed on disk).
// Playwright's setInputFiles accepts {name, mimeType, buffer} directly.

// A real, valid 1x1 PNG.
const PNG_1x1 = Buffer.from(
  'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
  'base64'
);

function validPng(name = 'TEST_ADMIN_pic.png') {
  return { name, mimeType: 'image/png', buffer: PNG_1x1 };
}

// A PHP payload disguised with a .jpg extension (must be rejected by the server).
function phpAsJpg(name = 'TEST_ADMIN_evil.jpg') {
  return { name, mimeType: 'image/jpeg', buffer: Buffer.from('<?php echo "pwned"; ?>') };
}

// A raw .php upload (must be rejected).
function rawPhp(name = 'TEST_ADMIN_shell.php') {
  return { name, mimeType: 'application/x-php', buffer: Buffer.from('<?php echo "pwned"; ?>') };
}

// Plain text renamed to .jpg (not a real image - should be rejected by content check).
function textAsJpg(name = 'TEST_ADMIN_notimage.jpg') {
  return { name, mimeType: 'image/jpeg', buffer: Buffer.from('this is definitely not an image') };
}

// Oversized (~5MB) valid-ish PNG header padded - should exceed size limits.
function oversizedPng(name = 'TEST_ADMIN_big.png') {
  const pad = Buffer.alloc(5 * 1024 * 1024, 0);
  return { name, mimeType: 'image/png', buffer: Buffer.concat([PNG_1x1, pad]) };
}

// A path-traversal style filename with valid image bytes.
function traversalName() {
  return { name: '..\\..\\TEST_ADMIN_trav.png', mimeType: 'image/png', buffer: PNG_1x1 };
}

module.exports = { validPng, phpAsJpg, rawPhp, textAsJpg, oversizedPng, traversalName };
