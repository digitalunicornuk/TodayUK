begin;
create table public.news_sources (
 id uuid primary key default gen_random_uuid(), workspace_id uuid not null references public.workspaces(id),
 name text not null check(length(name) between 1 and 160), feed_url text not null,
 trust text not null default 'unreviewed' check(trust in ('official','established','unreviewed')),
 enabled boolean not null default true, created_at timestamptz not null default now(),
 unique(workspace_id,id), unique(workspace_id,feed_url)
);
create table public.incoming_stories (
 id uuid primary key default gen_random_uuid(), workspace_id uuid not null references public.workspaces(id),source_id uuid,
 title text not null check(length(title) between 1 and 500), original_url text,
 original_text text not null check(length(original_text)<=200000), original_payload jsonb not null,
 fingerprint text not null, intake_kind text not null check(intake_kind in ('rss','web','social','tip')),
 status text not null default 'new' check(status in ('new','shortlisted','dismissed')),
 published_at timestamptz, received_at timestamptz not null default now(),
 unique(workspace_id,fingerprint),foreign key(workspace_id,source_id) references public.news_sources(workspace_id,id)
);
create index incoming_workspace_time on public.incoming_stories(workspace_id,received_at desc);
create table public.source_fetches (
 id uuid primary key default gen_random_uuid(),workspace_id uuid not null references public.workspaces(id),source_id uuid not null,
 fetched_at timestamptz not null default now(), added integer not null check(added>=0),duplicates integer not null check(duplicates>=0),
 outcome text not null check(outcome in ('success','error')),message text not null,
 foreign key(workspace_id,source_id) references public.news_sources(workspace_id,id)
);
do $$declare t text;begin
 foreach t in array array['news_sources','incoming_stories','source_fetches'] loop
 execute format('alter table public.%I enable row level security',t);
 execute format('revoke all on public.%I from public,anon,authenticated',t);
 execute format('grant select,insert on public.%I to authenticated',t);
 execute format('create policy members_read on public.%I for select to authenticated using(newsroom_private.has_role(workspace_id,array[''owner'',''editor'',''reviewer'',''viewer'']))',t);
 execute format('create policy owner_insert on public.%I for insert to authenticated with check(newsroom_private.has_role(workspace_id,array[''owner'',''editor'']) and exists(select 1 from public.module_config c where c.workspace_id=%I.workspace_id and c.module_key=''discovery'' and c.enabled))',t,t);
 execute format('create trigger audit_changes after insert or update on public.%I for each row execute function newsroom_private.audit_change()',t);
 end loop;
end;$$;
grant update(name,trust,enabled) on public.news_sources to authenticated;
create policy owner_update on public.news_sources for update to authenticated using(newsroom_private.has_role(workspace_id,array['owner'])) with check(newsroom_private.has_role(workspace_id,array['owner']));
grant update(status) on public.incoming_stories to authenticated;
create policy editorial_triage on public.incoming_stories for update to authenticated using(newsroom_private.has_role(workspace_id,array['owner','editor','reviewer'])) with check(newsroom_private.has_role(workspace_id,array['owner','editor','reviewer']));
-- Enable only the implemented discovery module; publication stays disabled.
update public.module_config set enabled=true where module_key='discovery' and workspace_id=(select id from public.workspaces where slug='todayuk');
insert into public.news_sources(workspace_id,name,feed_url,trust)
select id,'Croydon Council','https://news.croydon.gov.uk/feed/','official' from public.workspaces where slug='todayuk';
insert into newsroom_private.applied_migrations(version) values('20260910170000_discovery');
commit;
