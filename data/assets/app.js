/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// start the Stimulus applicationn
import './bootstrap';


import './styles/app.scss';

import './scripts/mask.js';
import './scripts/auth.js';
import './scripts/form.js';
import './scripts/conditions.js';
import './scripts/requests.js';
import './scripts/legal_entity.js';
import './scripts/catalog.js';

import App from "./scripts/App.js";

window.App = App;
