import App from '@hubleto/react-ui/core/App'

import TableQuestionnaires from "./Components/TableQuestionnaires"
import FormQuestionnaire from "./Components/FormQuestionnaire"
import InputTimestamp from "../Trainings/Components/InputTimestamp"

class QuestionnairesApp extends App {
  init() {
    super.init();
    // Registered from every app that uses it: whichever loads first wins, and
    // the questionnaire form must not depend on Trainings having loaded.
    globalThis.hubleto.registerReactComponent('InputTimestamp', InputTimestamp);
    globalThis.hubleto.registerReactComponent('QuestionnairesTableQuestionnaires', TableQuestionnaires);
    globalThis.hubleto.registerReactComponent('QuestionnairesFormQuestionnaire', FormQuestionnaire);
  }
}

globalThis.hubleto.registerApp('Hubleto/App/Custom/Questionnaires', new QuestionnairesApp());
