<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Harborboard — MCP server</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #17211d;
            --muted: #5c675f;
            --paper: #f6f1e7;
            --card: #fffdf8;
            --line: #e2d8c8;
            --accent: #0f6e56;
            --accent-soft: #e5f4ef;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Iowan Old Style", Palatino, "Palatino Linotype", Georgia, serif;
            background:
                radial-gradient(1200px 500px at 100% -10%, #efe4cf 0%, transparent 55%),
                var(--paper);
            color: var(--ink);
        }
        main { max-width: 880px; margin: 0 auto; padding: 48px 24px 72px; }
        header { display: flex; justify-content: space-between; gap: 24px; align-items: baseline; }
        .mark { letter-spacing: 0.14em; text-transform: uppercase; font-size: 0.78rem; color: var(--accent); font-family: ui-sans-serif, system-ui, sans-serif; }
        h1 { font-size: clamp(2.4rem, 6vw, 4.2rem); line-height: 0.95; font-weight: 500; margin: 12px 0 16px; }
        .lede { font-size: 1.2rem; max-width: 38rem; color: var(--muted); }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 32px 0; }
        .stat, .panel { background: var(--card); border: 1px solid var(--line); border-radius: 16px; }
        .stat { padding: 16px 18px; }
        .stat b { display: block; font-size: 1.8rem; font-variant-numeric: tabular-nums; }
        .stat span, .panel p, li { font-family: ui-sans-serif, system-ui, sans-serif; }
        .stat span { color: var(--muted); font-size: 0.85rem; }
        .panel { padding: 20px 22px; margin-top: 12px; }
        h2 { margin: 0 0 8px; font-size: 1.25rem; font-weight: 500; }
        code, pre { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 0.86rem; }
        pre { background: var(--ink); color: #f6f1e7; padding: 14px 16px; border-radius: 12px; overflow: auto; }
        ul { margin: 8px 0 0; padding-left: 18px; }
        li { margin: 6px 0; color: var(--muted); }
        a { color: var(--accent); }
        .pill { display: inline-block; background: var(--accent-soft); color: var(--accent); border-radius: 999px; padding: 4px 10px; font-family: ui-sans-serif, system-ui, sans-serif; font-size: 0.78rem; }
        @media (max-width: 700px) {
            .grid, header { grid-template-columns: 1fr; display: block; }
            .stat { margin-top: 12px; }
        }
    </style>
</head>
<body>
<main>
    <header>
        <div>
            <div class="mark">Harborboard</div>
            <h1>A classifieds CRM with an MCP server.</h1>
            <p class="lede">Listings, leads, and orders live in Laravel. Claude, Cursor, and MCP Inspector talk to them through tools, resources, and prompts.</p>
        </div>
    </header>

    <p><span class="pill">POST /mcp/harbor</span> <span class="pill">php artisan mcp:start harbor</span></p>

    <section class="grid" aria-label="Seeded workspace">
        <div class="stat"><b>{{ $counts['listings'] }}</b><span>Listings{{ $workspace ? ' in '.$workspace->name : '' }}</span></div>
        <div class="stat"><b>{{ $counts['leads'] }}</b><span>Leads</span></div>
        <div class="stat"><b>{{ $counts['orders'] }}</b><span>Orders</span></div>
    </section>

    @unless ($workspace)
        <section class="panel">
            <h2>Database is empty</h2>
            <p>Run <code>php artisan migrate --seed</code> to load the Northline Studio fixtures.</p>
        </section>
    @endunless

    <section class="panel">
        <h2>Try a conversation</h2>
        <p>With the stdio server running, ask:</p>
        <pre>Which published listings are under $700, and who still needs a reply?</pre>
        <ul>
            <li>Read tools search the signed-in workspace only.</li>
            <li><code>place-order</code> and <code>archive-listing</code> preview first and write only when <code>confirm</code> is true.</li>
            <li>HTTP clients send <code>Authorization: Bearer</code> from <code>php artisan harbor:token</code>.</li>
        </ul>
    </section>
</main>
</body>
</html>
