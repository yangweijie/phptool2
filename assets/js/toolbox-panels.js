/* FlyEnv Toolbox — Panel HTML Definitions (with i18n) */
var __p = {};
__p.diff = function() {
  var toolbar = '<div class="flex flex-wrap gap-2 mb-3">'
    + EP.button(_t('diff_sample'), { onclick: 'diffSample()' })
    + EP.button(_t('diff_swap'), { onclick: 'diffSwap()' })
    + EP.button(_t('diff_clear'), { onclick: 'diffClear()' })
    + EP.button(_t('diff_prev'), { onclick: 'diffPrev()' })
    + EP.button(_t('diff_next'), { onclick: 'diffNext()' })
    + EP.button(_t('diff_copy'), { onclick: 'diffCopy()' })
    + '</div>';
  var editors = '<div class="diff-grid">'
    + EP.card(EP.textarea('diffO', { rows: 12, class: 'mono', oninput: 'diffRun()' }), _t('diff_original'))
    + EP.card(EP.textarea('diffC', { rows: 12, class: 'mono', oninput: 'diffRun()' }), _t('diff_changed'))
    + '</div>';
  var summary = EP.card('<div id="diffStats" class="flex flex-wrap gap-2"></div>', _t('diff_summary'));
  var detail = EP.card('<div id="diffOut" class="diff-out"><div class="diff-col" id="diffOutO"></div><div class="diff-col" id="diffOutC"></div></div>', _t('diff_detail') || 'Diff');
  return EP.card(toolbar + editors + summary + detail, _t('diff_title'));
};

__p.md = function() {
  var inner =
    '<div class="tb-tabs" id="mdTabs">'
    + '<div class="tb-tab active" data-tab="tab-1" onclick="mdSwitchTab(\'tab-1\')">tab-1<span class="tb-tab__close" onclick="event.stopPropagation();mdCloseTab(\'tab-1\')">×</span></div>'
    + '<button class="tb-tab-add" onclick="mdAddTab()" title="' + _t('code_tab_add') + '">+</button>'
    + '</div>'
    + '<div class="md-main">'
    +   '<div class="md-left" id="mdLeft">'
    +     '<div class="md-toolbar">'
    +       EP.button(_t('md_open'), { icon: '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>', onclick: 'mdOpenFile()' })
    +       '<input type="file" id="mdFile" accept=".md,.MD,.markdown,.txt" style="display:none" onchange="mdOnFile(event)">'
    +     '</div>'
    +     EP.textarea('mdIn', { rows: 18, class: 'mono', placeholder: _t('md_ph'), oninput: 'mdRender()' })
    +   '</div>'
    +   '<div class="md-handle" id="mdHandle" onmousedown="mdSplitDown(event)"></div>'
    +   '<div class="md-right" id="mdRight">'
    +     '<div class="md-right-label">MarkdownPreview</div>'
    +     '<div class="md-preview vp-doc" id="mdOut"></div>'
    +   '</div>'
    + '</div>';
  return EP.card(inner, _t('md_title'));
};

__p.cron = function() {
  var modeRadios = EP.radioGroup('cronMode', [
    { value: 'auto', label: 'Auto' },
    { value: 'linux', label: 'Linux (5 fields)' },
    { value: 'seconds', label: 'Seconds (6 fields)' }
  ], 'auto');
  var genModes = [
    { value: 'everyMinute', label: 'Every minute' },
    { value: 'everyNMinutes', label: 'Every N minutes' },
    { value: 'hourly', label: 'Hourly' },
    { value: 'daily', label: 'Daily' },
    { value: 'weekly', label: 'Weekly' },
    { value: 'monthly', label: 'Monthly' }
  ];
  var dowOpts = [];
  ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'].forEach(function (d, i) {
    dowOpts.push({ value: i, label: d });
  });
  var genCard = EP.formItem(_t('cron_mode'), modeRadios)
    + EP.alert(_t('http_all') + ' / Auto-detects 5 or 6 fields', 'info')
    + EP.formItem(_t('cron_schedule'), EP.select('cGen', genModes, { onchange: 'cronToggleGen();cronGenerate();' }))
    + '<div id="cIntMinBox" style="display:none">' + EP.formItem('Interval minutes', EP.input('cIntMin', { value: 5, oninput: 'cronGenerate()' })) + '</div>'
    + '<div id="cHMBox" style="display:none" class="ep-grid2">'
    + EP.formItem('Hour', EP.input('cHour', { value: 9, oninput: 'cronGenerate()' }))
    + EP.formItem('Minute', EP.input('cMin', { value: 0, oninput: 'cronGenerate()' }))
    + '</div>'
    + '<div id="cDowBox" style="display:none">' + EP.formItem('Day of week', EP.select('cDow', dowOpts, { onchange: 'cronGenerate()' })) + '</div>'
    + '<div id="cDomBox" style="display:none">' + EP.formItem('Day of month', EP.input('cDom', { value: 1, oninput: 'cronGenerate()' })) + '</div>'
    + '<div id="cGenDesc"></div>';

  var examples = [
    { label: 'Every minute', expr: '* * * * *' },
    { label: 'Every 5 minutes', expr: '*/5 * * * *' },
    { label: 'Every day at midnight', expr: '0 0 * * *' },
    { label: 'Weekdays at 9:00', expr: '0 9 * * 1-5' },
    { label: 'First day of month', expr: '0 0 1 * *' }
  ];
  var exHtml = '<div class="ep-btn-group">';
  examples.forEach(function (e) { exHtml += EP.button(e.label, { onclick: "cronUseExample('" + e.expr + "')" }); });
  exHtml += '</div>';

  var parseCard = EP.formItem(_t('cron_expr'), EP.input('cExpr', { value: '*/5 * * * *', oninput: 'cronParse()' }))
    + '<div id="cTags" style="margin:6px 0"></div>'
    + EP.formItem(_t('cron_examples'), exHtml)
    + EP.formItem(_t('cron_count'), '<input id="cCount" type="range" min="1" max="30" value="10" class="ep-slider" oninput="cronParse()"><span id="cCountLbl" style="margin-left:8px">10</span>');

  var runsCard = '<div id="cErr"></div><div id="cRuns"></div>';

  return '<div class="tool-main">'
    + EP.card(genCard, _t('cron_generate'))
    + EP.card(parseCard, _t('cron_parse'))
    + EP.card(runsCard, _t('cron_next'))
    + '</div>';
};

__p.json = function() {
  var fmtOpts = [
    { value: 'json', label: _t('json_fmt_json'), selected: true },
    { value: 'json-minify', label: _t('json_fmt_minify') },
    { value: 'php', label: _t('json_fmt_php') },
    { value: 'js', label: _t('json_fmt_js') },
    { value: 'ts', label: _t('json_fmt_ts') },
    { value: 'yaml', label: _t('json_fmt_yaml') },
    { value: 'xml', label: _t('json_fmt_xml') },
    { value: 'plist', label: _t('json_fmt_plist') },
    { value: 'toml', label: _t('json_fmt_toml') },
    { value: 'goStruct', label: _t('json_fmt_gostruct') },
    { value: 'goBson', label: _t('json_fmt_gobson') },
    { value: 'rustSerde', label: _t('json_fmt_rust') },
    { value: 'Java', label: _t('json_fmt_java') },
    { value: 'Kotlin', label: _t('json_fmt_kotlin') },
    { value: 'MySQL', label: _t('json_fmt_mysql') },
    { value: 'JSDoc', label: _t('json_fmt_jsdoc') }
  ];
  var sortBtns = [
    { label: _t('json_asc'), onclick: "jsonSort('asc')" },
    { label: _t('json_desc'), onclick: "jsonSort('desc')" },
    { label: _t('json_none'), onclick: "jsonSort('none')" }
  ];
  var inner =
    '<div class="tb-tabs" id="jsonTabs">'
    + '<div class="tb-tab active" data-tab="tab-1" onclick="jsonSwitchTab(\'tab-1\')">tab-1<span class="tb-tab__close" onclick="event.stopPropagation();jsonCloseTab(\'tab-1\')">×</span></div>'
    + '<button class="tb-tab-add" onclick="jsonAddTab()" title="' + _t('code_tab_add') + '">+</button>'
    + '</div>'
    + '<div class="code-main">'
    +   '<div class="code-left" id="jsonLeft">'
    +     '<div class="code-toolbar">'
    +       '<span id="jsonType" class="muted"></span>'
    +       EP.button(_t('code_open'), { onclick: 'jsonOpenFile()' })
    +       '<input type="file" id="jsonFile" style="display:none" onchange="jsonOnFile(event)">'
    +     '</div>'
    +     EP.textarea('jsonIn', { rows: 16, class: 'mono', placeholder: _t('json_input_ph'), oninput: 'jsonInput()' })
    +   '</div>'
    +   '<div class="code-handle" id="jsonHandle" onmousedown="jsonSplitDown(event)"></div>'
    +   '<div class="code-right" id="jsonRight">'
    +     '<div class="code-toolbar">'
    +       EP.select('jsonFmt', fmtOpts, { onchange: 'jsonApplySort()' })
    +       EP.btnGroup(sortBtns)
    +       EP.button(_t('code_save'), { onclick: 'jsonSave()' })
    +     '</div>'
    +     EP.textarea('jsonOut', { rows: 16, class: 'mono', readonly: true })
    +   '</div>'
    + '</div>';
  return EP.card(inner, _t('json_title'));
};

