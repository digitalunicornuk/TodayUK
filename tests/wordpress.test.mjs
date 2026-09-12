import {readFile,writeFile,unlink} from 'node:fs/promises';
import ts from 'typescript';
import assert from 'node:assert/strict';
const path=new URL('../lib/wordpress/.test-client.mjs',import.meta.url);
await writeFile(path,ts.transpileModule(await readFile(new URL('../lib/wordpress/client.ts',import.meta.url),'utf8'),{compilerOptions:{module:ts.ModuleKind.ESNext,target:ts.ScriptTarget.ES2022}}).outputText);
try{
const {WordPressClient,wordpressConfig,articleHtml}=await import(path.href);
assert.equal(wordpressConfig({}),null);
for(const url of ['http://news.example','https://user:pass@news.example','https://localhost','https://127.0.0.1'])assert.throws(()=>wordpressConfig({WORDPRESS_URL:url,WORDPRESS_USERNAME:'test',WORDPRESS_APPLICATION_PASSWORD:'test'}));
assert.equal(articleHtml('<script>alert(1)</script>\n\nNext'),'<p>&lt;script&gt;alert(1)&lt;/script&gt;</p>\n<p>Next</p>');
let sent;
const client=new WordPressClient({url:'https://news.example',username:'editor',password:'secret'},async(url,opts)=>{sent={url,opts};if(url.endsWith('categories?slug=cr-news'))return Response.json([{id:7,slug:'cr-news'}]);return Response.json({id:42,link:'https://news.example/?p=42',status:'draft',modified_gmt:'2026-09-12T00:00:00'});});
await client.createDraft('10000000-0000-4000-8000-000000000001','Headline','Body');assert.equal(JSON.parse(sent.opts.body).status,'draft');assert.deepEqual(JSON.parse(sent.opts.body).categories,[7]);assert.equal(sent.opts.redirect,'manual');assert.ok(!sent.url.includes('secret'));
await client.update(42,'Updated title','Updated body');assert.ok(sent.url.endsWith('/posts/42'));assert.deepEqual(JSON.parse(sent.opts.body),{title:'Updated title',content:'<p>Updated body</p>'});await assert.rejects(client.update(0,'Title','Body'));
await client.publish(42);assert.deepEqual(JSON.parse(sent.opts.body),{status:'publish'});await assert.rejects(client.publish(-1));
let missingWrites=0;const missingCategory=new WordPressClient({url:'https://news.example',username:'editor',password:'secret'},async(url,opts)=>{if(opts.method==='POST')missingWrites++;return Response.json([]);});await assert.rejects(missingCategory.createDraft('10000000-0000-4000-8000-000000000001','Headline','Body'),/CR News category/);assert.equal(missingWrites,0);
const redirect=new WordPressClient({url:'https://news.example',username:'editor',password:'secret'},async()=>new Response(null,{status:302,headers:{Location:'https://other.example'}}));await assert.rejects(redirect.check(),/request failed/);
for(const [status,payload,expected] of [[401,null,/hosting gateway blocked API access/],[403,null,/hosting gateway blocked API access/],[401,{code:'incorrect_password',message:'secret'},/did not accept/],[401,{code:'rest_not_logged_in'},/without an authenticated user/],[403,{code:'rest_cannot_edit'},/account permissions/]]){
 const failed=new WordPressClient({url:'https://news.example',username:'editor',password:'secret'},async()=>payload?Response.json(payload,{status}):new Response('',{status}));
 await assert.rejects(failed.check(),e=>expected.test(e.message)&&!e.message.includes('secret'));
}
console.log('WordPress client checks passed: HTTPS, draft-first sends, escaped content, explicit publication, redirects and invalid post IDs.');
}finally{await unlink(path);}
