import { startStimulusApp } from '@symfony/stimulus-bundle';
import $ from 'jquery';
import select2 from 'select2';
import 'select2/dist/css/select2.min.css';

select2(window, $);
window.$ = window.jQuery = $;

startStimulusApp();