__p.code = function() {
  var langOpts = [
    { value: 'php', label: 'PHP', selected: true },
    { value: 'python', label: 'Python' },
    { value: 'node', label: 'Node.js' },
    { value: 'go', label: 'Go' },
    { value: 'rust', label: 'Rust' },
    { value: 'java', label: 'Java' }
  ];
  var toFmts = [
    { value: 'raw', label: 'Raw', selected: true },
    { value: 'json', label: 'JSON' },
    { value: 'json-minify', label: 'JSON Minify' },
    { value: 'php', label: 'PHP Array' },
    { value: 'js', label: 'JavaScript' },
    { value: 'ts', label: 'TypeScript' },
    { value: 'yaml', label: 'YAML' },
    { value: 'xml', label: 'XML' },
    { value: 'plist', label: 'PList' },
    { value: 'toml', label: 'TOML' },
    { value: 'goStruct', label: 'Go Struct' },
    { value: 'goBson', label: 'Go Bson' },
    { value: 'rustSerde', label: 'Rust Serde' },
    { value: 'Java', label: 'Java' },
    { value: 'Kotlin', label: 'Kotlin' },
    { value: 'MySQL', label: 'MySQL' },
    { value: 'JSDoc', label: 'JSDoc' }
  ];
  var binHtml = '<select id="codeBin" class="ep-select"><option value="">—</option>'
    + '<optgroup label="PHP"><option value="php">php</option></optgroup>'
    + '<optgroup label="Node"><option value="node">node</option></optgroup>'
    + '<optgroup label="Python"><option value="python">python</option></optgroup>'
    + '</select>';
  var inner =
    '<div class="tb-tabs" id="codeTabs">'
    + '<div class="tb-tab active" data-tab="tab-1" onclick="codeSwitchTab(\'tab-1\')">tab-1<span class="tb-tab__close" onclick="event.stopPropagation();codeCloseTab(\'tab-1\')">×</span></div>'
    + '<button class="tb-tab-add" onclick="codeAddTab()" title="' + _t('code_tab_add') + '">+</button>'
    + '</div>'
    + '<div class="code-main">'
    +   '<div class="code-left" id="codeLeft">'
    +     '<div class="code-toolbar">'
    +       EP.select('codeLang', langOpts, { onchange: 'codeOnLang()' })
    +       binHtml
    +       EP.button(_t('code_open'), { onclick: 'codeOpenFile()' })
    +       EP.button(_t('code_run'), { onclick: 'codeRun()' })
    +       '<input type="file" id="codeFile" style="display:none" onchange="codeOnFile(event)">'
    +     '</div>'
    +     EP.code('codeIn', { rows: 16, value: "<?php echo 'Hello FlyEnv';" })
    +   '</div>'
    +   '<div class="code-handle" id="codeHandle" onmousedown="codeSplitDown(event)"></div>'
    +   '<div class="code-right" id="codeRight">'
    +     '<div class="code-toolbar">'
    +       EP.select('codeTo', toFmts, { onchange: 'codeTransform()' })
    +       EP.button(_t('code_save'), { onclick: 'codeSave()' })
    +     '</div>'
    +     EP.textarea('cO3', { rows: 16, class: 'mono', readonly: true })
    +   '</div>'
    + '</div>';
  return EP.card(inner, _t('code_title'));
};

__p.clib = function() {
  var icGear = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>';
  var icPlus = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>';
  var icFolder = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>';
  var icMore = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><circle cx="12" cy="5" r="1.6"/><circle cx="12" cy="12" r="1.6"/><circle cx="12" cy="19" r="1.6"/></svg>';
  var icEdit = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4z"/></svg>';
  var icTop = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"/><polyline points="5 12 12 5 19 12"/></svg>';
  var icDel = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>';
  var icMove = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h6v6H4z"/><path d="M14 10l7-7m0 0h-5m5 0v5"/><path d="M20 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V5"/></svg>';
  var icSearch = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>';
  var icTerm = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 17 10 11 4 5"/><line x1="12" y1="19" x2="20" y2="19"/></svg>';

  var inner =
    '<div class="clib">'
    + '<div class="clib-bar">'
    +   '<span class="clib-bar__title">' + _t('clib_title') + '</span>'
    +   '<button class="clib-ico-btn" title="' + _t('clib_settings') + '" onclick="clibToggleSettings(event)">' + icGear + '</button>'
    +   '<div class="clib-set" id="clibSet" style="display:none"></div>'
    + '</div>'
    + '<div class="clib-tabs" id="clibTabs"></div>'
    + '<div class="clib-body">'
    +   '<div class="clib-side">'
    +     '<div class="clib-sec">'
    +       '<div class="clib-sec__h"><span>' + _t('clib_group') + '</span>'
    +         '<button class="clib-ico-btn sm" title="' + _t('clib_addgroup') + '" onclick="clibAddGroup()">' + icPlus + '</button></div>'
    +       '<div class="clib-list" id="clibGroups"></div>'
    +     '</div>'
    +     '<div class="clib-sec clib-sec--grow">'
    +       '<div class="clib-sec__h"><span>' + _t('clib_code') + '</span>'
    +         '<button class="clib-ico-btn sm" title="' + _t('clib_addcode') + '" onclick="clibAddCode()">' + icPlus + '</button></div>'
    +       '<div class="clib-list" id="clibCodes"></div>'
    +     '</div>'
    +     '<div class="clib-batch" id="clibBatch" style="display:none">'
    +       '<label class="ep-check"><input type="checkbox" id="clibSelAll" onchange="clibSelAll(this.checked)"> ' + _t('clib_selectall') + '</label>'
    +       '<div class="clib-batch__btns">'
    +         '<button class="clib-ico-btn sm" title="' + _t('clib_movegroup') + '" onclick="clibBatchMove()">' + icMove + '</button>'
    +         '<button class="clib-ico-btn sm danger" title="' + _t('clib_del') + '" onclick="clibBatchDel()">' + icDel + '</button>'
    +         '<button class="clib-link" onclick="clibExitBatch()">' + _t('clib_cancel') + '</button>'
    +       '</div>'
    +     '</div>'
    +   '</div>'
    +   '<div class="clib-main" id="clibMain"></div>'
    + '</div>';

  // stash icons for logic use
  window.__CLIB_ICONS = { gear: icGear, plus: icPlus, folder: icFolder, more: icMore, edit: icEdit, top: icTop, del: icDel, move: icMove, search: icSearch, term: icTerm };

  return EP.card(inner, _t('clib_title'));
};

__p.jwt = function() {
  var algos = [
    { value: 'none', label: 'none' },
    { value: 'HS256', label: 'HS256', selected: true },
    { value: 'HS384', label: 'HS384' },
    { value: 'HS512', label: 'HS512' }
  ];
  var encCard = EP.formItem(_t('jwt_algorithm'), EP.select('jAlg', algos, { onchange: 'jwtEnc()' }))
    + EP.formItem(_t('jwt_secret'), EP.input('jSec', { value: 'your-256-bit-secret', oninput: 'jwtEnc()' }))
    + EP.formItem(_t('jwt_header'), EP.code('jHead', { rows: 5, value: '{\n  "alg": "HS256",\n  "typ": "JWT"\n}', oninput: 'jwtEnc()' }))
    + EP.formItem(_t('jwt_payload'), EP.code('jPay', { rows: 8, value: '{\n  "sub": "1234567890",\n  "name": "FlyEnv",\n  "iat": 1516239022\n}', oninput: 'jwtEnc()' }))
    + '<div id="jEncErr"></div>'
    + EP.formItem(_t('jwt_token'), EP.textarea('jTok', { rows: 5, readonly: true }))
    + EP.row(EP.button(_t('jwt_decode_this'), { onclick: 'jwtUseCreated()' }), { end: true });
  var decCard = EP.formItem(_t('jwt_algorithm'), EP.select('jDAlg', algos, { onchange: 'jwtDec()' }))
    + EP.formItem(_t('jwt_secret'), EP.input('jDSec', { value: 'your-256-bit-secret', oninput: 'jwtDec()' }))
    + EP.formItem(_t('jwt_token'), EP.code('jDTok', { rows: 6, placeholder: 'Paste JWT here', oninput: 'jwtDec()' }))
    + '<div id="jDecMsg"></div>'
    + EP.formItem(_t('jwt_header'), EP.code('jDHead', { rows: 5, readonly: true }))
    + EP.formItem(_t('jwt_payload'), EP.code('jDPay', { rows: 8, readonly: true }));
  return '<div class="tool-main"><div class="ep-grid2">'
    + EP.card(encCard, _t('jwt_encode'))
    + EP.card(decCard, _t('jwt_decode'))
    + '</div></div>';
};

