import {readFile,writeFile,unlink} from 'node:fs/promises';
import ts from 'typescript';
import assert from 'node:assert/strict';
const path=new URL('../lib/editorial/.test-publish.mjs',import.meta.url);
await writeFile(path,ts.transpileModule(await readFile(new URL('../lib/editorial/publish.ts',import.meta.url),'utf8'),{compilerOptions:{module:ts.ModuleKind.ESNext,target:ts.ScriptTarget.ES2022}}).outputText);
try{
 const {publishApprovedDraft}=await import(path.href);
 for(const state of [null,'draft','publish','uncertain','sending','publishing']){
  const actions=[];const transport=async(_url,opts)=>{if(!opts.body)return Response.json({deliveries:state?[{draft_id:'one',draft_revision:4,state}]:[]});actions.push(JSON.parse(opts.body).action);return Response.json({message:'Published on WordPress.'});};
  if(['uncertain','sending','publishing'].includes(state))await assert.rejects(publishApprovedDraft('one',4,transport),/No duplicate/);else await publishApprovedDraft('one',4,transport);
  assert.deepEqual(actions,state===null?['send','publish']:state==='draft'?['publish']:[]);
 }
 const calls=[];await assert.rejects(publishApprovedDraft('one',4,async(_url,opts)=>{if(!opts.body)return Response.json({deliveries:[]});calls.push(JSON.parse(opts.body).action);return Response.json({error:'Send failed'},{status:409});}),/Send failed/);assert.deepEqual(calls,['send']);
 await assert.rejects(publishApprovedDraft('one',4,async()=>Response.json({deliveries:[{draft_id:'one',draft_revision:3,state:'draft'}]})),/earlier revision/);
 console.log('Publishing flow passed: send then publish, resume confirmed draft, no duplicate or uncertain retries, stop on failure.');
}finally{await unlink(path);}
