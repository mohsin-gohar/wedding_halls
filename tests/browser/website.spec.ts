import { test, expect, Page } from '@playwright/test';

const publicSeeds = [
  '/',
  '/venues',
  '/marketplace',
  '/vendors',
  '/gallery',
  '/packages',
  '/menus',
  '/services',
  '/about',
  '/contact',
  '/faqs',
  '/check-availability',
  '/rsvp',
  '/policies/terms',
  '/policies/privacy',
  '/policies/cancellation',
  '/sitemap.xml',
];

const protectedRoutes = [
  '/dashboard',
  '/admin',
  '/calendar',
  '/staff',
  '/staff/attendance',
  '/staff/attendance/calendar',
  '/staff/payroll',
  '/expenses',
  '/inventory',
  '/kitchen-kots',
  '/seating-planner',
  '/hall/media',
  '/notifications',
  '/account/support',
  '/account/api-tokens',
  '/vendor/dashboard',
  '/vendor/dashboard/photography',
  '/vendor/dashboard/catering',
  '/vendor/dashboard/decoration',
  '/vendor/dashboard/wedding-planners',
  '/vendor/dashboard/beauty-bridal',
  '/vendor/bookings',
  '/vendor/services',
  '/vendor/portfolio',
  '/vendor/locations',
  '/vendor/finance',
  '/vendor/quotes',
  '/customer/dashboard',
  '/customer/bookings',
  '/customer/events',
];

async function openPage(page: Page, path: string) {
  const failures: Array<{ type: string; value: string }> = [];
  page.on('console', message => {
    if (message.type() === 'error') failures.push({ type: 'console', value: message.text() });
  });
  page.on('pageerror', error => failures.push({ type: 'pageerror', value: error.message }));
  page.on('response', response => {
    if (response.url().startsWith('http://127.0.0.1:8000') && response.status() >= 400) {
      failures.push({ type: 'http', value: `${response.status()} ${response.url()}` });
    }
  });

  const response = await page.goto(path, { waitUntil: 'domcontentloaded', timeout: 90_000 });
  await page.waitForTimeout(1500);
  return { response, failures };
}

async function nativeLogin(page: Page, path: string, email: string, password: string) {
  await openPage(page, path);
  await page.locator('input[type="email"]').first().fill(email);
  await page.locator('input[type="password"]').first().fill(password);
  await Promise.race([
    page.locator('form').first().evaluate((form) => {
      const htmlForm = form as HTMLFormElement;
      htmlForm.submit();
    }),
    page.waitForTimeout(500),
  ]);
  await page.waitForURL(url => !url.pathname.includes(path), { timeout: 30_000 }).catch(() => undefined);
  await page.waitForTimeout(500);
}

test.describe('Public website', () => {
  test('homepage and discovered public links open without page or network errors', async ({ page }) => {
    test.setTimeout(300_000);
    const visited = new Set<string>();
    const discovered: string[] = [];
    const failures: Array<{ path: string; type: string; value: string }> = [];

    for (const seed of publicSeeds) {
      if (visited.has(seed)) continue;
      visited.add(seed);
      const { response, failures: pageFailures } = await openPage(page, seed);
      const status = response?.status() ?? 0;
      expect(status, `Expected ${seed} to return 2xx/3xx`).toBeLessThan(400);
      for (const failure of pageFailures) failures.push({ path: seed, ...failure });

      const links = await page.locator('a[href]').evaluateAll((elements) =>
        elements
          .map((element) => element.getAttribute('href'))
          .filter((href): href is string => !!href)
      );

      for (const href of links) {
        if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:') || href.startsWith('javascript:')) continue;
        let url: URL;
        try {
          url = new URL(href, 'http://127.0.0.1:8000');
        } catch {
          continue;
        }
        if (url.origin !== 'http://127.0.0.1:8000') continue;
        const path = `${url.pathname}${url.search}`;
        if (path.startsWith('/api/') || path.startsWith('/logout') || path.startsWith('/documents/') || path.startsWith('/webhooks/')) continue;
        if (['/login', '/register', '/customer/login', '/customer/register', '/2fa/setup', '/2fa/verify'].includes(url.pathname)) continue;
        discovered.push(path);
      }
    }

    for (const path of [...new Set(discovered)].slice(0, 40)) {
      if (visited.has(path)) continue;
      visited.add(path);
      const { response, failures: pageFailures } = await openPage(page, path);
      const status = response?.status() ?? 0;
      if (status >= 400) failures.push({ path, type: 'http', value: `${status} ${path}` });
      for (const failure of pageFailures) failures.push({ path, ...failure });
    }

    await page.screenshot({ path: 'test-results/riwaj-homepage-desktop.png', fullPage: true });
    expect(failures, `Browser failures:\n${JSON.stringify(failures, null, 2)}`).toEqual([]);
  });

  test('public pages have no horizontal overflow at all required widths', async ({ page }) => {
    test.setTimeout(300_000);
    const widths = [320, 360, 375, 390, 430, 768, 1024, 1280, 1440, 1920];
    const overflow: Array<{ width: number; path: string; scrollWidth: number; innerWidth: number }> = [];

    for (const width of widths) {
      await page.setViewportSize({ width, height: 900 });
      for (const path of ['/', '/venues', '/vendors', '/marketplace', '/about']) {
        await openPage(page, path);
        const dimensions = await page.evaluate(() => ({
          scrollWidth: document.documentElement.scrollWidth,
          innerWidth: window.innerWidth,
        }));
        if (dimensions.scrollWidth > dimensions.innerWidth + 1) {
          overflow.push({ width, path, ...dimensions });
        }
      }
    }

    expect(overflow, `Horizontal overflow:\n${JSON.stringify(overflow, null, 2)}`).toEqual([]);
  });
});

