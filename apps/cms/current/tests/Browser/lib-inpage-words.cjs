'use strict';

/*
 * In-page word-integrity gate (runs inside page.evaluate, no closures over Node scope). Author: Ald1n <https://ald1n.com>
 *
 * Owner rule 2026-11-24: a word is NEVER broken in the middle, on any page, at any width, in both themes.
 *   word-broken          a text word of 2..20 characters whose Range has client rects on two different lines
 *                        (hyphen, slash, comma and similar are real break points and split the token first;
 *                        strings longer than 20 characters such as URLs, SKUs and codes may break; code/pre are skipped).
 *   short-text-overflow  a badge / pill / button / tab / status / chip / count element whose content is wider than the
 *                        element (scrollWidth > clientWidth + 1, ellipsis excepted) or that sticks out of its parent
 *                        (a pill leaving its card), unless a scroll container around it legitimately clips it.
 * Returns [{ rule, s: selector, t: text, w: width px }], at most 40 entries; the harness turns them into findings.
 */
module.exports = function wordGate() {
  const MAX_WORD = 20;
  const out = [];
  const seen = new Set();
  const sel = (el) => {
    let s = el.tagName.toLowerCase() + (el.id ? '#' + el.id : '') + [...el.classList].slice(0, 3).map((c) => '.' + c).join('');
    const p = el.parentElement;
    if (p && p !== document.body) s = p.tagName.toLowerCase() + [...p.classList].slice(0, 2).map((c) => '.' + c).join('') + ' > ' + s;
    return s;
  };
  const add = (rule, el, text, w) => {
    const key = rule + '|' + sel(el) + '|' + text;
    if (seen.has(key) || out.length >= 40) return;
    seen.add(key);
    out.push({ rule, s: sel(el), t: String(text).slice(0, 60), w: Math.round(w) });
  };
  const shown = (el) => {
    const cs = getComputedStyle(el);
    return cs.display !== 'none' && cs.visibility !== 'hidden' && el.getClientRects().length > 0;
  };

  // 1. Broken words.
  const SKIP = /^(SCRIPT|STYLE|NOSCRIPT|TEXTAREA|CODE|PRE|SVG|CANVAS|OPTION|SELECT|TITLE)$/;
  const TOKEN = /[^\s\-‐-―\/·|,;:()\[\]{}"'„“”]+/g;
  const range = document.createRange();
  for (const el of document.body.querySelectorAll('*')) {
    if (SKIP.test(el.tagName) || el.closest('code, pre, svg, [data-word-break-ok]') || !shown(el)) continue;
    const box = el.getBoundingClientRect();
    if (box.width <= 3 || box.height <= 0) continue; // visually-hidden helpers (.a1-sr is 1px wide)
    const lh = parseFloat(getComputedStyle(el).lineHeight) || parseFloat(getComputedStyle(el).fontSize) * 1.3 || 18;
    // Cheap pre-filter: a word can only be split when the element holds more than one line.
    if (el.getClientRects().length < 2 && box.height < lh * 1.45) continue;
    for (const node of el.childNodes) {
      if (node.nodeType !== 3 || !node.nodeValue.trim()) continue;
      const text = node.nodeValue;
      TOKEN.lastIndex = 0;
      let m;
      while ((m = TOKEN.exec(text))) {
        const word = m[0];
        if (word.length < 2 || word.length > MAX_WORD) continue;
        range.setStart(node, m.index);
        range.setEnd(node, m.index + word.length);
        const rects = [...range.getClientRects()].filter((r) => r.width > 0 && r.height > 0);
        if (rects.length < 2) continue;
        const tops = rects.map((r) => r.top);
        const h = Math.min(...rects.map((r) => r.height));
        if (Math.max(...tops) - Math.min(...tops) > h * 0.6) add('word-broken', el, word, box.width);
      }
    }
  }

  // 2. Short-text components: content wider than the component, or the component sticking out of its parent.
  const TOKENS = new Set(['badge', 'pill', 'btn', 'button', 'tab', 'chip', 'status', 'count']);
  const isShort = (el) => el.matches('button, [role=tab], [role=button], .a1-tabs__link, input[type=submit], input[type=button]')
    || [...el.classList].some((c) => c.split(/[-_]+/).some((t) => TOKENS.has(t)));
  const insideScroller = (el) => {
    // Only a real scroll container counts (a tab bar, a table wrapper). overflow:hidden/clip would hide the very defect we look for.
    for (let n = el.parentElement; n && n !== document.body; n = n.parentElement) {
      const o = getComputedStyle(n).overflowX;
      if (o === 'auto' || o === 'scroll') return true;
    }
    return false;
  };
  for (const el of document.body.querySelectorAll('*')) {
    if (/^(INPUT|SELECT|TEXTAREA|SVG|svg|PATH|USE|OPTION)$/.test(el.tagName) && !el.matches('input[type=submit], input[type=button]')) continue;
    if (!isShort(el) || !shown(el) || el.closest('.a1-sr, [data-word-break-ok]')) continue;
    const cs = getComputedStyle(el);
    const box = el.getBoundingClientRect();
    if (box.width <= 3 || cs.position === 'fixed' || cs.position === 'sticky') continue;
    const text = (el.textContent || '').replace(/\s+/g, ' ').trim();
    if (!text) continue;
    if (cs.display !== 'inline' && cs.textOverflow !== 'ellipsis' && el.scrollWidth > el.clientWidth + 1) {
      add('short-text-overflow', el, text, box.width);
      continue;
    }
    const p = el.parentElement;
    if (p && cs.position !== 'absolute' && getComputedStyle(p).display !== 'contents' && !insideScroller(el)) {
      const pb = p.getBoundingClientRect();
      if (pb.width > 0 && (box.right > pb.right + 1.5 || box.left < pb.left - 1.5)) add('short-text-overflow', el, text, box.width);
    }
  }
  return out;
};
