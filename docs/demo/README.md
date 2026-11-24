# Harborboard MCP Inspector demo

Recorded against a seeded local stdio server (`php artisan mcp:inspector harbor`) with MCP Inspector v2.16.0.

## Assets

| File | What it shows |
| --- | --- |
| [inspector-walkthrough.mp4](inspector-walkthrough.mp4) | Full ~85s walkthrough (1920×1200) |
| [inspector-walkthrough.gif](inspector-walkthrough.gif) | Same session, compressed for README (~5.5 MB) |
| [01-search-listings.png](01-search-listings.png) | `search-listings` — published pieces under the price filter |
| [02-pipeline-summary.png](02-pipeline-summary.png) | `pipeline-summary` — Northline counts and attention queue |
| [03-create-lead.png](03-create-lead.png) | `create-lead` — Riley Chen on the brass arc lamp |
| [04-place-order-confirm.png](04-place-order-confirm.png) | `place-order` confirm — order `HB-1-0002` committed |

## Session outline

1. List tools; call `search-listings` with `status=published`.
2. Call `pipeline-summary` (workspace, actor, attention leads).
3. Call `create-lead` for Riley Chen / brass-arc-lamp.
4. Call `place-order` with `confirm=false` (preview), then `confirm=true` → `HB-1-0002`.
5. Read `harbor://playbook/qualification`; get `qualify-lead` for the converted Maya Ellis lead.
