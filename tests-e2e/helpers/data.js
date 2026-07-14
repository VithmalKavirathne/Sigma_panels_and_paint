// Shared constants + small utilities for the Sigma admin E2E suite.
const PREFIX = 'TEST_ADMIN_';

const CREDS = {
  email: process.env.ADMIN_EMAIL || 'admin@sigmapanels.com.au',
  password: process.env.ADMIN_PASSWORD || '',
};

// Admin routes (clean URLs from .htaccess). .php fallbacks also work.
const ADMIN = {
  login: '/admin/login',
  logout: '/admin/logout',
  dashboard: '/admin/dashboard',
  businessInfo: '/admin/business-info',
  homepage: '/admin/homepage',
  about: '/admin/about-basic',
  services: '/admin/services',
  gallery: '/admin/gallery',
  faqs: '/admin/faqs',
  quotes: '/admin/quote-requests',
  messages: '/admin/messages',
  seo: '/admin/seo-basic',
  settings: '/admin/settings-basic',
};

const PUBLIC = {
  home: '/',
  about: '/about',
  services: '/services',
  gallery: '/gallery',
  quote: '/quote',
  contact: '/contact',
  faq: '/faq',
  privacy: '/privacy-policy',
  terms: '/terms',
  sitemap: '/sitemap.xml',
  robots: '/robots.txt',
};

// A unique, easily-searchable, easily-cleaned token per run.
function stamp() {
  return PREFIX + Date.now().toString(36);
}

// Collects console + page errors so specs can assert "no console errors".
function attachConsoleGuard(page) {
  const errors = [];
  page.on('console', (msg) => {
    if (msg.type() === 'error') { errors.push(msg.text()); }
  });
  page.on('pageerror', (err) => { errors.push(String(err)); });
  return errors;
}

module.exports = { PREFIX, CREDS, ADMIN, PUBLIC, stamp, attachConsoleGuard };
