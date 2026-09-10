'use client';
import {useState,useMemo} from 'react';
import {createBrowserClient} from '@supabase/ssr';
import {Button} from '@/components/ui/button';
import {Input} from '@/components/ui/input';
export default function SignIn({url,publishableKey}:{url:string;publishableKey:string}){
 const db=useMemo(()=>url&&publishableKey?createBrowserClient(url,publishableKey):null,[url,publishableKey]);
 const [email,setEmail]=useState('');const [message,setMessage]=useState('');const [busy,setBusy]=useState(false);
 async function submit(event:React.FormEvent){event.preventDefault();if(!db||busy)return;setBusy(true);setMessage('');
 try{const {error}=await db.auth.signInWithOtp({email:email.trim(),options:{shouldCreateUser:true,emailRedirectTo:'https://todayuk-newsroom.digitalunicorn.chatgpt.site/auth/callback'}});setMessage(error?'The sign-in email could not be sent. Please try again shortly.':'Check your email for a secure sign-in link. Open it in this browser.');}catch{setMessage('The connection failed. Please retry.')}finally{setBusy(false)}}
 return <main className="desk" style={{maxWidth:620,paddingTop:80}}><a href="/" className="brand">TODAY<span style={{color:'#b52639'}}>UK</span></a><section className="panel mt-8"><p className="eyebrow">CR News</p><h1>Newsroom sign-in</h1><p className="muted mb-6">Use the email address assigned to your newsroom membership. Signing in does not grant editorial access by itself.</p><form onSubmit={submit}><label htmlFor="email">Email address</label><Input id="email" type="email" autoComplete="email" required value={email} onChange={e=>setEmail(e.target.value)} className="my-3"/><Button disabled={!db||busy} type="submit">{busy?'Sending…':'Email me a sign-in link'}</Button></form>{!db&&<p className="note">The newsroom connection is being configured.</p>}<p role="status" className="mt-5">{message}</p><a className="inline-block mt-6" href="/live">Already signed in? Open the newsroom →</a></section></main>
}