__p.hash = function() {
  var digests = [
    {value:'Bin',label:'Binary (base 2)'},
    {value:'Hex',label:'Hexadecimal (base 16)',selected:true},
    {value:'Base64',label:'Base64 (base 64)'},
    {value:'Base64url',label:'Base64url (base 64 with url safe chars)'}
  ];
  var algos = ['MD5','SHA1','SHA256','SHA224','SHA384','SHA3','RIPEMD160'];
  var rows = '';
  algos.forEach(function(a){
    rows += '<div style="margin:6px 0;display:flex;gap:8px;align-items:center">'
      + EP.inputPrepend('hash_'+a, a, {readonly:true, class:'ep-mono'})
      + EP.button(_t('copy'), {type:'link', small:true, onclick:"EP.copy(document.getElementById('hash_"+a+"').value)"})
      + '</div>';
  });
  var inner = EP.formItem(_t('hash_input'), EP.textarea('hashText',{rows:4, placeholder:_t('hash_ph'), oninput:'hashCompute()'}))
    + EP.formItem(_t('digest_encoding'), EP.select('hashDigest', digests, {onchange:'hashCompute()'}))
    + '<div id="hashResults">'+rows+'</div>';
  return '<div class="tool-main">'+EP.card(inner)+'</div>';
};

__p.encrypt = function() {
  var algos = [
    {value:'AES',label:'AES',selected:true},
    {value:'TripleDES',label:'TripleDES'},
    {value:'Rabbit',label:'Rabbit'},
    {value:'RC4',label:'RC4'}
  ];
  var twoCol = function (cfg) {
    return '<div style="display:flex;gap:12px">'
      + '<div style="flex:1">' + EP.formItem(cfg.textLbl, EP.textarea(cfg.tid, { rows:5, value: cfg.tval, placeholder: cfg.tph, oninput: cfg.tin })) + '</div>'
      + '<div style="flex:1;display:flex;flex-direction:column;gap:8px">'
      +   EP.formItem(_t('enc_secret'), EP.input(cfg.kid, { value: cfg.kval, oninput: cfg.kin }))
      +   EP.formItem(_t('enc_algo'), EP.select(cfg.aid, algos, { onchange: cfg.ain }))
      + '</div>'
      + '</div>';
  };
  var encInner = twoCol({
      textLbl: _t('enc_text'), tid:'encText', tval:'Lorem ipsum dolor sit amet',
      tph:_t('enc_input_ph'), tin:"encRun('enc')",
      kid:'encKey', kval:'my secret key', kin:"encRun('enc')",
      aid:'encAlgo', ain:"encRun('enc')"
    })
    + '<div style="margin-top:20px">' + EP.formItem(_t('enc_encrypted'), EP.textarea('encOut',{rows:3, readonly:true})) + '</div>';
  var decInner = twoCol({
      textLbl: _t('enc_text'), tid:'decText', tval:'U2FsdGVkX1/EC3+6P5dbbkZ3e1kQ5o2yzuU0NHTjmrKnLBEwreV489Kr0DIB+uBs',
      tph:_t('dec_input_ph'), tin:"encRun('dec')",
      kid:'decKey', kval:'my secret key', kin:"encRun('dec')",
      aid:'decAlgo', ain:"encRun('dec')"
    })
    + '<div style="margin-top:20px">' + EP.formItem(_t('enc_decrypted'), EP.textarea('decOut',{rows:3, readonly:true})) + '</div>';
  return '<div class="tool-main"><div class="ep-grid2">'
    + EP.card(encInner, _t('cenc'))
    + EP.card(decInner, _t('cdec'))
    + '</div></div>';
};

__p.ts = function() {
  var units = [
    {value:0,label:_t('ts_second'),selected:true},
    {value:1,label:_t('ts_millisecond')}
  ];
  var row1 = EP.row(
      EP.input('ts0',{placeholder:'Unix Timestamp'})
    + EP.select('tsFlag0', units, {class:'ep-select--unit'})
  ,{class:'ep-ts-row'})
  + '<div class="ep-form-item"><label>&nbsp;</label>'+EP.input('tsDate0',{readonly:true, placeholder:'Date time string'})+'</div>';
  var row2 = EP.row(
      EP.input('tsDate1',{type:'datetime-local'})
    + EP.input('tsStr1',{readonly:true, placeholder:'Unix Timestamp', class:'ep-grow'})
    + EP.select('tsFlag1', units, {class:'ep-select--unit'})
  ,{class:'ep-ts-row'});
  var inner = '<div class="ep-ts-label">'+_t('ts_current')+'</div>'
    + '<div class="ep-ts-current" id="tsNow" title="'+_t('copy')+'" ondblclick="EP.copy(this.textContent)">'+Math.floor(Date.now()/1000)+'</div>'
    + EP.card(row1, '')
    + EP.card(row2, '');
  return '<div class="tool-main">'+inner+'</div>';
};

__p.b64 = function() {
  var card1 = EP.row(EP.switch('b64EncSafe', 'b64Live()') + '<span style="margin-left:8px">' + _t('b64_urlsafe') + '</span>')
    + EP.formItem(_t('b64_string_to_encode'), EP.textarea('b64TextIn',{rows:5, placeholder:_t('b64_ph'), value:'Hello FlyEnv!', oninput:'b64Live()'}))
    + EP.formItem(_t('b64_of_string'), EP.textarea('b64TextOut',{rows:5, readonly:true}))
    + EP.row(EP.button(_t('b64_copy_b64'),{onclick:"EP.copy(g('b64TextOut').value)"}),{center:true});
  var card2 = EP.row(EP.switch('b64DecSafe', 'b64Live()') + '<span style="margin-left:8px">' + _t('b64_urlsafe') + '</span>')
    + EP.formItem(_t('b64_b64_to_decode'), EP.textarea('b64B64In',{rows:5, placeholder:_t('b64_ph'), oninput:'b64Live()'}))
    + EP.formItem(_t('b64_decoded'), EP.textarea('b64B64Out',{rows:5, readonly:true}))
    + '<div id="b64Err" class="ep-alert ep-alert--danger ep-hidden">'+_t('b64_invalid')+'</div>'
    + EP.row(EP.button(_t('b64_copy_dec'),{onclick:"EP.copy(g('b64B64Out').value)"}),{center:true});
  return '<div class="tool-main"><div class="ep-grid2">'+EP.card(card1,_t('b64_str_to_b64'))+EP.card(card2,_t('b64_b64_to_str'))+'</div></div>';
};

__p.url = function() {
  var card1 = EP.formItem(_t('url_your_string'), EP.textarea('urlEncIn',{rows:3, placeholder:_t('url_your_string'), value:'https://example.com/path?q=FlyEnv', oninput:'urlLive()'}))
    + EP.formItem(_t('url_encoded'), EP.textarea('urlEncOut',{rows:3, readonly:true}))
    + EP.row(EP.button(_t('b64_copy_b64'),{onclick:"EP.copy(g('urlEncOut').value)"}),{center:true});
  var card2 = EP.formItem(_t('url_your_string'), EP.textarea('urlDecIn',{rows:3, placeholder:_t('url_your_string'), oninput:'urlLive()'}))
    + EP.formItem(_t('url_decoded'), EP.textarea('urlDecOut',{rows:3, readonly:true}))
    + EP.row(EP.button(_t('b64_copy_dec'),{onclick:"EP.copy(g('urlDecOut').value)"}),{center:true});
  return '<div class="tool-main"><div class="ep-grid2">'+EP.card(card1,_t('url_encode_hdr'))+EP.card(card2,_t('url_decode_hdr'))+'</div></div>';
};

__p.html = function() {
  var card1 = EP.formItem(_t('html_input'), EP.textarea('htmlEncIn',{rows:3, placeholder:_t('html_input'), value:'<script>alert("Hi")<\/script>', oninput:'htmlLive()'}))
    + EP.formItem(_t('html_escaped'), EP.textarea('htmlEncOut',{rows:3, readonly:true}))
    + EP.row(EP.button(_t('b64_copy_b64'),{onclick:"EP.copy(g('htmlEncOut').value)"}),{center:true});
  var card2 = EP.formItem(_t('html_input'), EP.textarea('htmlDecIn',{rows:3, placeholder:_t('html_input'), oninput:'htmlLive()'}))
    + EP.formItem(_t('html_unescaped'), EP.textarea('htmlDecOut',{rows:3, readonly:true}))
    + EP.row(EP.button(_t('b64_copy_dec'),{onclick:"EP.copy(g('htmlDecOut').value)"}),{center:true});
  return '<div class="tool-main"><div class="ep-grid2">'+EP.card(card1,_t('html_encode_hdr'))+EP.card(card2,_t('html_decode_hdr'))+'</div></div>';
};

__p.urlparse = function() {
  var fields = [
    {k:'protocol',t:_t('urlparse_protocol')},
    {k:'username',t:_t('urlparse_username')},
    {k:'password',t:_t('urlparse_password')},
    {k:'hostname',t:_t('urlparse_hostname')},
    {k:'port',t:_t('urlparse_port')},
    {k:'pathname',t:_t('urlparse_path')},
    {k:'search',t:_t('urlparse_params')},
    {k:'hash',t:_t('urlparse_hash')}
  ];
  var rows = '';
  fields.forEach(function(f){
    rows += '<div class="ep-form-item" style="display:flex;align-items:center;gap:10px;margin-bottom:8px">'
      + '<label style="width:90px;flex-shrink:0;margin:0">'+f.t+'</label>'
      + EP.inputPrepend('up_'+f.k, '', {readonly:true, class:'ep-mono'})
      + EP.button(_t('copy'), {type:'link', small:true, onclick:"EP.copy(document.getElementById('up_"+f.k+"').querySelector('input').value)"})
      + '</div>';
  });
  var inner = EP.formItem(_t('urlparse_url'), EP.input('upUrl',{value:'https://me:pwd@www.macphpstudy.com:3000/sponsor.html?key1=value&key2=value2#thanks', placeholder:_t('urlparse_url'), oninput:'urlParseRun()'}))
    + '<div id="upFields">'+rows+'</div>'
    + '<div id="upQuery"></div>';
  return '<div class="tool-main">'+EP.card(inner)+'</div>';
};

