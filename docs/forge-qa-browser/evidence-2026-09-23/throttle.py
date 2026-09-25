from playwright.sync_api import sync_playwright
B='http://localhost:8080'
with sync_playwright() as p:
    b=p.chromium.launch(headless=True); c=b.new_context(); pg=c.new_page()
    pg.goto(B+'/auth/login'); tok=pg.input_value('input[name=_token]')
    print('A.4 login POSTs:', [pg.request.post(B+'/auth/login', form={'_token':tok,'login':'+998 90 000 00 00'}, max_redirects=0).status for _ in range(7)])
    pg.wait_for_timeout(61000)
    c2=b.new_context(); pg=c2.new_page(); pg.goto(B+'/auth/login')
    pg.click('#login'); pg.keyboard.type('901234567'); pg.click('form button[type=submit]'); pg.wait_for_selector('#code')
    tok=pg.input_value('input[name=_token]')
    print('A.6 verify POSTs:', [pg.request.post(B+'/auth/verify', form={'_token':tok,'code':'0000'}, max_redirects=0).status for _ in range(12)])
    b.close()
