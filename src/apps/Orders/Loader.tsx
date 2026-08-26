import App from '@hubleto/react-ui/core/App'

import TableOrders from "./Components/TableOrders"
import FormOrder from "./Components/FormOrder"

class OrdersApp extends App {
  init() {
    super.init();
    globalThis.hubleto.registerReactComponent('OrdersTableOrders', TableOrders);
    globalThis.hubleto.registerReactComponent('OrdersFormOrder', FormOrder);
  }
}

globalThis.hubleto.registerApp('Hubleto/App/Custom/Orders', new OrdersApp());
