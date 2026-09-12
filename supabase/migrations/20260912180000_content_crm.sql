begin;
alter table public.wordpress_deliveries add constraint delivery_workspace_unique unique(workspace_id,id);
create table public.content_management (
 delivery_id uuid primary key, workspace_id uuid not null,
 risk_level text, risk_notes text,
 version integer not null default 1, headline text not null, body text not null,
 remote_fingerprint text, remote_modified text, remote_status text,
 checked_at timestamptz, check_message text not null default '',
 operation uuid, operation_started timestamptz, pending_headline text, pending_body text,
 update_state text not null default 'idle' check(update_state in ('idle','updating','uncertain')),
 updated_at timestamptz not null default now(),
 foreign key(workspace_id,delivery_id) references public.wordpress_deliveries(workspace_id,id)
);
create table public.content_followups (
 id uuid primary key default gen_random_uuid(),workspace_id uuid not null,delivery_id uuid not null,
 task text not null check(length(trim(task)) between 1 and 2000),due_at timestamptz not null,
 completed boolean not null default false,created_at timestamptz not null default now(),
 foreign key(workspace_id,delivery_id) references public.wordpress_deliveries(workspace_id,id)
);
do $$declare t text;begin
 foreach t in array array['content_management','content_followups'] loop
 execute format('alter table public.%I enable row level security',t);
 execute format('revoke all on public.%I from public,anon,authenticated',t);
 execute format('grant select on public.%I to authenticated',t);
 execute format('create policy members_read on public.%I for select to authenticated using(newsroom_private.has_role(workspace_id,array[''owner'',''editor'',''reviewer'',''viewer'']))',t);
 execute format('create trigger audit_changes after insert or update on public.%I for each row execute function newsroom_private.audit_change()',t);
 end loop;
end;$$;
grant insert(workspace_id,delivery_id,task,due_at),update(task,due_at,completed) on public.content_followups to authenticated;
create policy writers_insert on public.content_followups for insert to authenticated with check(newsroom_private.has_role(workspace_id,array['owner','editor']));
create policy writers_update on public.content_followups for update to authenticated using(newsroom_private.has_role(workspace_id,array['owner','editor'])) with check(newsroom_private.has_role(workspace_id,array['owner','editor']));
create function public.content_manage(p_delivery uuid,p_action text,p_values jsonb default '{}'::jsonb) returns public.content_management language plpgsql security definer set search_path='' as $$
declare d public.wordpress_deliveries; m public.content_management;
begin
 select * into d from public.wordpress_deliveries where id=p_delivery;
 if d.id is null or d.state<>'publish' or not newsroom_private.has_role(d.workspace_id,array['owner']) then raise exception 'Owner and published article required';end if;
 insert into public.content_management(delivery_id,workspace_id,headline,body) values(d.id,d.workspace_id,d.headline,d.body) on conflict do nothing;
 select * into m from public.content_management where delivery_id=d.id for update;
 if p_action='check' then
  if m.update_state='updating' then raise exception 'Update still active';end if;
  update public.content_management set remote_fingerprint=case when remote_fingerprint is null or p_values->>'accept'='true' then p_values->>'fingerprint' else remote_fingerprint end,remote_modified=p_values->>'modified',remote_status=p_values->>'status',checked_at=now(),check_message=left(p_values->>'message',1000) where delivery_id=d.id;
 elsif p_action='reserve' then
  if m.update_state<>'idle' or m.version is distinct from (p_values->>'version')::int or m.remote_fingerprint is null or m.remote_fingerprint is distinct from p_values->>'fingerprint' then raise exception 'Article changed or update unresolved';end if;
  if length(trim(p_values->>'headline')) not between 1 and 500 or length(p_values->>'body')>100000 or coalesce(p_values->>'checks','false')<>'true' then raise exception 'Article and editorial checks required';end if;
  update public.content_management set operation=gen_random_uuid(),operation_started=now(),pending_headline=p_values->>'headline',pending_body=p_values->>'body',update_state='updating',risk_level=p_values->>'risk',risk_notes=p_values->>'notes' where delivery_id=d.id;
 elsif p_action in ('finish','uncertain','cancel') then
  if m.update_state<>'updating' or m.operation is distinct from (p_values->>'operation')::uuid then raise exception 'Update token mismatch';end if;
  if p_action='finish' then
   update public.content_management set headline=pending_headline,body=pending_body,pending_headline=null,pending_body=null,version=version+1,update_state='idle',operation=null,remote_fingerprint=p_values->>'fingerprint',remote_modified=p_values->>'modified',remote_status=p_values->>'status',checked_at=now(),check_message='Update confirmed by WordPress.',updated_at=now() where delivery_id=d.id;
  elsif p_action='cancel' then
   update public.content_management set update_state='idle',operation=null,pending_headline=null,pending_body=null,check_message='WordPress changed before the update. Check the live article.' where delivery_id=d.id;
  else update public.content_management set update_state='uncertain',check_message='Update outcome uncertain. Check and reconcile before retrying.' where delivery_id=d.id;end if;
 elsif p_action='reconcile' then
  if m.update_state='updating' and m.operation_started>now()-interval '2 minutes' then raise exception 'Update still active';end if;
  if m.update_state='idle' then raise exception 'No update to reconcile';end if;
  update public.content_management set headline=case when p_values->>'applied'='true' then pending_headline else headline end,body=case when p_values->>'applied'='true' then pending_body else body end,version=version+1,update_state='idle',operation=null,pending_headline=null,pending_body=null,remote_fingerprint=p_values->>'fingerprint',remote_modified=p_values->>'modified',remote_status=p_values->>'status',checked_at=now(),check_message=left(p_values->>'message',1000),updated_at=now() where delivery_id=d.id;
 else raise exception 'Invalid action';end if;
 select * into m from public.content_management where delivery_id=d.id;return m;
end;$$;
revoke all on function public.content_manage(uuid,text,jsonb) from public,anon;
grant execute on function public.content_manage(uuid,text,jsonb) to authenticated;
insert into newsroom_private.applied_migrations(version) values('20260912180000_content_crm');
commit;
