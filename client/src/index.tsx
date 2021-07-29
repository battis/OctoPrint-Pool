import {
  API,
  Authentication,
  JSXFactory, Modal,
  OAuth2,
  PageNotFound,
  render,
  renderLoadingPage,
  Routing,
  Visual
} from '@battis/web-app-client';
import '@battis/web-app-client/src/index.scss';
import Login from './components/Login';
import Queue from './components/Queue';

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
Queue.init();

Routing
  .add('/', async () => {
    Authentication.requireAuthentication();
    renderLoadingPage();
    const me = await API.get({endpoint: '/users/me'});
    render(Visual.goldenCenter(<>
      <h1>OctoPrint Pool</h1>
      {me && <p>Logged in as: {me.display_name ? `${me.display_name} (${me.username})` : me.username}</p>}
      <p>Max upload size: {(await API.get({endpoint:'/anonymous/info'})).max_upload_size}</p>
    </>));
  })
  .addRedirect('/login', Authentication.login_path) // convenient shortcut
  .addRedirect('/logout', Authentication.logout_path) // convenient shortcut
  .addUriListener()
  .check();