import { startStimulusApp } from '@symfony/stimulus-bundle';
import ActivityItemController from './controllers/activity_item_controller.js';
import DescriptionToggleController from './controllers/description_toggle_controller.js';
import InfiniteScrollController from './controllers/infinite_scroll_controller.js';
import ModalFormTargetController from './controllers/modal_form_target_controller.js';
import ParticipantsTableController from './controllers/activity/participants-table_controller.ts';
import PrintController from './controllers/print_controller.js';

const app = startStimulusApp();
app.register('activity-item', ActivityItemController);
app.register('description-toggle', DescriptionToggleController);
app.register('infinite-scroll', InfiniteScrollController);
app.register('modal-form-target', ModalFormTargetController);
app.register('participants-table', ParticipantsTableController);
app.register('print', PrintController);
