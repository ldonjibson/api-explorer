<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>API Explorer</title>
<style>
    :root {
        --bg: #0e1116; --bg2: #141922; --bg3: #1b2230; --line: #263042;
        --text: #dbe2ef; --muted: #8b95a8; --accent: #4f9cf9;
        --get: #4caf50; --post: #f5a623; --put: #7e57c2; --patch: #26a69a;
        --delete: #ef5350; --other: #78909c; --radius: 8px;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
        font: 14px/1.5 -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        background: var(--bg); color: var(--text); height: 100vh; overflow: hidden;
    }
    header {
        display: flex; align-items: center; gap: 12px; height: 52px; padding: 0 16px;
        background: var(--bg2); border-bottom: 1px solid var(--line);
    }
    header .logo { font-weight: 700; font-size: 15px; color: var(--accent); white-space: nowrap; }
    header .sub { color: var(--muted); font-size: 12px; white-space: nowrap; }
    header .spacer { flex: 1; }
    button, select, input, textarea {
        font: inherit; color: var(--text); background: var(--bg3);
        border: 1px solid var(--line); border-radius: var(--radius); outline: none;
    }
    input:focus, textarea:focus, select:focus { border-color: var(--accent); }
    button { cursor: pointer; padding: 7px 14px; }
    button:hover { filter: brightness(1.15); }
    button.primary { background: var(--accent); border-color: var(--accent); color: #06101f; font-weight: 600; }
    button.ghost { background: transparent; }
    .layout { display: flex; height: calc(100vh - 52px); }
    aside {
        width: 340px; min-width: 260px; border-right: 1px solid var(--line);
        background: var(--bg2); display: flex; flex-direction: column;
    }
    .side-tools { padding: 10px; border-bottom: 1px solid var(--line); display: flex; flex-direction: column; gap: 8px; }
    .side-tools input, .side-tools select { width: 100%; padding: 7px 10px; }
    .side-tools .filters { display: flex; gap: 6px; }
    .chip {
        padding: 3px 10px; border-radius: 999px; border: 1px solid var(--line);
        font-size: 12px; color: var(--muted); cursor: pointer; user-select: none;
    }
    .chip.on { background: var(--accent); border-color: var(--accent); color: #06101f; font-weight: 600; }
    .ep-list { overflow-y: auto; flex: 1; padding-bottom: 24px; }
    .group-title {
        padding: 10px 14px 4px; font-size: 11px; text-transform: uppercase;
        letter-spacing: .08em; color: var(--muted); font-weight: 700;
    }
    .ep {
        display: flex; align-items: center; gap: 8px; padding: 7px 14px; cursor: pointer;
        border-left: 2px solid transparent; overflow: hidden;
    }
    .ep:hover { background: var(--bg3); }
    .ep.sel { background: var(--bg3); border-left-color: var(--accent); }
    .ep .path {
        flex: 1; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        font-size: 13px; color: var(--text);
    }
    .ep .ep-name { font-size: 11px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .badge {
        font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 4px;
        color: #07120d; min-width: 44px; text-align: center; flex-shrink: 0;
    }
    .m-GET { background: var(--get); } .m-POST { background: var(--post); }
    .m-PUT { background: var(--put); } .m-PATCH { background: var(--patch); }
    .m-DELETE { background: var(--delete); } .m-HEAD, .m-OPTIONS { background: var(--other); }
    .src { font-size: 10px; color: var(--muted); border: 1px solid var(--line); border-radius: 4px; padding: 1px 5px; flex-shrink: 0; }
    main { flex: 1; overflow-y: auto; padding: 20px 24px; }
    .empty { color: var(--muted); text-align: center; margin-top: 15vh; }
    .empty h2 { color: var(--text); margin-bottom: 8px; }
    .panel { background: var(--bg2); border: 1px solid var(--line); border-radius: var(--radius); margin-bottom: 14px; }
    .panel > .head {
        padding: 10px 14px; border-bottom: 1px solid var(--line);
        font-weight: 600; font-size: 13px; display: flex; align-items: center; gap: 10px;
    }
    .panel .body { padding: 14px; }
    .urlbar { display: flex; gap: 8px; margin-bottom: 14px; }
    .urlbar select { width: 110px; padding: 8px; font-weight: 700; }
    .urlbar input { flex: 1; padding: 8px 12px; font-family: ui-monospace, Consolas, monospace; font-size: 13px; }
    .urlbar button { min-width: 90px; font-weight: 700; }
    .tabs { display: flex; gap: 2px; border-bottom: 1px solid var(--line); padding: 0 8px; }
    .tab { padding: 8px 14px; cursor: pointer; color: var(--muted); border-bottom: 2px solid transparent; font-size: 13px; }
    .tab.on { color: var(--text); border-bottom-color: var(--accent); font-weight: 600; }
    .tab .cnt { font-size: 10px; background: var(--bg3); border-radius: 999px; padding: 1px 6px; margin-left: 4px; }
    .tabpane { padding: 14px; }
    table.kv { width: 100%; border-collapse: collapse; }
    table.kv th {
        text-align: left; font-size: 11px; color: var(--muted); text-transform: uppercase;
        padding: 4px 8px; font-weight: 600;
    }
    table.kv td { padding: 3px 8px; vertical-align: middle; }
    table.kv input[type=text] { width: 100%; padding: 6px 9px; font-family: ui-monospace, Consolas, monospace; font-size: 12.5px; }
    table.kv input.ck { width: 15px; height: 15px; accent-color: var(--accent); }
    .rowbtn { background: transparent; border: none; color: var(--muted); font-size: 16px; cursor: pointer; padding: 2px 6px; }
    .rowbtn:hover { color: var(--delete); }
    textarea.body {
        width: 100%; min-height: 160px; resize: vertical; padding: 10px;
        font-family: ui-monospace, Consolas, monospace; font-size: 13px; background: var(--bg);
    }
    .desc { color: var(--muted); margin: 10px 0 0; font-size: 13px; white-space: pre-wrap; }
    .meta { color: var(--muted); font-size: 12px; margin-top: 6px; }
    /* response */
    .resp-stats { display: flex; gap: 18px; align-items: center; padding: 10px 14px; border-bottom: 1px solid var(--line); font-size: 13px; }
    .resp-stats .st { font-weight: 700; }
    .s2 { color: var(--get); } .s3 { color: var(--accent); }
    .s4 { color: var(--post); } .s5 { color: var(--delete); }
    pre.resp {
        margin: 0; padding: 14px; overflow: auto; max-height: 50vh;
        font: 12.5px/1.55 ui-monospace, Consolas, monospace; white-space: pre-wrap; word-break: break-word;
    }
    .spinner {
        display: inline-block; width: 14px; height: 14px; border: 2px solid var(--line);
        border-top-color: var(--accent); border-radius: 50%; animation: spin .8s linear infinite;
        vertical-align: -2px;
    }
    .key { color: #7ec6ff; } .str { color: #a5e07f; } .num { color: #f5a623; }
    .bool { color: #f06292; } .null { color: #8b95a8; }
    /* modals */
    .overlay {
        position: fixed; inset: 0; background: rgba(4, 8, 14, .65); display: none;
        align-items: center; justify-content: center; z-index: 50;
    }
    .overlay.open { display: flex; }
    .modal {
        background: var(--bg2); border: 1px solid var(--line); border-radius: 10px;
        width: min(720px, 92vw); max-height: 86vh; display: flex; flex-direction: column;
    }
    .modal .mhead { padding: 14px 18px; border-bottom: 1px solid var(--line); font-weight: 700; display: flex; justify-content: space-between; align-items: center; }
    .modal .mbody { padding: 18px; overflow-y: auto; }
    .modal textarea { width: 100%; min-height: 220px; font-family: ui-monospace, Consolas, monospace; font-size: 12.5px; background: var(--bg); padding: 10px; }
    .mfoot { padding: 12px 18px; border-top: 1px solid var(--line); display: flex; justify-content: flex-end; gap: 8px; }
    .msg { padding: 8px 12px; border-radius: var(--radius); margin-top: 10px; font-size: 13px; display: none; }
    .msg.err { background: #3a1a1e; color: #ff9e9e; display: block; }
    .msg.ok { background: #17301f; color: #8fe3a1; display: block; }
    .hint { color: var(--muted); font-size: 12px; margin: 8px 0; }
    .hint code { background: var(--bg3); padding: 1px 5px; border-radius: 4px; }
    .hitem { display: flex; align-items: center; gap: 8px; padding: 7px 4px; cursor: pointer; border-radius: 6px; }
    .hitem:hover { background: var(--bg3); }
    .hitem .path { flex: 1; font-family: ui-monospace, Consolas, monospace; font-size: 12.5px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .hitem .st { font-size: 11px; color: var(--muted); }
</style>
</head>
<body>

<header>
    <div class="logo">▲ API Explorer</div>
    <div class="sub">Laravel routes · OpenAPI · Postman</div>
    <div class="spacer"></div>
    <button class="ghost" id="btn-vars" title="Environment variables">Variables</button>
    <button class="ghost" id="btn-history" title="Recent requests">History</button>
    <button class="ghost" id="btn-import">Import</button>
    <button class="primary" id="btn-send-top">Send request</button>
</header>

<div class="layout">
    <aside>
        <div class="side-tools">
            <input type="text" id="search" placeholder="Search endpoints… (Ctrl+K)">
            <div class="filters">
                <span class="chip on" data-src="all">All</span>
                <span class="chip" data-src="routes">Laravel</span>
                <span class="chip" data-src="openapi">OpenAPI</span>
                <span class="chip" data-src="postman">Postman</span>
            </div>
        </div>
        <div class="ep-list" id="ep-list"></div>
    </aside>

    <main>
        <div class="empty" id="empty">
            <h2>No endpoint selected</h2>
            <p>Pick one from the list, or use <b>Import</b> to load an OpenAPI spec or Postman collection.</p>
            <p class="meta">Requests are sent through the server-side proxy, so CORS is never an issue.</p>
        </div>

        <div id="detail" style="display:none">
            <div class="urlbar">
                <select id="method"></select>
                <input type="text" id="url" spellcheck="false">
                <button class="primary" id="btn-send">Send</button>
            </div>
            <input type="text" id="base" spellcheck="false" placeholder="Base URL (leave empty to use the path as-is)" style="width:100%;margin-bottom:14px;padding:8px 12px;">
            <div class="desc" id="ep-desc"></div>
            <div class="meta" id="ep-meta"></div>

            <div class="panel" style="margin-top:14px">
                <div class="tabs">
                    <div class="tab on" data-pane="params">Params <span class="cnt" id="cnt-params">0</span></div>
                    <div class="tab" data-pane="headers">Headers <span class="cnt" id="cnt-headers">0</span></div>
                    <div class="tab" data-pane="body">Body</div>
                </div>
                <div class="tabpane" id="pane-params"></div>
                <div class="tabpane" id="pane-headers" style="display:none"></div>
                <div class="tabpane" id="pane-body" style="display:none">
                    <textarea class="body" id="body" spellcheck="false" placeholder='{"example": "json"}'></textarea>
                </div>
            </div>

            <div class="panel">
                <div class="head">Response</div>
                <div id="resp-area">
                    <div class="resp-stats" id="resp-stats" style="display:none"></div>
                    <div class="tabs" id="resp-tabs" style="display:none">
                        <div class="tab on" data-rpane="rbody">Body</div>
                        <div class="tab" data-rpane="rheaders">Headers</div>
                    </div>
                    <div class="tabpane" id="pane-rbody"><pre class="resp" id="rbody">Send a request to see the response here.</pre></div>
                    <div class="tabpane" id="pane-rheaders" style="display:none"><pre class="resp" id="rheaders"></pre></div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Import modal -->
<div class="overlay" id="ov-import">
    <div class="modal">
        <div class="mhead"><span>Import endpoints</span><button class="rowbtn" data-close>✕</button></div>
        <div class="mbody">
            <div class="hint">Paste an <b>OpenAPI/Swagger</b> spec or a <b>Postman collection</b> (v2.x) as JSON — or upload the file. The type is detected automatically.</div>
            <input type="file" id="import-file" style="width:100%;padding:8px;">
            <textarea id="import-text" spellcheck="false" placeholder='{"openapi": "3.0.0", …}  or  {"info": {"_postman_id": …}, "item": […}]'></textarea>
            <div class="msg" id="import-msg"></div>
        </div>
        <div class="mfoot">
            <button class="ghost" data-close>Cancel</button>
            <button class="primary" id="btn-import-go">Import</button>
        </div>
    </div>
</div>

<!-- Variables modal -->
<div class="overlay" id="ov-vars">
    <div class="modal">
        <div class="mhead"><span>Environment variables</span><button class="rowbtn" data-close>✕</button></div>
        <div class="mbody">
            <div class="hint">Use variables anywhere in the URL, headers or body with <code>&#123;&#123;name&#125;&#125;</code>. They are stored in your browser only.</div>
            <table class="kv"><tbody id="vars-table"></tbody></table>
        </div>
        <div class="mfoot">
            <button class="ghost" id="btn-var-add">+ Add variable</button>
            <button class="primary" data-close>Done</button>
        </div>
    </div>
</div>

<!-- History modal -->
<div class="overlay" id="ov-history">
    <div class="modal">
        <div class="mhead"><span>Recent requests</span><button class="rowbtn" data-close>✕</button></div>
        <div class="mbody" id="history-list"></div>
    </div>
</div>

<script>
(function () {
    'use strict';

    var API = @json(route('api-explorer.endpoints'));
    var IMPORT_URL = @json(route('api-explorer.import'));
    var SEND_URL = @json(route('api-explorer.send'));

    var LS = {
        get: function (k, d) { try { return JSON.parse(localStorage.getItem(k)) ?? d; } catch (e) { return d; } },
        set: function (k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (e) {} }
    };

    // Laravel CSRF: the page is served under the "web" group, so the
    // XSRF-TOKEN cookie is set — send it back on every mutating request.
    function xsrfHeaders() {
        var headers = { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' };
        var match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);
        if (match) headers['X-XSRF-TOKEN'] = decodeURIComponent(match[1]);
        return headers;
    }

    var state = {
        all: [],             // [{...endpoint, params:[{...row}], headers:[{...row}], bodyExample}]
        source: 'all',
        query: '',
        selected: null,
        imports: LS.get('apiexp.imports', []),
        vars: LS.get('apiexp.vars', []),
        history: LS.get('apiexp.history', [])
    };

    var METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];
    var $ = function (id) { return document.getElementById(id); };

    // ---------- boot ----------
    function boot() {
        buildMethodSelect();
        bindStatic();
        state.imports.forEach(function (eps) { merge(eps); });
        fetchEndpoints();
    }

    function fetchEndpoints() {
        fetch(API).then(function (r) { return r.json(); }).then(function (data) {
            (data.endpoints || []).forEach(normalize);
            renderList();
        });
    }

    function normalize(ep) {
        ep.params = (ep.params || []).map(function (p) {
            return { name: p.name, in: p.in || 'query', type: p.type || 'string',
                     required: !!p.required, description: p.description || null,
                     value: p.example || '', enabled: true };
        });
        ep.headers = (ep.headers || []).map(function (h) {
            return { name: h.name, value: h.value || '', description: h.description || null, enabled: true };
        });
        if (ep.bodyExample && !ep.body) ep.body = ep.bodyExample;
        state.all.push(ep);
    }

    function merge(endpoints) { endpoints.forEach(normalize); renderList(); }

    // ---------- sidebar ----------
    function filtered() {
        var q = state.query.toLowerCase();
        return state.all.filter(function (ep) {
            if (state.source !== 'all' && ep.source !== state.source) return false;
            if (!q) return true;
            return (ep.path + ' ' + (ep.name || '') + ' ' + (ep.group || '') + ' ' + ep.method)
                .toLowerCase().indexOf(q) !== -1;
        });
    }

    function renderList() {
        var list = filtered(), el = $('ep-list');
        el.innerHTML = '';

        var groups = {};
        list.forEach(function (ep) {
            var g = ep.group || 'Other';
            (groups[g] = groups[g] || []).push(ep);
        });

        Object.keys(groups).sort().forEach(function (g) {
            var t = document.createElement('div');
            t.className = 'group-title';
            t.textContent = g + ' (' + groups[g].length + ')';
            el.appendChild(t);

            groups[g].forEach(function (ep, i) {
                var row = document.createElement('div');
                row.className = 'ep' + (state.selected === ep ? ' sel' : '');
                row.innerHTML =
                    '<span class="badge m-' + ep.method + '">' + ep.method + '</span>' +
                    '<div style="flex:1;min-width:0">' +
                      '<div class="path"></div>' +
                      (ep.name ? '<div class="ep-name"></div>' : '') +
                    '</div>' +
                    '<span class="src">' + ep.source + '</span>';
                row.querySelector('.path').textContent = ep.path;
                if (ep.name) row.querySelector('.ep-name').textContent = ep.name;
                row.onclick = function () { select(ep); };
                ep._el = row;
                el.appendChild(row);
            });
        });
    }

    // ---------- detail ----------
    function select(ep) {
        if (state.selected && state.selected._el) state.selected._el.classList.remove('sel');
        state.selected = ep;
        if (ep._el) ep._el.classList.add('sel');

        $('empty').style.display = 'none';
        $('detail').style.display = 'block';

        $('method').value = ep.method;
        $('url').value = ep.path;
        $('base').value = LS.get('apiexp.base', ep.baseUrl || window.location.origin);
        $('ep-desc').textContent = ep.description || '';
        $('ep-meta').textContent =
            'Source: ' + ep.source + (ep.name ? ' · ' + ep.name : '');

        $('body').value = ep.body || '';
        if (ep.baseUrl) $('base').value = ep.baseUrl;

        renderParams();
        renderHeaders();
        showPane('params');
    }

    function buildMethodSelect() {
        $('method').innerHTML = METHODS.map(function (m) {
            return '<option value="' + m + '">' + m + '</option>';
        }).join('');
    }

    function renderParams() {
        var ep = state.selected;
        var pane = $('pane-params');
        pane.innerHTML = '';

        var table = document.createElement('table');
        table.className = 'kv';
        table.innerHTML = '<thead><tr><th style="width:26px"></th><th style="width:26%">Name</th>' +
            '<th style="width:20%">In</th><th>Value</th><th style="width:26px"></th></tr></thead>';
        var tbody = document.createElement('tbody');
        table.appendChild(tbody);

        function addRow(p) {
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td><input type="checkbox" class="ck"' + (p.enabled ? ' checked' : '') + '></td>' +
                '<td><input type="text" data-f="name" spellcheck="false"></td>' +
                '<td><select><option>path</option><option>query</option><option>header</option><option>body</option></select></td>' +
                '<td><input type="text" data-f="value" spellcheck="false"></td>' +
                '<td><button class="rowbtn" title="Remove">×</button></td>';

            var name = tr.querySelector('[data-f=name]');
            var value = tr.querySelector('[data-f=value]');
            var loc = tr.querySelector('select');
            var ck = tr.querySelector('.ck');

            name.value = p.name; value.value = p.value; loc.value = p.in;
            if (p.required) { name.title = 'Required path parameter'; name.style.borderColor = 'var(--post)'; }

            ck.onchange = function () { p.enabled = ck.checked; updateCounts(); };
            name.oninput = function () { p.name = name.value; };
            value.oninput = function () { p.value = value.value; };
            loc.onchange = function () { p.in = loc.value; };
            tr.querySelector('.rowbtn').onclick = function () {
                ep.params.splice(ep.params.indexOf(p), 1);
                tr.remove(); updateCounts();
            };
            tbody.appendChild(tr);
        }

        (ep.params || []).forEach(addRow);

        var add = document.createElement('tr');
        add.innerHTML = '<td colspan="5"><button class="ghost" style="padding:4px 10px">+ Add param</button></td>';
        add.querySelector('button').onclick = function () {
            var p = { name: '', in: 'query', type: 'string', required: false,
                      description: null, value: '', enabled: true };
            ep.params.push(p); addRow(p); updateCounts();
        };
        tbody.appendChild(add);
        pane.appendChild(table);
        updateCounts();
    }

    function renderHeaders() {
        var ep = state.selected;
        var pane = $('pane-headers');
        pane.innerHTML = '';

        var table = document.createElement('table');
        table.className = 'kv';
        table.innerHTML = '<thead><tr><th style="width:26px"></th><th style="width:30%">Name</th>' +
            '<th>Value</th><th style="width:26px"></th></tr></thead>';
        var tbody = document.createElement('tbody');
        table.appendChild(tbody);

        function addRow(h) {
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td><input type="checkbox" class="ck"' + (h.enabled ? ' checked' : '') + '></td>' +
                '<td><input type="text" data-f="name" spellcheck="false"></td>' +
                '<td><input type="text" data-f="value" spellcheck="false"></td>' +
                '<td><button class="rowbtn" title="Remove">×</button></td>';

            var name = tr.querySelector('[data-f=name]');
            var value = tr.querySelector('[data-f=value]');
            var ck = tr.querySelector('.ck');

            name.value = h.name; value.value = h.value;
            ck.onchange = function () { h.enabled = ck.checked; updateCounts(); };
            name.oninput = function () { h.name = name.value; };
            value.oninput = function () { h.value = value.value; };
            tr.querySelector('.rowbtn').onclick = function () {
                ep.headers.splice(ep.headers.indexOf(h), 1);
                tr.remove(); updateCounts();
            };
            tbody.appendChild(tr);
        }

        (ep.headers || []).forEach(addRow);

        var add = document.createElement('tr');
        add.innerHTML = '<td colspan="4"><button class="ghost" style="padding:4px 10px">+ Add header</button></td>';
        add.querySelector('button').onclick = function () {
            var h = { name: '', value: '', description: null, enabled: true };
            ep.headers.push(h); addRow(h); updateCounts();
        };
        tbody.appendChild(add);
        pane.appendChild(table);
        updateCounts();
    }

    function updateCounts() {
        var ep = state.selected;
        if (!ep) return;
        $('cnt-params').textContent = ep.params.filter(function (p) { return p.enabled; }).length;
        $('cnt-headers').textContent = ep.headers.filter(function (h) { return h.enabled; }).length;
    }

    // ---------- request building ----------
    function subst(s) {
        state.vars.forEach(function (v) {
            if (!v.name) return;
            s = s.split('{{' + v.name + '}}').join(v.value || '');
        });
        return s;
    }

    function collectParams(where) {
        return state.selected.params.filter(function (p) {
            return p.enabled && p.in === where && p.name !== '';
        });
    }

    function buildUrl() {
        var ep = state.selected;
        var base = subst($('base').value.trim()).replace(/\/+$/, '');
        var path = subst($('url').value.trim());

        collectParams('path').forEach(function (p) {
            path = path.split('{' + p.name + '}').join(encodeURIComponent(p.value));
        });

        if (!/^[a-z][a-z0-9+.\-]*:\/\//i.test(path)) {
            path = base + '/' + path.replace(/^\/+/, '');
        }

        var qs = collectParams('query').map(function (p) {
            return encodeURIComponent(p.name) + '=' + encodeURIComponent(p.value);
        }).join('&');

        return qs ? path + '?' + qs : path;
    }

    function collectHeaders() {
        var out = {};
        state.selected.headers.forEach(function (h) {
            if (h.enabled && h.name) out[h.name] = subst(h.value);
        });
        if (!out['Accept'] && !out['accept']) out['Accept'] = 'application/json';
        return out;
    }

    // ---------- send ----------
    function send() {
        if (!state.selected) return;
        var ep = state.selected;

        LS.set('apiexp.base', $('base').value.trim());

        var method = $('method').value;
        var url = buildUrl();
        var bodyText = subst($('body').value);

        $('rbody').innerHTML = '<span class="spinner"></span> Sending ' + method + ' ' + url + ' …';
        $('rheaders').textContent = '';
        $('resp-stats').style.display = 'none';
        $('resp-tabs').style.display = 'none';
        showRespPane('rbody');
        $('btn-send').disabled = true;

        var started = Date.now();

        fetch(SEND_URL, {
            method: 'POST',
            headers: Object.assign({ 'Content-Type': 'application/json' }, xsrfHeaders()),
            body: JSON.stringify({
                url: url, method: method,
                headers: collectHeaders(),
                body: ['POST', 'PUT', 'PATCH', 'DELETE'].indexOf(method) !== -1 ? bodyText : null
            }),
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); }).then(function (res) {
            $('btn-send').disabled = false;
            renderResponse(res, Date.now() - started, url, method);
        }).catch(function (e) {
            $('btn-send').disabled = false;
            $('rbody').textContent = 'Request failed: ' + e;
        });
    }

    function renderResponse(res, localMs, url, method) {
        if (res.ok === false && res.error) {
            $('rbody').innerHTML = '';
            $('rbody').textContent = '⚠ ' + res.error;
            return;
        }

        var cls = res.status < 300 ? 's2' : res.status < 400 ? 's3' : res.status < 500 ? 's4' : 's5';
        $('resp-stats').style.display = 'flex';
        $('resp-stats').innerHTML =
            '<span class="st ' + cls + '">' + res.status + ' ' + (res.statusText || '') + '</span>' +
            '<span>' + res.timeMs + ' ms</span>' +
            '<span>' + formatSize(res.size) + '</span>' +
            '<span style="color:var(--muted);font-size:12px">' + (res.contentType || '') + '</span>';

        $('rbody').innerHTML = highlight(res.body, res.contentType || '');
        $('rheaders').textContent = Object.keys(res.headers || {}).map(function (k) {
            return k + ': ' + res.headers[k];
        }).join('\n');
        $('resp-tabs').style.display = 'flex';

        state.history.unshift({
            method: method, url: url, status: res.status, ms: res.timeMs,
            ts: Date.now(),
            request: {
                url: url, method: method, headers: collectHeaders(),
                body: ['POST', 'PUT', 'PATCH', 'DELETE'].indexOf(method) !== -1 ? $('body').value : null
            }
        });
        state.history = state.history.slice(0, 25);
        LS.set('apiexp.history', state.history);
    }

    function formatSize(n) {
        return n < 1024 ? n + ' B' : (n / 1024).toFixed(1) + ' KB';
    }

    function highlight(text, contentType) {
        var safe = text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

        if (/json/i.test(contentType)) {
            try {
                safe = JSON.stringify(JSON.parse(text), null, 2)
                    .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            } catch (e) {}

            return safe
                .replace(/"([^"\\]*(\\.[^"\\]*)*)"(\s*:)?/g, function (m) {
                    return m.slice(-1) === ':' ? '<span class="key">' + m + '</span>' : '<span class="str">' + m + '</span>';
                })
                .replace(/\b(-?\d+\.?\d*([eE][+-]?\d+)?)\b/g, '<span class="num">$1</span>')
                .replace(/\b(true|false)\b/g, '<span class="bool">$1</span>')
                .replace(/\bnull\b/g, '<span class="null">null</span>');
        }

        return safe;
    }

    // ---------- import ----------
    function importContent(text) {
        var msg = $('import-msg');
        msg.className = 'msg';
        msg.textContent = 'Parsing…';
        msg.style.display = 'block';

        var guess = 'openapi';
        try {
            var parsed = JSON.parse(text);
            if (parsed && parsed.item) guess = 'postman';
        } catch (e) {
            guess = 'openapi'; // YAML specs arrive here too
        }

        fetch(IMPORT_URL, {
            method: 'POST',
            headers: Object.assign({ 'Content-Type': 'application/json' }, xsrfHeaders()),
            credentials: 'same-origin',
            body: JSON.stringify({ type: guess, content: text })
        }).then(function (r) { return r.json().then(function (j) { return { ok: r.ok, j: j }; }); })
        .then(function (res) {
            if (!res.ok) {
                msg.className = 'msg err';
                msg.textContent = res.j.message || 'Import failed.';
                return;
            }
            var imported = res.j.endpoints.map(function (ep) {
                ep.source = guess + '-import';
                return ep;
            });
            state.imports.push(imported);
            LS.set('apiexp.imports', state.imports);
            merge(imported);
            msg.className = 'msg ok';
            msg.textContent = res.j.message;
            setTimeout(closeModals, 900);
        }).catch(function (e) {
            msg.className = 'msg err';
            msg.textContent = 'Import failed: ' + e;
        });
    }

    // ---------- variables ----------
    function renderVars() {
        var tbody = $('vars-table');
        tbody.innerHTML = '';

        function addRow(v) {
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td style="width:44%"><input type="text" data-f="name" placeholder="name" spellcheck="false"></td>' +
                '<td><input type="text" data-f="value" placeholder="value" spellcheck="false"></td>' +
                '<td style="width:26px"><button class="rowbtn">×</button></td>';
            var name = tr.querySelector('[data-f=name]');
            var value = tr.querySelector('[data-f=value]');
            name.value = v.name; value.value = v.value;
            name.oninput = function () { v.name = name.value; saveVars(); };
            value.oninput = function () { v.value = value.value; saveVars(); };
            tr.querySelector('.rowbtn').onclick = function () {
                state.vars.splice(state.vars.indexOf(v), 1);
                tr.remove(); saveVars();
            };
            tbody.appendChild(tr);
        }

        state.vars.forEach(addRow);

        if (state.vars.length === 0) {
            state.vars.push({ name: 'token', value: '' });
            addRow(state.vars[0]);
        }
    }

    function saveVars() { LS.set('apiexp.vars', state.vars); }

    // ---------- history ----------
    function renderHistory() {
        var el = $('history-list');
        el.innerHTML = '';
        if (state.history.length === 0) {
            el.innerHTML = '<div class="hint">No requests yet.</div>';
            return;
        }
        state.history.forEach(function (h) {
            var d = document.createElement('div');
            d.className = 'hitem';
            d.innerHTML = '<span class="badge m-' + h.method + '">' + h.method + '</span>' +
                '<span class="path"></span>' +
                '<span class="st s' + String(h.status)[0] + '">' + h.status + '</span>' +
                '<span class="st">' + h.ms + ' ms</span>';
            d.querySelector('.path').textContent = h.url;
            d.onclick = function () {
                closeModals();
                $('rbody').innerHTML = 'Loading from history…';
                fetch(SEND_URL, {
                    method: 'POST',
                    headers: Object.assign({ 'Content-Type': 'application/json' }, xsrfHeaders()),
                    credentials: 'same-origin',
                    body: JSON.stringify(h.request)
                }).then(function (r) { return r.json(); })
                .then(function (res) { renderResponse(res, res.timeMs || 0, h.url, h.method); });
            };
            el.appendChild(d);
        });
    }

    // ---------- chrome ----------
    function showPane(name) {
        ['params', 'headers', 'body'].forEach(function (p) {
            $('pane-' + p).style.display = p === name ? 'block' : 'none';
        });
        document.querySelectorAll('.tabs .tab[data-pane]').forEach(function (t) {
            t.classList.toggle('on', t.getAttribute('data-pane') === name);
        });
    }

    function showRespPane(name) {
        ['rbody', 'rheaders'].forEach(function (p) {
            $('pane-' + p).style.display = p === name ? 'block' : 'none';
        });
        document.querySelectorAll('.tab[data-rpane]').forEach(function (t) {
            t.classList.toggle('on', t.getAttribute('data-rpane') === name);
        });
    }

    function closeModals() {
        document.querySelectorAll('.overlay').forEach(function (o) { o.classList.remove('open'); });
    }

    function bindStatic() {
        $('search').oninput = function () { state.query = this.value; renderList(); };

        document.querySelectorAll('.chip[data-src]').forEach(function (chip) {
            chip.onclick = function () {
                state.source = chip.getAttribute('data-src');
                document.querySelectorAll('.chip[data-src]').forEach(function (c) { c.classList.remove('on'); });
                chip.classList.add('on');
                renderList();
            };
        });

        document.querySelectorAll('.tab[data-pane]').forEach(function (t) {
            t.onclick = function () { showPane(t.getAttribute('data-pane')); };
        });
        document.querySelectorAll('.tab[data-rpane]').forEach(function (t) {
            t.onclick = function () { showRespPane(t.getAttribute('data-rpane')); };
        });

        $('btn-send').onclick = send;
        $('btn-send-top').onclick = function () {
            if (state.selected) { send(); } else { alert('Select an endpoint first.'); }
        };

        $('btn-import').onclick = function () { $('ov-import').classList.add('open'); };
        $('btn-vars').onclick = function () { renderVars(); $('ov-vars').classList.add('open'); };
        $('btn-history').onclick = function () { renderHistory(); $('ov-history').classList.add('open'); };
        $('btn-var-add').onclick = function () {
            var v = { name: '', value: '' };
            state.vars.push(v); renderVars(); saveVars();
        };

        $('btn-import-go').onclick = function () {
            var text = $('import-text').value.trim();
            if (!text) { alert('Paste the spec or collection JSON first.'); return; }
            importContent(text);
        };

        $('import-file').onchange = function () {
            var file = this.files[0];
            if (!file) return;
            var reader = new FileReader();
            reader.onload = function () {
                $('import-text').value = String(reader.result || '');
            };
            reader.readAsText(file);
        };

        document.querySelectorAll('[data-close]').forEach(function (b) {
            b.onclick = closeModals;
        });
        document.querySelectorAll('.overlay').forEach(function (o) {
            o.addEventListener('mousedown', function (e) { if (e.target === o) closeModals(); });
        });

        document.addEventListener('keydown', function (e) {
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault(); $('search').focus();
            }
            if (e.key === 'Escape') closeModals();
        });
    }

    boot();
})();
</script>
</body>
</html>
