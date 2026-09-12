// Resume only confirmed drafts; never resend an uncertain transfer.
export async function publishApprovedDraft(id:string,revision:number,transport:typeof fetch=fetch){
 const response=await transport('/api/publishing',{cache:'no-store'});
 const current=await response.json() as {error?:string;deliveries:Array<{draft_id:string;draft_revision:number;state:string}>};
 if(!response.ok)throw Error(current.error||'Could not check WordPress transfers.');
 const delivery=current.deliveries.find((d:{draft_id:string})=>d.draft_id===id);
 if(delivery){
  if(delivery.draft_revision!==revision)throw Error('An earlier revision is already in WordPress. Open the publishing desk to check it.');
  if(delivery.state==='publish')return 'Already live on WordPress.';
  if(delivery.state!=='draft')throw Error('The previous transfer needs checking in WordPress. No duplicate was sent.');
 }
 const send=async(action:string)=>{const r=await transport('/api/publishing',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({action,id,revision})});const result=await r.json() as {error?:string;message:string};if(!r.ok)throw Error(result.error||'WordPress publishing failed.');return result;};
 if(!delivery)await send('send');
 return (await send('publish')).message as string;
}
