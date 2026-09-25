"""Functional flows against the fake billing. Prints one line per check."""
import re, sys
from pathlib import Path
from playwright.sync_api import sync_playwright

BASE = 'http://localhost:8080'
OUT = Path(sys.argv[1]); OUT.mkdir(parents=True, exist_ok=True)
R = []


def check(id_, ok, note=''):
    R.append((id_, ok, note)); print(f"{id_:6} {'PASS' if ok else 'FAIL'}  {note}", flush=True)


def text(pg):
    return pg.inner_text('body')


def csrf(pg):
    return pg.get_attribute('meta[name=csrf-token]', 'content')


with sync_playwright() as p:
    b = p.chromium.launch(headless=True)
    ctx = b.new_context(viewport={'width': 1440, 'height': 900}, reduced_motion='reduce')
    pg = ctx.new_page()
    errors = []
    pg.on('pageerror', lambda e: errors.append(str(e)))
    pg.on('console', lambda m: m.type == 'error' and errors.append(m.text))
    dialogs = []
    pg.on('dialog', lambda d: (dialogs.append(d.message), d.dismiss()))

    # A.9 guest -> protected page
    pg.goto(f'{BASE}/change/lang/ru')
    pg.goto(f'{BASE}/finance')
    check('A.9', '/auth/login' in pg.url, pg.url)

    # A.1 login field
    a1 = pg.evaluate("(()=>{const i=document.querySelector('#login');return {type:i.type,af:i.autofocus,name:i.name}})()")
    check('A.1', a1 == {'type': 'tel', 'af': True, 'name': 'login'}, str(a1))

    # A.2 unknown phone
    pg.click('#login'); pg.keyboard.type('900000000'); pg.click('form button[type=submit]')
    pg.wait_for_load_state('networkidle'); pg.wait_for_timeout(500)
    unknown_ok = pg.query_selector('#code') is None
    toast = pg.evaluate("[...document.querySelectorAll('[role=alert],[role=status],.u-toast,[data-toast]')].filter(e=>!e.closest('[hidden]')&&e.getClientRects().length>0).map(e=>e.innerText.trim()).filter(Boolean).join(' | ')")
    pg.screenshot(path=str(OUT / 'A2_unknown_phone.png'))
    check('A.2', unknown_ok and bool(toast), f'no code field={unknown_ok}; message="{toast[:140]}"')

    # A.3 / A.5 wrong code
    pg.goto(f'{BASE}/auth/login')
    pg.click('#login'); pg.keyboard.type('901234567'); pg.click('form button[type=submit]')
    pg.wait_for_selector('#code')
    check('A.3', True, f'code screen at {pg.url}')
    pg.click('#code'); pg.keyboard.type('0000')
    pg.wait_for_load_state('networkidle'); pg.wait_for_timeout(800)
    still = pg.query_selector('#code') is not None
    msg = pg.evaluate("[...document.querySelectorAll('[role=alert],[role=status],.u-field-error,[data-toast]')].filter(e=>!e.closest('[hidden]')&&e.getClientRects().length>0).map(e=>e.innerText.trim()).filter(Boolean).join(' | ')")
    pg.screenshot(path=str(OUT / 'A5_wrong_code.png'))
    check('A.5', still and bool(msg), f'stays on code={still}; message="{msg[:140]}"')

    # A.7 correct code -> account list
    pg.fill('#code', ''); pg.click('#code'); pg.keyboard.type('1234')
    pg.wait_for_selector('a[href$="/select/account/1001"]')
    n = len(pg.query_selector_all('a[href*="/select/account/"]'))
    check('A.7', n == 2, f'{n} accounts listed at {pg.url}')

    # A.8 pick 1001
    pg.click('a[href$="/select/account/1001"]'); pg.wait_for_load_state('networkidle')
    t = text(pg)
    check('A.8', pg.url.rstrip('/') == BASE and 'Alisher Karimov' in t and '1001' in t, pg.url)

    # A.10 logged in -> /auth/login bounces
    pg.goto(f'{BASE}/auth/login')
    check('A.10', '/auth/login' not in pg.url, pg.url)

    # C.6 / E.1 device count matches
    pg.goto(BASE + '/')
    home_devices = re.search(r'(\d+)\s+устройств', text(pg))
    pg.goto(BASE + '/devices')
    rows = pg.evaluate("document.querySelectorAll('form[action*=\"/devices/delete/\"]').length")
    macs = len(re.findall(r'([0-9A-F]{2}:){5}[0-9A-F]{2}', text(pg)))
    check('C.6', home_devices and int(home_devices.group(1)) == macs, f'home={home_devices and home_devices.group(1)} devices page MACs={macs} deletable={rows}')

    # E.4/E.5 add device through the custom confirm
    before = macs
    pg.click('form[action$="/devices/add"] button[type=submit]')
    pg.wait_for_timeout(400)
    dlg = pg.evaluate("(()=>{const d=document.querySelector('[role=dialog]:not([hidden])');return d&&d.innerText.trim()})()")
    pg.screenshot(path=str(OUT / 'E4_add_confirm.png'))
    check('E.4', bool(dlg) and not dialogs, f'custom dialog="{(dlg or "")[:160]}" native={dialogs}')
    pg.click('[data-confirm-accept]'); pg.wait_for_load_state('networkidle'); pg.wait_for_timeout(500)
    after = len(re.findall(r'([0-9A-F]{2}:){5}[0-9A-F]{2}', text(pg)))
    flash = pg.evaluate("[...document.querySelectorAll('[role=alert],[role=status],[data-toast]')].filter(e=>!e.closest('[hidden]')&&e.getClientRects().length>0).map(e=>e.innerText.trim()).filter(Boolean).join(' | ')")
    pg.screenshot(path=str(OUT / 'E5_after_add.png'))
    check('E.5', after == before + 1, f'devices {before} -> {after}; flash="{flash[:120]}"')
    pg.goto(BASE + '/')
    hd = re.search(r'(\d+)\s+устройств', text(pg))
    check('E.5b', hd and int(hd.group(1)) == after, f'home count now {hd and hd.group(1)}')

    # E.7 delete the device just added (last deletable row) via custom confirm, cancel first
    pg.goto(BASE + '/devices')
    btns = pg.query_selector_all('form[action*="/devices/delete/"] button[type=submit]')
    btns[-1].click(); pg.wait_for_timeout(300)
    pg.click('[data-modal-close], [data-confirm-cancel]', timeout=3000) if pg.query_selector('[data-confirm-cancel]') or pg.query_selector('[role=dialog]:not([hidden]) [data-modal-close]') else pg.keyboard.press('Escape')
    pg.wait_for_timeout(300)
    unchanged = len(re.findall(r'([0-9A-F]{2}:){5}[0-9A-F]{2}', text(pg))) == after
    check('E.7a', unchanged and not dialogs, 'cancel leaves list unchanged, no native confirm')
    pg.query_selector_all('form[action*="/devices/delete/"] button[type=submit]')[-1].click(); pg.wait_for_timeout(300)
    pg.click('[data-confirm-accept]'); pg.wait_for_load_state('networkidle'); pg.wait_for_timeout(500)
    final = len(re.findall(r'([0-9A-F]{2}:){5}[0-9A-F]{2}', text(pg)))
    check('E.7b', final == before, f'devices {after} -> {final}')

    # D.7 method/CSRF guards
    r = pg.request.get(f'{BASE}/tariffs/connect', max_redirects=0)
    check('D.7a', r.status == 405, f'GET /tariffs/connect -> {r.status}')
    r = pg.request.post(f'{BASE}/tariffs/connect', form={'tariff': '9', 'timing': 'now'}, max_redirects=0)
    check('D.7b', r.status == 419, f'POST without CSRF -> {r.status}')

    # D.1-D.5 tariff switch
    pg.goto(BASE + '/tariffs')
    options = pg.evaluate("[...document.querySelectorAll('[data-tariff-option]')].map(i=>({id:i.value,name:i.dataset.tariffName,dis:i.disabled}))")
    prices = re.findall(r'\d[\d\s]* сум', text(pg))
    check('D.1', bool(options) and bool(prices), f'options={options} prices={prices[:4]}')
    target = next(o for o in options if not o['dis'])
    pg.check(f'[data-tariff-option][value="{target["id"]}"]', force=True)
    pg.click('[data-tariff-connect]'); pg.wait_for_timeout(400)
    dlg = pg.evaluate("(()=>{const d=[...document.querySelectorAll('[role=dialog]:not([hidden])')];return d.map(e=>e.innerText.trim()).join(' || ')})()")
    pg.screenshot(path=str(OUT / 'D3_confirm.png'))
    check('D.3', target['name'].strip() in (dlg or ''), f'dialog="{(dlg or "")[:200]}"')
    pg.click('[role=dialog]:not([hidden]) [data-modal-close]'); pg.wait_for_timeout(300)
    check('D.4', '/tariffs' in pg.url and 'не запланирована' in text(pg), 'cancel -> still "не запланирована"')
    pg.click('[data-tariff-connect]'); pg.wait_for_timeout(300)
    timing = pg.evaluate("[...document.querySelectorAll('[role=dialog]:not([hidden]) [data-tariff-timing]')].map(b=>b.dataset.tariffTiming)")
    pg.click('[role=dialog]:not([hidden]) [data-tariff-timing="month"]'); pg.wait_for_timeout(400)
    pg.wait_for_load_state('networkidle'); pg.wait_for_timeout(600)
    flash = pg.evaluate("[...document.querySelectorAll('[role=alert],[role=status],[data-toast]')].filter(e=>!e.closest('[hidden]')&&e.getClientRects().length>0).map(e=>e.innerText.trim()).filter(Boolean).join(' | ')")
    pg.screenshot(path=str(OUT / 'D5_after.png'), full_page=True)
    t = text(pg)
    check('D.5', 'Home 100' in t, f'timing options={timing}; flash="{flash[:160]}"; url={pg.url}')
    pending = re.search(r'(Смена тарифа[^\n]*|Следующ[^\n]*\n[^\n]*)', t)
    check('D.6', True, f'after month-switch page says: "{pending and pending.group(0)[:160]}"')
    pg.goto(BASE + '/')
    home_pending = re.findall(r'(Paket 30 kun[^\n]*)', text(pg))
    check('D.6b', True, f'home mentions pending tariff: {home_pending[:3]}')
    pg.screenshot(path=str(OUT / 'D6_home_after_switch.png'), full_page=True)

    # F statistics
    pg.goto(BASE + '/statistics')
    marker = pg.evaluate("window.__noReload = 1")
    pg.fill('#traffic-start', '2026-07-01') if pg.query_selector('#traffic-start') else None
    start_sel = pg.evaluate("document.querySelector('form input[name=start]').id")
    pg.fill(f'#{start_sel}', '2026-07-01')
    pg.click('form:has(input[name=start]) button[type=submit]'); pg.wait_for_load_state('networkidle'); pg.wait_for_timeout(800)
    still_same = pg.evaluate("window.__noReload === 1")
    check('F.2', still_same and '01.07.2026' in text(pg), f'no reload={still_same}; first row contains July={"07.2026" in text(pg)}')
    pg.fill(f'#{start_sel}', '2026-09-20')
    pg.fill('form input[name=end]', '2026-09-01')
    pg.click('form:has(input[name=start]) button[type=submit]'); pg.wait_for_load_state('networkidle'); pg.wait_for_timeout(800)
    err = pg.evaluate("[...document.querySelectorAll('[role=alert],.u-field-error,[data-toast],[aria-invalid=true]')].map(e=>e.innerText?.trim()||e.name).filter(Boolean).join(' | ')")
    pg.screenshot(path=str(OUT / 'F7_end_before_start.png'))
    check('F.7', bool(err), f'message="{err[:160]}"')
    pg.goto(BASE + '/statistics')
    pg.fill('form input[name=start]', '2024-01-01'); pg.fill('form input[name=end]', '2026-09-23')
    pg.click('form:has(input[name=start]) button[type=submit]'); pg.wait_for_load_state('networkidle'); pg.wait_for_timeout(1500)
    note = re.findall(r'[^\n]*(12|месяц)[^\n]*', text(pg))
    pg.screenshot(path=str(OUT / 'F5_long_range.png'), full_page=True)
    check('F.5', True, f'lines mentioning months: {[l for l in re.findall(r"[^\n]*месяц[^\n]*", text(pg))][:3]}')
    r = pg.request.get(f'{BASE}/traffic/detail', max_redirects=0)
    check('F.10', r.status in (301, 302, 308) and '/statistics' in r.headers.get('location', ''), f'{r.status} -> {r.headers.get("location")}')

    # G finance
    pg.goto(BASE + '/finance')
    pg.fill('form input[name=start]', '2026-01-01'); pg.fill('form input[name=end]', '2026-09-23')
    pg.click('form:has(input[name=start]) button[type=submit]'); pg.wait_for_load_state('networkidle'); pg.wait_for_timeout(1200)
    rows = pg.evaluate("[...document.querySelectorAll('table tbody tr')].filter(r=>r.offsetParent).map(r=>[...r.cells].map(c=>c.innerText.trim()))")
    pager = re.search(r'\d+\s*/\s*\d+', text(pg))
    check('G.1', bool(rows), f'{len(rows)} visible rows, first={rows[:1]}, pager={pager and pager.group(0)}')
    tone = pg.evaluate("[...document.querySelectorAll('table tbody tr td:last-child span')].slice(0,3).map(s=>s.className)")
    check('G.5', all('success' in c or 'ok' in c or 'green' in c for c in tone), str(tone))
    pg.click('th:has-text("Сумма")'); pg.wait_for_timeout(200)
    asc = pg.evaluate("[...document.querySelectorAll('table tbody tr')].filter(r=>r.offsetParent).map(r=>+r.cells[3].dataset.value)")
    sort_attr = pg.evaluate("[...document.querySelectorAll('th[aria-sort]')].map(t=>t.innerText.trim()+'='+t.getAttribute('aria-sort'))")
    check('G.9', asc == sorted(asc) or asc == sorted(asc, reverse=True), f'amounts after sort click={asc[:6]} aria={sort_attr}')
    pg.fill('input[type=search], [data-table-search]', 'Payme'); pg.wait_for_timeout(400)
    sys_col = pg.evaluate("[...document.querySelectorAll('table tbody tr')].filter(r=>r.offsetParent).map(r=>r.cells[2].innerText.trim())")
    check('G.10', sys_col and set(sys_col) == {'Payme'}, f'after search: {sys_col}')
    r = pg.request.get(f'{BASE}/payment/history', max_redirects=0)
    check('G.15', r.status in (301, 302, 308) and '/finance' in r.headers.get('location', ''), f'{r.status} -> {r.headers.get("location")}')

    # Top-up validation + redirect target (not followed)
    pg.goto(BASE + '/topup')
    token = csrf(pg)
    r = pg.request.post(f'{BASE}/topup', form={'_token': token, 'amount': '500'}, max_redirects=0)
    check('TU.1', r.status == 302 and r.headers.get('location', '').rstrip('/').endswith('/topup'), f'amount 500 -> {r.status} {r.headers.get("location")}')
    r = pg.request.post(f'{BASE}/topup', form={'_token': token, 'amount': '10 000'}, max_redirects=0)
    loc = r.headers.get('location', '')
    check('TU.2', r.status in (302, 303) and 'iwon' in loc and 'amount=1000000' in loc, f'{r.status} -> {loc[:200]}')

    # A.12 switch account -> no 1001 values left
    pg.goto(f'{BASE}/select/account/1002'); pg.wait_for_load_state('networkidle')
    t = text(pg)
    leak = [s for s in ('Alisher Karimov', 'Home 100', '125 000', 'D-100145') if s in t]
    pg.screenshot(path=str(OUT / 'A12_account_1002.png'), full_page=True)
    check('A.12', not leak and 'Nodira' in t, f'leaked 1001 values={leak}; negative balance shown as: {re.findall(r"[−-]\s?18[\s ]?500", t)}')
    pg.goto(BASE + '/tariffs'); s1002 = pg.url
    pg.goto(BASE + '/devices'); t = text(pg)
    has_add = pg.query_selector('form[action$="/devices/add"]') is not None
    check('A.12b', True, f'1002 (one-off) /tariffs -> {s1002}; devices add button present={has_add}')
    r = pg.request.get(f'{BASE}/select/account/9999', max_redirects=0)
    check('A.12c', r.status == 403, f'foreign account id -> {r.status}')

    # A.11 logout
    pg.goto(f'{BASE}/auth/logout'); pg.goto(f'{BASE}/finance')
    check('A.11', '/auth/login' in pg.url, pg.url)

    check('JS', not errors and not dialogs, f'page errors={errors[:3]} native dialogs={dialogs}')
    b.close()
