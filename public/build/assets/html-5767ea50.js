function a(t){if(t==null)return"";const e=String(t);if(!e.trim())return"";if(typeof document<"u"){const r=e.replace(/<\s*br\s*\/?>/gi,`
`).replace(/<\/\s*p\s*>/gi,`
`).replace(/<\/\s*div\s*>/gi,`
`).replace(/<\/\s*li\s*>/gi,`
`),n=document.createElement("template");return n.innerHTML=r,(n.content.textContent||n.content.innerText||"").replace(/\u00a0/g," ").replace(/[ \t]+\n/g,`
`).replace(/\n{3,}/g,`

`).trim()}return e.replace(/<\s*br\s*\/?>/gi,`
`).replace(/<\/\s*p\s*>/gi,`
`).replace(/<[^>]+>/g,"").replace(/&nbsp;/gi," ").replace(/&amp;/gi,"&").replace(/&lt;/gi,"<").replace(/&gt;/gi,">").replace(/&quot;/gi,'"').replace(/&#39;/gi,"'").replace(/\n{3,}/g,`

`).trim()}export{a as h};
