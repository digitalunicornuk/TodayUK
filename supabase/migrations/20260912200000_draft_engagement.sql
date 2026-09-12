begin;
alter table public.editorial_drafts add column engagement jsonb check(engagement is null or (jsonb_typeof(engagement)='object' and engagement->>'mode' in ('custom','off') and octet_length(engagement::text)<4000));
alter table public.wordpress_deliveries add column engagement jsonb;
grant update(engagement) on public.editorial_drafts to authenticated;
create or replace function newsroom_private.guard_editorial() returns trigger language plpgsql set search_path='' as $$
begin
 if (new.engagement,new.headline,new.body,new.risk_level,new.risk_notes,new.facts_checked,new.harm_checked,new.rights_checked) is distinct from (old.engagement,old.headline,old.body,old.risk_level,old.risk_notes,old.facts_checked,old.harm_checked,old.rights_checked) then
  if not newsroom_private.has_role(new.workspace_id,array['owner','editor']) then raise exception 'Only writers can change draft content';end if;
  if old.status not in ('draft','changes_requested') or new.status<>old.status then raise exception 'Reopen before editing';end if;
 end if;
 if new.status<>old.status then
  if new.status='draft' then
   if not newsroom_private.has_role(new.workspace_id,array['owner','editor']) then raise exception 'Only writers can reopen';end if;
   new.reviewed_by=null;new.reviewed_at=null;new.review_notes='';
  elsif new.status='in_review' and old.status in ('draft','changes_requested') then
   if not newsroom_private.has_role(new.workspace_id,array['owner','editor']) then raise exception 'Only writers submit';end if;
   if new.engagement is null then raise exception 'Approve the reader engagement choice before review';end if;
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
create or replace function public.reserve_wordpress_delivery(p_draft uuid,p_revision integer,p_action text) returns public.wordpress_deliveries language plpgsql security definer set search_path='' as $$
declare d public.editorial_drafts;v public.wordpress_deliveries;
begin
 select * into d from public.editorial_drafts where id=p_draft for update;
 if d.id is null or not newsroom_private.has_role(d.workspace_id,array['owner']) then raise exception 'Owner access required';end if;
 if d.status<>'approved' or d.revision<>p_revision then raise exception 'This exact revision must be approved';end if;
 if p_action='send' then
  insert into public.wordpress_deliveries(workspace_id,draft_id,draft_revision,headline,body,engagement,state,actor_id) values(d.workspace_id,d.id,d.revision,d.headline,d.body,d.engagement,'sending',auth.uid()) returning * into v;
 elsif p_action='publish' then
  select * into v from public.wordpress_deliveries where draft_id=d.id for update;
  if v.id is null or v.state<>'draft' or v.wordpress_id is null or v.draft_revision<>d.revision then raise exception 'A matching WordPress draft is required';end if;
  update public.wordpress_deliveries set state='publishing',actor_id=auth.uid(),updated_at=now() where id=v.id returning * into v;
 else raise exception 'Invalid delivery action';end if;
 return v;
end;$$;
insert into newsroom_private.applied_migrations(version) values('20260912200000_draft_engagement');
commit;
