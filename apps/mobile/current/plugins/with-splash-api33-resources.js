'use strict';

const fs = require('node:fs');
const path = require('node:path');

const STYLE_NAME = 'Theme.App.SplashScreen';
const API33_ITEM = 'android:windowSplashScreenBehavior';
const DEST_FILE = 'ald1n_splash_api33.xml';

function escapeXml(value) {
  return String(value)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&apos;');
}

function emitAttrs(attrs) {
  if (!attrs || typeof attrs !== 'object' || Array.isArray(attrs)) throw new Error('Malformed splash resource attributes');
  return Object.entries(attrs).map(([k, v]) => {
    if (!/^[A-Za-z_][A-Za-z0-9_.:-]*$/.test(k)) throw new Error(`Invalid XML attribute ${k}`);
    if (typeof v !== 'string') throw new Error(`Non-string XML value for ${k}`);
    return ` ${k}="${escapeXml(v)}"`;
  }).join('');
}

function renderStyle(style) {
  if (!style || !Array.isArray(style.item)) throw new Error('Invalid Expo splash style');
  const attrs=emitAttrs(style.$);
  const items=style.item.map(item=>{
    if (!item || typeof item._ !== 'string' || !item.$ || typeof item.$.name !== 'string')
      throw new Error('Unexpected Expo splash style item');
    if (Object.keys(item).some(k=>k !== '$' && k !== '_')) throw new Error('Unsupported nested Expo splash item');
    return `    <item${emitAttrs(item.$)}>${escapeXml(item._)}</item>`;
  }).join('\n');
  return `<?xml version="1.0" encoding="utf-8"?>\n<resources xmlns:tools="http://schemas.android.com/tools">\n  <style${attrs}>\n${items}\n  </style>\n</resources>\n`;
}

function splitSplashStyleByApi(resourceXml, androidProjectRoot, options = {}) {
  if (!resourceXml?.resources || !Array.isArray(resourceXml.resources.style))
    throw new Error('Expected generated Android styles XML');
  if (!options.introspect && !path.isAbsolute(androidProjectRoot)) throw new Error('Android project root must be absolute');
  const matching=resourceXml.resources.style.filter(x=>x?.$?.name === STYLE_NAME);
  if (matching.length !== 1) throw new Error('Expected exactly one Expo splash style');
  const splash=matching[0];
  if (splash.$.parent !== 'Theme.SplashScreen') throw new Error('Unexpected Expo splash parent');
  if (!Array.isArray(splash.item)) throw new Error('Missing Expo splash items');
  const bad=splash.item.filter(x=>x?.$?.name===API33_ITEM);
  if(bad.length !== 1) throw new Error('Expected exactly one API33 behavior item');
  if(bad[0]._ !== 'icon_preferred') throw new Error('Expected icon_preferred API33 splash behavior');
  for (const key of ['windowSplashScreenBackground','windowSplashScreenAnimatedIcon','postSplashScreenTheme']) {
    if (splash.item.filter(x=>x?.$?.name===key).length!==1) throw new Error(`Missing or duplicate Expo splash item ${key}`);
  }
  if (options.introspect) {
    splash.item=splash.item.filter(x=>x.$.name !== API33_ITEM);
    return resourceXml;
  }
  const v33Dir=path.join(androidProjectRoot,'app','src','main','res','values-v33');
  const dest=path.join(v33Dir,DEST_FILE);
  const v33Xml=renderStyle(splash);
  if(fs.existsSync(v33Dir)) {
    for(const file of fs.readdirSync(v33Dir)) {
      if(!file.endsWith('.xml') || file===DEST_FILE) continue;
      const src=fs.readFileSync(path.join(v33Dir,file),'utf8');
      if(/<style\b[^>]*name=["']Theme\.App\.SplashScreen["']/.test(src))
        throw new Error('Conflicting API33 splash style already exists');
    }
  }
  if (fs.existsSync(dest)) {
    if (fs.readFileSync(dest,'utf8') !== v33Xml) throw new Error('API33 splash destination already exists with different content');
  } else {
    fs.mkdirSync(v33Dir,{recursive:true});
    fs.writeFileSync(dest,v33Xml,{encoding:'utf8',flag:'wx'});
  }
  splash.item=splash.item.filter(x=>x.$.name !== API33_ITEM);
  return resourceXml;
}

function withAld1nSplashApi33Resources(config) {
  const { withAndroidStyles } = require('@expo/config-plugins');
  return withAndroidStyles(config, mod => {
    mod.modResults=splitSplashStyleByApi(mod.modResults, mod.modRequest.platformProjectRoot, { introspect: mod.modRequest.introspect === true });
    return mod;
  });
}

module.exports = withAld1nSplashApi33Resources;
module.exports.splitSplashStyleByApi = splitSplashStyleByApi;

function verifyGeneratedSplashResources(androidProjectRoot) {
  if (!path.isAbsolute(androidProjectRoot)) throw new Error('Android project root must be absolute');
  const res=path.join(androidProjectRoot,'app','src','main','res');
  const base=fs.readFileSync(path.join(res,'values','styles.xml'),'utf8');
  const v33=fs.readFileSync(path.join(res,'values-v33',DEST_FILE),'utf8');
  const styleRe=/<style\b[^>]*\bname="Theme\.App\.SplashScreen"[^>]*>[\s\S]*?<\/style>/g;
  const baseStyles=base.match(styleRe) || [];
  const v33Styles=v33.match(styleRe) || [];
  if (baseStyles.length !== 1 || v33Styles.length !== 1) throw new Error('Missing or duplicate generated splash styles');
  if (base.includes(API33_ITEM)) throw new Error('API33 item remains in unqualified values');
  if ((v33Styles[0].match(/android:windowSplashScreenBehavior/g)||[]).length !== 1 ||
      !v33Styles[0].includes('>icon_preferred</item>')) throw new Error('API33 splash behavior missing from qualified theme');
  for (const key of ['windowSplashScreenBackground','windowSplashScreenAnimatedIcon','postSplashScreenTheme']) {
    if (!baseStyles[0].includes('name="'+key+'"') || !v33Styles[0].includes('name="'+key+'"'))
      throw new Error('Missing shared splash style item '+key);
  }
  return true;
}
module.exports.verifyGeneratedSplashResources=verifyGeneratedSplashResources;