__p.regex = function() {
  var flagDefs = [['rx_g', 'regex_global', true], ['rx_i', 'regex_ignorecase', false], ['rx_m', 'regex_multiline', false], ['rx_s', 'regex_dotall', true], ['rx_u', 'regex_unicode', true], ['rx_v', 'regex_unicodesets', false]];
  var flagChecks = flagDefs.map(function (f) {
    return EP.check(f[0], _t(f[1]), f[2]);
  }).join(' ');
  var card1 = EP.formItem(_t('regex_test'), EP.textarea('rxP', { rows: 3, placeholder: 'Put the regex to test', value: '[a-z]+', oninput: 'rxCompute()' }))
    + '<div class="ep-flags" style="display:flex;flex-wrap:wrap;gap:4px 14px;margin:6px 0">' + flagChecks + '</div>'
    + EP.formItem(_t('regex_text'), EP.textarea('rxT', { rows: 5, placeholder: 'Put the text to match', value: 'Hello World', oninput: 'rxCompute()' }));
  var card2 = '<div id="rxMatches"></div>';
  var card3 = EP.card(EP.textarea('rxSample', { rows: 4, readonly: true, class: 'ep-mono', value: _t('regex_sample_na') }), _t('regex_sample'));
  var card4 = EP.card('<div class="ep-text-sm" style="opacity:.7">' + _t('regex_diagram_na') + '</div>', _t('regex_diagram'));
  return '<div class="tool-main">'
    + EP.card(card1, _t('regex_test'))
    + EP.card(card2, _t('regex_matches'))
    + card3 + card4
    + '</div>';
};

__p.chmod = function() {
  var scopes = [['read', _t('chmod_read')], ['write', _t('chmod_write')], ['execute', _t('chmod_execute')]];
  var owners = [['owner', _t('owner'), 'u'], ['group', _t('group'), 'g'], ['public', _t('others'), 'o']];
  var head = '<th></th>';
  owners.forEach(function (o) { head += '<th>' + o[1] + ' (' + o[2] + ')</th>'; });
  var rows = scopes.map(function (s) {
    var cells = ['<td>' + s[1] + '</td>'];
    owners.forEach(function (o) {
      var id = 'c_' + o[0].charAt(0) + s[0].charAt(0);
      cells.push('<td style="text-align:center"><input type="checkbox" id="' + id + '" onchange="fCM()"></td>');
    });
    return cells;
  });
  var table = '<table class="ep-table"><thead><tr>' + head + '</tr></thead><tbody>'
    + rows.map(function (r) { return '<tr>' + r.join('') + '</tr>'; }).join('') + '</tbody></table>';
  var result = '<div class="octal-result" id="cOct">000</div>'
    + '<div class="octal-result" id="cSym">---------</div>';
  var cmd = '<div class="ep-input-group"><input id="cCmd" class="ep-input" readonly value="chmod 000 path">'
    + '<span class="ep-input-group__append"><button class="ep-btn ep-btn--link" onclick="EP.copy(g(\'cCmd\').value)">' + _t('chmod_copy') + '</button></span></div>';
  var octInput = EP.input('cN', { placeholder: '644', oninput: 'fCN()', class: 'chmod-oct' });
  var presets = [[755, '7,5,5'], [644, '6,4,4'], [777, '7,7,7'], [700, '7,0,0']];
  var preHtml = '<div class="ep-btn-group">';
  presets.forEach(function (p) { preHtml += EP.button(String(p[0]), { onclick: 'sCM(' + p[1] + ')' }); });
  preHtml += '</div>';
  var inner = table + result
    + EP.formItem(_t('chmod_cmd'), cmd)
    + EP.formItem(_t('chmod_octal'), octInput)
    + preHtml;
  return '<div class="tool-main">' + EP.card(inner, _t('chmod_title')) + '</div>';
};

__p.token = function() {
  var sRow1 = EP.formItem(_t('token_uppercase'), EP.switch('tkUpper','tokenRun()',true))
    + EP.formItem(_t('token_lowercase'), EP.switch('tkLower','tokenRun()',true));
  var sRow2 = EP.formItem(_t('token_numbers'), EP.switch('tkNum','tokenRun()',true))
    + EP.formItem(_t('token_symbols'), EP.switch('tkSym','tokenRun()',false));
  var lenItem = '<div class="ep-form-item"><label>'+_t('token_length')+' (<span id="tkLenLabel">64</span>)</label>'
    + '<input id="tkLen" type="range" min="1" max="512" value="64" class="ep-slider" oninput="tokenRun()"></div>';
  var inner = EP.row(sRow1) + EP.row(sRow2) + lenItem
    + EP.formItem(_t('token_ph'), EP.textarea('tkOut',{rows:3, readonly:true}))
    + EP.row(EP.button(_t('token_copy'),{onclick:"EP.copy(g('tkOut').value)"}) + EP.button(_t('token_refresh'),{onclick:'tokenRun()'}),{end:true});
  return '<div class="tool-main">'+EP.card(inner)+'</div>';
};

__p.http = function() {
  var inner = EP.formItem(_t('http_search'), EP.input('hQ', { placeholder: _t('http_search'), oninput: 'fHQ()' }))
    + '<div id="hO2"></div>';
  return '<div class="tool-main">' + EP.card(inner, _t('http_title')) + '</div>';
};

__p.mime = function() {
  var mimeOpts = MIME_LIST.map(function (m) { return { value: m.mime, label: m.mime }; });
  var extOpts = MIME_LIST.map(function (m) { return { value: m.ext, label: '.' + m.ext }; });
  var card1 = EP.formItem(_t('mime_m2e'), EP.select('mMime', mimeOpts, { onchange: 'fMI()' }))
    + '<div id="mExts" style="margin-top:4px"></div>';
  var card2 = EP.formItem(_t('mime_e2m'), EP.select('mExt', extOpts, { onchange: 'fME()' }))
    + '<div id="mMimeOut" style="margin-top:4px"></div>';
  var tableRows = MIME_LIST.map(function (m) { return [m.mime, '.' + m.ext]; });
  var card3 = EP.table([_t('mime_table'), _t('mime_exts')], tableRows);
  return '<div class="tool-main"><div class="ep-grid2">'
    + EP.card(card1, _t('mime_m2e'))
    + EP.card(card2, _t('mime_e2m'))
    + '</div>' + EP.card(card3, _t('mime_table')) + '</div>';
};

__p.bom = function() {
  var headerBtn = EP.button(_t("bom_cleanup"), { id: "bomCleanBtn", type: "primary", onclick: "bomClean()" });
  var BOM_DEF = ".idea\n.git\n.svn\n.vscode\nnode_modules";
  var inner =
    '<div class="bom-wrap">'
    + '<div class="flex gap-2" style="margin-bottom:12px">'
    + EP.input("bomPath", { placeholder: _t("bom_path_ph"), class: "bom-path-input", oninput: "bomPathInput()" })
    + '<button class="ep-btn ep-btn--icon" title="' + _t("bom_path_ph") + '" onclick="bomChoose()">'
    + '<svg viewBox="0 0 24 24" width="16" height="16"><path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" fill="none" stroke="currentColor" stroke-width="2"/></svg>'
    + '</button>'
    + '<input type="file" id="bomDirInput" webkitdirectory style="display:none" onchange="bomOnDir(event)">'
    + '</div>'
    + EP.textarea("bomExclude", { rows: 6, class: "mono", value: BOM_DEF, placeholder: _t("bom_excludes_ph"), oninput: "bomExcludeInput()" })
    + '<div class="ep-card bom-filetype" style="margin-top:12px">'
    + '<div class="ep-card__header">' + _t("bom_filetype") + '</div>'
    + '<div id="bomExtList" class="bom-ext-list"></div>'
    + '</div>'
    + '<div id="bomProgress" class="bom-progress" style="display:none">'
    + '<div class="bom-progress-bar"><div class="bom-progress-fill" id="bomProgressFill"></div></div>'
    + '<div class="bom-progress-text" id="bomProgressText">0 / 0</div>'
    + '</div>'
    + '<div id="bomResult" class="ep-card" style="display:none;margin-top:12px">'
    + '<div class="ep-card__header">' + _t("bom_result") + '</div>'
    + '<div id="bomResultBody"></div>'
    + '</div>'
    + '</div>';
  return EP.card(inner, _t("bom_title"), headerBtn);
};

