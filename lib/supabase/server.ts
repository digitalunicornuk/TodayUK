import {createServerClient} from '@supabase/ssr';
import {cookies} from 'next/headers';
export async function newsroomClient(){
 const jar=await cookies();
 const url=process.env.SUPABASE_URL;
 const key=process.env.SUPABASE_PUBLISHABLE_KEY;
 if(!url||!key) return null;
 return createServerClient(url,key,{cookies:{getAll:()=>jar.getAll(),setAll(values){for(const {name,value,options} of values) jar.set(name,value,{...options,secure:true,sameSite:'lax'});}}});
}
