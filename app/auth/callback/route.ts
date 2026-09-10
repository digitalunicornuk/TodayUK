import {createServerClient} from '@supabase/ssr';
import {NextRequest,NextResponse} from 'next/server';
const origin='https://todayuk-newsroom.digitalunicorn.chatgpt.site';
export async function GET(request:NextRequest){
 const params=new URL(request.url).searchParams;
 const code=params.get('code');
 const response=NextResponse.redirect(origin+'/live',303);
 response.headers.set('Cache-Control','private, no-store');
 const url=process.env.SUPABASE_URL;
 const key=process.env.SUPABASE_PUBLISHABLE_KEY;
 let failure='expired';
 if(code&&url&&key){
  const db=createServerClient(url,key,{cookies:{getAll:()=>request.cookies.getAll(),setAll(values){for(const {name,value,options} of values)response.cookies.set(name,value,{...options,secure:true,sameSite:'lax'});}}});
  try{
   const flowId=params.get('sb_flow_id');
   const {error}=await db.auth.exchangeCodeForSession(code,flowId?{flowId}:undefined);
   if(!error)return response;
   console.warn('Newsroom callback failed',{code:error.code,status:error.status});
   if(error.code==='flow_state_not_found'||error.code==='bad_code_verifier'||error.code==='pkce_verifier_not_found')failure='browser';
  }catch{failure='connection';console.warn('Newsroom callback connection failed');}
 }else if(!url||!key){failure='configuration';}
 response.headers.set('Location',origin+'/signin?error='+failure);
 return response;
}
