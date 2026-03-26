import fs from 'node:fs'
import path from 'node:path'
import { fileURLToPath } from 'node:url'
import { defineConfig, loadEnv, type Plugin } from 'vite'
import uni from '@dcloudio/vite-plugin-uni'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

/**
 * 微信小程序无 `fetch` / `Headers`，@supabase/supabase-js 打进 vendor 后会在加载时执行
 * `globalObject.fetch.bind(...)` 直接崩溃。必须在 vendor 最前面注入最小 Web API。
 * 标记 __WTE_MP_FETCH_PATCH__ 防止重复包裹。
 */
function mpWeixinFetchPolyfill(): Plugin {
  const banner = `;var __WTE_MP_FETCH_PATCH__=1;(function(){
var _gs=[typeof globalThis!=="undefined"?globalThis:null,typeof global!=="undefined"?global:null,typeof self!=="undefined"?self:null].filter(Boolean);
function _hdrs(h){
  if(!h)return{};
  if(typeof h.forEach==="function"){var o={};h.forEach(function(v,k){o[k]=v;});return o;}
  return h;
}
function _install(g){
  if(!g||typeof g.fetch==="function")return;
  function _req(url,method,header,data){
    return new Promise(function(resolve,reject){
      var run=typeof uni!=="undefined"&&uni.request?function(o){uni.request(o)}:function(o){wx.request(o)};
      run({url:url,method:method||"GET",header:header||{},data:data,dataType:"json",
        success:function(ret){
          var sc=ret.statusCode||0;
          var d=ret.data;
          resolve({ok:sc>=200&&sc<300,status:sc,
            text:function(){return Promise.resolve(typeof d==="string"?d:JSON.stringify(d));},
            json:function(){return Promise.resolve(d);}
          });
        },
        fail:function(e){reject(e);}
      });
    });
  }
  g.fetch=function(input,init){
    init=init||{};
    var u=typeof input==="string"?input:(input&&input.url)||"";
    return _req(u,(init.method||"GET").toUpperCase(),_hdrs(init.headers||{}),init.body);
  };
  if(typeof g.Headers!=="function"){
    g.Headers=function Headers(init){
      this._h={};
      if(init&&typeof init==="object"){
        if(Array.isArray(init)){for(var i=0;i<init.length;i++){var p=init[i];if(p&&p.length>=2)this._h[String(p[0]).toLowerCase()]=String(p[1]);}}
        else{for(var k in init)if(Object.prototype.hasOwnProperty.call(init,k))this._h[String(k).toLowerCase()]=String(init[k]);}
      }
    };
    g.Headers.prototype.get=function(k){return this._h[String(k).toLowerCase()]||null;};
    g.Headers.prototype.set=function(k,v){this._h[String(k).toLowerCase()]=String(v);};
    g.Headers.prototype.has=function(k){return Object.prototype.hasOwnProperty.call(this._h,String(k).toLowerCase());};
    g.Headers.prototype.append=function(k,v){var key=String(k).toLowerCase();if(this._h[key]!==undefined)this._h[key]+=", "+String(v);else this._h[key]=String(v);};
    g.Headers.prototype.delete=function(k){delete this._h[String(k).toLowerCase()];};
    g.Headers.prototype.forEach=function(cb,thisArg){for(var k in this._h)if(Object.prototype.hasOwnProperty.call(this._h,k))cb.call(thisArg,this._h[k],k,this);};
  }
  if(typeof g.Request!=="function"){g.Request=function Request(u,i){this.url=typeof u==="string"?u:"";this.i=i||{};};}
  if(typeof g.Response!=="function"){
    g.Response=function Response(body,init){
      this._b=body;this.status=(init&&init.status)||200;this.ok=this.status>=200&&this.status<300;
    };
    g.Response.prototype.text=function(){return Promise.resolve(this._b==null?"":String(this._b));};
    g.Response.prototype.json=function(){
      try{var x=this._b;return Promise.resolve(typeof x==="string"?JSON.parse(x):x);}catch(e){return Promise.reject(e);}
    };
  }
}
for(var i=0;i<_gs.length;i++)_install(_gs[i]);
})();`

  return {
    name: 'mp-weixin-fetch-polyfill',
    apply: 'build',
    enforce: 'post',
    closeBundle() {
      const roots = [
        path.resolve(process.cwd(), 'dist/build/mp-weixin'),
        path.resolve(process.cwd(), 'dist/dev/mp-weixin'),
      ]
      for (const root of roots) {
        const vendorPath = path.join(root, 'common', 'vendor.js')
        if (!fs.existsSync(vendorPath)) continue
        let code = fs.readFileSync(vendorPath, 'utf8')
        if (code.includes('__WTE_MP_FETCH_PATCH__')) continue
        fs.writeFileSync(vendorPath, banner + code, 'utf8')
      }
    },
  }
}

// https://uniapp.dcloud.net.cn/collocation/vite-config.html
// 显式注入 VITE_*，避免 mp-weixin 产物里 import.meta.env 退化为 {} 导致运行异常
export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), 'VITE_')
  // 体验/联调时有时只在仓库根目录配置了 .env，因此允许从上一级目录补齐 VITE_*。
  // 小程序端本地环境（process.cwd()）为空时，这个兜底能减少“配了但读不到”的情况。
  const rootEnv = loadEnv(mode, path.resolve(process.cwd(), '..'), 'VITE_')
  const mergedEnv = { ...rootEnv, ...env }
  return {
    plugins: [uni(), mpWeixinFetchPolyfill()],
    resolve: {
      alias: {
        /** 避免加载 @supabase/node-fetch/browser.js（真机无 window/global/self 会同步抛错） */
        '@supabase/node-fetch': path.resolve(__dirname, 'src/shims/supabase-node-fetch.ts'),
      },
    },
    define: {
      'import.meta.env.VITE_SUPABASE_URL': JSON.stringify(mergedEnv.VITE_SUPABASE_URL ?? ''),
      'import.meta.env.VITE_SUPABASE_ANON_KEY': JSON.stringify(mergedEnv.VITE_SUPABASE_ANON_KEY ?? ''),
      'import.meta.env.VITE_API_BASE_URL': JSON.stringify(mergedEnv.VITE_API_BASE_URL ?? ''),
      'import.meta.env.VITE_APP_CONFIG_URL': JSON.stringify(mergedEnv.VITE_APP_CONFIG_URL ?? ''),
    },
  }
})
