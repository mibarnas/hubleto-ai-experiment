import App from '@hubleto/react-ui/core/App'

import TableCertificates from "./Components/TableCertificates"
import FormCertificate from "./Components/FormCertificate"

class CertificatesApp extends App {
  init() {
    super.init();

    globalThis.hubleto.registerReactComponent('CertificatesTableCertificates', TableCertificates);
    globalThis.hubleto.registerReactComponent('CertificatesFormCertificate', FormCertificate);
  }
}

globalThis.hubleto.registerApp('Hubleto/App/Custom/Certificates', new CertificatesApp());
