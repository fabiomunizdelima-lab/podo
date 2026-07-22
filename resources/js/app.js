import './bootstrap';
import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

// Il calendario viene inizializzato on-demand nella pagina Agenda
import { initAgenda } from './agenda';
window.initAgenda = initAgenda;
