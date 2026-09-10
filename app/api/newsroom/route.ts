import {newsroomClient} from '@/lib/supabase/server';
const respond=(data:unknown,status=200)=>Response.json(data,{status,headers:{'Cache-Control':'private, no-store'}});
export async function GET(){
 const db=await newsroomClient();if(!db)return respond({error:'Newsroom connection is being configured.'},503);
 const {data:{user},error}=await db.auth.getUser();
 if(error||!user)return respond({error:'Sign in to your newsroom account.'},401);
 const {data:workspaces,error:workspaceError}=await db.from('workspaces').select('id,name,slug').eq('slug','todayuk');
 if(workspaceError)return respond({error:'Could not load your workspace. Please retry.'},502);
 const workspace=workspaces?.[0];if(!workspace)return respond({error:'Your account is verified. Newsroom membership is awaiting approval.'},403);
 const results=await Promise.all([
 db.from('hierarchy_nodes').select('id,parent_id,kind,code,name').eq('workspace_id',workspace.id).order('code'),
 db.from('tags').select('id,category,label').eq('workspace_id',workspace.id).order('label'),
 db.from('workspace_members').select('role').eq('workspace_id',workspace.id).eq('user_id',user.id).single(),
 db.from('audit_events').select('id,entity_table,action,created_at').eq('workspace_id',workspace.id).order('created_at',{ascending:false}).limit(10)
 ]);
 if(results.some(r=>r.error))return respond({error:'Could not load the complete newsroom. Please retry.'},502);
 return respond({workspace,nodes:results[0].data,tags:results[1].data,membership:results[2].data,audits:results[3].data});
}
