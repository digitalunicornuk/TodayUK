/* Google owns allocation/guarantees; house creative remains until a filled slot is confirmed. */
(()=>{
 const placements=[...document.querySelectorAll('[data-ad-unit]')].filter(x=>/^\/\d+\/[\w/-]+$/.test(x.dataset.adUnit||''));
 if(!placements.length)return;let started=false,allowed=false;const slots=[];
 function consent(ok){allowed=ok;if(!ok){if(started&&window.googletag?.apiReady)googletag.cmd.push(()=>googletag.destroySlots(slots));placements.forEach(p=>{p.querySelector('.gam-slot').hidden=true;p.querySelector('.house-ad').hidden=false;p.querySelector('.ad-label').textContent='Advertisement · Digital Unicorn';});return;}if(started)return;started=true;
 window.googletag=window.googletag||{cmd:[]};const script=document.createElement('script');script.async=true;script.src='https://securepubads.g.doubleclick.net/tag/js/gpt.js';script.crossOrigin='anonymous';document.head.appendChild(script);
 googletag.cmd.push(()=>{if(!allowed)return;googletag.pubads().setPrivacySettings({nonPersonalizedAds:true});
 googletag.pubads().addEventListener('slotRenderEnded',event=>{const box=document.getElementById(event.slot.getSlotElementId())?.closest('[data-ad-unit]');if(!box)return;const filled=!event.isEmpty&&allowed;box.querySelector('.gam-slot').hidden=!filled;box.querySelector('.house-ad').hidden=filled;box.querySelector('.ad-label').textContent=filled?'Advertisement':'Advertisement · Digital Unicorn';});
 for(const box of placements){const el=box.querySelector('.gam-slot');el.hidden=false;const wide=['header_banner','home_leaderboard'].includes(box.dataset.adSlot);if(box.getBoundingClientRect().width<(wide?320:300)){el.hidden=true;continue;}const sizes=wide?[[970,90],[728,90],[320,100],[320,50]]:[[300,250]];const slot=googletag.defineSlot(box.dataset.adUnit,sizes,el.id);if(!slot)continue;if(wide)slot.defineSizeMapping(googletag.sizeMapping().addSize([1024,0],[[970,90],[728,90]]).addSize([768,0],[[728,90]]).addSize([0,0],[[320,100],[320,50]]).build());slot.addService(googletag.pubads());slot.setTargeting('placement',box.dataset.adSlot);for(const [key,value]of Object.entries(window.todayukAdContext||{}))if(value&&(!Array.isArray(value)||value.length))slot.setTargeting(key,value);slots.push(slot);}
 googletag.enableServices();slots.forEach(slot=>googletag.display(slot.getSlotElementId()));});
 }
 // Fail closed if a supported TCF consent signal is unavailable. No custom reader profile targeting.
 if(typeof window.__tcfapi==='function')window.__tcfapi('addEventListener',2,(tc,success)=>{if(!success)return;const ready=['tcloaded','useractioncomplete'].includes(tc.eventStatus);consent(!!(ready&&tc.gdprApplies===true&&tc.purpose?.consents?.[1]&&tc.vendor?.consents?.[755]));});
})();
