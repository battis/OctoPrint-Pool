import { API, Authentication, OAuth2, PageNotFound, renderLoadingPage, Routing } from '@battis/web-app-client';
import '@battis/web-app-client/src/index.scss';
import Login from './components/ui/Login';
import Home from './components/routes/Home';
import Upload from './components/routes/Upload';
import Manage from './components/routes/Manage';
import MultiEmbed from './components/routes/MultiEmbed';

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
Authentication.init({ loginComponent: Login });

Routing
  .add('/', Home)
  .add('/queues/{id}/upload', Upload)
  .add('/queues/{id}/manage', Manage)
  .add(/multi\/([0-9+]+)\/?(.*)$/, MultiEmbed)
  .addRedirect('/login', Authentication.login_path) // convenient shortcut
  .addRedirect('/logout', Authentication.logout_path) // convenient shortcut
  .addRedirect('/error', '/')
  .addUriListener()
  .check();