

import Alpine from 'alpinejs';
if (document.getElementById('chartIngresos')) {
    import('chart.js/auto').then(({ default: Chart }) => {
        window.Chart = Chart;
        document.dispatchEvent(new Event('tt:chart-ready'));
    }).catch(() => {
        document.getElementById('chartIngresos').replaceWith(document.createTextNode('No se pudo cargar el gráfico. Consulta los totales del panel.'));
    });
}
if (document.getElementById('calendario')) {
    Promise.all([import('@fullcalendar/core'), import('@fullcalendar/core/locales/es'), import('@fullcalendar/daygrid'),
        import('@fullcalendar/timegrid'), import('@fullcalendar/interaction'), import('@fullcalendar/list')]).then(([core, es, day, time, interaction, list]) => {
        window.FullCalendar = { Calendar: class extends core.Calendar {
            constructor(element, options) { super(element, { plugins: [day.default,time.default,interaction.default,list.default], locales: [es.default], ...options }); }
        }};
        document.dispatchEvent(new Event('tt:calendar-ready'));
    }).catch(() => { document.getElementById('calendarioEstado').textContent = 'No se pudo cargar el calendario. Recarga la página para reintentar.'; });
}

window.Alpine = Alpine;

Alpine.start();
