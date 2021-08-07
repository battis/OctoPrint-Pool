import { API, Embed, JSXFactory, render, renderLoadingPage, Routing } from '@battis/web-app-client';
import Queue from '../objects/Queue/Queue';
import Manager from '../objects/Queue/Manager';
import { PageHandler } from 'vanilla-router';
import ErrorMessage from './ErrorMessage';

const Manage: PageHandler = async id => {
  renderLoadingPage();
  const queue = await Queue.get(id);
  if (queue) {
    if (queue.manageable) {
      render(<Embed>
        <Manager
          user={await API.get({ endpoint: '/users/me' })}
          queue={queue}
          files={await API.get({ endpoint: `/queues/${id}/files/mine` })}
        />
      </Embed>);
    } else {
      ErrorMessage(`${queue.name} cannot be managed online.`)
    }
  } else {
    ErrorMessage(`Unknown queue ID#${id} requested.`);
  }
};

export default Manage;