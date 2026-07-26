# BlaineSide UCP

Front-end for the BlaineSide User Control Panel (design/prototype phase).
Self-contained HTML files — no build step, no dependencies.

## Files

| File | Purpose |
|------|---------|
| `index.html` | Entry point — redirects to the dashboard |
| `dashboard.html` | Main UCP dashboard (stats, bulletin carousel, notifications) |
| `bulletin.html` | County Bulletin management page (create/edit/delete, dashboard slots) |

## Local preview

These are plain HTML files. Open any of them directly in a browser, or serve
the folder:

```bash
# Python 3
python3 -m http.server 8000
# then visit http://localhost:8000
```

## Notes for the back-end developer

Data is currently mocked in-file via JavaScript arrays. Integration points are
commented in each file:

- `dashboard.html` — `SIDEBAR`, `BULLETIN`, `NOTIFS`, and `finishLoading()`
- `bulletin.html` — `BULLETINS` array, plus `UCP_NAME`, `MAX_SHOWN` (5), `PER_PAGE` (6)

Bulletins marked `shown: true` feed the dashboard carousel (max 5). The author
field is locked to the session UCP name. Links are validated on save
(`javascript:`, `data:`, `ftp:` schemes are rejected).
