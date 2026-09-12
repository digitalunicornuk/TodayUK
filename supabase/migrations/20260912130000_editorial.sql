begin;
create table public.editorial_drafts (
 id uuid primary key default gen_random_uuid(), workspace_id uuid not null references public.workspaces(id),
 group_id uuid not null, headline text not null check(length(headline) between 1 and 500),
 body text not null default '' check(length(body)<=100000),
 risk_level text not null default 'unassessed' check(risk_level in ('unassessed','standard','sensitive','high')),
 risk_notes text not null default '' check(length(risk_notes)<=4000),
 facts_checked boolean not null default false, harm_checked boolean not null default false,
 rights_checked boolean not null default false,
 status text not null default 'draft' check(status in ('draft','in_review','changes_requested','approved')),
 review_notes text not null default '' check(length(review_notes)<=4000),
 revision integer not null default 1,
 created_by uuid not null default auth.uid() references auth.users(id),
 reviewed_by uuid references auth.users(id), reviewed_at timestamptz,
 created_at timestamptz not null default now(), updated_at timestamptz not null default now(),
 foreign key(workspace_id,group_id) references public.story_groups(workspace_id,id)
);
alter table public.editorial_drafts enable row level security;
revoke all on public.editorial_drafts from public,anon,authenticated;
grant select on public.editorial_drafts to authenticated;
grant insert(workspace_id,group_id,headline,body) on public.editorial_drafts to authenticated;
grant update(headline,body,risk_level,risk_notes,facts_checked,harm_checked,rights_checked,status,review_notes) on public.editorial_drafts to authenticated;
create policy members_read on public.editorial_drafts for select to authenticated using(newsroom_private.has_role(workspace_id,array['owner','editor','reviewer','viewer']));
create policy writers_insert on public.editorial_drafts for insert to authenticated with check(newsroom_private.has_role(workspace_id,array['owner','editor']) and created_by=auth.uid());
create policy editors_update on public.editorial_drafts for update to authenticated using(newsroom_private.has_role(workspace_id,array['owner','editor','reviewer'])) with check(newsroom_private.has_role(workspace_id,array['owner','editor','reviewer']));
create function newsroom_private.guard_editorial() returns trigger language plpgsql set search_path='' as $$
begin
 if (new.headline,new.body,new.risk_level,new.risk_notes,new.facts_checked,new.harm_checked,new.rights_checked) is distinct from (old.headline,old.body,old.risk_level,old.risk_notes,old.facts_checked,old.harm_checked,old.rights_checked) then
  if not newsroom_private.has_role(new.workspace_id,array['owner','editor']) then raise exception 'Only writers can change draft content';end if;
  if old.status not in ('draft','changes_requested') or new.status<>old.status then raise exception 'Reopen before editing';end if;
 end if;
 if new.status<>old.status then
  if new.status='draft' then
   if not newsroom_private.has_role(new.workspace_id,array['owner','editor']) then raise exception 'Only writers can reopen';end if;
   new.reviewed_by=null;new.reviewed_at=null;new.review_notes='';
  elsif new.status='in_review' and old.status in ('draft','changes_requested') then
   if not newsroom_private.has_role(new.workspace_id,array['owner','editor']) then raise exception 'Only writers submit';end if;
   if length(trim(new.body))=0 or new.risk_level='unassessed' or not(new.facts_checked and new.harm_checked and new.rights_checked) or length(trim(new.risk_notes))=0 then raise exception 'Complete the editorial checks before review';end if;
   new.reviewed_by=null;new.reviewed_at=null;new.review_notes='';
  elsif new.status in ('approved','changes_requested') and old.status='in_review' then
   if not newsroom_private.has_role(new.workspace_id,array['owner','reviewer']) then raise exception 'Reviewer role required';end if;
   if new.risk_level='high' and new.status='approved' then raise exception 'High-risk drafts remain blocked pending specialist clearance';end if;
   if length(trim(new.review_notes))=0 then raise exception 'A review note is required';end if;
   new.reviewed_by=auth.uid();new.reviewed_at=now();
  else raise exception 'Invalid editorial transition';end if;
 elsif new.review_notes<>old.review_notes then raise exception 'Review notes require a review decision';
 end if;
 new.revision=old.revision+1;new.updated_at=now();return new;
end;$$;
create trigger editorial_guard before update on public.editorial_drafts for each row execute function newsroom_private.guard_editorial();
create trigger audit_changes after insert or update or delete on public.editorial_drafts for each row execute function newsroom_private.audit_change();
insert into newsroom_private.applied_migrations(version) values('20260912130000_editorial');
commit;
