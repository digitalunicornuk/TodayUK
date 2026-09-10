import {XMLParser,XMLValidator} from 'fast-xml-parser';
export const approvedFeed='https://news.croydon.gov.uk/feed/';
export function canonicalUrl(value:string){
 const u=new URL(value);if(!['https:','http:'].includes(u.protocol)||u.username||u.password)throw new Error('Use a public HTTP or HTTPS story URL.');
 u.hash='';for(const key of [...u.searchParams.keys()])if(/^(utm_|fbclid$|gclid$)/i.test(key))u.searchParams.delete(key);
 if(u.hostname==='lbc-app-w-wp-newsroom-p.azurewebsites.net')u.hostname='news.croydon.gov.uk';
 return u.href;
}
export function plain(value:unknown){return String(value??'').replace(/<[^>]*>/g,' ').replace(/\s+/g,' ').trim();}
export function parseFeed(xml:string){
 if(/<!DOCTYPE|<!ENTITY/i.test(xml)||XMLValidator.validate(xml)!==true)throw new Error('The source returned invalid or unsupported XML.');
 const parsed=new XMLParser({ignoreAttributes:false,parseTagValue:false,processEntities:true}).parse(xml);
 if(!parsed.rss?.channel)throw new Error('This source did not return an RSS feed.');
 const items=parsed.rss.channel.item;const rows=items?(Array.isArray(items)?items:[items]):[];
 return rows.slice(0,100).map((item:Record<string,unknown>)=>{
  const title=plain(item.title).slice(0,500);if(!title)throw new Error('A feed item has no title.');
  const original_url=canonicalUrl(String(item.link));
  const date=new Date(String(item.pubDate??''));
  return {title,original_url,original_text:plain(item['content:encoded']??item.description).slice(0,200000),original_payload:item,published_at:Number.isNaN(date.getTime())?null:date.toISOString()};
 });
}
export async function fetchFeed(url:string){
 if(url!==approvedFeed)throw new Error('This RSS address has not been approved for fetching.');
 const response=await fetch(url,{redirect:'error',signal:AbortSignal.timeout(15000),headers:{Accept:'application/rss+xml, application/xml'}});
 if(!response.ok||!response.body)throw new Error('The source could not be reached. Try again later.');
 const reader=response.body.getReader();const parts:Uint8Array[]=[];let length=0;
 try{while(true){const {done,value}=await reader.read();if(done)break;length+=value.length;if(length>2000000)throw new Error('The feed exceeds the 2 MB import limit.');parts.push(value);}}finally{await reader.cancel();}
 const bytes=new Uint8Array(length);let offset=0;for(const part of parts){bytes.set(part,offset);offset+=part.length;}
 return parseFeed(new TextDecoder().decode(bytes));
}
export async function fingerprint(value:string){const bytes=await crypto.subtle.digest('SHA-256',new TextEncoder().encode(value));return [...new Uint8Array(bytes)].map(b=>b.toString(16).padStart(2,'0')).join('');}
