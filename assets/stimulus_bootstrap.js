import { startStimulusApp } from '@symfony/stimulus-bundle';

// Application-wide, domain-agnostic controllers.
import CollapsibleCollectionController from './controllers/application/collapsible_collection_controller.js';
import ConfirmModalController from './controllers/application/confirm_modal_controller.js';
import DescriptionToggleController from './controllers/application/description_toggle_controller.js';
import EditLockController from './controllers/application/edit_lock_controller.js';
import FormCollectionController from './controllers/application/form_collection_controller.js';
import FormStepperController from './controllers/application/form_stepper_controller.js';
import InfiniteScrollController from './controllers/application/infinite_scroll_controller.js';
import LabelChipsController from './controllers/application/label_chips_controller.js';
import LocalisedFieldsController from './controllers/application/localised_fields_controller.js';
import ModalCloseController from './controllers/application/modal_close_controller.js';
import ModalFormTargetController from './controllers/application/modal_form_target_controller.js';
import PrintController from './controllers/application/print_controller.js';

// Activity-specific controllers.
import ActivityItemController from './controllers/activity/activity_item_controller.js';
import SignupFieldController from './controllers/activity/signup_field_controller.js';
import SignupListController from './controllers/activity/signup_list_controller.js';

const app = startStimulusApp();

// Registered with flat identifiers so the templates keep using `data-controller="form-stepper"` etc. despite the
// subdirectories -- the path-based autoload would otherwise namespace them (e.g. `application--form-stepper`). The
// framework-scaffolded csrf_protection controller stays at the controllers/ root and autoloads as `csrf-protection`.
app.register('collapsible-collection', CollapsibleCollectionController);
app.register('confirm-modal', ConfirmModalController);
app.register('description-toggle', DescriptionToggleController);
app.register('edit-lock', EditLockController);
app.register('form-collection', FormCollectionController);
app.register('form-stepper', FormStepperController);
app.register('infinite-scroll', InfiniteScrollController);
app.register('label-chips', LabelChipsController);
app.register('localised-fields', LocalisedFieldsController);
app.register('modal-close', ModalCloseController);
app.register('modal-form-target', ModalFormTargetController);
app.register('print', PrintController);

app.register('activity-item', ActivityItemController);
app.register('signup-field', SignupFieldController);
app.register('signup-list', SignupListController);
