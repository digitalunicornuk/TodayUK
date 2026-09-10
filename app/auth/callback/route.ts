import {newsroomClient} from '@/lib/supabase/server';
const origin='https://todayuk-newsroom.digitalunicorn.chatgpt.site';
export async function GET(request:Request){
 const code=new URL(request.url).searchParams.get('code');
 const db=await newsroomClient();
 if(code&&db){const {error}=await db.auth.exchangeCodeForSession(code);if(!error)return Response.redirect(origin+'/live',303);}
 return Response.redirect(origin+'/signin?error=expired',303);
}
