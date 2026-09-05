(() => {
  const c = window.auditLogTableConfig;
  if (!c) return;
  const $ = (id) => document.getElementById(id);
  const body = $('audit-log-table-body'), pager = $('audit-log-table-pagination'), summary = $('audit-log-table-summary');
  const searchForm = $('audit-log-search-form'), searchInput = $('audit-log-search-input');
  const filterForm = $('audit-log-filter-form'), drawer = $('audit-log-filter-drawer'), backdrop = $('audit-log-filter-backdrop');
  const allowedSort = new Set(['created_at', 'event', 'source', 'actor', 'entity']);
  const trim = (v) => String(v ?? '').trim();
  const esc = (v) => String(v ?? '').replace(/[&<>"']/g, (m) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
  let timer = null, request = 0, activeController = null;
  const stateFromUrl = () => {
    const p = new URLSearchParams(location.search), q = trim(p.get('q')), candidate = trim(p.get('sort_by'));
    const explicit = allowedSort.has(candidate);
    return { q, page: Math.max(1, Number.parseInt(p.get('page') || '1', 10) || 1), sort_by: q.length >= 2 && !explicit ? 'relevance' : (explicit ? candidate : 'created_at'), sort_dir: p.get('sort_dir') === 'asc' ? 'asc' : 'desc', source: ['audit_logs','audit_events'].includes(p.get('source')) ? p.get('source') : '' };
  };
  const s = stateFromUrl();
  const params = () => {
    const out = {page:String(s.page), per_page:'20', sort_dir:s.sort_dir};
    if (s.sort_by !== 'relevance') out.sort_by = s.sort_by;
    if (s.q) out.q = s.q;
    if (s.source) out.source = s.source;
    return new URLSearchParams(out).toString();
  };
  const sync = () => { if (searchInput) searchInput.value = s.q; if (filterForm?.elements.source) filterForm.elements.source.value = s.source; };
  const updateUrl = (replace = false) => { const url = new URL(location.href); url.search = params(); history[replace ? 'replaceState' : 'pushState'](null, '', url); };
  const toggleDrawer = (open) => { drawer?.classList.toggle('d-none', !open); backdrop?.classList.toggle('d-none', !open); };
  const renderSort = () => document.querySelectorAll('[data-sort-indicator]').forEach((n) => { const active = n.dataset.sortIndicator === s.sort_by; n.textContent = active ? (s.sort_dir === 'asc' ? '↑' : '↓') : '↕'; n.classList.toggle('text-muted', !active); });
  const date = (v) => { const m = String(v ?? '').match(/^(\d{4})-(\d{2})-(\d{2})[ T](\d{2}):(\d{2})/); return m ? `${m[3]}/${m[2]}/${m[1]} ${m[4]}:${m[5]}` : String(v ?? '-'); };
  const renderRows = (rows) => {
    if (!rows.length) { body.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Belum ada audit log yang cocok.</td></tr>'; return; }
    body.innerHTML = rows.map((r) => `<tr><td>${esc(r.id)}</td><td class="text-nowrap">${esc(date(r.created_at))}</td><td><span class="badge bg-light-secondary text-secondary">${esc(r.source)}</span></td><td><span class="badge bg-light-primary text-primary">${esc(r.event)}</span></td><td><div>${esc(r.actor_id || '-')}</div><small class="text-muted">${esc(r.actor_role || '')}</small></td><td><div>${esc(r.entity_type || '-')}</div><small class="text-muted">${esc(r.entity_id || '')}</small><div class="small text-muted">${esc(r.bounded_context || '')}</div></td><td>${esc(r.reason)}</td><td><a class="btn btn-sm btn-light-primary" href="${esc(r.show_url)}">Detail</a></td></tr>`).join('');
  };
  const renderSummary = (m) => { const total=Number(m.total||0), page=Number(m.page||1), from=total?((page-1)*20)+1:0; summary.textContent=`Menampilkan ${from} sampai ${Math.min(page*20,total)} dari ${total} audit log`; };
  const renderPager = (m) => {
    if (m.last_page <= 1) { pager.innerHTML=''; return; }
    const start=Math.max(1,m.page-2), end=Math.min(m.last_page,m.page+2); let html='<nav><ul class="pagination pagination-primary mb-0">';
    html+=`<li class="page-item ${m.page===1?'disabled':''}"><a class="page-link" href="#" data-page="${m.page-1}">‹</a></li>`;
    for(let p=start;p<=end;p+=1) html+=`<li class="page-item ${p===m.page?'active':''}"><a class="page-link" href="#" data-page="${p}">${p}</a></li>`;
    pager.innerHTML=html+`<li class="page-item ${m.page===m.last_page?'disabled':''}"><a class="page-link" href="#" data-page="${m.page+1}">›</a></li></ul></nav>`;
  };
  const load = async (replace=false) => {
    activeController?.abort(); const controller=new AbortController(); activeController=controller; const current=++request;
    body.innerHTML='<tr><td colspan="8" class="text-center text-muted py-4">Memuat data...</td></tr>';
    try {
      const res=await fetch(`${c.endpoint}?${params()}`,{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},signal:controller.signal}); const json=await res.json();
      if(current!==request)return; if(!res.ok||!json.success)throw new Error('audit-table-response');
      renderRows(json.data.rows||[]); renderSummary(json.data.meta||{}); renderPager(json.data.meta||{}); renderSort(); sync(); updateUrl(replace);
    } catch(error) { if(error.name==='AbortError'||current!==request)return; body.innerHTML='<tr><td colspan="8" class="text-center text-danger py-4">Gagal memuat audit log.</td></tr>'; pager.innerHTML=''; summary.textContent='Data audit log gagal dimuat.'; }
  };
  const runSearch = () => { const value=trim(searchInput?.value); s.q=value.length>=2?value:''; s.sort_by=s.q?'relevance':'created_at'; s.sort_dir='desc'; s.page=1; load(); };
  searchForm?.addEventListener('submit',(e)=>{e.preventDefault();const value=trim(searchInput?.value);if(value.length===0||value.length>=2)runSearch();});
  searchInput?.addEventListener('input',()=>{clearTimeout(timer);const value=trim(searchInput.value);if(value.length<2){s.q='';s.sort_by='created_at';s.sort_dir='desc';s.page=1;timer=setTimeout(()=>load(),160);return;}timer=setTimeout(runSearch,220);});
  $('open-audit-log-filter')?.addEventListener('click',()=>toggleDrawer(true)); $('close-audit-log-filter')?.addEventListener('click',()=>toggleDrawer(false)); backdrop?.addEventListener('click',()=>toggleDrawer(false));
  filterForm?.addEventListener('submit',(e)=>{e.preventDefault();s.source=trim(new FormData(filterForm).get('source'));s.page=1;toggleDrawer(false);load();});
  $('reset-audit-log-filter')?.addEventListener('click',()=>{s.source='';s.page=1;sync();toggleDrawer(false);load();});
  document.querySelector('#audit-log-table thead')?.addEventListener('click',(e)=>{const b=e.target.closest('[data-sort-by]');if(!b)return;const key=b.dataset.sortBy;s.sort_dir=s.sort_by===key&&s.sort_dir==='asc'?'desc':'asc';s.sort_by=key;s.page=1;load();});
  pager?.addEventListener('click',(e)=>{const a=e.target.closest('[data-page]');if(!a||a.parentElement.classList.contains('disabled'))return;e.preventDefault();s.page=Number(a.dataset.page||1);load();});
  window.addEventListener('popstate',()=>{Object.assign(s,stateFromUrl());sync();renderSort();load(true);}); sync(); renderSort(); load(true);
})();