test.describe('Authentication and RBAC', () => {
  test('protected routes reject unauthenticated access', async ({ page }) => {
    const failures: Array<{ path: string; status: number; finalPath: string; reason: string }> = [];
    for (const path of protectedRoutes) {
      const response = await page.goto(path, { waitUntil: 'domcontentloaded', timeout: 90_000 });
      const status = response?.status() ?? 0;
      const finalPath = new URL(page.url()).pathname;

      // A guest must either be redirected to a login screen or receive an
      // explicit 401/403. A 200 on the protected URL itself is a leak.
      const redirectedToLogin = finalPath.includes('login');
      const explicitlyDenied = status === 401 || status === 403;
      if (!redirectedToLogin && !explicitlyDenied) {
        failures.push({
          path,
          status,
          finalPath,
          reason: 'Protected route did not redirect to login or return 401/403',
        });
      }

      await page.waitForTimeout(300);
    }
    expect(failures, `Unauthenticated access was not denied:\n${JSON.stringify(failures, null, 2)}`).toEqual([]);
  });

  for (const persona of [
    { name: 'Super Admin', email: 'admin@riwajgrand.pk', password: 'admin1235', routes: ['/admin', '/dashboard'] },
    { name: 'Tenant Owner', email: 'owner@riwajgrand.pk', password: 'password', routes: ['/dashboard', '/staff', '/staff/payroll', '/calendar'] },
    { name: 'Customer', email: 'customer@example.com', password: 'password', routes: ['/customer/dashboard', '/customer/events'] },
  ]) {
    test(`${persona.name} can login and open authorized dashboards`, async ({ page }) => {
      await nativeLogin(page, persona.name === 'Customer' ? '/customer/login' : '/login', persona.email, persona.password);

      expect(page.url(), 'Login should leave the authentication page').not.toContain(persona.name === 'Customer' ? '/customer/login' : '/login');

      for (const route of persona.routes) {
        let response = await page.goto(route, { waitUntil: 'domcontentloaded', timeout: 90_000 }).catch(() => null);
        if (!response) {
          await page.waitForTimeout(3000);
          response = await page.goto(route, { waitUntil: 'domcontentloaded', timeout: 90_000 }).catch(() => null);
        }
        await page.waitForTimeout(800);
        const status = response?.status() ?? 0;
        const finalPath = new URL(page.url()).pathname;

        // An authorized persona must actually land on the dashboard, not be
        // bounced back to a login screen or receive a server error.
        expect(status, `${persona.name} received ${status} on ${route}`).toBeLessThan(400);
        expect(finalPath, `${persona.name} was redirected away from ${route}`).not.toContain('login');
        await page.screenshot({ path: `test-results/${persona.name.toLowerCase().replaceAll(' ', '-')}-${route.replaceAll('/', '-')}.png`, fullPage: true });
      }
    });
  }

  test('customer cannot open tenant administration routes', async ({ page }) => {
    await nativeLogin(page, '/customer/login', 'customer@example.com', 'password');

    for (const route of ['/dashboard', '/admin', '/staff/payroll', '/vendor/finance']) {
      const response = await page.goto(route, { waitUntil: 'domcontentloaded', timeout: 90_000 });
      await page.waitForTimeout(500);

      const status = response?.status() ?? 0;
      const finalPath = new URL(page.url()).pathname;

      // The customer must be denied (401/403), redirected away from the
      // tenant route, or sent to a login screen — never served the page.
      const denied = status === 401 || status === 403;
      const redirectedAway = finalPath !== route;
      expect(
        denied || redirectedAway,
        `Customer was served ${route} (status ${status}, final ${finalPath})`
      ).toBeTruthy();
    }
  });
});
