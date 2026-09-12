import {newsroomClient} from '@/lib/supabase/server';
const reply=(data:unknown,status=200)=>Response.json(data,{status,headers:{'Cache-Control':'private, no-store'}});
async function context(){const db=await newsroomClient();if(!db)return null;const {data:{user}}=await db.auth.getUser();if(!user)return null;const {data:w}=await db.from('workspaces').select('id').eq('slug','todayuk').single();if(!w)return null;const {data:m}=await db.from('workspace_members').select('role').eq('workspace_id',w.id).eq('user_id',user.id).single();return {db,w,role:m?.role};}
export async function GET(){const c=await context();if(!c)return reply({error:'Sign in with an approved newsroom account.'},401);const r=await c.db.from('editorial_drafts').select('*').eq('workspace_id',c.w.id).order('updated_at',{ascending:false}).limit(200);if(r.error)return reply({error:'Editorial desk is unavailable.'},503);return reply({drafts:r.data,role:c.role});}
export async function POST(request:Request){
 if(request.headers.get('origin')!=='https://todayuk-newsroom.digitalunicorn.chatgpt.site')return reply({error:'Request origin not allowed.'},403);
 const c=await context();if(!c)return reply({error:'Sign in with an approved newsroom account.'},401);
 try{const raw=await request.text();if(raw.length>120000)return reply({error:'Draft is too large.'},413);const b=JSON.parse(raw);let r;const {db,w}=c;
 if(b.action==='create')r=await db.from('editorial_drafts').insert({workspace_id:w.id,group_id:b.group_id,headline:b.headline,body:''}).select('id').single();
 else {let fields;
 if(b.action==='save')fields={headline:b.headline,body:b.body,risk_level:b.risk_level,risk_notes:b.risk_notes,facts_checked:b.facts_checked===true,harm_checked:b.harm_checked===true,rights_checked:b.rights_checked===true};
 else if(b.action==='transition'&&['draft','in_review','approved','changes_requested'].includes(b.status))fields={status:b.status,...(b.status==='approved'||b.status==='changes_requested'?{review_notes:String(b.review_notes??'')}: {})};
 else return reply({error:'Unknown editorial action.'},400);
 if(!Number.isInteger(b.revision))return reply({error:'Reload this draft before saving.'},400);
 r=await db.from('editorial_drafts').update(fields).eq('workspace_id',w.id).eq('id',b.id).eq('revision',b.revision).select('id').single();
 }
 if(r.error)return reply({error:r.error.code==='P0001'?r.error.message:'Could not save. Your role may not allow this action, or the draft changed. Refresh and try again.'},409);
 return reply({message:'Editorial record saved.',id:r.data?.id});
 }catch{return reply({error:'Invalid editorial submission.'},400);}
}
