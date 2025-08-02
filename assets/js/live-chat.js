(function($){
  function initLiveChat(){
    const entryForm    = $('#bdp-livechat-entry');
    const historyBox   = $('#bdp-live-chat-history');
    const sendForm     = $('#bdp-live-chat-form');
    const sendBtn      = sendForm.find('button');
    const closeBtn     = $('#bdp-close-chat');
    const endChatBtn   = $('#bdp-end-chat');
    const ratingBox    = $('#bdp-chat-rating');
    const ratingStars  = ratingBox.find('.bdp-star');
    const ratingSubmit = $('#bdp-rating-submit');
    const ratingComment= $('#bdp-rating-comment');
    let ratingValue    = 0;
    const waitBox      = $('<div id="bdp-live-chat-wait" style="margin-top:10px;color:#888;"></div>');
    historyBox.before(waitBox);

    if (!entryForm.length) return;

    const memoryStore = {};
    let usingMemory = false;
    const storage = (() => {
      try {
        const t = 'bdp_test';
        window.localStorage.setItem(t, '1');
        window.localStorage.removeItem(t);
        return window.localStorage;
      } catch (e) {
        try {
          const t = 'bdp_test';
          window.sessionStorage.setItem(t, '1');
          window.sessionStorage.removeItem(t);
          return window.sessionStorage;
        } catch (e2) {
          usingMemory = true;
          console.warn('⚠️ Incognito mode detected. Session persistence may be limited.');
          return {
            getItem: key => memoryStore[key],
            setItem: (key, val) => { memoryStore[key] = val; },
            removeItem: key => { delete memoryStore[key]; }
          };
        }
      }
    })();

    if (usingMemory) {
      waitBox.text('⚠️ Incognito mode detected. Session persistence may be limited.').show();
    }

    let chatId      = parseInt(storage.getItem('bdp_chat_id') || '0', 10);
    let lastId      = 0;
    let visitorName = storage.getItem('bdp_chat_name')   || '';
    let status      = storage.getItem('bdp_chat_status') || 'pending';
    let lastStatus  = status;
    let pollTimer   = null;

    if (usingMemory && !chatId) {
      setTimeout(() => {
        if (!confirm('Your previous chat session could not be restored. Would you like to start a new chat?')) {
          return;
        }
      }, 0);
    }

    function startPolling(){
      poll();
      pollTimer = setInterval(poll, 5000);
    }

    function stopPolling(){
      if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
      }
    }

    function showRating(){
      stopPolling();
      sendForm.hide();
      historyBox.hide();
      ratingBox.show();
      endChatBtn.hide();
    }

    function resetChat(){
      stopPolling();
      chatId      = 0;
      lastId      = 0;
      visitorName = '';
      status      = 'pending';
      lastStatus  = 'pending';
      storage.removeItem('bdp_chat_id');
      storage.removeItem('bdp_chat_name');
      storage.removeItem('bdp_chat_status');
      historyBox.empty().hide();
      sendForm.hide();
      ratingBox.hide();
      sendBtn.prop('disabled', true);
      endChatBtn.hide();
      entryForm.show()[0].reset();
      waitBox.hide();
    }

    function renderMessage(msg){
      const label = msg.sender_type === 'admin'
        ? '🛠️ Support Team:'
        : '👤 ' + msg.sender_name + ':';
      const p = $('<p>')
        .addClass(msg.sender_type === 'admin' ? 'bdp-msg-admin' : 'bdp-msg-visitor')
        .text(label + ' ' + msg.message);
      historyBox.append(p);
      historyBox.scrollTop(historyBox[0].scrollHeight);
      lastId = msg.id;
    }

    function poll(){
      if (!chatId) return;
      $.post(pluginData.ajaxUrl, {
        action:   'blitz_dock_live_chat_poll',
        nonce:    pluginData.messageNonce,
        chat_id:  chatId,
        last_id:  lastId
      }, function(resp){
        if (resp.success) {
          status = resp.data.status;
          storage.setItem('bdp_chat_status', status);
          resp.data.messages.forEach(renderMessage);

           if (status === 'pending') {
            waitBox.text('Please wait... a support agent will join shortly.').show();
            sendBtn.prop('disabled', true);
            endChatBtn.show();
          } else if (status === 'active') {
            if (lastStatus !== 'active') {
              waitBox.hide();
            }
            sendForm.show();
            historyBox.show();
            sendBtn.prop('disabled', false);
            endChatBtn.show();
          } else if (status === 'closed') {
            if (!storage.getItem('bdp_rating_' + chatId)) {
              showRating();
            } else {
              resetChat();
            }
            endChatBtn.hide();
            return;
          }
          lastStatus = status;
        }
      });
    }

    entryForm.on('submit', function(e){
      e.preventDefault();
      if (chatId && storage.getItem('bdp_chat_status') !== 'closed') {
        alert('An active chat session already exists.');
        return;
      }
      const name     = entryForm.find('[name="lc_name"]').val().trim();
      const email    = entryForm.find('[name="lc_email"]').val().trim();
      const phone    = entryForm.find('[name="lc_phone"]').val().trim();
      const firstMsg = entryForm.find('[name="lc_message"]').val().trim();
      if (!name || !email || !firstMsg) {
        alert('Please fill required fields');
        return;
      }
      $.post(pluginData.ajaxUrl, {
        action:  'blitz_dock_live_chat_request',
        nonce:   pluginData.messageNonce,
        name:    name,
        email:   email,
        phone:   phone,
        message: firstMsg
      }, function(resp){
        if (resp.success) {
          chatId = resp.data.chat_id;
          visitorName = name;
          storage.setItem('bdp_chat_id', chatId);
          storage.setItem('bdp_chat_name', visitorName);
          sendForm.show();
          historyBox.show();
          sendBtn.prop('disabled', true);
          endChatBtn.show();
          renderMessage({
            id:          0,
            sender_type: 'visitor',
            sender_name: visitorName,
            message:     firstMsg
          });
          startPolling();
        } else {
          alert(resp.data || resp.message || 'Error');
        }
      });
    });

    sendForm.on('submit', function(e){
      e.preventDefault();
      const msgInput = sendForm.find('[name="lc_message"]');
      const message  = msgInput.val().trim();
      if (!message || !chatId) return;
      sendBtn.prop('disabled', true);
      $.post(pluginData.ajaxUrl, {
        action:  'blitz_dock_live_chat_send',
        nonce:   pluginData.messageNonce,
        chat_id: chatId,
        name:    visitorName,
        message: message
      }, function(resp){
        if (resp.success) {
          renderMessage({
            id:          0,
            sender_type: 'visitor',
            sender_name: visitorName,
            message:     message
          });
          msgInput.val('');
          setTimeout(() => sendBtn.prop('disabled', false), 1000);
        } else {
          alert(resp.data || resp.message || 'Error');
          sendBtn.prop('disabled', false);
        }
      });
    });

    sendForm.on('input', '[name="lc_message"]', function(){
      sendBtn.prop('disabled', !$(this).val().trim());
    });

    endChatBtn.on('click', function(){
      if (!chatId) return;
      if (!confirm('End this chat?')) return;
      $.post(pluginData.ajaxUrl, {
        action:  'blitz_dock_close_chat_visitor',
        nonce:   pluginData.messageNonce,
        chat_id: chatId
      }, function(){
        storage.setItem('bdp_chat_status', 'closed');
        if (!storage.getItem('bdp_rating_' + chatId)) {
          showRating();
        } else {
          resetChat();
        }
      });
    });

    ratingStars.on('click', function(){
      ratingValue = parseInt($(this).data('val'), 10);
      ratingStars.each(function(){
        $(this).text($(this).data('val') <= ratingValue ? '⭐' : '☆');
      });
    });

    ratingSubmit.on('click', function(e){
      e.preventDefault();
      if (!ratingValue) return;
      $.post(pluginData.ajaxUrl, {
        action:  'blitz_dock_submit_rating',
        nonce:   pluginData.messageNonce,
        chat_id: chatId,
        rating:  ratingValue,
        comment: ratingComment.val()
      }, function(){
        storage.setItem('bdp_rating_' + chatId, '1');
        resetChat();
      });
    });

    if (chatId && visitorName && storage.getItem('bdp_chat_status') !== 'closed') {
      entryForm.hide();
      sendForm.show();
      historyBox.show();
      sendBtn.prop('disabled', true);
      endChatBtn.show();
      startPolling();
    } else if (chatId && visitorName && storage.getItem('bdp_chat_status') === 'closed' && !storage.getItem('bdp_rating_' + chatId)) {
      showRating();
    }
  }

  $(document).ready(initLiveChat);
})(jQuery);