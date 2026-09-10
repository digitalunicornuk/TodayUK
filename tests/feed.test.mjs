import assert from 'node:assert/strict';
import {readFile,mkdtemp,writeFile,rm} from 'node:fs/promises';
import ts from 'typescript';
const temp=await mkdtemp(new URL('../.feed-test-',import.meta.url));
try{
 const source=await readFile('lib/discovery/feed.ts','utf8');await writeFile(temp+'/feed.mjs',ts.transpileModule(source,{compilerOptions:{module:ts.ModuleKind.ESNext,target:ts.ScriptTarget.ES2022}}).outputText);
 const {parseFeed,canonicalUrl,fetchFeed,fingerprint}=await import(temp+'/feed.mjs');
 const xml='<rss><channel><item><title>A &amp; B</title><link>https://news.croydon.gov.uk/story/?utm_source=rss</link><description><![CDATA[<p>Original report</p>]]></description><pubDate>bad date</pubDate></item></channel></rss>';
 const rows=parseFeed(xml);assert.equal(rows.length,1);assert.equal(rows[0].title,'A & B');assert.equal(rows[0].original_text,'Original report');assert.equal(rows[0].published_at,null);assert.equal(rows[0].original_payload.description,'<p>Original report</p>');
 assert.equal(canonicalUrl('https://lbc-app-w-wp-newsroom-p.azurewebsites.net/story/#x'),'https://news.croydon.gov.uk/story/');
 assert.throws(()=>canonicalUrl('javascript:alert(1)'));assert.throws(()=>parseFeed('<html>error</html>'));assert.throws(()=>parseFeed('<!DOCTYPE rss><rss/>'));assert.throws(()=>parseFeed('<rss>'));
 await assert.rejects(fetchFeed('https://127.0.0.1/feed'));
 assert.equal(await fingerprint(rows[0].original_url),await fingerprint(canonicalUrl('https://news.croydon.gov.uk/story/?utm_medium=email')));
 console.log('Feed checks passed: RSS parsing, retained originals, safe URLs, deduplication, malformed XML and fetch allowlist.');
}finally{await rm(temp,{recursive:true,force:true});}
