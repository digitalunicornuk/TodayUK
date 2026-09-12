export type WordPressConfig={url:string;username:string;password:string};
export type WordPressPost={id:number;link:string;status:string;modified_gmt:string};
export function wordpressConfig(env:Record<string,string|undefined>=process.env):WordPressConfig|null{
 const {WORDPRESS_URL:url,WORDPRESS_USERNAME:username,WORDPRESS_APPLICATION_PASSWORD:password}=env;
 if(!url||!username||!password)return null;
 const u=new URL(url);
 if(u.protocol!=='https:'||u.username||u.password||u.search||u.hash||u.port||!u.hostname.includes('.')||/^(localhost|127\.|10\.|192\.168\.|169\.254\.|\[)/.test(u.hostname))throw Error('WordPress needs a public HTTPS address.');
 return {url:u.href.replace(/\/$/,''),username,password};
}
export function articleHtml(text:string){return text.trim().split(/\n\s*\n/).map(p=>'<p>'+p.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;').replace(/\n/g,'<br />')+'</p>').join('\n');}
export class WordPressClient{
 constructor(private config:WordPressConfig,private transport:typeof fetch=fetch){}
 private async request(path:string,body?:Record<string,unknown>):Promise<unknown>{
  let response:Response;
  try{response=await this.transport(this.config.url+'/wp-json/wp/v2/'+path,{method:body?'POST':'GET',redirect:'manual',signal:AbortSignal.timeout(20000),headers:{Authorization:'Basic '+btoa(String.fromCharCode(...new TextEncoder().encode(this.config.username+':'+this.config.password))),'Content-Type':'application/json'},...(body?{body:JSON.stringify(body)}:{})});}catch{throw Error('WordPress did not respond. Check its posts before retrying a send.');}
  if(!response.ok)throw Error(response.status===401||response.status===403?'WordPress rejected the connection or publishing permission.':'WordPress request failed. Check its posts before retrying a send.');
  return response.json();
 }
 async check(){const user=await this.request('users/me?context=edit') as {id?:number};if(!Number.isInteger(user.id))throw Error('WordPress returned an invalid account.');return true;}
 private post(value:unknown){const p=value as WordPressPost;if(!Number.isInteger(p?.id)||p.id<=0||typeof p.link!=='string'||!['draft','pending','publish','private','future'].includes(p.status))throw Error('WordPress returned an invalid post. Check the site before retrying.');const link=new URL(p.link);if(link.protocol!=='https:'||link.origin!==new URL(this.config.url).origin)throw Error('WordPress returned an unexpected post address.');return p;}
 async createDraft(id:string,headline:string,body:string){if(!/^[0-9a-f-]{36}$/i.test(id)||!headline.trim()||!body.trim())throw Error('The approved draft is incomplete.');const categories=await this.request('categories?slug=cr-news') as Array<{id:number;slug:string}>;const category=Array.isArray(categories)?categories.find(c=>c.slug==='cr-news'&&Number.isSafeInteger(c.id)&&c.id>0):undefined;if(!category)throw Error('Create the CR News category in WordPress before sending this story.');return this.post(await this.request('posts',{categories:[category.id],title:headline,content:articleHtml(body),status:'draft',slug:'todayuk-'+id,comment_status:'closed',ping_status:'closed'}));}
 async read(id:number){if(!Number.isSafeInteger(id)||id<1)throw Error('Invalid WordPress post.');return this.post(await this.request('posts/'+id+'?context=edit'));}
 async publish(id:number){if(!Number.isSafeInteger(id)||id<1)throw Error('Invalid WordPress post.');return this.post(await this.request('posts/'+id,{status:'publish'}));}
}
