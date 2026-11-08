/* KT Avil Mill: small, dependency-free frontend interactions. */
(() => {
  const $ = (selector, root = document) => root.querySelector(selector);
  const $$ = (selector, root = document) => [...root.querySelectorAll(selector)];
  const money = n => '₹' + (Number(n) || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});

  function buildSackRows() {
    const count = Math.max(1, Math.min(500, Number($('#num_sacks')?.value || 1)));
    const host = $('#sackRows');
    if (!host) return;
    const old = $$('input[name="weight[]"]', host).map(el => el.value);
    host.innerHTML = '';
    for (let i = 0; i < count; i++) {
      const label = document.createElement('label');
      label.textContent = `Sack ${i + 1} weight (kg)`;
      const input = document.createElement('input');
      input.type = 'number'; input.name = 'weight[]'; input.min = '0.001'; input.step = '0.001'; input.required = true;
      input.value = old[i] || ''; input.className = 'sack-weight';
      label.appendChild(input); host.appendChild(label);
    }
    updatePurchaseTotals();
  }
  function updatePurchaseTotals() {
    const weights = $$('.sack-weight').map(el => Number(el.value) || 0);
    const total = weights.reduce((a, b) => a + b, 0);
    const rate = Number($('#rate_per_kg')?.value || 0);
    if ($('#totalWeight')) $('#totalWeight').textContent = total.toFixed(3);
    if ($('#purchaseAmount')) $('#purchaseAmount').textContent = money(total * rate);
  }
  $('#num_sacks')?.addEventListener('change', buildSackRows);
  $('#num_sacks')?.addEventListener('input', buildSackRows);
  $('#sackRows')?.addEventListener('input', updatePurchaseTotals);
  $('#rate_per_kg')?.addEventListener('input', updatePurchaseTotals);
  if ($('#sackRows')) buildSackRows();

  function updateSaleTotal() {
    const product = $('#product_id')?.selectedOptions?.[0];
    if (!product) return;
    const unit = $('#unit'); if (unit) unit.value = product.dataset.unit || '';
    const price = $('#unit_price'); if (price && !price.dataset.userEdited) price.value = product.dataset.price || '';
    const total = (Number($('#quantity')?.value || 0) * Number(price?.value || 0));
    if ($('#lineTotal')) $('#lineTotal').textContent = money(total);
    const stock = Number(product.dataset.stock || 0), qty = Number($('#quantity')?.value || 0);
    const warn = $('#stockWarn');
    if (warn) warn.textContent = qty > stock ? `Warning: entered quantity exceeds displayed stock (${stock}). The server will validate stock.` : '';
  }
  $('#product_id')?.addEventListener('change', () => { const p=$('#product_id').selectedOptions[0]; const price=$('#unit_price'); if(price){price.value=p.dataset.price||''; price.dataset.userEdited='';} updateSaleTotal(); });
  $('#unit_price')?.addEventListener('input', e => { e.target.dataset.userEdited='1'; updateSaleTotal(); });
  $('#quantity')?.addEventListener('input', updateSaleTotal);
  if ($('#product_id')) updateSaleTotal();

  // Keep form submissions in the same PHP endpoints, but submit them asynchronously.
  $$('form[action^="api/"]').forEach(form => {
    form.addEventListener('submit', async event => {
      event.preventDefault();
      const button = $('button[type="submit"]', form); if (button) button.disabled = true;
      try {
        const response = await fetch(form.action, {method:'POST', body:new FormData(form), headers:{'X-Requested-With':'XMLHttpRequest'}});
        const type = response.headers.get('content-type') || '';
        const result = type.includes('application/json') ? await response.json() : {ok:response.ok, message:await response.text()};
        if (!response.ok || result.error) throw new Error(result.error || result.message || 'Unable to save record.');
        alert(result.message || 'Record saved successfully.');
        window.location.reload();
      } catch (error) { alert(error.message || 'Request failed.'); }
      finally { if (button) button.disabled = false; }
    });
  });

  // Reports AJAX
  async function loadReport() {
    const from = $('#rFrom')?.value, to = $('#rTo')?.value;
    if (!from || !to || from > to) { alert('Choose a valid date range.'); return; }
    const summary = $('#reportSummary'); if (summary) summary.innerHTML = '<p class="empty">Loading…</p>';
    try {
      const response = await fetch(`api/report_data.php?from=${encodeURIComponent(from)}&to=${encodeURIComponent(to)}`);
      const data = await response.json();
      if (!response.ok || data.error) throw new Error(data.error || 'Could not load report.');
      const s = data.summary || {};
      if (summary) summary.innerHTML = [
        ['Revenue',s.revenue],['Other income',s.other_income],['Expenses',s.expenses],['Estimated COGS',s.cogs],['Estimated gross profit',s.gross],['Estimated net operating profit',s.net_op],['Cash flow',s.cash_flow],['Paddy received (kg)',s.paddy_in_kg]
      ].map(([label,value])=>`<div class="card"><div class="muted">${label}</div><strong>${label.includes('(kg)') ? (Number(value)||0).toFixed(3)+' kg' : money(value)}</strong></div>`).join('');
      const products = $('#reportProducts');
      if (products) products.innerHTML = data.by_product?.length ? `<table class="table"><thead><tr><th>Product</th><th>Quantity</th><th>Unit</th><th>Total</th></tr></thead><tbody>${data.by_product.map(r=>`<tr><td>${escapeHtml(r.product_name)}</td><td>${Number(r.qty).toFixed(3)}</td><td>${escapeHtml(r.unit)}</td><td>${money(r.total)}</td></tr>`).join('')}</tbody></table>` : '<p class="empty">No sales in this period.</p>';
      const tx = $('#reportTx');
      if (tx) tx.innerHTML = data.transactions?.length ? `<table class="table"><thead><tr><th>Date</th><th>Type</th><th>Party/category</th><th>Amount</th></tr></thead><tbody>${data.transactions.map(r=>`<tr><td>${escapeHtml(r.d)}</td><td>${escapeHtml(r.type)}</td><td>${escapeHtml(r.party || '')}</td><td>${money(r.amount)}</td></tr>`).join('')}</tbody></table>` : '<p class="empty">No transactions in this period.</p>';
    } catch (error) { if (summary) summary.innerHTML = `<p class="alert error">${escapeHtml(error.message)}</p>`; }
  }
  function escapeHtml(value) { return String(value ?? '').replace(/[&<>"']/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch])); }
  $('#loadReport')?.addEventListener('click', loadReport);
  $('#weekBtn')?.addEventListener('click', () => { const now=new Date(); const day=(now.getDay()+6)%7; const monday=new Date(now); monday.setDate(now.getDate()-day); $('#rFrom').value=monday.toISOString().slice(0,10); $('#rTo').value=now.toISOString().slice(0,10); loadReport(); });
  $('#monthBtn')?.addEventListener('click', () => { const now=new Date(); $('#rFrom').value=new Date(now.getFullYear(),now.getMonth(),1).toISOString().slice(0,10); $('#rTo').value=now.toISOString().slice(0,10); loadReport(); });
  if ($('#reportSummary')) loadReport();

  // Product edit modal
  $('#newProductBtn')?.addEventListener('click', () => { const modal=$('#prodModal'); if(!modal)return; const form=$('form',modal); form?.reset(); if($('#f_id'))$('#f_id').value='0'; if($('#prodModalTitle'))$('#prodModalTitle').textContent='New product'; modal.classList.add('open'); });
  $('#prodModal')?.addEventListener('click', e => { if(e.target.id==='prodModal') e.currentTarget.classList.remove('open'); });
  $$('[data-edit-product]').forEach(button => button.addEventListener('click', () => {
    const data=JSON.parse(button.dataset.editProduct || '{}');
    const fields={id:'f_id',name:'f_name',unit:'f_unit',default_price:'f_price',pricing_method:'f_method',sack_size_kg:'f_sack',notes:'f_notes'};
    Object.entries(fields).forEach(([key,id])=>{const el=$('#'+id);if(el)el.value=data[key]??'';});
    if($('#f_active'))$('#f_active').checked=String(data.is_active)==='1';
    if($('#f_ash'))$('#f_ash').checked=String(data.is_ash_income)==='1';
    if($('#prodModalTitle'))$('#prodModalTitle').textContent='Edit product'; $('#prodModal')?.classList.add('open');
  }));
  $('#loadXml')?.addEventListener('click', async () => { try { const r=await fetch('api/xml_export.php'); const text=await r.text(); if($('#xmlBox'))$('#xmlBox').value=text; } catch(e){alert('Could not load XML export.');} });
})();
