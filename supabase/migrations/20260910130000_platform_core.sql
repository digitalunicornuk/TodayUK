-- Sprint 1 foundation. Apply through the migration workflow after permission tests.
begin;
create schema if not exists newsroom_private;
revoke all on schema newsroom_private from public;
grant usage on schema newsroom_private to authenticated;
create table public.workspaces (
 id uuid primary key default gen_random_uuid(), slug text not null unique,
 name text not null, created_at timestamptz not null default now()
);
create table public.workspace_members (
 workspace_id uuid not null references public.workspaces(id),
 user_id uuid not null references auth.users(id),
 role text not null check(role in ('owner','editor','reviewer','viewer')),
 created_at timestamptz not null default now(), primary key(workspace_id,user_id)
);
create index workspace_members_user_idx on public.workspace_members(user_id);
create function newsroom_private.has_role(w uuid, roles text[]) returns boolean
language sql stable security definer set search_path = '' as $$
 select exists(select 1 from public.workspace_members m where m.workspace_id=w and m.user_id=(select auth.uid()) and m.role=any(roles));
$$;
revoke all on function newsroom_private.has_role(uuid,text[]) from public;
grant execute on function newsroom_private.has_role(uuid,text[]) to authenticated;
create table public.hierarchy_nodes (
 id uuid primary key default gen_random_uuid(), workspace_id uuid not null references public.workspaces(id),
 parent_id uuid, kind text not null, code text not null, name text not null,
 created_at timestamptz not null default now(), unique(workspace_id,id),unique(workspace_id,code),
 foreign key(workspace_id,parent_id) references public.hierarchy_nodes(workspace_id,id),check(parent_id is distinct from id)
);
create table public.tags (
 id uuid primary key default gen_random_uuid(),workspace_id uuid not null references public.workspaces(id),
 category text not null check(category in ('publication','district','locality','council','story_type','topic','entity','organisation','risk','advertising_context')),
 label text not null, created_at timestamptz not null default now(),unique(workspace_id,id),unique(workspace_id,category,label)
);
create table public.node_tags (
 workspace_id uuid not null references public.workspaces(id),node_id uuid not null,tag_id uuid not null,
 signal text not null check(signal in ('factual','audience','context')),
 primary key(workspace_id,node_id,tag_id,signal),
 foreign key(workspace_id,node_id) references public.hierarchy_nodes(workspace_id,id),
 foreign key(workspace_id,tag_id) references public.tags(workspace_id,id)
);
create table public.module_config (
 workspace_id uuid not null references public.workspaces(id),module_key text not null,
 enabled boolean not null default false,settings jsonb not null default '{}'::jsonb,
 primary key(workspace_id,module_key),check(jsonb_typeof(settings)='object')
);
create table public.audit_events (
 id uuid primary key default gen_random_uuid(),workspace_id uuid not null references public.workspaces(id),
 actor_id uuid,entity_table text not null,action text not null,record_before jsonb,record_after jsonb,
 created_at timestamptz not null default now()
);
create index audit_workspace_time_idx on public.audit_events(workspace_id,created_at desc);
create function newsroom_private.audit_change() returns trigger language plpgsql security definer set search_path = '' as $$
declare before_record jsonb; after_record jsonb; w uuid;
begin
 if TG_OP <> 'INSERT' then before_record=to_jsonb(OLD); end if;
 if TG_OP <> 'DELETE' then after_record=to_jsonb(NEW); end if;
 w=coalesce((after_record->>'workspace_id')::uuid,(before_record->>'workspace_id')::uuid);
 insert into public.audit_events(workspace_id,actor_id,entity_table,action,record_before,record_after)
 values(w,auth.uid(),TG_TABLE_NAME,TG_OP,before_record,after_record);
 return coalesce(NEW,OLD);
end;$$;
revoke all on function newsroom_private.audit_change() from public;
-- Membership management is deliberately backend-only; no client can self-enrol or promote.
do $$declare t text;begin
 foreach t in array array['workspaces','workspace_members','hierarchy_nodes','tags','node_tags','module_config','audit_events'] loop
 execute format('alter table public.%I enable row level security',t);
 execute format('revoke all on public.%I from anon, authenticated',t);
 execute format('grant select on public.%I to authenticated',t);
 if t='workspaces' then
 execute format('create policy member_read on public.%I for select to authenticated using (newsroom_private.has_role(id,array[''owner'',''editor'',''reviewer'',''viewer'']))',t);
 else
 execute format('create policy member_read on public.%I for select to authenticated using (newsroom_private.has_role(workspace_id,array[''owner'',''editor'',''reviewer'',''viewer'']))',t);
 end if;
 end loop;
 foreach t in array array['hierarchy_nodes','tags','node_tags'] loop
 execute format('grant insert, update on public.%I to authenticated',t);
 execute format('create policy editor_insert on public.%I for insert to authenticated with check (newsroom_private.has_role(workspace_id,array[''owner'',''editor'']))',t);
 execute format('create policy editor_update on public.%I for update to authenticated using (newsroom_private.has_role(workspace_id,array[''owner'',''editor''])) with check (newsroom_private.has_role(workspace_id,array[''owner'',''editor'']))',t);
 end loop;
 foreach t in array array['workspace_members','hierarchy_nodes','tags','node_tags','module_config'] loop
 execute format('create trigger audit_changes after insert or update or delete on public.%I for each row execute function newsroom_private.audit_change()',t);
 end loop;
end;$$;
-- Clients cannot alter/delete audits, memberships or module switches.
commit;
