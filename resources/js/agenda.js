import { Calendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';
import itLocale from '@fullcalendar/core/locales/it';

export function initAgenda(el, options = {}) {
    const calendar = new Calendar(el, {
        plugins: [dayGridPlugin, timeGridPlugin, interactionPlugin],
        locale: itLocale,
        initialView: window.innerWidth < 768 ? 'timeGridDay' : 'timeGridWeek',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,timeGridWeek,timeGridDay',
        },
        slotMinTime: '07:00:00',
        slotMaxTime: '21:00:00',
        nowIndicator: true,
        height: 'auto',
        events: options.feedUrl,

        // Trascinare su una fascia oraria crea l'appuntamento (come dice la pagina)
        selectable: true,
        selectMirror: true,
        select(info) {
            // In vista mese la selezione è di giornate intere: passiamo solo l'inizio
            // e lascia che sia il form a proporre la durata predefinita.
            if (options.onSelect) options.onSelect(info.start, info.allDay ? null : info.end);
            calendar.unselect();
        },

        // Trascinare o ridimensionare un evento sposta l'appuntamento
        editable: true,
        eventDrop(info) {
            if (options.onMove) options.onMove(info);
        },
        eventResize(info) {
            if (options.onMove) options.onMove(info);
        },

        eventClick(info) {
            if (options.onEventClick) options.onEventClick(info.event);
        },
    });

    calendar.render();

    // Ri-adatta la vista al resize (mobile/desktop)
    window.addEventListener('resize', () => {
        const view = window.innerWidth < 768 ? 'timeGridDay' : calendar.view.type;
        if (calendar.view.type !== view) calendar.changeView(view);
    });

    return calendar;
}