__p.qr = function() {
  var eccOpts = [
    { value: "low", label: _t("qr_low") },
    { value: "medium", label: _t("qr_medium"), selected: true },
    { value: "quartile", label: _t("qr_quartile") },
    { value: "high", label: _t("qr_high") },
  ];
  var inner = EP.formItem(_t("qr_data"), EP.input("qI", { value: "https://flyenv.com", oninput: "qrLive()" }))
    + '<div class="ep-form-row2">'
    + EP.formItem(_t("qr_fg"), '<input id="qFg" type="color" value="#000000" class="ep-color" oninput="qrLive()">')
    + EP.formItem(_t("qr_bg"), '<input id="qBg" type="color" value="#ffffff" class="ep-color" oninput="qrLive()">')
    + '</div>'
    + EP.formItem(_t("qr_ecc"), EP.select("qE", eccOpts, { onchange: "qrLive()" }))
    + '<div class="flex flex-col items-center gap-3" style="margin-top:12px">'
    + '<div id="qrOut" class="qr-box"></div>'
    + EP.button(_t("qr_download"), { onclick: "qrDownload()" })
    + '</div>';
  return EP.card(inner, _t("qr_title"));
};

__p.wifi = function() {
  var encOpts = [["WPA", "WPA/WPA2"], ["WEP", "WEP"], ["nopass", _t("wifi_nopass")], ["WPA2-EAP", "WPA2-EAP"]];
  var eapOpts = [["MD5","MD5"],["POTP","POTP"],["GTC","GTC"],["TLS","TLS"],["IKEv2","IKEv2"],["SIM","SIM"],["AKA","AKA"],["AKA'","AKA'"],["TTLS","TTLS"],["PWD","PWD"],["LEAP","LEAP"],["PSK","PSK"],["FAST","FAST"],["TEAP","TEAP"],["EKE","EKE"],["NOOB","NOOB"],["PEAP","PEAP"]];
  var phase2Opts = [["None","None"],["MSCHAPV2","MSCHAPV2"]];
  var inner = EP.formItem(_t("wifi_enc"), EP.select("wEnc", encOpts, { onchange: "wifiToggle()" }))
    + EP.formItem(_t("wifi_ssid"), EP.input("wSSID", { value: "MyWiFi", oninput: "wifiGen()" }))
    + EP.formItem(_t("wifi_hidden"), EP.switch("wHidden", "wifiGen()"))
    + '<div id="wifiPwdBox">' + EP.formItem(_t("wifi_pass"), EP.input("wPass", { value: "password123", type: "password", oninput: "wifiGen()" })) + '</div>'
    + '<div id="wifiEapBox" style="display:none">'
    + EP.formItem(_t("wifi_eap_method"), EP.select("wEap", eapOpts, { onchange: "wifiGen()" }))
    + EP.formItem(_t("wifi_identity"), EP.input("wEapId", { oninput: "wifiGen()" }))
    + EP.formItem(_t("wifi_anon"), EP.switch("wEapAnon", "wifiGen()"))
    + EP.formItem(_t("wifi_phase2"), EP.select("wEapP2", phase2Opts, { onchange: "wifiGen()" }))
    + '</div>'
    + '<div class="ep-form-row2">'
    + EP.formItem(_t("qr_fg"), '<input id="wFg" type="color" value="#000000" class="ep-color" oninput="wifiGen()">')
    + EP.formItem(_t("qr_bg"), '<input id="wBg" type="color" value="#ffffff" class="ep-color" oninput="wifiGen()">')
    + '</div>'
    + '<div class="flex flex-col items-center gap-3" style="margin-top:12px">'
    + '<div id="wO2" class="qr-box"></div>'
    + EP.button(_t("qr_download"), { onclick: "wifiDownload()" })
    + '</div>';
  return EP.card(inner, _t("wifi_title"));
};

__p.img = function() {
  var qOpts = [["90", "90"], ["70", "70"], ["50", "50"], ["30", "30"]];
  var wOpts = [["3840", "3840"], ["1920", "1920"], ["1280", "1280"], ["800", "800"]];
  var fmtOpts = [["jpeg", "JPEG"], ["png", "PNG"], ["webp", "WebP"]];
  var posOpts = [["center", "Center"], ["top-left", "Top-Left"], ["top-right", "Top-Right"], ["bottom-left", "Bottom-Left"], ["bottom-right", "Bottom-Right"]];
  var texOpts = [["none", "None"], ["paper", "Paper"], ["canvas", "Canvas"], ["wood", "Wood"], ["marble", "Marble"]];
  var tab = function (name, label) {
    return '<div class="img-tab' + (name === 'batch' ? ' active' : '') + '" data-tab="' + name + '" onclick="imgSwitch(\'' + name + '\')">' + label + '</div>';
  };
  var batch = '<div class="img-pane" id="imgPaneBatch">'
    + '<div class="flex gap-2 mb-3"><button class="btn" onclick="imgAddClick()">' + _t('img_add') + '</button>'
    + EP.button(_t('img_compress_all'), { onclick: 'imgCompressFiles()' }) + '</div>'
    + '<input type="file" id="imgFile" multiple accept="image/*" style="display:none" onchange="imgOnFiles(event)">'
    + '<div id="imgList" class="img-list"></div>'
    + '</div>';
  var basic = '<div class="img-pane" id="imgPaneBasic" style="display:none">'
    + EP.formItem(_t('img_path'), EP.input('iI', { placeholder: _t('img_path'), oninput: 'imgCfg()' }))
    + EP.formItem(_t('img_quality'), EP.select('iQ', qOpts, { onchange: 'imgCfg()' }))
    + EP.formItem(_t('img_maxw'), EP.select('iW', wOpts, { onchange: 'imgCfg()' }))
    + EP.button(_t('img_compress_all'), { onclick: 'imgBasicCompress()' })
    + '<div class="ot" id="imgO" style="margin-top:8px;min-height:40px"></div>'
    + '</div>';
  var compress = '<div class="img-pane" id="imgPaneCompress" style="display:none">'
    + EP.formItem(_t('img_quality'), EP.select('iQ2', qOpts, { onchange: 'imgCfg()' }))
    + EP.formItem(_t('img_maxw'), EP.select('iW2', wOpts, { onchange: 'imgCfg()' }))
    + EP.formItem(_t('img_format'), EP.select('iFmt', fmtOpts, { onchange: 'imgCfg()' }))
    + '</div>';
  var effects = '<div class="img-pane" id="imgPaneEffects" style="display:none">'
    + EP.formItem(_t('img_brightness'), EP.input('iBright', { value: '0', oninput: 'imgCfg()' }))
    + EP.formItem(_t('img_contrast'), EP.input('iContrast', { value: '0', oninput: 'imgCfg()' }))
    + EP.formItem(_t('img_blur'), EP.input('iBlur', { value: '0', oninput: 'imgCfg()' }))
    + '</div>';
  var watermark = '<div class="img-pane" id="imgPaneWatermark" style="display:none">'
    + EP.formItem(_t('img_wm_text'), EP.input('iWmText', { oninput: 'imgCfg()' }))
    + EP.formItem(_t('img_wm_pos'), EP.select('iWmPos', posOpts, { onchange: 'imgCfg()' }))
    + EP.formItem(_t('img_wm_opacity'), EP.input('iWmOp', { value: '50', oninput: 'imgCfg()' }))
    + '</div>';
  var texture = '<div class="img-pane" id="imgPaneTexture" style="display:none">'
    + EP.formItem(_t('img_tex'), EP.select('iTex', texOpts, { onchange: 'imgCfg()' }))
    + '</div>';
  var inner = '<div class="img-tabs" id="imgTabs">'
    + tab('batch', _t('img_batch')) + tab('basic', _t('img_basic')) + tab('compress', _t('img_compress'))
    + tab('effects', _t('img_effects')) + tab('watermark', _t('img_watermark')) + tab('texture', _t('img_texture'))
    + '</div>'
    + batch + basic + compress + effects + watermark + texture;
  return EP.card(inner, _t('img_title'));
};

__p.capture = function() {
  var ruleBtn = function (rule, label) {
    return '<button class="btn btn--sm" onclick="capAddRule(\'' + rule + '\')">+ ' + label + '</button>';
  };
  var inner = '<div class="ep-form">'
    + '<div class="ep-form-item" style="margin-bottom:16px">'
    + '<label style="margin-bottom:6px;display:block">' + _t('cap_shortcut') + '</label>'
    + '<div class="cap-key-card" id="capKeyCard" onmouseenter="capKeyEnter()" onmouseleave="capKeyLeave()">'
    + '<div class="cap-key" id="capKey">' + _t('cap_none') + '</div>'
    + '<div class="cap-key-tip">' + _t('cap_shortcut_tip') + '</div>'
    + '</div>'
    + '<span class="cap-key-clear" onclick="capClearKey()">' + _t('cap_clear') + '</span>'
    + '</div>'
    + '<div class="ep-form-item" style="margin-bottom:16px">'
    + '<label style="margin-bottom:6px;display:block">' + _t('cap_dir') + '</label>'
    + '<div class="ep-input-group">'
    + EP.input('capDir', { value: CAP_CFG.dir, placeholder: _t('cap_dir_ph'), oninput: 'capCfg()' })
    + '<button class="ep-input-group__append" onclick="capChooseDir()">' + _t('cap_browse') + '</button>'
    + '</div>'
    + '<input type="file" id="capDirInput" webkitdirectory style="display:none" onchange="capOnDir(event)">'
    + '</div>'
    + '<div class="ep-form-item" style="margin-bottom:16px">'
    + '<label style="margin-bottom:6px;display:block">' + _t('cap_name') + '</label>'
    + '<div class="flex flex-wrap gap-2 mb-2">'
    + ruleBtn('{index}', _t('cap_rule_index')) + ruleBtn('{timestamp}', _t('cap_rule_timestamp')) + ruleBtn('{datetime}', _t('cap_rule_datetime')) + ruleBtn('{uuid}', _t('cap_rule_uuid'))
    + '</div>'
    + EP.input('capName', { value: CAP_CFG.name, oninput: 'capCfg()' })
    + '</div>'
    + '<div class="flex gap-2">'
    + EP.button(_t('cap_save'), { onclick: 'capSave()', class: 'mb-2' })
    + EP.button(_t('cap_do'), { onclick: 'capDo(false)', class: 'mb-2' })
    + EP.button(_t('cap_do_hide'), { onclick: 'capDo(true)', class: 'mb-2' })
    + '</div>'
    + '<div class="ot" id="capO" style="margin-top:8px;min-height:40px"></div>';
  return EP.card(inner, _t('cap_title'));
};

