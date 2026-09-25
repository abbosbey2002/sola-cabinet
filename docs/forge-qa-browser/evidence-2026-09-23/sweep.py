"""Matrix sweep: lang x theme x viewport x page. Collects console errors,
failed requests, horizontal overflow and a few text heuristics per cell."""
import json, re, sys
from pathlib import Path
from playwright.sync_api import sync_playwright

BASE = 'http://localhost:8080'
OUT = Path(sys.argv[1]); OUT.mkdir(parents=True, exist_ok=True)
ACCOUNT = sys.argv[2] if len(sys.argv) > 2 else '1001'
PAGES = ['/', '/tariffs', '/devices', '/statistics', '/finance', '/services', '/topup']
LANGS = ['ru', 'uz', 'en']
THEMES = ['light', 'dark']
VIEWPORTS = {'1440': (1440, 900), '390': (390, 844)}


def login(page):
    page.goto(f'{BASE}/auth/login')
    page.click('#login'); page.keyboard.type('901234567')
    page.click('form button[type=submit]')
    page.wait_for_selector('#code')
    page.click('#code'); page.keyboard.type('1234')
    page.wait_for_selector(f'a[href$="/select/account/{ACCOUNT}"]')
    page.goto(f'{BASE}/select/account/{ACCOUNT}')
    page.wait_for_load_state('networkidle')
    print('after login:', page.url, flush=True)


results = []
with sync_playwright() as p:
    browser = p.chromium.launch(headless=True)
    c0 = browser.new_context(); p0 = c0.new_page(); login(p0)
    state = c0.storage_state(); c0.close()
    for vp, (w, h) in VIEWPORTS.items():
        for theme in THEMES:
            ctx = browser.new_context(storage_state=state, reduced_motion='reduce', viewport={'width': w, 'height': h}, is_mobile=(vp == '390'), has_touch=(vp == '390'))
            ctx.add_init_script(f"try{{localStorage.setItem('sola-theme','{theme}')}}catch(e){{}}")
            page = ctx.new_page()
            log = {'console': [], 'failed': []}
            page.on('console', lambda m: m.type in ('error', 'warning') and log['console'].append(f'{m.type}: {m.text}'))
            page.on('pageerror', lambda e: log['console'].append(f'pageerror: {e}'))
            page.on('response', lambda r: r.status >= 400 and log['failed'].append(f'{r.status} {r.url}'))
            page.on('requestfailed', lambda r: log['failed'].append(f'FAILED {r.url} {r.failure}'))
            for lang in LANGS:
                page.goto(f'{BASE}/change/lang/{lang}')
                for path in PAGES:
                    log['console'].clear(); log['failed'].clear()
                    resp = page.goto(BASE + path)
                    page.wait_for_load_state('networkidle')
                    info = page.evaluate("""() => {
                        const d = document.documentElement;
                        const text = document.body.innerText;
                        const wide = [...document.querySelectorAll('body *')]
                          .filter(e => e.getBoundingClientRect().right > d.clientWidth + 1 && getComputedStyle(e).position !== 'fixed')
                          .slice(0, 5).map(e => e.tagName + '.' + (e.className?.baseVal ?? e.className).toString().slice(0, 60));
                        return {
                          url: location.pathname, title: document.title, lang: d.lang,
                          theme: d.getAttribute('data-theme'),
                          sw: d.scrollWidth, cw: d.clientWidth, wide,
                          hyphenMoney: (text.match(/(^|\\s)-\\s?\\d[\\d\\s]*(сум|so'm|soʻm|sum|UZS)/gmi) || []).slice(0, 5),
                          minusOne: /(^|\\s)-1(\\s|$)/.test(text),
                          rawKeys: (text.match(/\\b[a-z_]+\\.[a-z_]+(\\.[a-z_]+)+\\b/g) || []).slice(0, 5),
                          h1: [...document.querySelectorAll('h1,h2')].slice(0, 4).map(e => e.innerText.trim()),
                        };
                    }""")
                    name = f"{vp}_{theme}_{lang}_{path.strip('/') or 'home'}"
                    page.screenshot(path=str(OUT / f'{name}.png'), full_page=True)
                    results.append({'cell': name, 'status': resp.status if resp else None, **info,
                                    'console': list(log['console']), 'failed': list(log['failed'])})
            ctx.close()
    browser.close()

(OUT / 'sweep.json').write_text(json.dumps(results, ensure_ascii=False, indent=1))
for r in results:
    flags = []
    if r['status'] != 200: flags.append(f"status={r['status']}")
    if r['sw'] > r['cw']: flags.append(f"OVERFLOW {r['sw']}>{r['cw']} {r['wide']}")
    if r['console']: flags.append(f"console={r['console'][:3]}")
    if r['failed']: flags.append(f"failed={r['failed'][:3]}")
    if r['hyphenMoney']: flags.append(f"hyphen={r['hyphenMoney']}")
    if r['minusOne']: flags.append('shows -1')
    if r['rawKeys']: flags.append(f"rawkeys={r['rawKeys']}")
    if r['lang'] != r['cell'].split('_')[2]: flags.append(f"htmllang={r['lang']}")
    print(r['cell'], r['url'], '|', ' ; '.join(flags) or 'ok')
