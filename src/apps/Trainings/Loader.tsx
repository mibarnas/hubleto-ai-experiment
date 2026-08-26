import App from '@hubleto/react-ui/core/App'

import TableTrainings from "./Components/TableTrainings"
import FormTraining from "./Components/FormTraining"
import TableSchedules from "./Components/TableSchedules"
import FormSchedule from "./Components/FormSchedule"
import TableAttendees from "./Components/TableAttendees"
import FormAttendee from "./Components/FormAttendee"
import TableCertificates from "./Components/TableCertificates"
import FormCertificate from "./Components/FormCertificate"

class TrainingsApp extends App {
  init() {
    super.init();

    globalThis.hubleto.registerReactComponent('TrainingsTableTrainings', TableTrainings);
    globalThis.hubleto.registerReactComponent('TrainingsFormTraining', FormTraining);
    globalThis.hubleto.registerReactComponent('TrainingsTableSchedules', TableSchedules);
    globalThis.hubleto.registerReactComponent('TrainingsFormSchedule', FormSchedule);
    globalThis.hubleto.registerReactComponent('TrainingsTableAttendees', TableAttendees);
    globalThis.hubleto.registerReactComponent('TrainingsFormAttendee', FormAttendee);
    globalThis.hubleto.registerReactComponent('TrainingsTableCertificates', TableCertificates);
    globalThis.hubleto.registerReactComponent('TrainingsFormCertificate', FormCertificate);
  }
}

globalThis.hubleto.registerApp('Hubleto/App/Custom/Trainings', new TrainingsApp());
