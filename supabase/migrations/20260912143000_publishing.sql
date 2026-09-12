begin;
create table public.wordpress_deliveries (
 id uuid primary key default gen_random_uuid(),workspace_id uuid not null references public.workspaces(id),
 draft_id uuid not null unique references public.editorial_drafts(id),draft_revision integer not null,
 headline text not null,body text not null,
 state text not null check(state in ('sending','draft','publishing','publish','uncertain')),
 wordpress_id bigint,wordpress_url text,remote_status text,
 message text not null default '',actor_id uuid not null references auth.users(id),
 created_at timestamptz not null default now(),updated_at timestamptz not null default now()
);
alter table public.wordpress_deliveries enable row level security;
revoke all on public.wordpress_deliveries from public,anon,authenticated;
grant select on public.wordpress_deliveries to authenticated;
create policy members_read on public.wordpress_deliveries for select to authenticated using(newsroom_private.has_role(workspace_id,array['owner','editor','reviewer','viewer']));
create trigger audit_changes after insert or update on public.wordpress_deliveries for each row execute function newsroom_private.audit_change();
create function public.reserve_wordpress_delivery(p_draft uuid,p_revision integer,p_action text) returns public.wordpress_deliveries language plpgsql security definer set search_path='' as $$
declare d public.editorial_drafts;v public.wordpress_deliveries;
begin
 select * into d from public.editorial_drafts where id=p_draft for update;
 if d.id is null or not newsroom_private.has_role(d.workspace_id,array['owner']) then raise exception 'Owner access required';end if;
 if d.status<>'approved' or d.revision<>p_revision then raise exception 'This exact revision must be approved';end if;
 if p_action='send' then
  insert into public.wordpress_deliveries(workspace_id,draft_id,draft_revision,headline,body,state,actor_id) values(d.workspace_id,d.id,d.revision,d.headline,d.body,'sending',auth.uid()) returning * into v;
 elsif p_action='publish' then
  select * into v from public.wordpress_deliveries where draft_id=d.id for update;
  if v.id is null or v.state<>'draft' or v.wordpress_id is null or v.draft_revision<>d.revision then raise exception 'A matching WordPress draft is required';end if;
  update public.wordpress_deliveries set state='publishing',actor_id=auth.uid(),updated_at=now() where id=v.id returning * into v;
 else raise exception 'Invalid delivery action';end if;
 return v;
end;$$;
create function public.finish_wordpress_delivery(p_id uuid,p_state text,p_post bigint,p_url text,p_remote text,p_message text) returns void language plpgsql security definer set search_path='' as $$
declare v public.wordpress_deliveries;
begin
 select * into v from public.wordpress_deliveries where id=p_id for update;
 if v.id is null or not newsroom_private.has_role(v.workspace_id,array['owner']) or v.actor_id<>auth.uid() then raise exception 'Owner access required';end if;
 if v.state not in ('sending','publishing') or p_state not in ('draft','publish','uncertain') then raise exception 'Invalid delivery outcome';end if;
 if p_state<>'uncertain' and (p_post is null or p_post<1 or p_url not like 'https://%') then raise exception 'Missing WordPress result';end if;
 update public.wordpress_deliveries set state=p_state,wordpress_id=coalesce(p_post,wordpress_id),wordpress_url=coalesce(p_url,wordpress_url),remote_status=p_remote,message=left(p_message,500),updated_at=now() where id=p_id;
end;$$;
revoke all on function public.reserve_wordpress_delivery(uuid,integer,text) from public,anon;
revoke all on function public.finish_wordpress_delivery(uuid,text,bigint,text,text,text) from public,anon;
grant execute on function public.reserve_wordpress_delivery(uuid,integer,text) to authenticated;
grant execute on function public.finish_wordpress_delivery(uuid,text,bigint,text,text,text) to authenticated;
create function newsroom_private.guard_active_delivery() returns trigger language plpgsql security definer set search_path='' as $$
begin
 if exists(select 1 from public.wordpress_deliveries where draft_id=old.id and state in ('sending','publishing')) then raise exception 'A WordPress transfer is active. Resolve it before changing this draft';end if;
 return new;
end;$$;
create trigger active_delivery_guard before update on public.editorial_drafts for each row execute function newsroom_private.guard_active_delivery();
insert into newsroom_private.applied_migrations(version) values('20260912143000_publishing');
commit;
