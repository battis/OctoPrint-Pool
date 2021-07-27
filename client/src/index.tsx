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
import Login from './components/Login';

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
Authentication.init({loginComponent: Login});

Routing
  .add('/', async () => {
    Authentication.requireAuthentication();
    renderLoadingPage();
    const me = await API.get({endpoint: '/oauth2/me'});
    render(Visual.goldenCenter(<>
      <h1>OctoPrint Pool</h1>
      {me && <p>Logged in as: {me.display_name ? `${me.display_name} (${me.username})` : me.username}</p>}
      <p>Max upload size: {(await API.get({endpoint:'/anonymous/info'})).max_upload_size}</p>
    </>));
  })
  .add('/login', Authentication.userLogin) // convenient shortcut
  .add('/logout', Authentication.userLogout) // convenient shortcut
  .add(Uploader.ROUTE, Uploader.pageHandler)
  .addUriListener()
  .check();