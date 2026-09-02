import Alpine from 'alpinejs';
import bookingForm from './bookingForm';
import carousel from './carousel';
import 'flowbite';

window.Alpine = Alpine;
Alpine.data('bookingForm', bookingForm);
Alpine.data('carousel', carousel);
Alpine.start();
