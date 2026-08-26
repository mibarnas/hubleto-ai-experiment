import App from '@hubleto/react-ui/core/App'

import TableQuestionnaires from "./Components/TableQuestionnaires"
import FormQuestionnaire from "./Components/FormQuestionnaire"

class QuestionnairesApp extends App {
  init() {
    super.init();
    globalThis.hubleto.registerReactComponent('QuestionnairesTableQuestionnaires', TableQuestionnaires);
    globalThis.hubleto.registerReactComponent('QuestionnairesFormQuestionnaire', FormQuestionnaire);
  }
}

globalThis.hubleto.registerApp('Hubleto/App/Custom/Questionnaires', new QuestionnairesApp());
