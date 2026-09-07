import App from '@hubleto/react-ui/core/App'

import TableOrders from "./Components/TableOrders"
import FormOrder from "./Components/FormOrder"

class TrainingOrdersApp extends App {
  init() {
    super.init();
    // Prefixed with the app name: the community Orders app registers
    // `OrdersTableOrders`, and the two were overwriting each other in the
    // global component registry.
    globalThis.hubleto.registerReactComponent('TrainingOrdersTableOrders', TableOrders);
    globalThis.hubleto.registerReactComponent('TrainingOrdersFormOrder', FormOrder);
  }
}

globalThis.hubleto.registerApp('Hubleto/App/Custom/TrainingOrders', new TrainingOrdersApp());
