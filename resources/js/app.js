import './bootstrap';
import Alpine from 'alpinejs';
import { initAgenda } from './agenda';

// ⚠️ Deve stare PRIMA di Alpine.start(): lo start percorre il DOM in modo
// sincrono ed esegue subito gli x-init, fra cui il mount() dell'agenda che
// chiama window.initAgenda. Assegnandolo dopo, in agenda risultava undefined
// e il calendario non veniva mai creato.
window.initAgenda = initAgenda;

window.Alpine = Alpine;
Alpine.start();
