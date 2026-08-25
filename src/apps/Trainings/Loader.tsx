import App from '@hubleto/react-ui/core/App'

import TableTrainings from "./Components/TableTrainings"
import FormTraining from "./Components/FormTraining"
import TableTrainingDates from "./Components/TableTrainingDates"
import FormTrainingDate from "./Components/FormTrainingDate"
import TableApplicants from "./Components/TableApplicants"
import FormApplicant from "./Components/FormApplicant"
import TableTrainingOrders from "./Components/TableTrainingOrders"
import FormTrainingOrder from "./Components/FormTrainingOrder"
import TableQuestionnaireAnswers from "./Components/TableQuestionnaireAnswers"
import FormQuestionnaireAnswer from "./Components/FormQuestionnaireAnswer"

class TrainingsApp extends App {
  init() {
    super.init();

    globalThis.hubleto.registerReactComponent('TrainingsTableTrainings', TableTrainings);
    globalThis.hubleto.registerReactComponent('TrainingsFormTraining', FormTraining);
    globalThis.hubleto.registerReactComponent('TrainingsTableTrainingDates', TableTrainingDates);
    globalThis.hubleto.registerReactComponent('TrainingsFormTrainingDate', FormTrainingDate);
    globalThis.hubleto.registerReactComponent('TrainingsTableApplicants', TableApplicants);
    globalThis.hubleto.registerReactComponent('TrainingsFormApplicant', FormApplicant);
    globalThis.hubleto.registerReactComponent('TrainingsTableTrainingOrders', TableTrainingOrders);
    globalThis.hubleto.registerReactComponent('TrainingsFormTrainingOrder', FormTrainingOrder);
    globalThis.hubleto.registerReactComponent('TrainingsTableQuestionnaireAnswers', TableQuestionnaireAnswers);
    globalThis.hubleto.registerReactComponent('TrainingsFormQuestionnaireAnswer', FormQuestionnaireAnswer);
  }
}

globalThis.hubleto.registerApp('Hubleto/App/Custom/Trainings', new TrainingsApp());
