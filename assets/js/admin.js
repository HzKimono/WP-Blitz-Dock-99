function logEvent(type, topic, target, subtype = '') {
  jQuery.post(bdp_ajax.url, {
    action: 'blitz_dock_log_event',
    nonce: bdp_ajax.log_nonce,
    event_type: type,
    event_topic: topic,
    event_target: target,
    event_subtype: subtype
  });
}

document.addEventListener('DOMContentLoaded', function () {
  // TAB SWITCHING␊
  const tabs = document.querySelectorAll('.bdp-tabs button');
  const panels = document.querySelectorAll('.bdp-tab-panel');
  tabs.forEach(tab => {
    tab.addEventListener('click', function (e) {
      e.preventDefault();
      tabs.forEach(t => t.setAttribute('aria-selected', 'false'));
      panels.forEach(p => p.classList.remove('active'));
      this.setAttribute('aria-selected', 'true');
      const panel = document.getElementById(this.getAttribute('aria-controls'));
      if (panel) panel.classList.add('active');
    });
  });

  // ==== SOSYAL MEDYA REPEATER ====
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
      msg.innerText = 'Henüz sosyal medya bağlantısı eklenmemiş. Eklemek için butona tıklayın.';
      socialContainer.after(msg);
    }
  }

  if (addSocialBtn && socialContainer) {
    addSocialBtn.addEventListener('click', () => {
      removeEmptyInfoMsg();
      const row = document.createElement('div');
      row.className = 'bdp-social-row';
      let options = '<option value="">Platform Seç</option>';
      Object.keys(socialPlatforms).forEach(key => {
        const label = socialPlatforms[key];
        options += `<option value="${key}">${label}</option>`;
      });
      row.innerHTML = `
        <img src="${pluginData.iconsBaseURL}default.png" width="24" height="24">
        <select name="bdp_social_links[0][platform]">${options}</select>
        <input type="url" name="bdp_social_links[0][url]" placeholder="https://">
        <button type="button" class="button bdp-remove-link">Sil</button>
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
    faqContainer.querySelectorAll('.bdp-faq-row').forEach((row, idx) => {
      row.querySelector('input[type="text"]').name = `bdp_faq_items[${idx}][question]`;
      row.querySelector('textarea').name = `bdp_faq_items[${idx}][answer]`;
    });
  }

  if (addFaqBtn && faqContainer) {
    addFaqBtn.addEventListener('click', () => {
      const row = document.createElement('div');
      row.className = 'bdp-faq-row';
      row.style.display = 'flex';
      row.style.flexDirection = 'column';
      row.style.gap = '8px';
      row.style.marginBottom = '1rem';
      row.innerHTML = `
        <input type="text" name="bdp_faq_items[0][question]" placeholder="Soru Başlığı">
        <textarea name="bdp_faq_items[0][answer]" rows="3" placeholder="Cevap"></textarea>
        <button type="button" class="button bdp-remove-faq">Sil</button>
      `;
      faqContainer.appendChild(row);
      reindexFaqRows();
    });

    faqContainer.addEventListener('click', function(e) {
      if (e.target.classList.contains('bdp-remove-faq')) {
        e.target.closest('.bdp-faq-row').remove();
        reindexFaqRows();
      }
    });
  }

  // ==== ONLINE MAĞAZALAR REPEATER ====
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
    // İlk yüklemede mevcut satırlar için ikonları güncelle
    ecommContainer.querySelectorAll('.bdp-ecomm-row').forEach(updateEcommIcon);

    addEcommBtn.addEventListener('click', () => {
      const row = document.createElement('div');
      row.className = 'bdp-ecomm-row';
      let options = '<option value="">Platform Seç</option>';
      ecommPlatforms.forEach(p => {
        const label = p.charAt(0).toUpperCase() + p.slice(1);
        options += `<option value="${p}">${label}</option>`;
      });
      row.innerHTML = `
        <img src="${pluginData.ecommIconsBaseURL}default.png" width="24" height="24" class="bdp-ecomm-icon">
        <select name="bdp_ecomm_links[0][name]" class="bdp-ecomm-select">${options}</select>
        <input type="url" name="bdp_ecomm_links[0][url]" placeholder="https://">
        <button type="button" class="button bdp-remove-ecomm">Sil</button>`;
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

  // ==== LOCATION EMBED PREVIEW ====
  const ta = jQuery('#bdp_location_embed');
  const previewContainer = jQuery('#bdp-location-preview .bdp-map-responsive');

  const html_entity_decode = (str) => {
    const tmp = document.createElement('textarea');
    tmp.innerHTML = str;
    return tmp.value;
  };

 function render() {
    const code = html_entity_decode( ta.val().trim() );
    previewContainer.html(code);
  }

  ta.on('input change paste', function(){ setTimeout(render, 100); });
  render();
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
});
// ==== LIVE CHAT ADMIN PANEL ====
const adminChatHistory = document.getElementById('bdp-admin-chat-history');
const adminChatForm    = document.getElementById('bdp-admin-chat-form');

if (adminChatHistory && adminChatForm) {
  const chatId = parseInt(adminChatForm.getAttribute('data-chat-id'), 10);
  let lastId = 0;

  // ─── renderMessage: arrow function olarak tanımlandı
  const renderMessage = (msg) => {
    const p = document.createElement('p');
    const label = msg.sender_type === 'admin'
      ? '🛠️ Support Team:'
      : `👤 ${msg.sender_name}:`;
    p.innerText = `${label} ${msg.message}`;
    adminChatHistory.appendChild(p);
    adminChatHistory.scrollTop = adminChatHistory.scrollHeight;
    lastId = msg.id;
  };

  // ─── poll: arrow function olarak tanımlandı
  const poll = () => {
    const data = {
      action:   'blitz_dock_live_chat_poll',
      nonce:    pluginData.messageNonce,
      chat_id:  chatId,
      last_id:  lastId
    };
    jQuery.post(pluginData.ajaxUrl, data, (resp) => {
      if (resp.success) {
        resp.data.messages.forEach(renderMessage);
      }
    });
  };

  // İlk çek ve sonra her 5 saniyede bir
  poll();
  setInterval(poll, 5000);

  // Mesaj gönderme handler’ı
  adminChatForm.addEventListener('submit', (e) => {
    e.preventDefault();
    const input = adminChatForm.querySelector('[name="message"]');
    const msg = input.value.trim();
    if (!msg) return;

   jQuery.post(pluginData.ajaxUrl, {
      action:   'blitz_dock_admin_send_message',
      nonce:    pluginData.messageNonce,
      chat_id:  chatId,
      message:  msg
    }, (resp) => {
      if (resp.success) {
        renderMessage({
          id: 0,
          sender_type: 'admin',
          sender_name: 'Support Team',
          message: msg
        });
        input.value = '';
      } else {
        alert('Error sending message');
      }
    });
  });
}

// ==== CHAT LIST POLLING ====
const pendingBody = document.getElementById('bdp-pending-chats');
const activeBody  = document.getElementById('bdp-active-chats');

if (pendingBody && activeBody) {
  const escapeHTML = (str) => str.replace(/[&<>"']/g, (c) => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[c]));

  const renderTables = (data) => {
    const pRows = data.pending.map(p => `
      <tr>
        <td>${escapeHTML(p.name)}</td>
        <td>${escapeHTML(p.email)}</td>
        <td>${escapeHTML(p.phone)}</td>
        <td>${escapeHTML(p.first_message)}</td>
        <td>${escapeHTML(p.created_at)}</td>
        <td><button type="button" class="button bdp-accept-chat" data-id="${p.id}">📩 Accept</button></td>
      </tr>`).join('');
    pendingBody.innerHTML = pRows || `<tr><td colspan="6">No pending requests.</td></tr>`;

    const aRows = data.active.map(a => `
      <tr>
        <td>${escapeHTML(a.name)}</td>
        <td>${escapeHTML(a.email)}</td>
        <td>${escapeHTML(a.phone)}</td>
        <td>${escapeHTML(a.created_at)}</td>
        <td>
          <a class="button" href="admin.php?page=blitz-dock-live-chat&chat_id=${a.id}">Open</a>
          <button type="button" class="button bdp-close-chat" data-id="${a.id}" style="margin-left:4px;">Close</button>
        </td>
      </tr>`).join('');
    activeBody.innerHTML = aRows || `<tr><td colspan="5">No active chats.</td></tr>`;
  };

  const pollChats = () => {
    jQuery.post(pluginData.ajaxUrl, { action:'blitz_dock_check_chats', nonce: pluginData.messageNonce }, (resp) => {
      if (resp.success) {
        renderTables(resp.data);
      }
    });
  };

  pollChats();
  setInterval(pollChats, 5000);

  document.addEventListener('click', (e) => {
    if (e.target.classList.contains('bdp-accept-chat')) {
      const id = e.target.getAttribute('data-id');
      jQuery.post(pluginData.ajaxUrl, { action:'blitz_dock_accept_chat', nonce: pluginData.messageNonce, chat_id: id }, () => pollChats());
    }
    if (e.target.classList.contains('bdp-close-chat')) {
      const id = e.target.getAttribute('data-id');
      jQuery.post(pluginData.ajaxUrl, { action:'blitz_dock_close_chat', nonce: pluginData.messageNonce, chat_id: id }, () => pollChats());
    }
  });
}