__p.rsa = function() {
  var slider = '<div class="flex items-center gap-3 mb-4" style="justify-content:center">'
    + '<span class="flex-shrink-0">' + _t("rsa_bits") + ':</span>'
    + '<input id="rB" type="range" min="512" max="16384" step="8" value="2048" class="ep-slider" style="flex:1;max-width:420px" oninput="rsaGen()">'
    + '<span id="rBVal" style="min-width:56px;text-align:right;font-variant-numeric:tabular-nums">2048</span>'
    + EP.button(_t("rsa_refresh"), { onclick: "rsaGen()" })
    + '</div>';
  var keys = '<div class="ep-grid2">'
    + '<div><div class="ep-pane-hd">' + _t("rsa_public") + ' <span class="copy-mini" onclick="EP.copy(g(\'rPub\').value)">' + _t("copy") + '</span></div>'
    + EP.textarea("rPub", { rows: 16, class: "mono", readonly: true }) + '</div>'
    + '<div><div class="ep-pane-hd">' + _t("rsa_private") + ' <span class="copy-mini" onclick="EP.copy(g(\'rPriv\').value)">' + _t("copy") + '</span></div>'
    + EP.textarea("rPriv", { rows: 16, class: "mono", readonly: true }) + '</div>'
    + '</div>';
  return EP.card(slider + keys, _t("rsa_title"));
};

__p.file = function() {
  var inner =
    '<div id="fiDroper" class="fi-droper">'
    +   '<div id="fiSelect" class="fi-select" onclick="fiPick()">'
    +     '<span class="fi-icon"><svg viewBox="0 0 24 24" width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 16V4"/><path d="M8 8l4-4 4 4"/><path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg></span>'
    +     '<span class="fi-tip">' + _t('file_drop') + '</span>'
    +   '</div>'
    + '</div>'
    + '<input type="file" id="fiInput" style="display:none" onchange="fiOnFile(event)">'
    + '<div id="fO" class="fi-out"></div>';
  return EP.card(inner, _t("file_title"));
};

__p.timing = function() {
  var inner = '<div class="flex gap-2 mb-2"><div style="flex:1">' + EP.input("tI2", { value: "https://example.com" }) + '</div>'
    + EP.button(_t("test"), { onclick: "reqTime()" }) + '</div>'
    + '<div id="tO2" style="margin-top:8px"></div>';
  return EP.card(inner, _t("timing_title"));
};

__p.suck = function() {
  var playSvg = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>';
  var stopSvg = '<svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor"><rect x="6" y="6" width="12" height="12" rx="2"/></svg>';
  var gearSvg = '<svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>';

  var setBody =
    EP.formItem(_t('ss_save_path'), '<div class="ss-path"><input id="ssDir" class="ep-input" placeholder="'+_t('ss_save_path_ph')+'"><span class="ss-folder" onclick="ssPickDir()" title="'+_t('ss_save_path')+'">'+folderSvg()+'</span></div>')
    + EP.formItem(_t('ss_window_count'), EP.input('ssWin', { type: 'number', value: 2, min: 1, max: 8 }))
    + EP.formItem(_t('ss_proxy'), EP.input('ssProxy', { placeholder: 'http://127.0.0.1:1087' }))
    + EP.formItem(_t('ss_timeout'), EP.input('ssTimeout', { type: 'number', value: 10 }))
    + EP.formItem(_t('ss_max_img'), '<div class="ss-num-m">'+EP.input('ssMaxImg', { type: 'number', value: 0 })+'<span class="ss-m">M</span></div>')
    + EP.formItem(_t('ss_max_video'), '<div class="ss-num-m">'+EP.input('ssMaxVideo', { type: 'number', value: 0 })+'<span class="ss-m">M</span></div>')
    + EP.formItem(_t('ss_page_limit'), EP.input('ssPageLimit', { placeholder: _t('ss_page_limit_ph') }))
    + EP.formItem(_t('ss_link_exclusion'), EP.textarea('ssExclude', { rows: 4, placeholder: _t('ss_link_exclusion_ph') }));

  var inner =
    '<div class="ss-tool">'
    + '<div class="ss-nav"><div class="left"><span class="text-xl">'+_t('suck_title')+'</span></div>'
    + '<div class="ss-gear" title="'+_t('ss_settings')+'" onclick="ssOpenSet()">'+gearSvg+'</div></div>'
    + '<div class="ss-main">'
    +   '<div class="ss-top">'
    +     EP.input('ssUrl', { placeholder: 'URL', value: 'https://example.com' })
    +     '<button id="ssRunBtn" class="ep-btn ep-btn--primary ep-btn--icon" onclick="ssToggle()">'+playSvg+'</button>'
    +     '<button id="ssOpenBtn" class="ep-btn ep-btn--icon" style="display:none" onclick="ssOpenDir()" title="'+_t('ss_open_dir')+'">'+folderSvg()+'</button>'
    +   '</div>'
    +   '<div class="ss-stat" id="ssStat"></div>'
    +   '<div class="ss-tables">'
    +     '<div class="ss-block"><div class="ss-block-hd"><span id="ssLinksHd">url</span>'
    +       EP.input('ssSearch', { class: 'ep-input--sm', placeholder: _t('base.placeholderSearch'), oninput: 'ssRenderLinks()' })
    +     '</div><div class="ss-block-body" id="ssLinks"></div></div>'
    +     '<div class="ss-block"><div class="ss-block-hd"><span>host</span>'
    +       EP.input('ssHostSearch', { class: 'ep-input--sm', placeholder: _t('base.placeholderSearch'), oninput: 'ssRenderHosts()' })
    +     '</div><div class="ss-block-body" id="ssHosts"></div></div>'
    +   '</div>'
    + '</div>'
    + '<div class="obf-modal" id="ssSetModal" style="display:none">'
    +   '<div class="obf-modal__mask" onclick="ssCloseSet()"></div>'
    +   '<div class="obf-modal__panel">'
    +     '<div class="obf-modal__nav"><div class="left" onclick="ssCloseSet()"><span class="obf-back">&#8249;</span> <span>'+_t('ss_settings')+'</span></div>'
    +       '<button class="ep-btn ep-btn--primary" onclick="ssSaveSet()">'+_t('base.confirm')+'</button></div>'
    +     '<div class="obf-modal__body ss-set-body">'+setBody+'</div>'
    +   '</div>'
    + '</div>'
    + '</div>';
  return inner;
};

function folderSvg() {
  return '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>';
}

__p.ssl = function() {
  var folderIco = '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>';
  // header row: title on left, generate button on right
  var header = '<div class="flex justify-between items-center mb-3">'
    + '<span class="text-lg font-medium">' + _t("ssl_title") + '</span>'
    + EP.button(_t("ssl_gen_btn"), { onclick: "sslMake()", type: "primary" })
    + '</div>';
  var inner = header
    + EP.formItem('', EP.textarea("sslDomains", { rows: 5, placeholder: _t("ssl_domains_ph") }))
    + EP.formItem(_t("ssl_ca_path"),
        '<div class="ep-input-group">'
        + EP.input("sslCaPath", { placeholder: _t("ssl_ca_path_ph"), readonly: true })
        + '<span class="ep-input-group__append" onclick="g(\'sslCaFile\').click()">' + folderIco + '</span>'
        + '<input type="file" id="sslCaFile" style="display:none" onchange="sslOnCaFile(event)">'
        + '</div>')
    + EP.formItem(_t("ssl_save_path"),
        '<div class="ep-input-group">'
        + EP.input("sslSavePath", { placeholder: _t("ssl_save_path_ph"), readonly: true })
        + '<span class="ep-input-group__append" onclick="g(\'sslSaveFile\').click()">' + folderIco + '</span>'
        + '<input type="file" id="sslSaveFile" webkitdirectory style="display:none" onchange="sslOnSaveDir(event)">'
        + '</div>');
  return EP.card(inner);
};

