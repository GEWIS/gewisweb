import { startStimulusApp } from '@symfony/stimulus-bundle';

// Application-wide, domain-agnostic controllers.
import ConfirmModalController from './controllers/application/confirm_modal_controller.ts';
import CopyController from './controllers/application/copy_controller.ts';
import CosmeticsToggleController from './controllers/application/cosmetics_toggle_controller.ts';
import DescriptionToggleController from './controllers/application/description_toggle_controller.ts';
import DismissibleController from './controllers/application/dismissible_controller.ts';
import EditLockController from './controllers/application/edit_lock_controller.ts';
import FormCollectionController from './controllers/application/form_collection_controller.ts';
import FormStepperController from './controllers/application/form_stepper_controller.ts';
import InfiniteScrollController from './controllers/application/infinite_scroll_controller.ts';
import LabelChipsController from './controllers/application/label_chips_controller.ts';
import LocalisedFieldsController from './controllers/application/localised_fields_controller.ts';
import MarkdownEditorController from './controllers/application/markdown_editor_controller.ts';
import ModalCloseController from './controllers/application/modal_close_controller.ts';
import ModalFormTargetController from './controllers/application/modal_form_target_controller.ts';
import NavDropdownController from './controllers/application/nav_dropdown_controller.ts';
import NavigateSelectController from './controllers/application/navigate_select_controller.ts';
import NotificationSettingsController from './controllers/application/notification_settings_controller.ts';
import NotificationsController from './controllers/application/notifications_controller.ts';
import PrintController from './controllers/application/print_controller.ts';
import SortableController from './controllers/application/sortable_controller.ts';

// User-specific controllers.
import ExternalAppSigningController from './controllers/user/external_app_signing_controller.ts';

// Activity-specific controllers.
import ActivityItemController from './controllers/activity/activity_item_controller.ts';
import SignupFieldController from './controllers/activity/signup_field_controller.ts';
import SignupListController from './controllers/activity/signup_list_controller.ts';

// Decision-specific controllers.
import DocumentUploadController from './controllers/decision/document_upload_controller.ts';
import LiveSortableController from './controllers/decision/live_sortable_controller.ts';
import MemberSearchController from './controllers/decision/member_search_controller.ts';
import RevisionFilterController from './controllers/decision/revision_filter_controller.ts';

// Photo-specific controllers.
import AlbumSearchController from './controllers/photo/album_search_controller.ts';
import CoverController from './controllers/photo/cover_controller.ts';
import GalleryController from './controllers/photo/gallery_controller.ts';
import UploadController from './controllers/photo/upload_controller.ts';

const app = startStimulusApp();

// Registered with flat identifiers so the templates keep using `data-controller="form-stepper"` etc. despite the
// subdirectories -- the path-based autoload would otherwise namespace them (e.g. `application--form-stepper`). The
// framework-scaffolded csrf_protection controller stays at the controllers/ root and autoloads as `csrf-protection`.
app.register('confirm-modal', ConfirmModalController);
app.register('copy', CopyController);
app.register('cosmetics-toggle', CosmeticsToggleController);
app.register('description-toggle', DescriptionToggleController);
app.register('dismissible', DismissibleController);
app.register('edit-lock', EditLockController);
app.register('form-collection', FormCollectionController);
app.register('form-stepper', FormStepperController);
app.register('infinite-scroll', InfiniteScrollController);
app.register('label-chips', LabelChipsController);
app.register('localised-fields', LocalisedFieldsController);
app.register('markdown-editor', MarkdownEditorController);
app.register('modal-close', ModalCloseController);
app.register('modal-form-target', ModalFormTargetController);
app.register('nav-dropdown', NavDropdownController);
app.register('navigate-select', NavigateSelectController);
app.register('notification-settings', NotificationSettingsController);
app.register('notifications', NotificationsController);
app.register('document-upload', DocumentUploadController);
app.register('live-sortable', LiveSortableController);
app.register('member-search', MemberSearchController);
app.register('print', PrintController);
app.register('revision-filter', RevisionFilterController);
app.register('sortable', SortableController);

app.register('external-app-signing', ExternalAppSigningController);

app.register('activity-item', ActivityItemController);
app.register('signup-field', SignupFieldController);
app.register('signup-list', SignupListController);

app.register('album-search', AlbumSearchController);
app.register('photo-cover', CoverController);
app.register('gallery', GalleryController);
app.register('photo-upload', UploadController);
