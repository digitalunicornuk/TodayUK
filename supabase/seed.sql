begin;
insert into public.workspaces(id,slug,name) values('10000000-0000-4000-8000-000000000001','todayuk','TodayUK') on conflict(slug) do nothing;
insert into public.hierarchy_nodes(workspace_id,kind,code,name)
select id,'publication','CR','CR News' from public.workspaces where slug='todayuk' on conflict(workspace_id,code) do nothing;
insert into public.hierarchy_nodes(workspace_id,parent_id,kind,code,name)
select workspace_id,id,'postcode_district',d,d from public.hierarchy_nodes cross join unnest(array['CR0','CR2','CR3','CR5','CR6','CR7','CR8','CR9']) d where code='CR' and workspace_id=(select id from public.workspaces where slug='todayuk') on conflict(workspace_id,code) do nothing;
insert into public.tags(workspace_id,category,label)
select workspace_id,'district',code from public.hierarchy_nodes where kind='postcode_district' and workspace_id=(select id from public.workspaces where slug='todayuk') on conflict(workspace_id,category,label) do nothing;
insert into public.node_tags(workspace_id,node_id,tag_id,signal)
select n.workspace_id,n.id,t.id,'factual' from public.hierarchy_nodes n join public.tags t on n.workspace_id=t.workspace_id and n.code=t.label and t.category='district' where n.workspace_id=(select id from public.workspaces where slug='todayuk') on conflict do nothing;
insert into public.module_config(workspace_id,module_key,enabled)
select id,m,false from public.workspaces cross join unnest(array['discovery','publishing','engagement','contributors','quiz','social','ads','du_bridge']) m where slug='todayuk' on conflict do nothing;
commit;
