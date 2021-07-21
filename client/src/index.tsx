import {
  API,
  Authentication,
  JSXFactory,
  OAuth2,
  PageNotFound,
  render,
  renderLoadingPage,
  Routing,
  Visual
} from '@battis/web-app-client';
import '@battis/web-app-client/src/index.scss';
import Uploader from './components/Uploader';

declare const __PUBLIC_PATH__: string;
declare const __API_URL__: string;
declare const __OAUTH_CLIENT_ID__: string;

renderLoadingPage();

Routing.init({
  root: __PUBLIC_PATH__,
  page404: PageNotFound
});
OAuth2.init({ client_id: __OAUTH_CLIENT_ID__ });
API.init({ url: __API_URL__ });
Authentication.init();

Routing
  .add('/', () => {
    render(Visual.goldenCenter(<h1>OctoPrint Pool</h1>));
  })
  .add(Uploader.ROUTE, Uploader.pageHandler)
  .addUriListener()
  .check();