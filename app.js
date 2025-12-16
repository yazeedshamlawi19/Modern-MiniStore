(function(){ 
  // IIFE (Immediately Invoked Function Expression)
  // هذا الغلاف يمنع تلويث الـ global scope
  // أي متغير أو دالة هنا لن تكون متاحة خارج هذا الملف

  const $ = (sel, ctx=document) => ctx.querySelector(sel);
  // دالة اختصار:
  // $ تستقبل selector (CSS selector)
  // و ctx هو السياق (افتراضي document)
  // ترجع أول عنصر يطابق الـ selector

  const $$ = (sel, ctx=document) => Array.from(ctx.querySelectorAll(sel));
  // دالة اختصار ثانية:
  // $$ ترجع جميع العناصر المطابقة للـ selector
  // querySelectorAll يرجع NodeList
  // Array.from تحولها لمصفوفة عادية (Array)

  const IS_ADMIN  = !!window.IS_ADMIN;
  // IS_ADMIN:
  // نأخذ القيمة من window.IS_ADMIN (قادمة من PHP)
  // !! يحولها لقيمة منطقية true أو false فقط

  const IS_LOGGED = !!window.IS_LOGGED;
  // IS_LOGGED:
  // نفس الفكرة، هل المستخدم مسجل دخول أم لا
  // القيمة قادمة من PHP ومحوّلة لـ boolean

 
  const T = {
    // كائن يحتوي جميع النصوص المستخدمة في الواجهة
    // الهدف: توحيد النصوص وسهولة تغييرها لاحقًا

    brand: "Modern MiniStore 🛍️", // اسم المتجر
    loginToBuy: "سجّل الدخول للشراء", // نص يظهر لغير المسجلين
    addToCart: "إضافة للعربة", // زر إضافة للعربة
    stock: "المخزون", // نص المخزون
    edit: "تعديل", // زر تعديل (للمسؤول)
    del: "حذف", // زر حذف (للمسؤول)
    welcomeTitle: "مرحبًا بك!", // عنوان نافذة الترحيب
    welcomeBody: "تسوق بتجربة بنفسجية أنيقة ✨", // نص الترحيب
    start: "ابدأ", // زر بدء
    added: "تمت الإضافة إلى العربة", // إشعار إضافة للعربة
    placed: "تم تنفيذ الطلب", // إشعار تنفيذ الطلب
    logged: "تم تسجيل الدخول", // إشعار تسجيل الدخول
    failed: "حدث خطأ", // رسالة خطأ عامة
  };

  const t = (k)=> T[k] || k;
  // دالة ترجمة بسيطة:
  // تستقبل مفتاح (key)
  // إذا كان موجود في الكائن T ترجعه
  // وإذا لم يكن موجود ترجع المفتاح نفسه


  function injectChrome(){
    // دالة مسؤولة عن:
    // - إنشاء الهيدر
    // - إنشاء حاوية الإشعارات
    // - إنشاء نافذة الترحيب

    if (!$('.site-header')){
      // إذا لم يكن هناك عنصر class="site-header" في الصفحة

      const header = document.createElement('header');
      // إنشاء عنصر header جديد

      header.className = 'site-header';
      // إعطاؤه class site-header

      header.innerHTML = `
        <div class="container inner">
          <div class="brand">
            <div class="logo">M</div>
            <span>${t('brand')}</span>
          </div>
        </div>`;
      // HTML داخلي للهيدر:
      // شعار + اسم المتجر من كائن النصوص

      document.body.prepend(header);
      // إضافة الهيدر في أعلى الصفحة (قبل أي محتوى)
    }

    if (!$('#toast-stack')){
      // إذا لم تكن حاوية الإشعارات موجودة

      const wrap = document.createElement('div');
      // إنشاء div جديد

      wrap.id = 'toast-stack';
      // إعطاؤه id خاص بالإشعارات

      document.body.append(wrap);
      // إضافته في نهاية body
    }

    if (!localStorage.getItem('welcomed')){
      // فحص localStorage:
      // إذا المستخدم لم يشاهد نافذة الترحيب من قبل

      const modal = document.createElement('div');
      // إنشاء عنصر div لنافذة الترحيب

      modal.id = 'welcome-modal';
      // إعطاؤه id خاص

      modal.innerHTML = `
        <div class="card">
          <h3>${t('welcomeTitle')}</h3>
          <p class="muted mt-1">${t('welcomeBody')}</p>
          <div class="actions mt-2">
            <button class="btn primary" id="welcome-go">${t('start')}</button>
          </div>
        </div>`;
      // محتوى نافذة الترحيب:
      // عنوان + نص + زر "ابدأ"

      document.body.append(modal);
      // إضافة المودال للصفحة

      requestAnimationFrame(()=> modal.classList.add('show'));
      // إضافة class show في الإطار التالي
      // لضمان عمل الأنيميشن بشكل صحيح

      modal.querySelector('#welcome-go').addEventListener('click', ()=>{
        // عند الضغط على زر "ابدأ"

        modal.classList.remove('show');
        // إخفاء نافذة الترحيب

        localStorage.setItem('welcomed', '1');
        // حفظ قيمة في localStorage
        // حتى لا تظهر نافذة الترحيب مرة أخرى
      });
    }
  }

  
  function toast(msg, ms=2200){
    // دالة إنشاء إشعار (Toast)
    // msg = نص الإشعار
    // ms = مدة الظهور (افتراضي 2200 مللي ثانية)

    const host = $('#toast-stack');
    // جلب حاوية الإشعارات

    if (!host) return;
    // إذا لم تكن موجودة، نخرج من الدالة

    const el = document.createElement('div');
    // إنشاء عنصر div للإشعار

    el.className = 'toast';
    // إعطاؤه class toast

    el.textContent = msg;
    // وضع نص الإشعار

    host.append(el);
    // إضافة الإشعار إلى الحاوية

    requestAnimationFrame(()=> el.classList.add('show'));
    // إضافة class show لتشغيل أنيميشن الظهور

    setTimeout(()=> { 
      el.classList.remove('show');
      // إزالة class show (أنيميشن الاختفاء)

      setTimeout(()=> el.remove(), 350);
      // بعد انتهاء الأنيميشن يتم حذف العنصر من DOM
    }, ms);
  }

  
  const io = new IntersectionObserver((entries)=>{
    // IntersectionObserver:
    // يراقب متى يدخل العنصر ضمن مجال رؤية المستخدم

    entries.forEach(e=>{
      if (e.isIntersecting) {
        // إذا أصبح العنصر ظاهرًا في الشاشة

        e.target.classList.add('reveal-in');
        // إضافة class reveal-in لتشغيل تأثير الظهور

        io.unobserve(e.target);
        // إيقاف مراقبة هذا العنصر بعد ظهوره مرة واحدة
      }
    });
  }, { threshold: .12 });
  // threshold: .12 يعني
  // يتم التفعيل عندما يظهر 12% من العنصر داخل الشاشة

  function watchReveal(){
    // دالة لتفعيل مراقبة الظهور

    $$('.card, [data-reveal], .table tr')
      .forEach(el=> io.observe(el));
    // مراقبة:
    // - كل الكروت
    // - أي عنصر لديه data-reveal
    // - صفوف الجداول
  }

 
  const listEl = $('#products-list');
  // جلب عنصر عرض المنتجات (div أو main)

  async function fetchJSON(url){
    // دالة لجلب JSON من السيرفر

    const r = await fetch(url);
    // إرسال طلب fetch إلى الرابط

    if(!r.ok) throw new Error(r.status);
    // إذا الرد ليس ناجح (status != 200)
    // نرمي خطأ

    return r.json();
    // تحويل الرد إلى JSON وإرجاعه
  }

  function card(it){
  // دالة card:
  // تستقبل كائن المنتج it (قادم من قاعدة البيانات عبر API)
  // وترجع HTML يمثل كرت منتج واحد

  const admin = IS_ADMIN 
    ? (
        `<button class="btn" data-edit="${it.id}">${t('edit')}</button>
         <button class="btn danger" data-del="${it.id}">${t('del')}</button>`
      ) 
    : '';
  // admin:
  // إذا المستخدم مسؤول (IS_ADMIN = true)
  // يتم إنشاء زرين:
  // - زر تعديل مع data-edit يحمل id المنتج
  // - زر حذف مع data-del يحمل id المنتج
  // إذا لم يكن مسؤول → قيمة فارغة (لا تظهر أزرار)

  const buy   = IS_LOGGED 
    ? `<a class="btn primary" href="cart_add.php?id=${it.id}" data-addcart="${it.id}">${t('addToCart')}</a>`
    : `<a class="btn muted" href="user_login.php?next=cart.php">${t('loginToBuy')}</a>`;
  // buy:
  // إذا المستخدم مسجل دخول:
  // يظهر زر "إضافة للعربة" مع رابط cart_add.php
  // ويحمل data-addcart لاستخدامه في JavaScript
  // إذا غير مسجل:
  // يظهر زر يوجه لصفحة تسجيل الدخول

  return `<article class="card product" data-reveal>
    <!-- عنصر article يمثل كرت المنتج -->
    <!-- class card لتنسيق الكرت -->
    <!-- class product لتسهيل التعامل معه -->
    <!-- data-reveal لاستخدام تأثير الظهور -->

    <img src="${it.image_url || 'https://picsum.photos/seed/p'+it.id+'/400/300'}" alt="${it.name||''}">
    <!-- صورة المنتج -->
    <!-- إذا لم يوجد image_url -->
    <!-- يتم استخدام صورة افتراضية من picsum -->
    <!-- alt يحتوي اسم المنتج -->

    <div class="row" style="justify-content:space-between;margin-top:8px;">
      <!-- صف يحتوي اسم المنتج والسعر -->

      <h3 style="margin:0">${it.name}</h3>
      <!-- اسم المنتج -->

      <strong>${Number(it.price).toFixed(2)} USD</strong>
      <!-- السعر -->
      <!-- يتم تحويله إلى رقم -->
      <!-- وتنسيقه ليظهر منزلتين عشريتين -->
    </div>

    <p class="muted" style="margin:6px 0">
      ${t('stock')}: ${it.stock}
    </p>
    <!-- نص المخزون -->
    <!-- muted لتلوين النص بلون هادئ -->

    <div class="row" style="gap:8px;flex-wrap:wrap">
      ${admin}${buy}
    </div>
    <!-- صف يحتوي أزرار الإدارة (إن وجدت) وزر الشراء -->
  </article>`;
}

async function load(){
  // دالة تحميل المنتجات من السيرفر

  if (!listEl) return;
  // إذا عنصر عرض المنتجات غير موجود في الصفحة
  // نخرج من الدالة مباشرة

  try {
    const items = await fetchJSON('api/products.php');
    // جلب المنتجات من API
    // النتيجة مصفوفة منتجات

    window.__lastProducts = items;
    // تخزين آخر منتجات محمّلة في window
    // مفيد للبحث أو التصفية لاحقًا

    window.__renderProducts = (arr)=>{
      // دالة عامة لإعادة رسم المنتجات

      listEl.innerHTML = arr.map(card).join('');
      // تحويل كل منتج إلى HTML باستخدام card()
      // ثم دمجهم داخل عنصر العرض

      watchReveal();
      // تفعيل تأثير الظهور بعد إضافة العناصر
    };

    window.__renderProducts(items);
    // عرض جميع المنتجات لأول مرة

  } catch(e){
    // في حال حدوث خطأ أثناء التحميل

    listEl.innerHTML = '<p class="muted">تعذر تحميل المنتجات.</p>';
    // عرض رسالة خطأ للمستخدم

    console.error(e);
    // طباعة الخطأ في الكونسول للمطور
  }
}

document.addEventListener('click', async (e)=>{
  // مستمع عام لكل النقرات في الصفحة

  const del = e.target.closest('[data-del]');
  // البحث عن أقرب عنصر يحتوي data-del

  if (del && IS_ADMIN) {
    // إذا تم الضغط على زر حذف
    // وكان المستخدم مسؤول

    const id = del.getAttribute('data-del');
    // استخراج id المنتج

    if (confirm(t('del') + ' #' + id + '؟')) {
      // تأكيد الحذف من المستخدم

      try {
        await fetch(
          'api/products.php?id='+encodeURIComponent(id), 
          { method:'DELETE' }
        );
        // إرسال طلب DELETE لحذف المنتج

        await load();
        // إعادة تحميل المنتجات بعد الحذف

        toast('✓');
        // عرض إشعار نجاح

      } catch(err){ 
        toast(t('failed')); 
        // عرض إشعار فشل
      }
    }
  }

  const edit = e.target.closest('[data-edit]');
  // البحث عن زر التعديل

  if (edit && IS_ADMIN) {
    // إذا المستخدم مسؤول وضغط تعديل

    const id = edit.getAttribute('data-edit');
    // استخراج id المنتج

    const cardEl = edit.closest('.product');
    // الحصول على كرت المنتج الكامل

    const curName = cardEl.querySelector('h3')?.textContent || '';
    // استخراج الاسم الحالي

    const curPrice = (cardEl.querySelector('strong')?.textContent || '0').split(' ')[0];
    // استخراج السعر الحالي (بدون USD)

    const curStock = (cardEl.querySelector('.muted')?.textContent || '').replace(/\D+/g, '') || '0';
    // استخراج المخزون
    // إزالة أي حروف غير رقمية

    const curImg = cardEl.querySelector('img')?.getAttribute('src') || '';
    // استخراج رابط الصورة الحالية

    const name = prompt('اسم المنتج', curName);
    if (name == null) return;
    // نافذة إدخال الاسم الجديد

    const price = prompt('السعر', curPrice);
    if (price == null) return;
    // نافذة إدخال السعر الجديد

    const stock = prompt('المخزون', curStock);
    if (stock == null) return;
    // نافذة إدخال المخزون

    const image_url = prompt(
      'رابط الصورة (اختياري)', 
      curImg.includes('picsum.photos') ? '' : curImg
    );
    // إدخال رابط الصورة (اختياري)

    try {
      const f = new FormData();
      // إنشاء FormData لإرسال البيانات

      f.append('id', id);
      f.append('name', name);
      f.append('price', price);
      f.append('stock', stock);
      f.append('image_url', image_url || '');

      await fetch('api/products.php', { 
        method:'POST', 
        body:f 
      });
      // إرسال البيانات لتحديث المنتج

      await load();
      // إعادة تحميل المنتجات

      toast('✓');
      // إشعار نجاح

    } catch(err){ 
      toast(t('failed')); 
      // إشعار فشل
    }
  }

  const addCart = e.target.closest('[data-addcart]');
  // البحث عن زر إضافة للعربة

  if (addCart) toast(t('added'));
  // عرض إشعار "تمت الإضافة"
});

document.addEventListener('submit', (e)=>{
  // مستمع لإرسال النماذج (forms)

  const form = e.target;
  // النموذج الذي تم إرساله

  const action = (form.getAttribute('action')||'').toLowerCase();
  // جلب رابط action وتحويله لحروف صغيرة

  if (/(login|user_login|checkout|place_order|cod_place_order|orders\.php)/.test(action)) {
    // إذا كان الفورم متعلق بتسجيل الدخول أو الطلب

    toast(
      /login|user_login/.test(action) 
        ? t('logged') 
        : t('placed'), 
      1500
    );
    // عرض إشعار مناسب:
    // - تسجيل دخول
    // - أو تنفيذ طلب
  }
}, true);

document.addEventListener('DOMContentLoaded', ()=>{
  // عند اكتمال تحميل الصفحة

  injectChrome();
  // إنشاء الهيدر + التوست + نافذة الترحيب

  load();
  // تحميل المنتجات

  watchReveal();
  // تفعيل تأثير الظهور

  setTimeout(watchReveal, 60);
  // إعادة التفعيل بعد تأخير بسيط
  // لضمان عمل التأثير مع كل العناصر
});
})(); 
