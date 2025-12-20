(function(){ 

  const $ = (sel, ctx=document) => ctx.querySelector(sel);

  const $$ = (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));

  const IS_ADMIN  = !!window.IS_ADMIN;

  const IS_LOGGED = !!window.IS_LOGGED;

  const T = {
    brand: "Modern MiniStore 🛍️",
    loginToBuy: "سجّل الدخول للشراء",
    addToCart: "إضافة للعربة",
    stock: "المخزون",
    edit: "تعديل",
    del: "حذف",
    welcomeTitle: "مرحبًا بك!",
    welcomeBody: "تسوق بتجربة بنفسجية أنيقة ✨",
    start: "ابدأ",
    added: "تمت الإضافة إلى العربة",
    placed: "تم تنفيذ الطلب",
    logged: "تم تسجيل الدخول",
    failed: "حدث خطأ",
  };

  const t = (k)=> T[k] || k;

  function injectChrome(){

    if (!$('.site-header')){

      const header = document.createElement('header');

      header.className = 'site-header';

      header.innerHTML = `
        <div class="container inner">
          <div class="brand">
            <div class="logo">M</div>
            <span>${t('brand')}</span>
          </div>
        </div>`;

      document.body.prepend(header);
    }

    if (!$('#toast-stack')){

      const wrap = document.createElement('div');

      wrap.id = 'toast-stack';

      document.body.append(wrap);
    }

    if (!localStorage.getItem('welcomed')){

      const modal = document.createElement('div');

      modal.id = 'welcome-modal';

      modal.innerHTML = `
        <div class="card">
          <h3>${t('welcomeTitle')}</h3>
          <p class="muted mt-1">${t('welcomeBody')}</p>
          <div class="actions mt-2">
            <button class="btn primary" id="welcome-go">${t('start')}</button>
          </div>
        </div>`;

      document.body.append(modal);

      requestAnimationFrame(()=> modal.classList.add('show'));

      modal.querySelector('#welcome-go').addEventListener('click', ()=>{
        modal.classList.remove('show');
        localStorage.setItem('welcomed', '1');
      });
    }
  }

  function toast(msg, ms=2200){

    const host = $('#toast-stack');

    if (!host) return;

    const el = document.createElement('div');

    el.className = 'toast';

    el.textContent = msg;

    host.append(el);

    requestAnimationFrame(()=> el.classList.add('show'));

    setTimeout(()=> { 
      el.classList.remove('show');
      setTimeout(()=> el.remove(), 350);
    }, ms);
  }

  const io = new IntersectionObserver((entries)=>{

    entries.forEach(e=>{
      if (e.isIntersecting) {
        e.target.classList.add('reveal-in');
        io.unobserve(e.target);
      }
    });
  }, { threshold: .12 });

  function watchReveal(){

    $$('.card, [data-reveal], .table tr')
      .forEach(el=> io.observe(el));
  }

  const listEl = $('#products-list');

  async function fetchJSON(url){

    const r = await fetch(url);

    if(!r.ok) throw new Error(r.status);

    return r.json();
  }

  function card(it){

    const admin = IS_ADMIN 
      ? (
          `<button class="btn" data-edit="${it.id}">${t('edit')}</button>
           <button class="btn danger" data-del="${it.id}">${t('del')}</button>`
        ) 
      : '';

   const buy = `
  <a class="btn primary" href="product_details.php?id=${it.id}">
    عرض التفاصيل
  </a>
`;

    return `<article class="card product" data-reveal>

      <img src="${it.image_url || 'https://picsum.photos/seed/p'+it.id+'/400/300'}" alt="${it.name||''}">

      <div class="row" style="justify-content:space-between;margin-top:8px;">

        <h3 style="margin:0">${it.name}</h3>

        <strong>${Number(it.price).toFixed(2)} USD</strong>
      </div>

      <p class="muted" style="margin:6px 0">
        ${t('stock')}: ${it.stock}
      </p>

      <div class="row" style="gap:8px;flex-wrap:wrap">
        ${admin}${buy}
      </div>
    </article>`;
  }

  async function load(){

    if (!listEl) return;

    try {
      const items = await fetchJSON('api/products.php');

      window.__lastProducts = items;

      window.__renderProducts = (arr)=>{

        listEl.innerHTML = arr.map(card).join('');

        watchReveal();
      };

      window.__renderProducts(items);

    } catch(e){

      listEl.innerHTML = '<p class="muted">تعذر تحميل المنتجات.</p>';

      console.error(e);
    }
  }

  document.addEventListener('click', async (e)=>{

    const del = e.target.closest('[data-del]');

    if (del && IS_ADMIN) {

      const id = del.getAttribute('data-del');

      if (confirm(t('del') + ' #' + id + '؟')) {

        try {
          await fetch(
            'api/products.php?id='+encodeURIComponent(id), 
            { method:'DELETE' }
          );

          await load();

          toast('✓');

        } catch(err){ 
          toast(t('failed')); 
        }
      }
    }

    const edit = e.target.closest('[data-edit]');

    if (edit && IS_ADMIN) {

      const id = edit.getAttribute('data-edit');

      const cardEl = edit.closest('.product');

      const curName = cardEl.querySelector('h3')?.textContent || '';

      const curPrice = (cardEl.querySelector('strong')?.textContent || '0').split(' ')[0];

      const curStock = (cardEl.querySelector('.muted')?.textContent || '').replace(/\D+/g, '') || '0';

      const curImg = cardEl.querySelector('img')?.getAttribute('src') || '';

      const name = prompt('اسم المنتج', curName);
      if (name == null) return;

      const price = prompt('السعر', curPrice);
      if (price == null) return;

      const stock = prompt('المخزون', curStock);
      if (stock == null) return;

      const image_url = prompt(
        'رابط الصورة (اختياري)', 
        curImg.includes('picsum.photos') ? '' : curImg
      );

      try {
        const f = new FormData();

        f.append('id', id);
        f.append('name', name);
        f.append('price', price);
        f.append('stock', stock);
        f.append('image_url', image_url || '');

        await fetch('api/products.php', { 
          method:'POST', 
          body:f 
        });

        await load();

        toast('✓');

      } catch(err){ 
        toast(t('failed')); 
      }
    }

    const addCart = e.target.closest('[data-addcart]');

    if (addCart) toast(t('added'));
  });

  document.addEventListener('submit', (e)=>{

    const form = e.target;

    const action = (form.getAttribute('action')||'').toLowerCase();

    if (/(login|user_login|checkout|place_order|cod_place_order|orders\.php)/.test(action)) {

      toast(
        /login|user_login/.test(action) 
          ? t('logged') 
          : t('placed'), 
        1500
      );
    }
  }, true);

  document.addEventListener('DOMContentLoaded', ()=>{

    injectChrome();

    load();

    watchReveal();

    setTimeout(watchReveal, 60);
  });
})();
