from playwright.sync_api import sync_playwright
R=[]
def ck(n,ok,d=""): R.append((n,ok,d))
with sync_playwright() as p:
    b=p.chromium.launch()
    errs=[]
    pg=b.new_page(viewport={"width":1440,"height":940})
    pg.on("pageerror", lambda e: errs.append(str(e)))
    pg.goto("file:///home/claude/build/blaineside-ucp/dashboard.html")
    pg.wait_for_timeout(1800)

    # Member rank now shows
    pg.evaluate("UCP_NAME='admintest';UCP_RANK=0;UCP_ROLE='Member';applyIdentity()")
    pg.wait_for_timeout(80)
    ck("Member rank shows in top-right", pg.eval_on_selector('#acctRole','e=>e.textContent')=='Member')
    ck("Member role line visible", pg.evaluate("getComputedStyle(document.getElementById('acctRole')).display")!="none")
    ck("Member shows in dropdown", pg.eval_on_selector('#menuRole','e=>e.textContent')=='Member')
    # empty role also falls back to Member
    pg.evaluate("UCP_ROLE='';applyIdentity()")
    pg.wait_for_timeout(60)
    ck("Empty role falls back to Member", pg.eval_on_selector('#acctRole','e=>e.textContent')=='Member')

    # logout button present + wired
    ck("Logout button exists", pg.evaluate("document.getElementById('logoutBtn')!==null"))

    # service rows present with IDs
    for sid in ['svc-game','svc-forums','svc-ucp','svc-discord']:
        ck(f"Status row {sid} exists", pg.evaluate(f"document.getElementById('{sid}')!==null"))
    # game server is the placeholder
    ck("Game server = Not launched", 'Not launched' in pg.eval_on_selector('#svc-game .val','e=>e.textContent'))
    # checks run (rows should leave 'Checking…' after probes resolve) - give them time
    pg.wait_for_timeout(7000)
    forums_val=pg.eval_on_selector('#svc-forums .val','e=>e.textContent')
    ck("Forums status resolved (not stuck Checking)", forums_val!='Checking…', forums_val)

    # background layer present
    ck("Background ::before layer exists", pg.evaluate("""()=>{
        const s=getComputedStyle(document.querySelector('.main'),'::before');
        return s.backgroundImage && s.backgroundImage!=='none';
    }"""))

    ck("No JS errors", len([e for e in errs if 'Failed to load resource' not in e and 'fetch' not in e.lower() and 'favicon' not in e.lower()])==0, str(errs)[:200])
    b.close()
fails=[r for r in R if not r[1]]
print(f"FINAL: {len(R)} checks | {len(R)-len(fails)} passed | {len(fails)} failed")
for n,ok,d in R:
    if not ok: print("  FAIL:",n,"—",d)
