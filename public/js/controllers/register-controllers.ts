import { Application } from "@hotwired/stimulus";
import ParticipantsTableController from './activity/participants-table_controller'

const application = Application.start()
application.debug = true

// register controllers here:
application.register('participants-table', ParticipantsTableController)

