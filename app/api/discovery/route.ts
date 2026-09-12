import {newsroomClient} from '@/lib/supabase/server';
import {approvedFeed,canonicalUrl,fetchFeed,fingerprint} from '@/lib/discovery/feed';
const reply=(body:unknown,status=200)=>Response.json(body,{status,headers:{'Cache-Control':'private, no-store'}});
async function context(){
 const db=await newsroomClient();if(!db)return null;
 const {data:{user}}=await db.auth.getUser();if(!user)return null;
 const {data:w}=await db.from('workspaces').select('id').eq('slug','todayuk').single();if(!w)return null;
 const {data:m}=await db.from('workspace_members').select('role').eq('workspace_id',w.id).eq('user_id',user.id).single();
 return {db,w,role:m?.role};
}
export async function GET(){const c=await context();if(!c)return reply({error:'Sign in with an approved newsroom account.'},401);
 const results=await Promise.all([c.db.from('news_sources').select('*').eq('workspace_id',c.w.id).order('name'),c.db.from('incoming_stories').select('*').eq('workspace_id',c.w.id).order('published_at',{ascending:false,nullsFirst:false}).order('received_at',{ascending:false}).limit(200),c.db.from('source_fetches').select('*').eq('workspace_id',c.w.id).order('fetched_at',{ascending:false}).limit(20),c.db.from('story_pitches').select('*').eq('workspace_id',c.w.id).limit(500)]);
 if(results.some(r=>r.error))return reply({error:'Discovery is not available yet. Please retry shortly.'},503);
 return reply({sources:results[0].data,stories:results[1].data,fetches:results[2].data,pitches:results[3].data,role:c.role});
}
export async function POST(request:Request){
 if(request.headers.get('origin')!=='https://todayuk-newsroom.digitalunicorn.chatgpt.site')return reply({error:'Request origin not allowed.'},403);
 const c=await context();if(!c)return reply({error:'Sign in with an approved newsroom account.'},401);
 if(!['owner','editor','reviewer'].includes(c.role))return reply({error:'Your role cannot change discovery records.'},403);
 let body;try{const text=await request.text();if(text.length>210000)return reply({error:'Submission is too large.'},413);body=JSON.parse(text);}catch{return reply({error:'Invalid submission.'},400);}
 const {db,w,role}=c;
 try{
  if(body.action==='pitch'){
   const headline=String(body.headline??'').trim(),alternative=String(body.alternative??'').trim(),reader_value=String(body.reader_value??'').trim(),reporting_questions=String(body.reporting_questions??'').trim();
   if(!headline||headline.length>500||alternative.length>500||reader_value.length>2000||reporting_questions.length>4000)return reply({error:'Check the headline and note lengths.'},400);
   const fields={headline,alternative,reader_value,reporting_questions};const existing=await db.from('story_pitches').select('story_id').eq('workspace_id',w.id).eq('story_id',body.id).maybeSingle();if(existing.error)throw new Error('Could not load headline ideas.');const r=existing.data?await db.from('story_pitches').update(fields).eq('workspace_id',w.id).eq('story_id',body.id).select('story_id').single():await db.from('story_pitches').insert({workspace_id:w.id,story_id:body.id,...fields});if(r.error)throw new Error('Could not save the headline ideas.');return reply({message:'Headline ideas saved. Original source unchanged.'});
  }
  if(body.action==='research'||(body.action==='triage'&&body.status==='shortlisted')){
   const {data:story,error:storyError}=await db.from('incoming_stories').select('id,title,status,original_text,original_url').eq('workspace_id',w.id).eq('id',body.id).single();
   if(storyError||!story)throw new Error('Could not find this story.');
   if(body.action==='research'&&story.status!=='shortlisted')return reply({error:'Shortlist this story first.'},400);
   const existing=await db.from('story_group_items').select('group_id').eq('workspace_id',w.id).eq('story_id',story.id).order('created_at').limit(1).maybeSingle();
   if(existing.error)throw new Error('Could not load story research.');
   let groupId=existing.data?.group_id;
   if(!groupId){
    const pitch=await db.from('story_pitches').select('headline').eq('workspace_id',w.id).eq('story_id',story.id).maybeSingle();
    if(pitch.error)throw new Error('Could not load the story headline.');
    // A stable ID makes retries and simultaneous clicks reuse the same research file.
    groupId=story.id;
    const group=await db.from('story_groups').upsert({id:groupId,workspace_id:w.id,title:pitch.data?.headline||story.title},{onConflict:'id',ignoreDuplicates:true});
    if(group.error)throw new Error('Could not prepare research. Please retry.');
    const link=await db.from('story_group_items').upsert({workspace_id:w.id,group_id:groupId,story_id:story.id},{onConflict:'workspace_id,group_id,story_id',ignoreDuplicates:true});
    if(link.error)throw new Error('Could not attach the source. Please retry.');
   }
   if(body.action==='triage'){
    const updated=await db.from('incoming_stories').update({status:'shortlisted'}).eq('workspace_id',w.id).eq('id',story.id).select('id').single();
    if(updated.error)throw new Error('Research is prepared, but shortlisting failed. Please retry.');
   }
   let draftId:string|undefined;
   if(['owner','editor'].includes(role)){
    const existingDraft=await db.from('editorial_drafts').select('id').eq('workspace_id',w.id).eq('group_id',groupId).order('updated_at',{ascending:false}).limit(1).maybeSingle();
    if(existingDraft.error)throw new Error('Could not open the draft. Please retry.');
    draftId=existingDraft.data?.id;
    if(!draftId){
     const heading=await db.from('story_groups').select('title').eq('workspace_id',w.id).eq('id',groupId).single();
     if(heading.error)throw new Error('Could not load the draft headline.');
     const sourceText=(story.original_text||'').replace(/\s*The post [\s\S]*? appeared first on [\s\S]*$/i,'').trim();
     const body=sourceText+(story.original_url?'\n\nSource: '+story.original_url:'');
     const created=await db.from('editorial_drafts').upsert({id:groupId,workspace_id:w.id,group_id:groupId,headline:heading.data.title,body,risk_notes:'Automatically prepared from retained source material. This is a source-based starter, not a verified or fully researched article. Check the full source, attribution, dates, completeness and rights before approval.'},{onConflict:'id',ignoreDuplicates:true});
     if(created.error)throw new Error('Story shortlisted, but its draft could not be created. Click Continue story to retry.');
     draftId=groupId;
    }
   }
   return reply({message:draftId?'Draft ready. Opening your story…':'Shortlisted. Your research file is ready.',group_id:groupId,draft_id:draftId});
  }
  if(body.action==='triage'){
   if(!['new','shortlisted','dismissed'].includes(body.status))return reply({error:'Choose a valid queue status.'},400);
   const r=await db.from('incoming_stories').update({status:body.status}).eq('workspace_id',w.id).eq('id',body.id).select('id').single();if(r.error)throw new Error('Could not update this story.');return reply({message:'Story updated.'});
  }
  if(!['owner','editor'].includes(role))return reply({error:'Only owners and editors can add stories.'},403);
  const {data:config}=await db.from('module_config').select('enabled').eq('workspace_id',w.id).eq('module_key','discovery').single();if(!config?.enabled)return reply({error:'Discovery is paused.'},403);
  if(body.action==='source'){
   if(role!=='owner')return reply({error:'Only the owner can configure sources.'},403);
   if(!['official','established','unreviewed'].includes(body.trust)||typeof body.enabled!=='boolean')return reply({error:'Invalid source settings.'},400);
   const r=await db.from('news_sources').update({trust:body.trust,enabled:body.enabled}).eq('workspace_id',w.id).eq('id',body.id).select('id').single();if(r.error)throw new Error('Could not save the source.');return reply({message:'Source settings saved.'});
  }
  if(body.action==='fetch'){
   const {data:source}=await db.from('news_sources').select('*').eq('workspace_id',w.id).eq('id',body.id).single();if(!source?.enabled||source.feed_url!==approvedFeed)return reply({error:'This source is paused or unavailable.'},400);
   let added=0,duplicates=0;
   try{
    const items=await fetchFeed(source.feed_url);
    for(const item of items){const r=await db.from('incoming_stories').insert({...item,workspace_id:w.id,source_id:source.id,intake_kind:'rss',fingerprint:await fingerprint(item.original_url)});if(r.error?.code==='23505')duplicates++;else if(r.error)throw new Error('Import stopped. Retry safely to finish the remaining items.');else added++;}
    const run=await db.from('source_fetches').insert({workspace_id:w.id,source_id:source.id,added,duplicates,outcome:'success',message:`${added} new, ${duplicates} already in queue.`});if(run.error)throw new Error('Stories imported, but the fetch record could not be saved.');
    return reply({message:`${added} new stories. ${duplicates} duplicates skipped.`});
   }catch(e){const message=e instanceof Error?e.message:'Feed import failed.';await db.from('source_fetches').insert({workspace_id:w.id,source_id:source.id,added,duplicates,outcome:'error',message});throw new Error(message);}
  }
  if(body.action==='manual'){
   const title=String(body.title??'').trim();const original_text=String(body.text??'').trim();const kind=body.kind;
   if(!title||title.length>500||!['web','social','tip'].includes(kind)||original_text.length>200000)return reply({error:'Enter a title and a valid intake type.'},400);
   const original_url=body.url?canonicalUrl(String(body.url)):null;if(kind!=='tip'&&!original_url)return reply({error:'Add the original story URL.'},400);
   const r=await db.from('incoming_stories').insert({workspace_id:w.id,title,original_url,original_text,original_payload:{title,url:original_url,text:original_text},intake_kind:kind,fingerprint:await fingerprint(original_url??title+'\n'+original_text)});
   if(r.error?.code==='23505')return reply({message:'This item is already in the queue.'});if(r.error)throw new Error('Could not save the story.');return reply({message:'Added to the incoming queue.'});
  }
  return reply({error:'Unknown action.'},400);
 }catch(e){return reply({error:e instanceof Error?e.message:'Discovery request failed.'},400);}
}
