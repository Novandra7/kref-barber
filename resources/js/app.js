import Alpine from 'alpinejs';
import bookingForm from './bookingForm';
import carousel from './carousel';
import 'flowbite';
import './echo';

window.Alpine = Alpine;
Alpine.data('bookingForm', bookingForm);
Alpine.data('carousel', carousel);
Alpine.start();

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allow your team to quickly build robust real-time web applications.
 */

import './echo';
