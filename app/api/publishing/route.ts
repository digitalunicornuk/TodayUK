import {articleLengthError} from '@/lib/editorial/article';
import {newsroomClient} from '@/lib/supabase/server';
import {WordPressClient,wordpressConfig} from '@/lib/wordpress/client';
const reply=(data:unknown,status=200)=>Response.json(data,{status,headers:{'Cache-Control':'private, no-store'}});
async function context(){const db=await newsroomClient();if(!db)return null;const {data:{user}}=await db.auth.getUser();if(!user)return null;const {data:w}=await db.from('workspaces').select('id').eq('slug','todayuk').single();if(!w)return null;const {data:m}=await db.from('workspace_members').select('role').eq('workspace_id',w.id).eq('user_id',user.id).single();return {db,w,role:m?.role};}
export async function GET(){const c=await context();if(!c)return reply({error:'Sign in with an approved newsroom account.'},401);let config;try{config=wordpressConfig();}catch{return reply({error:'WordPress connection settings need attention.'},503);}const r=await c.db.from('wordpress_deliveries').select('*').eq('workspace_id',c.w.id).order('created_at',{ascending:false}).limit(200);if(r.error)return reply({error:'Publishing records are not available yet.'},503);return reply({configured:!!config,destination:config?.url??null,deliveries:r.data,role:c.role});}
export async function POST(request:Request){
 if(request.headers.get('origin')!=='https://todayuk-newsroom.digitalunicorn.chatgpt.site')return reply({error:'Request origin not allowed.'},403);
 const c=await context();if(!c||c.role!=='owner')return reply({error:'Only the workspace owner can send articles to WordPress.'},403);
 try{const raw=await request.text();if(raw.length>2000)return reply({error:'Invalid request.'},413);const b=JSON.parse(raw),config=wordpressConfig();if(!config)return reply({error:'Connect the WordPress destination first.'},409);const wp=new WordPressClient(config);
 if(b.action==='check'){await wp.check();return reply({message:'WordPress account connection succeeded.'});}
 if(!['send','publish'].includes(b.action)||!Number.isInteger(b.revision))return reply({error:'Invalid publishing action.'},400);
 const {data:draft,error:draftError}=await c.db.from('editorial_drafts').select('body').eq('workspace_id',c.w.id).eq('id',b.id).eq('revision',b.revision).single();if(draftError||!draft)return reply({error:'This draft changed. Refresh before publishing.'},409);const lengthError=articleLengthError(draft.body);if(lengthError)return reply({error:lengthError},409);
 const reserved=await c.db.rpc('reserve_wordpress_delivery',{p_draft:b.id,p_revision:b.revision,p_action:b.action});if(reserved.error)return reply({error:'Could not reserve this approved revision. A transfer may already exist. Refresh the publishing desk.'},409);
 const delivery=reserved.data;let post;
 try{post=b.action==='send'?await wp.createDraft(delivery.draft_id,delivery.headline,delivery.body):await wp.publish(delivery.wordpress_id);}catch(e){await c.db.rpc('finish_wordpress_delivery',{p_id:delivery.id,p_state:'uncertain',p_post:null,p_url:null,p_remote:null,p_message:'Transfer outcome needs checking in WordPress. Do not resend.'});throw e;}
 const state=post.status==='publish'?'publish':post.status==='draft'?'draft':'uncertain';
 const completed=await c.db.rpc('finish_wordpress_delivery',{p_id:delivery.id,p_state:state,p_post:post.id,p_url:post.link,p_remote:post.status,p_message:state==='uncertain'?'Check the returned WordPress status.':'WordPress confirmed the result.'});
 if(completed.error)return reply({error:'WordPress responded, but the newsroom record could not be updated. Check WordPress before taking another action.'},409);
 return reply({message:state==='publish'?'Published on WordPress.':state==='draft'?'Saved as a WordPress draft.':'WordPress returned a status that needs review.'});
 }catch(e){return reply({error:e instanceof Error?e.message:'Publishing request failed.'},400);}
}