__p.obf = function() {
  var genBtn = EP.button(_t("obf_generate"), {
    id: "obGenBtn", type: "primary", small: true, onclick: "obRun()"
  });
  var inner =
    EP.formItem(_t("obf_php_version"), EP.select("obPhp", [{ value: "", label: _t("obf_php_version") }], { onchange: "obPhpChange()" }))
    + EP.formItem(_t("obf_src"),
        '<div class="obf-path-row">'
        + EP.input("obSrc", { placeholder: _t("obf_src"), readonly: true })
        + EP.button(_t("obf_pick_file"), { onclick: "obPickSrcFile()", small: true, class: "obf-pick" })
        + EP.button(_t("obf_pick_dir"), { onclick: "obPickSrcDir()", small: true, class: "obf-pick" })
        + "</div>")
    + EP.formItem(_t("obf_desc"),
        '<div class="obf-path-row">'
        + EP.input("obDesc", { placeholder: _t("obf_desc"), oninput: "obDescInput()" })
        + EP.button(_t("obf_pick_file"), { onclick: "obPickDescFile()", small: true, class: "obf-pick", id: "obDescFileBtn" })
        + EP.button(_t("obf_pick_dir"), { onclick: "obPickDescDir()", small: true, class: "obf-pick", id: "obDescDirBtn" })
        + "</div>")
    + '<div class="obf-config-row">'
        + EP.button(_t("obf_config"), { onclick: "obConfigOpen()", small: true })
        + "</div>"
    + '<div id="obResult" class="obf-result"></div>'
    // hidden pickers
    + '<input type="file" id="obSrcFileInput" accept=".php" style="display:none" onchange="obSrcFileOn(event)">'
    + '<input type="file" id="obSrcDirInput" webkitdirectory style="display:none" onchange="obSrcDirOn(event)">'
    + '<input type="file" id="obDescFileInput" accept=".php" style="display:none" onchange="obDescFileOn(event)">'
    + '<input type="file" id="obDescDirInput" webkitdirectory style="display:none" onchange="obDescDirOn(event)">'
    + '<input type="file" id="obCnfInput" accept=".cnf,.txt,.php" style="display:none" onchange="obCnfImportOn(event)">'
    + obfConfigDrawerHtml();
  return EP.card(inner, _t("obf_title"), genBtn);
};

// Php Obfuscator — config drawer (Monaco substitute: EP.code textarea)
function obfConfigDrawerHtml() {
  return ""
    + '<div id="obCnfModal" class="obf-modal" style="display:none">'
    +   '<div class="obf-modal__mask" onclick="obConfigClose()"></div>'
    +   '<div class="obf-modal__panel">'
    +     '<div class="obf-modal__nav">'
    +       '<div class="left" onclick="obConfigClose()">'
    +         '<span class="obf-back">&#10005;</span>'
    +         '<span class="ml-3">' + _t("obf_config_title") + "</span>"
    +       "</div>"
    +       '<div class="obf-split">'
    +         '<button class="ep-btn ep-btn--primary ep-btn--small" onclick="obConfigConfirm()">' + _t("obf_confirm") + "</button>"
    +         '<button class="ep-btn ep-btn--primary ep-btn--small obf-caret" onclick="obConfigToggleMenu(this)">&#9662;</button>'
    +         '<div class="obf-menu" id="obCnfMenu" style="display:none">'
    +           '<div class="obf-menu__item" onclick="obCnfImport()">' + _t("obf_import") + "</div>"
    +           '<div class="obf-menu__item" onclick="obCnfExport()">' + _t("obf_export") + "</div>"
    +         "</div>"
    +       "</div>"
    +     "</div>"
    +     '<div class="obf-modal__body env-editor-wrap">'
    +       '<div id="obCnf" class="env-cm"></div>'
    +       '<canvas id="obMini" class="env-mini"></canvas>'
    +     "</div>"
    +   "</div>"
    + "</div>";
}

// System Environment — list of existing shell env config files + editor drawer
// (1:1 with FlyEnv SystenEnv macOS: list files → edit → save back)
__p.env = function() {
  var inner =
    '<div id="envList" class="env-list"></div>'
    // editor drawer (reuses .obf-modal generic 75% right drawer styles)
    + '<div id="envEditorModal" class="obf-modal" style="display:none">'
    +   '<div class="obf-modal__mask" onclick="envEditorClose()"></div>'
    +   '<div class="obf-modal__panel">'
    +     '<div class="obf-modal__nav">'
    +       '<div class="left" onclick="envEditorClose()">'
    +         '<span class="obf-back">&#10005;</span>'
    +         '<span class="ml-3" id="envEditorTitle"></span>'
    +       "</div>"
    +       '<button class="ep-btn ep-btn--primary ep-btn--small" id="envSaveBtn" onclick="envSave()">' + _t("env_save") + "</button>"
    +     "</div>"
    +     '<div class="obf-modal__body env-editor-wrap">'
    +       '<div id="envEditor" class="env-cm"></div>'
    +       '<canvas id="envMini" class="env-mini"></canvas>'
    +     "</div>"
    +   "</div>"
    + "</div>";
  return EP.card(inner, _t("env_title"));
};

__p.portkill = function() {
  var inner = EP.formItem(_t("pk_port"), EP.input("pkInput", { placeholder: _t("pk_ph") }))
    + '<div class="flex gap-2 mb-2">'
    + EP.button(_t("lookup"), { onclick: "pkSearch()" })
    + EP.button(_t("pk_kill_sel"), { onclick: "pkKillSel()" })
    + EP.button(_t("pk_kill_all"), { onclick: "pkKillAll()", class: "btn-danger" })
    + '</div>'
    + '<div class="ot" id="pkO"></div>';
  return EP.card(inner, _t("portkill_title"));
};

__p.prockill = function() {
  var inner = EP.formItem(_t("proc_name"), EP.input("procInput", { placeholder: _t("proc_ph") }))
    + '<div class="flex gap-2 mb-2">'
    + EP.button(_t("lookup"), { onclick: "procSearch()" })
    + EP.button(_t("pk_kill_sel"), { onclick: "procKillSel()" })
    + EP.button(_t("pk_kill_all"), { onclick: "procKillAll()", class: "btn-danger" })
    + '</div>'
    + '<div class="ot" id="procO"></div>';
  return EP.card(inner, _t("prockill_title"));
};

// ── Keycode Info ──
__p.wss = function() {
  var urlInput = '<input id="wUrl" class="ep-input" value="wss://echo.websocket.events" placeholder="ws://localhost:3000/ws" onkeyup="if(event.key===\'Enter\')wssConnect()">';
  var proto = EP.formItem(_t('wss_protocol'),
    '<div class="ep-radio-group">'
    + '<label class="ep-radio"><input type="radio" name="wProto" value="websocket" checked onchange="wssProtoChange()"> WebSocket</label>'
    + '<label class="ep-radio"><input type="radio" name="wProto" value="sse" onchange="wssProtoChange()"> SSE</label>'
    + '</div>');
  var urlRow = '<div class="flex gap-2 items-end" style="margin:12px 0">' + urlInput
    + EP.button(_t('wss_connect'), { onclick: 'wssConnect()', id: 'wConnBtn' })
    + EP.button(_t('wss_disconnect'), { onclick: 'wssDisconnect()', id: 'wDiscBtn' })
    + '<span id="wStatus" class="ep-tag ep-tag--info">' + _t('wss_disconnected') + '</span>'
    + '</div>';
  var alerts = '<div id="wWsAlert" class="ep-alert ep-alert--info" style="margin-top:8px">' + _t('wss_ws_hdr_note') + '</div>'
    + '<div id="wErr" class="ep-alert ep-alert--danger ep-hidden"></div>';
  var connCard = EP.card(proto + urlRow + alerts, _t('wss_title'));

  var tabbar = '<div class="wss-tabbar">'
    + '<button type="button" class="wss-tab active" onclick="wssSwitchTab(this,\'wPaneParams\')">' + _t('wss_params') + '</button>'
    + '<button type="button" class="wss-tab" onclick="wssSwitchTab(this,\'wPaneHeaders\')">' + _t('wss_headers') + '</button>'
    + '<button type="button" class="wss-tab" onclick="wssSwitchTab(this,\'wPaneAuth\')">' + _t('wss_auth') + '</button>'
    + '<button type="button" class="wss-tab" id="wTabWsOpts" onclick="wssSwitchTab(this,\'wPaneWsOpts\')">' + _t('wss_ws_opts') + '</button>'
    + '<button type="button" class="wss-tab" id="wTabSseOpts" style="display:none" onclick="wssSwitchTab(this,\'wPaneSseOpts\')">' + _t('wss_sse_opts') + '</button>'
    + '</div>';
  var paramsPane = '<div class="wss-pane" id="wPaneParams"><div id="wParamRows"></div>'
    + EP.button(_t('wss_add_param'), { onclick: 'wssAddParam()' }) + '</div>';
  var headersPane = '<div class="wss-pane" id="wPaneHeaders" hidden>'
    + '<div class="ep-alert ep-alert--warning" style="margin-bottom:8px">' + _t('wss_hdr_note') + '</div>'
    + '<div id="wHeaderRows"></div>' + EP.button(_t('wss_add_header'), { onclick: 'wssAddHeader()' }) + '</div>';
  var authPane = '<div class="wss-pane" id="wPaneAuth" hidden>'
    + EP.formItem(_t('wss_bearer'), EP.textarea('wBearer', { rows: 4 }))
    + EP.button(_t('wss_apply_auth'), { onclick: 'wssApplyAuth()' }) + '</div>';
  var wsOptsPane = '<div class="wss-pane" id="wPaneWsOpts" hidden>'
    + EP.formItem(_t('wss_subprotocols'), EP.input('wSub', { placeholder: 'graphql-transport-ws, chat' }))
    + EP.formItem(_t('wss_heartbeat'), '<input type="checkbox" id="wHb" onchange="wssHbToggle()">')
    + '<div id="wHbOpts" style="display:none">'
    + EP.formItem(_t('wss_interval'), EP.input('wHbInt', { value: '30' }))
    + EP.formItem(_t('wss_hb_msg'), EP.textarea('wHbMsg', { rows: 4, value: 'ping' }))
    + '</div></div>';
  var sseOptsPane = '<div class="wss-pane" id="wPaneSseOpts" hidden>'
    + EP.formItem(_t('wss_event_filter'), EP.input('wSseFilter', { placeholder: 'message' }))
    + EP.formItem(_t('wss_last_event_id'), EP.input('wSseLastId')) + '</div>';
  var cfgCard = EP.card(tabbar + paramsPane + headersPane + authPane + wsOptsPane + sseOptsPane, _t('wss_req_config'));

  var sendCard = '<div id="wSendCard">' + EP.card(
      EP.formItem(_t('wss_msg_type'),
        '<div class="ep-radio-group">'
        + '<label class="ep-radio"><input type="radio" name="wMsgMode" value="json" checked> JSON</label>'
        + '<label class="ep-radio"><input type="radio" name="wMsgMode" value="text"> Text</label>'
        + '</div>')
      + EP.formItem(_t('wss_send_msg'), EP.textarea('wMsg', { rows: 10 }))
      + '<div class="flex gap-2">' + EP.button(_t('wss_send'), { onclick: 'wssSend()' }) + EP.button(_t('wss_format'), { onclick: 'wssFormat()' }) + '</div>'
    , _t('wss_send_msg')) + '</div>';
  var sseInfoCard = '<div id="wSseInfoCard" style="display:none">' + EP.card(
      '<div class="ep-alert ep-alert--info">' + _t('wss_sse_info') + '</div>'
      + '<div class="muted" style="font-size:13px;margin-top:8px">' + _t('wss_supported') + '<br>' + _t('wss_duration') + ' <span id="wDur">0s</span></div>'
    , 'SSE Info') + '</div>';
  var rightCol = '<div id="wRightCol">' + sendCard + sseInfoCard + '</div>';

  var logsCard = EP.card(
      '<div class="flex justify-between" style="margin-bottom:8px">'
      + '<span class="muted" style="font-size:13px"><span id="wLogCount">0</span> ' + _t('wss_entries') + ' · ' + _t('wss_duration') + ' <span id="wDur2">0s</span></span>'
      + EP.button(_t('wss_clear'), { onclick: 'wssClearLogs()', type: 'link' })
      + '</div>'
      + '<div id="wO" class="wss-logs"></div>'
    , _t('wss_logs'));

  return '<div class="tool-main">' + connCard
    + '<div class="ep-grid2" style="margin-top:12px">' + cfgCard + rightCol + '</div>'
    + '<div style="margin-top:12px">' + logsCard + '</div></div>';
};

