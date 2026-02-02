(function(){
  function onReady(fn){
    if(document.readyState === "loading"){
      document.addEventListener("DOMContentLoaded", fn);
    } else {
      fn();
    }
  }

  function copyText(text){
    if(!text) return Promise.reject();
    if(navigator.clipboard && navigator.clipboard.writeText){
      return navigator.clipboard.writeText(text);
    }
    // fallback
    var ta = document.createElement("textarea");
    ta.value = text;
    ta.setAttribute("readonly", "readonly");
    ta.style.position = "absolute";
    ta.style.left = "-9999px";
    document.body.appendChild(ta);
    ta.select();
    try {
      document.execCommand("copy");
      document.body.removeChild(ta);
      return Promise.resolve();
    } catch(e){
      document.body.removeChild(ta);
      return Promise.reject(e);
    }
  }

  function toast(msg){
    var el = document.createElement("div");
    el.textContent = msg || "Copied";
    el.style.position = "fixed";
    el.style.right = "18px";
    el.style.bottom = "18px";
    el.style.background = "#1d2327";
    el.style.color = "#fff";
    el.style.padding = "10px 12px";
    el.style.borderRadius = "10px";
    el.style.zIndex = "99999";
    el.style.boxShadow = "0 8px 24px rgba(0,0,0,0.2)";
    document.body.appendChild(el);
    setTimeout(function(){ el.remove(); }, 1400);
  }

  onReady(function(){
    // Add copy buttons next to Kid Codes in admin tables (best effort).
    var codeEls = document.querySelectorAll("table code");
    for(var i=0;i<codeEls.length;i++){
      var codeEl = codeEls[i];
      var txt = (codeEl.textContent || "").trim();
      if(!txt || txt.indexOf("KID-") !== 0) continue;

      // Avoid duplicates
      if(codeEl.parentElement && codeEl.parentElement.querySelector(".kqas-copy-btn")) continue;

      var btn = document.createElement("button");
      btn.type = "button";
      btn.className = "button kqas-copy-btn";
      btn.style.marginLeft = "8px";
      btn.textContent = "Copy";

      btn.addEventListener("click", (function(text){
        return function(){
          copyText(text).then(function(){
            toast("Copied: " + text);
          }).catch(function(){
            toast("Copy failed");
          });
        };
      })(txt));

      if(codeEl.parentElement){
        codeEl.parentElement.appendChild(btn);
      }
    }
  });
})();
