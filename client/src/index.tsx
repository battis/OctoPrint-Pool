import {
  API,
  Authentication,
  Embed,
  JSXFactory,
  OAuth2,
  PageNotFound,
  render,
  renderLoadingPage,
  Routing,
  Visual
} from '@battis/web-app-client';
import '@battis/web-app-client/src/index.scss';
import Login from './components/ui/Login';
import Queue from './components/objects/Queue/Queue';
import ServerInfo from './components/objects/ServerInfo';
import Uploader from './components/objects/Queue/Uploader';
import Manager from './components/objects/Queue/Manager';

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

const goHomeOnError = () => Routing.navigateTo('/');

Routing
  .add('/', async () => {
    Authentication.requireAuthentication();
    render(Visual.goldenCenter(<>
      <h1>OctoPrint Pool</h1>
      <ServerInfo />
      <ul>
        {(await Queue.list()).map(queue => <li>{queue.name}: <a href={`/queues/${queue.id}/manage`}>Manage</a> / <a
          href={`/queues/${queue.id}/upload`}>Upload</a></li>)}
      </ul>
    </>));
  })
  .add('/queues/{id}/upload', async id => {
    renderLoadingPage();
    const queue = await Queue.get(id);
    if (queue) {
      render(<Embed><Uploader queue={queue} /></Embed>);
    } else {
      goHomeOnError();
    }
  })
  .add('/queues/{id}/manage', async id => {
    renderLoadingPage();
    const queue = await Queue.get(id);
    if (queue) {
      render(<Embed>
        <Manager
          user={await API.get({ endpoint: '/users/me' })}
          queue={queue}
          files={await API.get({ endpoint: `/queues/${id}/files/mine` })}
        />
      </Embed>);
    } else {
      goHomeOnError();
    }
  })
  .addRedirect('/login', Authentication.login_path) // convenient shortcut
  .addRedirect('/logout', Authentication.logout_path) // convenient shortcut
  .addUriListener()
  .check();