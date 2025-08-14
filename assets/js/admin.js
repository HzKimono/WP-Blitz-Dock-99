(function(){
  const { __ } = wp.i18n;

 const tablist = document.querySelector('.bdp-admin-layout .bdp-tabs');
  if(tablist){
    const tabs = Array.from(tablist.querySelectorAll('[role="tab"]'));
    const content = document.querySelector('.bdp-admin-layout .bdp-content');
    const panels = content ? Array.from(content.querySelectorAll('[role="tabpanel"]')) : [];
    const title = document.getElementById('bdp-active-panel-title'); // breadcrumb current tab

    function setHeaderFrom(btn){
      if(title && btn?.dataset?.label){
        title.textContent = btn.dataset.label;
      }
    }

    tabs.forEach(t => t.setAttribute('tabindex', t.getAttribute('aria-selected') === 'true' ? '0' : '-1'));
    const selected = tabs.find(t => t.getAttribute('aria-selected') === 'true') || tabs[0];
    if(selected){ showPanel(selected.getAttribute('aria-controls')); setHeaderFrom(selected); }

    tablist.addEventListener('click', (e) => {
      const btn = e.target.closest('[role="tab"]');
      if(!btn) return;
      e.preventDefault();
      activate(btn);
    });

    tablist.addEventListener('keydown', (e) => {
      const cur = document.activeElement.closest('[role="tab"]');
      if(!cur) return;
      let i = tabs.indexOf(cur);
      if(['ArrowUp','ArrowLeft'].includes(e.key)){ e.preventDefault(); i=(i-1+tabs.length)%tabs.length; tabs[i].focus(); }
      if(['ArrowDown','ArrowRight'].includes(e.key)){ e.preventDefault(); i=(i+1)%tabs.length; tabs[i].focus(); }
      if(e.key==='Home'){ e.preventDefault(); tabs[0].focus(); }
      if(e.key==='End'){ e.preventDefault(); tabs[tabs.length-1].focus(); }
      if(e.key==='Enter' || e.key===' '){ e.preventDefault(); activate(cur); }
    });

   function activate(btn){
      tabs.forEach(t => { t.setAttribute('aria-selected','false'); t.setAttribute('tabindex','-1'); });
      btn.setAttribute('aria-selected','true'); btn.setAttribute('tabindex','0');
      showPanel(btn.getAttribute('aria-controls'));
      setHeaderFrom(btn);
    }


    function showPanel(id){
      panels.forEach(p => p.hidden = (p.id !== id));
    }
  }

  // ==== SOCIAL MEDIA REPEATER ====
  const addSocialBtn    = document.getElementById('bdp-add-link');
  const socialContainer = document.getElementById('bdp-social-links');
  const socialPlatforms = pluginData.socialPlatforms || {};

  function reindexSocialRows() {
    socialContainer.querySelectorAll('.bdp-social-row').forEach((row, idx) => {
      row.querySelector('select').name = `bdp_social_links[${idx}][platform]`;
      row.querySelector('input[type="url"]').name = `bdp_social_links[${idx}][url]`;
    });
  }

  function removeEmptyInfoMsg() {
    const infoMsg = document.getElementById('no-social-message');
    if (infoMsg) infoMsg.remove();
  }

  function showEmptyInfoMsg() {
    if (!document.getElementById('no-social-message')) {
      const msg = document.createElement('div');
      msg.id = 'no-social-message';
      msg.style.color = '#888';
      msg.style.margin = '1em 0';
      msg.innerText = __('No social media links added yet. Click the button to add one.', 'blitz-dock');
      socialContainer.after(msg);
    }
  }

  if (addSocialBtn && socialContainer) {
    addSocialBtn.addEventListener('click', () => {
      removeEmptyInfoMsg();
      const row = document.createElement('div');
      row.className = 'bdp-social-row';
      let options = '<option value="">' + __('Select Platform', 'blitz-dock') + '</option>';
      Object.keys(socialPlatforms).forEach(key => {
        const label = socialPlatforms[key];
        options += `<option value="${key}">${label}</option>`;
      });
      row.innerHTML = `
        <img src="${pluginData.iconsBaseURL}default.png" width="24" height="24">
        <select name="bdp_social_links[0][platform]">${options}</select>
       <input type="url" name="bdp_social_links[0][url]" placeholder="https://">
        <button type="button" class="button bdp-remove-link">${__( 'Delete', 'blitz-dock' )}</button>
      `;
      socialContainer.appendChild(row);
      reindexSocialRows();
    });

    socialContainer.addEventListener('click', function(e) {
      if (e.target.classList.contains('bdp-remove-link')) {
        e.target.closest('.bdp-social-row').remove();
        reindexSocialRows();
        if (socialContainer.querySelectorAll('.bdp-social-row').length === 0) {
          showEmptyInfoMsg();
        }
      }
    });

    socialContainer.addEventListener('change', function(e) {
      if (e.target.tagName === 'SELECT') {
        const img = e.target.closest('.bdp-social-row').querySelector('img');
        img.src = pluginData.iconsBaseURL + (e.target.value || 'default') + '.png';
      }
    });
  }

 // ==== FAQ REPEATER ====
  const addFaqBtn    = document.getElementById('bdp-add-faq');
  const faqContainer = document.getElementById('bdp-faq-items');

  function reindexFaqRows() {
    faqContainer.querySelectorAll('.bdp-faq-card').forEach((card, idx) => {
      const qInput   = card.querySelector('input[type="text"]');
      const aTextarea = card.querySelector('textarea');
      const labels    = card.querySelectorAll('.bdp-faq-label');
      const qId = `bdp_faq_question_${idx}`;
      const aId = `bdp_faq_answer_${idx}`;
      if (qInput) {
        qInput.name = `bdp_faq_items[${idx}][question]`;
        qInput.id   = qId;
      }
      if (aTextarea) {
        aTextarea.name = `bdp_faq_items[${idx}][answer]`;
        aTextarea.id   = aId;
      }
      if (labels[0]) labels[0].setAttribute('for', qId);
      if (labels[1]) labels[1].setAttribute('for', aId);
    });
  }

  if (addFaqBtn && faqContainer) {
    addFaqBtn.addEventListener('click', () => {
      const card = document.createElement('div');
      card.className = 'bdp-faq-card';
      card.innerHTML = `
        <div class="bdp-faq-field">
          <label class="bdp-faq-label">${__( 'Add a Question', 'blitz-dock' )}</label>
          <input type="text" placeholder="${__( 'Question', 'blitz-dock' )}">
        </div>
        <div class="bdp-faq-field">
          <label class="bdp-faq-label">${__( 'Provide an Answer', 'blitz-dock' )}</label>
          <textarea rows="3" placeholder="${__( 'Answer', 'blitz-dock' )}"></textarea>
        </div>
        <button type="button" class="button bdp-remove-faq">${__( 'Remove', 'blitz-dock' )}</button>
      `;
      faqContainer.appendChild(card);
      reindexFaqRows();
    });

    faqContainer.addEventListener('click', function(e) {
      if (e.target.classList.contains('bdp-remove-faq')) {
        e.target.closest('.bdp-faq-card').remove();
        reindexFaqRows();
      }
    });
  }

   // ==== ONLINE STORES REPEATER ====
  const addEcommBtn    = document.getElementById('bdp-add-ecomm');
  const ecommContainer = document.getElementById('bdp-ecomm-items');
  const ecommPlatforms = ['amazon','hepsiburada','sahibinden','shopify','temu','trendyol'];

  function updateEcommIcon(row) {
    const select = row.querySelector('.bdp-ecomm-select');
    const img    = row.querySelector('.bdp-ecomm-icon');
    if (select && img) {
      img.src = pluginData.ecommIconsBaseURL + (select.value || 'default') + '.png';
    }
  }

  function reindexEcommRows() {
    ecommContainer.querySelectorAll('.bdp-ecomm-row').forEach((row, idx) => {
      row.querySelector('select').name = `bdp_ecomm_links[${idx}][name]`;
      row.querySelector('input[type="url"]').name = `bdp_ecomm_links[${idx}][url]`;
    });
  }

  if (addEcommBtn && ecommContainer) {
    // Update icons for existing rows on load
    ecommContainer.querySelectorAll('.bdp-ecomm-row').forEach(updateEcommIcon);

    addEcommBtn.addEventListener('click', () => {
      const row = document.createElement('div');
      row.className = 'bdp-ecomm-row';
     let options = '<option value="">' + __('Select Platform', 'blitz-dock') + '</option>';
      ecommPlatforms.forEach(p => {
        const label = p.charAt(0).toUpperCase() + p.slice(1);
        options += `<option value="${p}">${label}</option>`;
      });
      row.innerHTML = `
        <img src="${pluginData.ecommIconsBaseURL}default.png" width="24" height="24" class="bdp-ecomm-icon">
        <select name="bdp_ecomm_links[0][name]" class="bdp-ecomm-select">${options}</select>
         <input type="url" name="bdp_ecomm_links[0][url]" placeholder="https://">
        <button type="button" class="button bdp-remove-ecomm">${__( 'Delete', 'blitz-dock' )}</button>`;
      ecommContainer.appendChild(row);
      updateEcommIcon(row);
      reindexEcommRows();
    });

    ecommContainer.addEventListener('click', function(e) {
      if (e.target.classList.contains('bdp-remove-ecomm')) {
        e.target.closest('.bdp-ecomm-row').remove();
        reindexEcommRows();
      }
    });

    ecommContainer.addEventListener('change', function(e) {
      if (e.target.classList.contains('bdp-ecomm-select')) {
        updateEcommIcon(e.target.closest('.bdp-ecomm-row'));
      }
    });
  }

// ==== AVATAR PICKER MODAL ====
  const avatarModal   = document.getElementById('bdp-avatar-modal');
  const avatarGrid    = document.getElementById('bdp-avatar-grid');
  const avatarChoices = avatarGrid ? Array.from(avatarGrid.querySelectorAll('.bdp-avatar-choice')) : [];
  const avatarPreview = document.getElementById('bdp-avatar-preview');
  const avatarInput   = document.getElementById('bdp-selected-avatar');
  const avatarFile    = document.getElementById('bdp-custom-avatar');
  const avatarSearch  = document.getElementById('bdp-avatar-search');
  const modalClose    = document.getElementById('bdp-avatar-close');

  function openAvatarModal() {
    if (!avatarModal) return;
    avatarModal.classList.add('open');
    avatarModal.setAttribute('aria-hidden', 'false');
    avatarModal.focus();
    if (avatarSearch) {
      avatarSearch.value = '';
      avatarSearch.focus();
    }
    if (avatarChoices.length) {
      avatarChoices.forEach(btn => (btn.style.display = ''));
    }
  }

  function closeAvatarModal() {
    if (!avatarModal) return;
    avatarModal.classList.remove('open');
    avatarModal.setAttribute('aria-hidden', 'true');
  }

  if (avatarPreview) {
    avatarPreview.addEventListener('click', openAvatarModal);
  }

  if (modalClose) {
    modalClose.addEventListener('click', closeAvatarModal);
  }

  if (avatarModal) {
    avatarModal.addEventListener('click', (e) => {
      if (e.target === avatarModal) {
        closeAvatarModal();
      }
    });
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && avatarModal && avatarModal.classList.contains('open')) {
      closeAvatarModal();
    }
  });

  if (avatarGrid && avatarPreview && avatarInput) {
    avatarGrid.addEventListener('click', (e) => {
      const btn = e.target.closest('.bdp-avatar-choice');
      if (!btn) return;
      avatarChoices.forEach(b => b.classList.remove('selected'));
      btn.classList.add('selected');
      avatarPreview.src = btn.querySelector('img').src;
      avatarInput.value = btn.dataset.avatar;
      if (avatarFile) avatarFile.value = '';
      closeAvatarModal();
    });
  }

  if (avatarFile && avatarPreview && avatarInput) {
    avatarFile.addEventListener('change', () => {
      if (!avatarFile.files.length) return;
      avatarChoices.forEach(b => b.classList.remove('selected'));
      const file = avatarFile.files[0];
      const reader = new FileReader();
      reader.onload = () => {
        avatarPreview.src = reader.result;
      };
      reader.readAsDataURL(file);
      avatarInput.value = '';
      closeAvatarModal();
    });
  }

   if (avatarSearch) {
    avatarSearch.addEventListener('input', () => {
      const val = avatarSearch.value.toLowerCase();
      avatarChoices.forEach(btn => {
        const name = btn.dataset.avatar.toLowerCase();
        btn.style.display = name.includes(val) ? '' : 'none';
      });
    });
  }

  // ==== GOOGLE MAP PREVIEW ====
  const mapField   = document.getElementById('bdp_location_embed');
  const mapPreview = document.getElementById('bdp-map-preview');
  let gmScript = null;

  function showPlaceholder(msg){
    mapPreview.innerHTML = `<p class="bdp-map-placeholder">${msg}</p>`;
  }

  function initMap(){
    const canvas = document.getElementById('bdp-map-canvas');
    if (!canvas) return;
    try {
      new google.maps.Map(canvas, {center:{lat:0,lng:0}, zoom:2});
    } catch(e){
      showPlaceholder(mapPreview.dataset.error);
    }
  }

  function loadMapScript(key){
    if (gmScript) gmScript.remove();
    gmScript = document.createElement('script');
    gmScript.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(key)}&callback=bdpAdminInitMap`;
    gmScript.onerror = () => showPlaceholder(mapPreview.dataset.error);
    document.body.appendChild(gmScript);
    window.bdpAdminInitMap = initMap;
  }

  function renderMap(){
    const val = mapField.value.trim();
    mapPreview.innerHTML = '';
    if (!val){
      showPlaceholder(mapPreview.dataset.placeholder);
      return;
    }
    if (val.startsWith('<iframe')){
      mapPreview.innerHTML = val;
      return;
    }
    const apiKeyPattern = /^AIza[0-9A-Za-z\-_]+$/;
    if (apiKeyPattern.test(val)){
      mapPreview.innerHTML = '<div id="bdp-map-canvas"></div>';
      if (typeof google === 'object' && google.maps){
        initMap();
      } else {
        loadMapScript(val);
      }
    } else {
      showPlaceholder(mapPreview.dataset.placeholder);
    }
  }

  if (mapField && mapPreview){
    mapField.addEventListener('input', renderMap);
    renderMap();
  }
 const showNotice = (el, msg) => {
    const notice = document.createElement('div');
    notice.className = 'notice notice-error';
    notice.textContent = msg;
    el.parentNode.insertBefore(notice, el.nextSibling);
    setTimeout(() => notice.remove(), 3000);
  };

  // ==== MESSAGE STATUS UPDATE ====
  document.addEventListener('change', (e) => {
    if (e.target.classList.contains('bdp-status-select')) {
      const select = e.target;
      const id = select.getAttribute('data-id');
      const status = select.value;
      jQuery.post(bdp_ajax.url, {
        action: 'bdp_update_status',
        nonce: bdp_ajax.update_nonce,
        id,
        status
      }, (resp) => {
        if (resp.success) {
          const row = select.closest('tr');
          if (row) {
            row.className = row.className.replace(/status-[a-z]+/, resp.data.css);
          }
        } else {
          showNotice(select, 'Error updating status');
        }
      }).fail(() => {
        showNotice(select, 'AJAX error');
      });
    }
  });

  const filterSelect = document.getElementById('bdp_filter_status');
  if (filterSelect) {
    filterSelect.addEventListener('change', () => {
      const status = filterSelect.value;
      jQuery.post(bdp_ajax.url, {
        action: 'bdp_filter_messages',
        nonce: bdp_ajax.filter_nonce,
        status_filter: status
      }, (resp) => {
        if (resp.success) {
          document.getElementById('bdp-messages-body').innerHTML = resp.data.rows;
          document.getElementById('messages-pagination').innerHTML = resp.data.pagination;
        } else {
          showNotice(filterSelect, 'Error loading messages');
        }
      }).fail(() => {
        showNotice(filterSelect, 'AJAX error');
      });
    });
  }
})();

(function(){
  const card = document.querySelector('.bdp-card--messages');
  if (!card) return;

  const labelEl = card.querySelector('.js-bdp-msg-label');
  const statEl  = card.querySelector('.js-bdp-msg-stat');
  const fillEl  = card.querySelector('.js-bdp-msg-fill');
  const bar     = card.querySelector('.bdp-card__bar');
  const valueEl = card.querySelector('.bdp-card__value');

  function setTone(cls){
    bar.classList.remove('is-good','is-warn','is-bad','is-neutral');
    if (cls) bar.classList.add(cls);
  }

  function updateCard(status){
    const total     = parseInt(card.dataset.total || '0', 10);
    const completed = parseInt(card.dataset.completed || '0', 10);
    const pending   = parseInt(card.dataset.pending || '0', 10);
    const canceled  = parseInt(card.dataset.canceled || '0', 10);

    let label, value, progress, tone;
    switch (status) {
      case null:
      case undefined:
        label    = wp.i18n ? wp.i18n.__('Total (last 7 days):','blitz-dock') : 'Total (last 7 days):';
        value    = total;
        progress = total > 0 ? 100 : 0;
        tone     = 'is-neutral';
        break;
      case 'pending':
        label = wp.i18n ? wp.i18n.__('Pending (last 7 days):','blitz-dock') : 'Pending (last 7 days):';
        value = pending;
        progress = total ? Math.round((pending / total) * 100) : 0;
        tone = 'is-warn';
        break;
      case 'canceled':
        label = wp.i18n ? wp.i18n.__('Canceled (last 7 days):','blitz-dock') : 'Canceled (last 7 days):';
        value = canceled;
        progress = total ? Math.round((canceled / total) * 100) : 0;
        tone = 'is-bad';
        break;
      case 'completed':
        label = wp.i18n ? wp.i18n.__('Completed (last 7 days):','blitz-dock') : 'Completed (last 7 days):';
        value = completed;
        const den = Math.max(1, completed + pending);
        progress = Math.round((completed / den) * 100);
        tone = 'is-good';
        break;
    }

    labelEl.textContent = label;
    statEl.textContent  = (value || 0).toLocaleString();
    fillEl.style.width  = (progress || 0) + '%';
    bar.setAttribute('aria-valuenow', String(progress || 0));
    setTone(tone);
    if (valueEl) valueEl.textContent = (value || 0).toLocaleString();
  }

  document.addEventListener('click', function(e){
    const chip = e.target.closest('.bdp-status-chip[data-status]');
    if (!chip || !card.contains(chip)) return;
    const status = chip.getAttribute('data-status');
    card.querySelectorAll('.bdp-status-chip').forEach(el => el.removeAttribute('aria-current'));
    chip.setAttribute('aria-current','true');
    updateCard(status);
  });

  document.addEventListener('keydown', function(e){
    const chip = e.target.closest('.bdp-status-chip[data-status]');
    if (!chip || !card.contains(chip)) return;
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      chip.click();
    }
  });

  updateCard(null);
})();

(function(){
  const card = document.querySelector('.bdp-card--messages');
  if (!card) return;

  const labelEl = card.querySelector('.js-bdp-msg-label');
  const statEl  = card.querySelector('.js-bdp-msg-stat');
  const fillEl  = card.querySelector('.js-bdp-msg-fill');
  const bar     = card.querySelector('.bdp-card__bar');

  function setTone(cls){
    bar.classList.remove('is-good','is-warn','is-bad');
    if (cls) bar.classList.add(cls);
  }

  function updateCard(status){
    const total     = parseInt(card.dataset.total || '0', 10);
    const completed = parseInt(card.dataset.completed || '0', 10);
    const pending   = parseInt(card.dataset.pending || '0', 10);
    const canceled  = parseInt(card.dataset.canceled || '0', 10);

    let label, value, progress, tone;
    switch ((status || 'completed')) {
      case 'pending':
        label = wp.i18n ? wp.i18n.__('Pending (last 7 days):','blitz-dock') : 'Pending (last 7 days):';
        value = pending;
        progress = total ? Math.round((pending / total) * 100) : 0;
        tone = 'is-warn';
        break;
      case 'canceled':
        label = wp.i18n ? wp.i18n.__('Canceled (last 7 days):','blitz-dock') : 'Canceled (last 7 days):';
        value = canceled;
        progress = total ? Math.round((canceled / total) * 100) : 0;
        tone = 'is-bad';
        break;
      case 'completed':
      default:
        label = wp.i18n ? wp.i18n.__('Completed (last 7 days):','blitz-dock') : 'Completed (last 7 days):';
        value = completed;
        const den = Math.max(1, completed + pending);
        progress = Math.round((completed / den) * 100);
        tone = 'is-good';
        break;
    }

    labelEl.textContent = label;
    statEl.textContent  = (value || 0).toLocaleString();
    fillEl.style.width  = (progress || 0) + '%';
    bar.setAttribute('aria-valuenow', String(progress || 0));
    setTone(tone);
  }

  document.addEventListener('click', function(e){
    const btn = e.target.closest('.bdp-status-label[data-status]');
    if (!btn || !card.contains(btn)) return;
    const status = btn.getAttribute('data-status');
    card.querySelectorAll('.bdp-status-label').forEach(b => b.setAttribute('aria-pressed', b === btn ? 'true' : 'false'));
    updateCard(status);
  });

  updateCard('completed');
})();
