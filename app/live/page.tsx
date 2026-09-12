'use client';
import {useEffect,useState} from 'react';
import Discovery from './discovery';
import NewsroomNavigation from '@/components/newsroom/navigation';
import {Button} from '@/components/ui/button';
type Data={workspace:{name:string};nodes:{id:string;code:string;name:string;kind:string}[];tags:{id:string;label:string;category:string}[];membership:{role:string};audits:{id:string;entity_table:string;action:string;created_at:string}[]};
export default function Live(){const [data,setData]=useState<Data|null>(null),[error,setError]=useState(''),[busy,setBusy]=useState(true);
 async function refresh(){setBusy(true);setError('');try{const r=await fetch('/api/newsroom',{cache:'no-store'});const d=await r.json() as Data & {error?:string};if(!r.ok){setData(null);setError(d.error??'Could not load the newsroom.')}else setData(d)}catch{setData(null);setError('Could not reach the newsroom. Please retry.')}finally{setBusy(false)}}
 useEffect(()=>{void refresh()},[]);
 return <><NewsroomNavigation active="stories"/><main className="desk">{error&&<section className="panel"><p role="alert">{error}</p><a className="pill inline-block mt-4" href="/signin">Sign in to the newsroom →</a><Button onClick={refresh} disabled={busy}>Try again</Button></section>}{busy&&!data&&!error&&<p>Loading stories…</p>}{data&&<Discovery/>}</main></>;
}
