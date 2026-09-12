import {newsroomClient} from '@/lib/supabase/server';
import {canonicalUrl} from '@/lib/discovery/feed';
const reply=(data:unknown,status=200)=>Response.json(data,{status,headers:{'Cache-Control':'private, no-store'}});
async function context(){const db=await newsroomClient();if(!db)return null;const {data:{user}}=await db.auth.getUser();if(!user)return null;const {data:w}=await db.from('workspaces').select('id').eq('slug','todayuk').single();if(!w)return null;const {data:m}=await db.from('workspace_members').select('role').eq('workspace_id',w.id).eq('user_id',user.id).single();return {db,w,role:m?.role};}
export async function GET(){const c=await context();if(!c)return reply({error:'Sign in with an approved newsroom account.'},401);const tables=['story_groups','story_group_items','research_claims','claim_evidence'] as const;const results=await Promise.all(tables.map(t=>c.db.from(t).select('*').eq('workspace_id',c.w.id).order('created_at',{ascending:false}).limit(500)));if(results.some(r=>r.error))return reply({error:'Research is not available yet.'},503);return reply({groups:results[0].data,links:results[1].data,claims:results[2].data,evidence:results[3].data,role:c.role});}
export async function POST(request:Request){
 if(request.headers.get('origin')!=='https://todayuk-newsroom.digitalunicorn.chatgpt.site')return reply({error:'Request origin not allowed.'},403);
 const c=await context();if(!c)return reply({error:'Sign in with an approved newsroom account.'},401);if(!['owner','editor','reviewer'].includes(c.role))return reply({error:'Your role cannot edit research.'},403);
 try{
 const raw=await request.text();if(raw.length>16000)return reply({error:'Submission is too large.'},413);const b=JSON.parse(raw);const {db,w}=c;let r;
 if(b.action==='group'){const title=String(b.title??'').trim();if(!title||title.length>500)return reply({error:'Enter a group title (up to 500 characters).'},400);r=await db.from('story_groups').insert({workspace_id:w.id,title}).select('id').single();}
 else if(b.action==='link'){r=await db.from('story_group_items').insert({workspace_id:w.id,group_id:b.group_id,story_id:b.story_id});if(r.error?.code==='23505')return reply({message:'Story already linked.'});}
 else if(b.action==='unlink'){r=await db.from('story_group_items').delete().eq('workspace_id',w.id).eq('group_id',b.group_id).eq('story_id',b.story_id);}
 else if(b.action==='claim'){const claim=String(b.claim??'').trim();if(!claim||claim.length>4000)return reply({error:'Enter a claim (up to 4,000 characters).'},400);r=await db.from('research_claims').insert({workspace_id:w.id,group_id:b.group_id,claim});}
 else if(b.action==='evidence'){const publisher=String(b.publisher??'').trim(),excerpt=String(b.excerpt??'').trim();if(!publisher||publisher.length>200||!excerpt||excerpt.length>10000||!['supports','contradicts','context'].includes(b.relationship))return reply({error:'Enter a publisher, excerpt and evidence relationship.'},400);r=await db.from('claim_evidence').insert({workspace_id:w.id,claim_id:b.claim_id,publisher,excerpt,source_url:canonicalUrl(String(b.source_url)),relationship:b.relationship});}
 else return reply({error:'Unknown research action.'},400);
 if(r.error)return reply({error:'Could not save the research record. Check the selected group and story.'},400);
 return reply({message:'Research saved.',id:r.data?.id});
 }catch{return reply({error:'Invalid research submission. Check the fields and URL.'},400);}
}
