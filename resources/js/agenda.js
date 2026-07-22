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
        eventClick(info) {
            if (options.onEventClick) options.onEventClick(info.event);
        },
        dateClick(info) {
            if (options.onDateClick) options.onDateClick(info.date);
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
