// Only included for a signed-in reader who opted into reading history.
setTimeout(()=>{if(document.visibilityState!=='visible')return;fetch(todayukReading.url,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({action:'tur_read',nonce:todayukReading.nonce,post_id:String(todayukReading.post)})}).catch(()=>{});},15000);
