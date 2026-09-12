'use client';
import {useEffect,useState} from 'react';
import NewsroomNavigation from '@/components/newsroom/navigation';
type Published={id:string;draft_id:string;headline:string;state:string;wordpress_url:string|null;updated_at:string};
export default function Published(){const [stories,setStories]=useState<Published[]>([]),[loaded,setLoaded]=useState(false),[error,setError]=useState('');
 async function load(){setError('');try{const r=await fetch('/api/publishing',{cache:'no-store'}),data=await r.json() as {deliveries:Published[];error?:string};if(!r.ok)throw Error(data.error);setStories(data.deliveries.filter(d=>d.state==='publish'));setLoaded(true);}catch(e){setError(e instanceof Error?e.message:'Could not load published stories.');}}
 useEffect(()=>{void load();},[]);
 return <><NewsroomNavigation active="published"/><main className="desk"><div className="intro"><div><h1>Published</h1><p>Your stories confirmed live on WordPress.</p></div><button onClick={()=>void load()}>Refresh</button></div>{error&&<p role="alert" className="note">{error}</p>}{!loaded&&!error&&<p>Loading published stories…</p>}{loaded&&!stories.length&&<div className="panel"><h2>No published stories yet</h2><a href="/editorial">Open your drafts →</a></div>}<div className="story-grid">{stories.map(s=><article className="panel" key={s.id}><span className="pill">Published</span><h2 className="my-4">{s.headline}</h2><p className="muted">{new Date(s.updated_at).toLocaleString('en-GB')}</p><p className="mt-4">{s.wordpress_url&&<a href={s.wordpress_url} target="_blank" rel="noreferrer">View live article ↗</a>}</p><a href={'/editorial?draft='+encodeURIComponent(s.draft_id)}>View saved article →</a></article>)}</div><p className="mt-6"><a href="/live">Choose your next story →</a></p></main></>;
}
