import App from '@hubleto/react-ui/core/App'

import TableWorkers from "./Components/TableWorkers"
import FormWorker from "./Components/FormWorker"

class WorkersApp extends App {
  init() {
    super.init();

    globalThis.hubleto.registerReactComponent('WorkersTableWorkers', TableWorkers);
    globalThis.hubleto.registerReactComponent('WorkersFormWorker', FormWorker);
  }
}

globalThis.hubleto.registerApp('Hubleto/App/Custom/Workers', new WorkersApp());
