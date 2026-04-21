import { startStimulusApp } from '@symfony/stimulus-bundle';
import CsrfProtectionController from './controllers/csrf_protection_controller.js';
import HelloController from './controllers/hello_controller.js';
import MapController from './controllers/map_controller.js';

const app = startStimulusApp();
app.register('csrf-protection', CsrfProtectionController);
app.register('hello', HelloController);
app.register('map', MapController);