__p.keycode = function() {
  var fld = function (label, id, ph) {
    return '<div class="ep-form-item" style="margin-bottom:8px">'
      + '<label style="width:74px;flex-shrink:0;margin:0">' + label + '</label>'
      + EP.inputPrepend(id, '', { readonly: true, class: 'ep-mono', placeholder: ph })
      + EP.button(_t('copy'), { type: 'link', small: true, onclick: "EP.copy(document.getElementById('" + id + "').querySelector('input').value)" })
      + '</div>';
  };
  var inner =
    '<div class="ep-card" style="text-align:center;padding:40px 0;margin-bottom:16px">'
    +   '<div id="kcKeyDisplay" style="font-size:42px;font-weight:800;color:var(--dm);margin-bottom:8px">⌨</div>'
    +   '<div id="kcHint" style="font-size:13px;opacity:.7">' + _t('kc_hint') + '</div>'
    + '</div>'
    + fld(_t('kc_key'), 'kcV_key', 'Key name...')
    + fld(_t('kc_keycode'), 'kcV_keyCode', 'Keycode...')
    + fld(_t('kc_code'), 'kcV_code', 'Code...')
    + fld(_t('kc_loc'), 'kcV_loc', 'Location...')
    + fld(_t('kc_mod'), 'kcV_mod', 'None')
    + '<div id="kcInfo" style="display:none"></div>'
    + '<div id="kcHist" style="display:none"></div>'
    + '<div class="flex gap-2" style="margin-top:8px"><button class="btn" onclick="kcClear()">' + _t('kc_clr') + '</button></div>';
  return EP.card(inner);
};

// ── Regex / Git Cheatsheet Content ──
var REGEX_MEMO = '### Normal characters\n\nExpression | Description\n:--|:--\n`.` or `[^\\n\\r]` | any character *excluding* a newline or carriage return\n`[A-Za-z]` | alphabet\n`[a-z]` | lowercase alphabet\n`[A-Z]` | uppercase alphabet\n`\\d` or `[0-9]` | digit\n`\\D` or `[^0-9]` | non-digit\n`_` | underscore\n`\\w` or `[A-Za-z0-9_]` | alphabet, digit or underscore\n`\\W` or `[^A-Za-z0-9_]` | inverse of `\\w`\n`\\S` | inverse of `\\s`\n\n### Whitespace characters\n\nExpression | Description\n:--|:--\n` ` | space\n`\\t` | tab\n`\\n` | newline\n`\\r` | carriage return\n`\\s` | space, tab, newline or carriage return\n\n### Character set\n\nExpression | Description\n:--|:--\n`[xyz]` | either `x`, `y` or `z`\n`[^xyz]` | neither `x`, `y` nor `z`\n`[1-3]` | either `1`, `2` or `3`\n`[^1-3]` | neither `1`, `2` nor `3`\n\n- Think of a character set as an `OR` operation on the single characters that are enclosed between the square brackets.\n- Use `^` after the opening `[` to \u201cnegate\u201d the character set.\n- Within a character set, `.` means a literal period.\n\n### Characters that require escaping\n\n#### Outside a character set\n\nExpression | Description\n:--|:--\n`\\.` | period\n`\\^` | caret\n`\\$` | dollar sign\n`\\|` | pipe\n`\\\\` | back slash\n`\\/` | forward slash\n`\\(` | opening bracket\n`\\)` | closing bracket\n`\\[` | opening square bracket\n`\\]` | closing square bracket\n`\\{` | opening curly bracket\n`\\}` | closing curly bracket\n\n#### Inside a character set\n\nExpression | Description\n:--|:--\n`\\\\` | back slash\n`\\]` | closing square bracket\n\n- A `^` must be escaped only if it occurs immediately after the opening `[` of the character set.\n- A `-` must be escaped only if it occurs between two alphabets or two digits.\n\n### Quantifiers\n\nExpression | Description\n:--|:--\n`{2}` | exactly 2\n`{2,}` | at least 2\n`{2,7}` | at least 2 but no more than 7\n`*` | 0 or more\n`+` | 1 or more\n`?` | exactly 0 or 1\n\n- The quantifier goes *after* the expression to be quantified.\n\n### Boundaries\n\nExpression | Description\n:--|:--\n`^` | start of string\n`$` | end of string\n`\\b` | word boundary\n\n- How word boundary matching works:\n    - At the beginning of the string if the first character is `\\w`.\n    - Between two adjacent characters within the string, if the first character is `\\w` and the second character is `\\W`.\n    - At the end of the string if the last character is `\\w`.\n\n### Matching\n\nExpression | Description\n:--|:--\n`foo\\|bar` | match either `foo` or `bar`\n`foo(?=bar)` | match `foo` if it\u2019s before `bar`\n`foo(?!bar)` | match `foo` if it\u2019s *not* before `bar`\n`(?<=bar)foo` | match `foo` if it\u2019s after `bar`\n`(?<!bar)foo` | match `foo` if it\u2019s *not* after `bar`\n\n### Grouping and capturing\n\nExpression | Description\n:--|:--\n`(foo)` | capturing group; match and capture `foo`\n`(?:foo)` | non-capturing group; match `foo` but *without* capturing `foo`\n`(foo)bar\\1` | `\\1` is a backreference to the 1st capturing group; match `foobarfoo`\n\n- Capturing groups are only relevant in the following methods:\n    - `string.match(regexp)`\n    - `string.matchAll(regexp)`\n    - `string.replace(regexp, callback)`\n- `\\N` is a backreference to the `Nth` capturing group. Capturing groups are numbered starting from 1.\n\n## References and tools\n\n- [MDN](https://developer.mozilla.org/en-US/docs/Web/JavaScript/Guide/Regular_Expressions)\n- [RegExplained](https://leaverou.github.io/regexplained/)\n';


__p.regex_memo = function() {
  return '<div class="tool-main"><div class="md-article vp-doc" id="regMemoOut" style="min-height:100%"></div></div>';
};

__p.git_memo = function() {
  return '<div class="gm-wrap">'
    + '<div class="gm-head"><span class="gm-title">' + _t('git_title') + '</span></div>'
    + '<div class="tool-main gm-body"><article class="md-article vp-doc gm-article" id="gitMemoOut"></article></div>'
    + '</div>';
};
