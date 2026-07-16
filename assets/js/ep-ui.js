/* FlyEnv Toolbox — Element Plus UI helpers (ep-ui.js)
 * Generates Element Plus–style component markup so each tool panel
 * can be authored concisely and stay visually 1:1 with the original
 * FlyEnv (Vue 3 + Element Plus) tool UI.
 */
var EP = {
  /* ── Card ─────────────────────────────────────────────── */
  card: function (inner, header, headerRight) {
    var head =
      header || headerRight
        ? '<div class="ep-card__header' +
          (headerRight ? " ep-card__header--row" : "") +
          '">' +
          (header || "") +
          (headerRight ? '<span class="ep-card__header-right">' + headerRight + "</span>" : "") +
          "</div>"
        : "";
    return '<div class="ep-card">' + head + inner + "</div>";
  },

  /* ── Form item (label on top) ────────────────────────── */
  formItem: function (label, control) {
    return (
      '<div class="ep-form-item"><label>' + label + "</label>" + control + "</div>"
    );
  },

  /* ── Input ────────────────────────────────────────────── */
  input: function (id, opts) {
    opts = opts || {};
    var type = opts.type || "text";
    var ph = opts.placeholder ? ' placeholder="' + EP._a(opts.placeholder) + '"' : "";
    var val = opts.value !== undefined ? ' value="' + EP._a(opts.value) + '"' : "";
    var ro = opts.readonly ? " readonly" : "";
    var cls = opts.class ? " " + opts.class : "";
    var on = opts.oninput ? ' oninput="' + opts.oninput + '"' : "";
    return (
      '<input id="' + id + '" class="ep-input' + cls + '" type="' + type + '"' +
      ph + val + ro + on + ">"
    );
  },

  /* ── Textarea (plain) ─────────────────────────────────── */
  textarea: function (id, opts) {
    opts = opts || {};
    var ph = opts.placeholder ? ' placeholder="' + EP._a(opts.placeholder) + '"' : "";
    var val = opts.value !== undefined ? EP._a(opts.value) : "";
    var ro = opts.readonly ? " readonly" : "";
    var rows = opts.rows ? ' rows="' + opts.rows + '"' : "";
    var cls = opts.class ? " " + opts.class : "";
    var on = opts.oninput ? ' oninput="' + opts.oninput + '"' : "";
    return (
      '<textarea id="' + id + '" class="ep-textarea' + cls + '"' + ph + rows + ro + on + ">" +
      val +
      "</textarea>"
    );
  },

  /* ── Code editor (textarea styled as Monaco substitute) ─ */
  code: function (id, opts) {
    opts = opts || {};
    var ro = opts.readonly ? " readonly" : "";
    var val = opts.value !== undefined ? EP._a(opts.value) : "";
    var cls = opts.class ? " " + opts.class : "";
    var rows = opts.rows ? ' rows="' + opts.rows + '"' : "";
    var on = opts.oninput ? ' oninput="' + opts.oninput + '"' : "";
    return (
      '<textarea id="' + id + '" class="ep-code' + cls + '"' + ro + rows + on + ">" +
      val +
      "</textarea>"
    );
  },

  /* ── Select ───────────────────────────────────────────── */
  select: function (id, options, opts) {
    opts = opts || {};
    var cls = opts.class ? " " + opts.class : "";
    var on = opts.onchange ? ' onchange="' + opts.onchange + '"' : "";
    var html = '<select id="' + id + '" class="ep-select' + cls + '"' + on + ">";
    for (var i = 0; i < options.length; i++) {
      var o = options[i];
      var val, lbl, sel = "";
      if (Array.isArray(o)) { val = o[0]; lbl = o[1]; }
      else { val = o.value; lbl = o.label; sel = o.selected ? " selected" : ""; }
      html +=
        '<option value="' + EP._a(val) + '"' + sel + ">" + EP._a(lbl) + "</option>";
    }
    html += "</select>";
    return html;
  },

  /* ── Button ───────────────────────────────────────────── */
  button: function (label, opts) {
    opts = opts || {};
    var cls =
      "ep-btn" +
      (opts.type ? " ep-btn--" + opts.type : "") +
      (opts.small ? " ep-btn--small" : "") +
      (opts.class ? " " + opts.class : "");
    var id = opts.id ? ' id="' + opts.id + '"' : "";
    var onclick = opts.onclick ? ' onclick="' + opts.onclick + '"' : "";
    var icon = opts.icon ? opts.icon + " " : "";
    return '<button class="' + cls + '"' + id + onclick + ">" + icon + label + "</button>";
  },

  /* ── Button group ─────────────────────────────────────── */
  btnGroup: function (btns) {
    var html = '<div class="ep-btn-group">';
    for (var i = 0; i < btns.length; i++) {
      html += EP.button(btns[i].label, btns[i]);
    }
    return html + "</div>";
  },

  /* ── Row layout ───────────────────────────────────────── */
  row: function (inner, opts) {
    opts = opts || {};
    var cls = "ep-row" + (opts.end ? " ep-row--end" : "") + (opts.center ? " ep-row--center" : "") + (opts.class ? " " + opts.class : "");
    return '<div class="' + cls + '">' + inner + "</div>";
  },

  /* ── Table ────────────────────────────────────────────── */
  table: function (headers, rows) {
    var h = '<table class="ep-table"><thead><tr>';
    headers.forEach(function (c) {
      h += "<th>" + c + "</th>";
    });
    h += "</tr></thead><tbody>";
    rows.forEach(function (r) {
      h += "<tr>";
      r.forEach(function (c) {
        h += "<td>" + c + "</td>";
      });
      h += "</tr>";
    });
    h += "</tbody></table>";
    return h;
  },

  /* ── Tag ──────────────────────────────────────────────── */
  tag: function (text, type) {
    return '<span class="ep-tag' + (type ? " ep-tag--" + type : "") + '">' + text + "</span>";
  },

  /* ── Alert ────────────────────────────────────────────── */
  alert: function (text, type) {
    return '<div class="ep-alert' + (type ? " ep-alert--" + type : "") + '">' + text + "</div>";
  },

  /* ── Radio group ──────────────────────────────────────── */
  radioGroup: function (name, options, active) {
    var html = '<div class="ep-radio-group" id="' + name + '">';
    for (var i = 0; i < options.length; i++) {
      var o = options[i];
      var act = o.value === active ? " is-active" : "";
      html +=
        '<div class="ep-radio' + act + '" data-val="' + EP._a(o.value) + '" onclick="EP._radio(\'' +
        name +
        "',this)\">" +
        EP._a(o.label) +
        "</div>";
    }
    html += "</div>";
    return html;
  },

  /* ── Checkbox ─────────────────────────────────────────── */
  check: function (id, label, checked) {
    return (
      '<label class="ep-check"><input type="checkbox" id="' + id + '"' +
      (checked ? " checked" : "") +
      ">" + label + "</label>"
    );
  },

  /* ── Switch (el-switch) ──────────────────────────────── */
  switch: function (id, onchange, checked) {
    return (
      '<label class="ep-switch"><input type="checkbox" id="' + id + '"' +
      (checked ? " checked" : "") +
      (onchange ? ' onchange="' + onchange + '"' : "") +
      '><span class="ep-switch__track"></span></label>'
    );
  },

  /* ── Input with prepend (el-input #prepend) ───────────── */
  inputPrepend: function (id, prepend, opts) {
    opts = opts || {};
    var ph = opts.placeholder ? ' placeholder="' + EP._a(opts.placeholder) + '"' : "";
    var val = opts.value !== undefined ? ' value="' + EP._a(opts.value) + '"' : "";
    var ro = opts.readonly ? " readonly" : "";
    return (
      '<div class="ep-input-group"><div class="ep-input-group__prepend">' + prepend + "</div>" +
      '<input id="' + id + '" class="ep-input"' + ph + val + ro + "></div>"
    );
  },

  /* ── Internal helpers ─────────────────────────────────── */
  _a: function (s) {
    return String(s == null ? "" : s)
      .replace(/&/g, "&amp;")
      .replace(/"/g, "&quot;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;");
  },

  _radio: function (name, el) {
    var grp = document.getElementById(name);
    if (!grp) return;
    grp.querySelectorAll(".ep-radio").forEach(function (r) {
      r.classList.remove("is-active");
    });
    el.classList.add("is-active");
    var inp = document.getElementById(name + "_val");
    if (!inp) {
      inp = document.createElement("input");
      inp.type = "hidden";
      inp.id = name + "_val";
      grp.appendChild(inp);
    }
    inp.value = el.getAttribute("data-val");
  },

  /* ── Clipboard copy ───────────────────────────────────── */
  copy: function (text) {
    text = String(text == null ? "" : text);
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(text).then(
        function () {
          EP.toast(_t("copy_ok") || "Copied", "success");
        },
        function () {
          EP._fallbackCopy(text);
        }
      );
    } else {
      EP._fallbackCopy(text);
    }
  },

  _fallbackCopy: function (text) {
    var ta = document.createElement("textarea");
    ta.value = text;
    ta.style.position = "fixed";
    ta.style.opacity = "0";
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand("copy");
      EP.toast(_t("copy_ok") || "Copied", "success");
    } catch (e) {
      EP.toast("Copy failed", "danger");
    }
    document.body.removeChild(ta);
  },

  /* ── Toast (el-message) ───────────────────────────────── */
  toast: function (msg, type) {
    var t = document.createElement("div");
    t.className = "ep-toast" + (type ? " ep-toast--" + type : "");
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(function () {
      if (t.parentNode) t.parentNode.removeChild(t);
    }, 2000);
  },
};
