jQuery(function($){
  const start   = $('#bdp-start-date');
  const end     = $('#bdp-end-date');
  const topic   = $('#bdp-topic-filter');
  const subtype = $('#bdp-subtype-filter');
  const pagination = $('#bdp-analytics-pagination');
  let topicChart, subChart;

  const renderTable = rows => {
    const body = $('#bdp-analytics-table tbody');
    body.empty();
    rows.forEach(r => {
      $('<tr>')
        .append(`<td>${r.created_at}</td>`)
        .append(`<td>${r.event_type}</td>`)
        .append(`<td>${r.event_topic}</td>`)
        .append(`<td>${r.event_target || ''}</td>`)
        .append(`<td>${r.event_subtype || ''}</td>`)
        .appendTo(body);
    });
  };

  const renderPagination = (current, total) => {
    if(!pagination.length) return;
    let html = '';
    for(let i=1; i<=total; i++) {
      const cls = i === current ? ' class="active"' : '';
      html += `<a href="#" data-page="${i}"${cls}>${i}</a> `;
    }
    pagination.html(html);
  };

  const load = (page=1) => {
    $.post(bdp_ajax.url, {
      action: 'bdp_filter_analytics',
      nonce: bdp_ajax.analytics_nonce,
      start_date: start.val(),
      end_date: end.val(),
      topic: topic.val(),
      subtype: subtype.val(),
      page: page
    }, resp => {
      if(!resp.success) return;
      const d = resp.data;
      $('#bdp-total-events').text(d.total);
      const tc = {click:0,view:0,submit:0};
      d.type_counts.forEach(t=>{tc[t.type]=parseInt(t.c,10)});
      $('#bdp-total-clicks').text(tc.click||0);
      $('#bdp-total-views').text(tc.view||0);
      $('#bdp-total-submits').text(tc.submit||0);
      renderTable(d.events);
      renderPagination(d.page, d.total_pages);

      const tl = d.topics.map(t=>t.topic); const tcg = d.topics.map(t=>parseInt(t.c,10));
      if(!topicChart){
        topicChart = new Chart(document.getElementById('bdp-topic-chart').getContext('2d'),{
          type:'doughnut',data:{labels:tl,datasets:[{data:tcg}]},options:{plugins:{legend:{position:'bottom'}}}
        });
      }else{ topicChart.data.labels=tl; topicChart.data.datasets[0].data=tcg; topicChart.update(); }

      const sl=d.sub_counts.map(s=>s.subtype); const sc=d.sub_counts.map(s=>parseInt(s.c,10));
      if(!subChart){
        subChart=new Chart(document.getElementById('bdp-subtype-chart').getContext('2d'),{
          type:'bar',data:{labels:sl,datasets:[{data:sc,backgroundColor:'#4e79a7'}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}
        });
      }else{ subChart.data.labels=sl; subChart.data.datasets[0].data=sc; subChart.update(); }

      let opts='<option value="">All Subtypes</option>';
      d.subtypes.forEach(s=>{opts+=`<option value="${s}">${s}</option>`});
      subtype.html(opts);
    });
  };

  start.on('change',()=>load());
  end.on('change',()=>load());
  topic.on('change',()=>{subtype.val('');load();});
  subtype.on('change',()=>load());
  pagination.on('click','a',function(e){e.preventDefault();load(parseInt($(this).data('page'),10));});

  load();
});