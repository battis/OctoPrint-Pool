import { Button, Component, JSXFactory, Routing, Visual } from '@battis/web-app-client';
import QueueFile from '../File';
import Queue from './Queue';
import './Manager.scss';

type ManagerConfig = { user: object, queue: Queue, files: object[] }

export default class Manager extends Component {

  private user;
  private queue: Queue;
  private files: QueueFile[];

  public constructor({ user, queue, files }: ManagerConfig) {
    super();
    this.user = user;
    this.queue = queue;
    this.files = files.map(data => new QueueFile(data));
  }

  public render() {
    return this.element = <div class='queue manager'>
      <div class='manager'>
        <h3 class="title">{this.user.display_name || this.user.username}'s {this.queue.name}</h3>
        <table class='files'>
          <tbody>
          { !this.files.length ? Visual.goldenCenter(<p>No files available in queue.</p>) : this.files.map(file => <tr>
            <td>{file}</td>
          </tr>)}
          </tbody>
        </table>
      </div>
      <Button class='toggler' onclick={() => Routing.navigateTo(`/queues/${this.queue.id}/upload`)}>Upload to {this.queue.name}</Button>
    </div>;
  }
}