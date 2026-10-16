/**
 * Front-end entry point.
 *
 * Interactivity is Alpine.js (https://alpinejs.dev). Each behaviour is a
 * component in ./components, registered here with Alpine.data() and used in
 * Twig with x-data="name". Keep logic in the component and markup to plain
 * property and method names: templates stay readable, and the site can move
 * to Alpine's CSP build (@alpinejs/csp) without changing them.
 */
import Alpine from 'alpinejs';

import colorScheme from './components/color-scheme.js';
import disclosure from './components/disclosure.js';

Alpine.data('colorScheme', colorScheme);
Alpine.data('disclosure', disclosure);

// Lets plugins and the browser devtools extension find Alpine.
window.Alpine = Alpine;

Alpine.start();
