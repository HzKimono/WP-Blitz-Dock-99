(function($) {

  // — Marka adı mapping (sosyal ve mağaza için) —
  const brandNames = {
    whatsapp: "WhatsApp",
    email:    "E-Mail",
    twitter:  "Twitter",
    facebook: "Facebook",
    instagram:"Instagram",
    linkedin: "LinkedIn",
    youtube:  "YouTube",
    telegram: "Telegram",
    reddit:   "Reddit",
    tiktok:   "TikTok"
  };

  const ecommNames = {
    amazon:      "Amazon",
    hepsiburada: "Hepsiburada",
    sahibinden:  "Sahibinden",
    shopify:     "Shopify",
    temu:        "Temu",
    trendyol:    "Trendyol"
  };
  const iconMap = {};
  (pluginData.dockItems || []).forEach(item => {
    iconMap[item.slug] = pluginData.iconsBaseURL + item.icon;
  });

  function renderDockItems() {
    const container = $('#bdp-home-screen').empty();
    (pluginData.dockItems || []).forEach(item => {
      $('<div>', {
        class: 'bdp-dock-item',
        role: 'button',
        tabindex: 0,
        'data-target': 'bdp-topic-' + item.slug,
        'aria-label': item.label
      }).append(
        $('<img>', {
          src: iconMap[item.slug] || pluginData.iconsBaseURL + 'default.png',
          alt: item.label,
          class: 'bdp-dock-icon'
        }),
        $('<span>', { class: 'bdp-dock-label', text: item.label })
      ).appendTo(container);
    });
  }

  // — Yardımcı fonksiyonlar —
  function getSocialLabel(id) {
    return id ? (brandNames[id] || (id.charAt(0).toUpperCase() + id.slice(1))) : "";
  }
  function getEcommLabel(id) {
    return id ? (ecommNames[id] || (id.charAt(0).toUpperCase() + id.slice(1))) : "";
  }

  function showHome(homeScreen, topicSections) {
    homeScreen.css('display', 'flex');
    topicSections.css('display', 'none');
  }

  function showTopic(topicId, homeScreen, topicSections) {
    if (topicId === 'bdp-topic-social') renderSocialLinks();
    if (topicId === 'bdp-topic-ecomm')  renderEcommLinks();
    homeScreen.css('display', 'none');
    topicSections.css('display', 'none');
    $('#' + topicId).css('display', 'flex');
  }

function togglePanel(toggleBtn, panel, homeScreen, topicSections) {
    const isHidden = panel.attr('aria-hidden') === 'true';
    if (isHidden) {
     panel.css('display', 'flex').attr('aria-hidden', 'false').addClass('expanded');
      toggleBtn.attr('aria-expanded', 'true');
      $('html').addClass('bdp-open');       // html elementine ekledik
      renderSocialLinks();
      renderEcommLinks();
      showHome(homeScreen, topicSections);
    } else {
      panel.css('display', 'none').attr('aria-hidden', 'true').removeClass('expanded');
      toggleBtn.attr('aria-expanded', 'false');
      $('html').removeClass('bdp-open');    // html elementinden kaldırdık
    }
  }

  function renderSocialLinks() {
    const container = $('#bdp-social-links').empty();
    (pluginData.socialLinks || []).forEach(link => {
      const id = (link.platform || link.name || '').toLowerCase();
      if (!id || !link.url) return;
      $('<a>', {
        href: link.url,
        target: '_blank',
        rel: 'noopener noreferrer',
        class: 'bdp-social-link',
        'data-id': id
      }).append(
        $('<img>', {
          src: pluginData.iconsBaseURL + id + '.png',
          alt: getSocialLabel(id),
          onerror: `this.src='${pluginData.iconsBaseURL}default.png'`
        }),
        $('<span>').text(getSocialLabel(id))
      ).appendTo(container);
    });
  }

  function renderEcommLinks() {
    const container = $('#bdp-ecomm-links').empty();
    (pluginData.ecommLinks || []).forEach(link => {
      const id = (link.name || '').toLowerCase();
      if (!id || !link.url) return;
      $('<a>', {
        href: link.url,
        target: '_blank',
        rel: 'noopener noreferrer',
        class: 'bdp-ecomm-link',
        'data-id': id
      }).append(
        $('<img>', {
          src: pluginData.iconsBaseURL + id + '.png',
          alt: getEcommLabel(id),
          onerror: `this.src='${pluginData.iconsBaseURL}default.png'`
        }),
        $('<span>').text(getEcommLabel(id))
      ).appendTo(container);
    });
  }
  
   function logEvent(type, topic, target, subtype = '') {
    jQuery.post(bdp_ajax.url, {
     action: 'blitz_dock_log_event',
      nonce:  bdp_ajax.log_nonce,
      event_type: type,
       event_topic: topic,
      event_target: target,
      event_subtype: subtype
    });
  }
  function init() {
    console.log("✅ Blitz Dock initialized.");

    const toggleBtn     = $('#bdp-toggle, .dock-avatar');
    const panel         = $('#bdp-chat-panel');
    const closeBtn      = $('#bdp-close-chat');
    const homeScreen    = $('#bdp-home-screen');
      renderDockItems();
    let navButtons      = homeScreen.find('.bdp-dock-item');
    const topicSections = $('.bdp-topic');
    const backButtons   = $('.bdp-back-btn');
    const ctaBox        = $('#bdp-cta-message');
    const form          = $('#bdp-message-form');

    if (!toggleBtn.length || !panel.length) return;

   toggleBtn.on('click', function(e) {
      e.preventDefault();
      const wasHidden = panel.attr('aria-hidden') === 'true';
      togglePanel($(this), panel, homeScreen, topicSections);
      if (wasHidden) {
        logEvent('view', 'panel_open', 'dock_avatar');
      }
    });
    closeBtn.on('click', () => {
      panel.css('display','none').attr('aria-hidden','true');
      toggleBtn.attr('aria-expanded','false');
      $('html').removeClass('bdp-open');  // html’den de temizle
    });

     homeScreen.on('click', '.bdp-dock-item', function() {
      navButtons.removeClass('active');
      $(this).addClass('active');
      const target = $(this).data('target');
      showTopic(target, homeScreen, topicSections);
      if (target === 'bdp-topic-social')    logEvent('click', 'social_links', 'social_links');
      if (target === 'bdp-topic-ecomm')     logEvent('click', 'ecomm_links',  'ecomm_links');
      if (target === 'bdp-topic-location')  logEvent('click', 'location',     'map_embed');
      if (target === 'bdp-topic-message')   logEvent('click', 'message',      'message_form');
      if (target === 'bdp-topic-faq')       logEvent('click', 'faq',          'faq');


    });

    $('#bdp-social-links').on('click', '.bdp-social-link', function() {
      const platform = $(this).data('id');
      logEvent('click', 'social_links', 'social_links', platform);
    });
    $('#bdp-ecomm-links').on('click', '.bdp-ecomm-link', function() {
      const platform = $(this).data('id');
      logEvent('click', 'ecomm_links', 'ecomm_links', platform);
    });
    backButtons.on('click', () => showHome(homeScreen, topicSections));

    $(document).on('keydown', e => {
      if (e.key === 'Escape' && panel.attr('aria-hidden') === 'false') {
        panel.css('display','none').attr('aria-hidden','true');
        toggleBtn.attr('aria-expanded','false');
        $('html').removeClass('bdp-open');
      }
    });

    // CTA ve shake efektleri aynı...
    if (ctaBox.length) {
      setTimeout(() => {
        ctaBox.css('display','inline-block').addClass('bdp-fade-in');
        toggleBtn.addClass('bdp-shake');
        setTimeout(() => { ctaBox.removeClass('bdp-fade-in').css('display','none'); toggleBtn.removeClass('bdp-shake'); }, 3000);
      }, pluginData.ctaDelay || 2000);
    }
    setInterval(() => { toggleBtn.addClass('bdp-shake'); setTimeout(() => toggleBtn.removeClass('bdp-shake'), 500); }, 10000);

    $('.bdp-faq-a').hide();
    $('.bdp-faq-q').on('click', function() {
      const $item = $(this).closest('.bdp-faq-item');
      $item.toggleClass('open').find('.bdp-faq-a').stop(true,true).slideToggle(200);
    });

    form.on('submit', function(e) {
      e.preventDefault();
      const data = {
        action: 'blitz_dock_submit_message',
        nonce:  pluginData.messageNonce,
        name:   form.find('[name="name"]').val().trim(),
        email:  form.find('[name="email"]').val().trim(),
        message:form.find('[name="message"]').val().trim()
      };
      if (!data.name || !data.email || !data.message) {
        alert('Lütfen tüm alanları doldurun.');
        return;
      }
      $.post(pluginData.ajaxUrl, data, function(resp) {
        if (resp.success) {
          form.hide();
          $('#bdp-message-success').text(resp.data).show();
          form[0].reset();
        logEvent('submit', 'message', 'message_form');
        } else {
          alert(resp.data || 'Bir hata oluştu.');
        }
      });
    });
   // CTA ve shake efektleri aynı...
    showHome(homeScreen, topicSections);
  }

  $(document).ready(init);

})(jQuery);
