import './stimulus_bootstrap.js';
import '@hotwired/turbo';

/*
 * Disable Turbo Drive globally — it intercepts page navigation via AJAX,
 * which prevents CDN-loaded scripts (Chart.js, Leaflet) from re-executing
 * on each page, causing blank maps and charts.
 */
import { Turbo } from '@hotwired/turbo';
Turbo.session.drive = false;

import './styles/app.css';
