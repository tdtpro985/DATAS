Chart.defaults.color = 'rgba(55,65,81,0.75)';
Chart.defaults.borderColor = 'rgba(0,0,0,0.07)';
Chart.defaults.font.family = "'Inter', sans-serif";

// Toggle timeline cards
function toggleTl(idx) {
    const card = document.getElementById('tlcard-' + idx);
    card.classList.toggle('open');
}

const BLUE='rgba(96,165,250,0.85)',PURPLE='rgba(167,139,250,0.85)',
      GREEN='rgba(52,211,153,0.85)',YELLOW='rgba(251,191,36,0.85)',
      ORANGE='rgba(255,122,0,0.85)',MUTED='rgba(148,163,184,0.6)';

const srcMap = { DPWH:ORANGE, BCI:BLUE, EGOV:PURPLE };
const stMap  = { 'For Execution':BLUE,'Awarded':GREEN,'For Bidding':YELLOW,'Priority':ORANGE };

// Activity chart
(function(){
    const data = window.SR_DATA?.activity30 ?? [];
    const phToday = new Date().toLocaleString('en-CA',{timeZone:'Asia/Manila'}).slice(0,10);
    const ctx = document.getElementById('actChart').getContext('2d');
    new Chart(ctx, { type:'bar', data:{
        labels: data.map(d=>d.label),
        datasets:[{ label:'Updates', data:data.map(d=>d.cnt),
            backgroundColor: data.map(d=>d.date===phToday?BLUE:'rgba(96,165,250,0.35)'),
            borderRadius:5, borderSkipped:false, maxBarThickness:30 }]
    }, options:{ responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{display:false}, tooltip:{backgroundColor:'#FFFFFF',borderColor:'rgba(0,0,0,0.12)', titleColor:'#000000', bodyColor:'#000000',borderWidth:1,
            callbacks:{title:i=>data[i[0].dataIndex].date,label:i=>` ${i.raw} update${i.raw!==1?'s':''}`}}},
        scales:{ x:{grid:{display:false},ticks:{maxRotation:45,font:{size:10}}},
                 y:{beginAtZero:true,ticks:{stepSize:1,precision:0},grid:{color:'rgba(0,0,0,0.07)'}}}
    }});
})();

// Source doughnut
(function(){
    const data = window.SR_DATA?.bySource ?? [];
    if (!data.length) return;
    const ctx = document.getElementById('srcChart').getContext('2d');
    new Chart(ctx, { type:'doughnut', data:{
        labels:data.map(s=>s.source),
        datasets:[{ data:data.map(s=>s.cnt), backgroundColor:data.map(s=>srcMap[s.source]||MUTED),
            borderWidth:2, borderColor:'#FFFFFF', hoverOffset:6 }]
    }, options:{ responsive:true, maintainAspectRatio:false, cutout:'68%',
        plugins:{ legend:{position:'bottom',labels:{padding:10,boxWidth:10,font:{size:10}}},
                  tooltip:{backgroundColor:'#FFFFFF',borderColor:'rgba(0,0,0,0.1)', titleColor:'#000000', bodyColor:'#000000',borderWidth:1} }
    }});
})();

// Status bar
(function(){
    const data = window.SR_DATA?.byStatus ?? [];
    if (!data.length) return;
    const ctx = document.getElementById('stChart').getContext('2d');
    new Chart(ctx, { type:'bar', data:{
        labels:data.map(s=>s.proj_status),
        datasets:[{ label:'Projects', data:data.map(s=>s.cnt),
            backgroundColor:data.map(s=>stMap[s.proj_status]||MUTED),
            borderRadius:6, borderSkipped:false, maxBarThickness:56 }]
    }, options:{ responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{display:false}, tooltip:{backgroundColor:'#FFFFFF',borderWidth:1,borderColor:'rgba(0,0,0,0.1)', titleColor:'#000000', bodyColor:'#000000'}},
        scales:{ x:{grid:{display:false}}, y:{beginAtZero:true,ticks:{stepSize:1,precision:0},grid:{color:'rgba(0,0,0,0.07)'}}}
    }});
})();

// Pipeline bar
(function(){
    const labels = ['Assigned','Contacted','Quoted','Sales Qual.','To Win','Complete'];
    const counts = window.SR_DATA?.pipeline ?? [0,0,0,0,0,0];
    const colors = [MUTED,BLUE,PURPLE,YELLOW,GREEN,'rgba(52,211,153,1)'];
    const ctx = document.getElementById('plChart').getContext('2d');
    new Chart(ctx, { type:'bar', data:{
        labels, datasets:[{ label:'Count', data:counts, backgroundColor:colors,
            borderRadius:6, borderSkipped:false, maxBarThickness:48 }]
    }, options:{ responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{display:false}, tooltip:{backgroundColor:'#FFFFFF',borderWidth:1,borderColor:'rgba(0,0,0,0.1)', titleColor:'#000000', bodyColor:'#000000'}},
        scales:{ x:{grid:{display:false}}, y:{beginAtZero:true,ticks:{stepSize:1,precision:0},grid:{color:'rgba(0,0,0,0.07)'}}}
    }});
})();
