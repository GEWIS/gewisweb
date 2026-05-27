import { startStimulusApp } from '@symfony/stimulus-bundle';
import ModalFormTargetController from './controllers/modal_form_target_controller.js';
import ParticipantsTableController from './controllers/activity/participants-table_controller.ts';
import PrintController from './controllers/print_controller.js';

const app = startStimulusApp();
app.register('modal-form-target', ModalFormTargetController);
app.register('participants-table', ParticipantsTableController);
app.register('print', PrintController);
