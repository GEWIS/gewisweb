import { startStimulusApp } from '@symfony/stimulus-bundle';

// Application-wide, domain-agnostic controllers.
import ConfirmModalController from './controllers/application/confirm_modal_controller.ts';
import DescriptionToggleController from './controllers/application/description_toggle_controller.ts';
import EditLockController from './controllers/application/edit_lock_controller.ts';
import FormCollectionController from './controllers/application/form_collection_controller.ts';
import FormStepperController from './controllers/application/form_stepper_controller.ts';
import InfiniteScrollController from './controllers/application/infinite_scroll_controller.ts';
import LabelChipsController from './controllers/application/label_chips_controller.ts';
import LocalisedFieldsController from './controllers/application/localised_fields_controller.ts';
import MarkdownEditorController from './controllers/application/markdown_editor_controller.ts';
import ModalCloseController from './controllers/application/modal_close_controller.ts';
import ModalFormTargetController from './controllers/application/modal_form_target_controller.ts';
import PrintController from './controllers/application/print_controller.ts';

// Activity-specific controllers.
import ActivityItemController from './controllers/activity/activity_item_controller.ts';
import SignupFieldController from './controllers/activity/signup_field_controller.ts';
import SignupListController from './controllers/activity/signup_list_controller.ts';

// Photo-specific controllers.
import GalleryController from './controllers/photo/gallery_controller.ts';
import UploadController from './controllers/photo/upload_controller.ts';

const app = startStimulusApp();

// Registered with flat identifiers so the templates keep using `data-controller="form-stepper"` etc. despite the
// subdirectories -- the path-based autoload would otherwise namespace them (e.g. `application--form-stepper`). The
// framework-scaffolded csrf_protection controller stays at the controllers/ root and autoloads as `csrf-protection`.
app.register('confirm-modal', ConfirmModalController);
app.register('description-toggle', DescriptionToggleController);
app.register('edit-lock', EditLockController);
app.register('form-collection', FormCollectionController);
app.register('form-stepper', FormStepperController);
app.register('infinite-scroll', InfiniteScrollController);
app.register('label-chips', LabelChipsController);
app.register('localised-fields', LocalisedFieldsController);
app.register('markdown-editor', MarkdownEditorController);
app.register('modal-close', ModalCloseController);
app.register('modal-form-target', ModalFormTargetController);
app.register('print', PrintController);

app.register('activity-item', ActivityItemController);
app.register('signup-field', SignupFieldController);
app.register('signup-list', SignupListController);

app.register('gallery', GalleryController);
app.register('photo-upload', UploadController);
