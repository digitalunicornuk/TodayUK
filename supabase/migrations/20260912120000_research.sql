-- Research records remain separate from publication approvals.
begin;
create table public.story_groups (
 id uuid primary key default gen_random_uuid(),
 workspace_id uuid not null references public.workspaces(id),
 title text not null check(length(title) between 1 and 500),
 created_at timestamptz not null default now(),
 unique(workspace_id,id)
);
create table public.story_group_items (
 workspace_id uuid not null references public.workspaces(id),
 group_id uuid not null,
 story_id uuid not null references public.incoming_stories(id),
 created_at timestamptz not null default now(),
 primary key(workspace_id,group_id,story_id),
 foreign key(workspace_id,group_id) references public.story_groups(workspace_id,id)
);
alter table public.incoming_stories add constraint incoming_workspace_id_unique unique(workspace_id,id);
alter table public.story_group_items add constraint group_story_workspace_fk foreign key(workspace_id,story_id) references public.incoming_stories(workspace_id,id);
create table public.research_claims (
 id uuid primary key default gen_random_uuid(),
 workspace_id uuid not null references public.workspaces(id),
 group_id uuid not null,
 claim text not null check(length(claim) between 1 and 4000),
 created_at timestamptz not null default now(),
 unique(workspace_id,id),
 foreign key(workspace_id,group_id) references public.story_groups(workspace_id,id)
);
create table public.claim_evidence (
 id uuid primary key default gen_random_uuid(),
 workspace_id uuid not null references public.workspaces(id),
 claim_id uuid not null,
 source_url text not null check(source_url ~ '^https?://'),
 publisher text not null check(length(publisher) between 1 and 200),
 excerpt text not null check(length(excerpt) between 1 and 10000),
 relationship text not null check(relationship in ('supports','contradicts','context')),
 created_at timestamptz not null default now(),
 foreign key(workspace_id,claim_id) references public.research_claims(workspace_id,id)
);
do $$declare t text;begin
 foreach t in array array['story_groups','story_group_items','research_claims','claim_evidence'] loop
 execute format('alter table public.%I enable row level security',t);
 execute format('revoke all on public.%I from public,anon,authenticated',t);
 execute format('grant select,insert on public.%I to authenticated',t);
 execute format('create policy member_read on public.%I for select to authenticated using(newsroom_private.has_role(workspace_id,array[''owner'',''editor'',''reviewer'',''viewer'']))',t);
 execute format('create policy researcher_insert on public.%I for insert to authenticated with check(newsroom_private.has_role(workspace_id,array[''owner'',''editor'',''reviewer'']))',t);
 execute format('create trigger audit_changes after insert or update or delete on public.%I for each row execute function newsroom_private.audit_change()',t);
 end loop;
end;$$;
grant delete on public.story_group_items to authenticated;
create policy researcher_unlink on public.story_group_items for delete to authenticated using(newsroom_private.has_role(workspace_id,array['owner','editor','reviewer']));
alter table public.story_groups enable row level security;
alter table public.story_group_items enable row level security;
alter table public.research_claims enable row level security;
alter table public.claim_evidence enable row level security;
insert into newsroom_private.applied_migrations(version) values('20260912120000_research');
commit;
