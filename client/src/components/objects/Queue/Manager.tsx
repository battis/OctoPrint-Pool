import { Button, Component, JSXFactory, Routing } from '@battis/web-app-client';
import QueueFile from '../File';
import Queue from './Queue';
import './Manager.scss';

type ManagerConfig = {user: object, queue: Queue, files: object[]}

export default class Manager extends Component {

  private user;
  private queue;
  private files;

  public constructor({ user, queue, files }: ManagerConfig) {
    super();
    this.user = user;
    this.queue = queue;
    this.files = files.map(data => new QueueFile(data));
  }

  public render() {
    return this.element = <div class="queue manager">
      <h1>{this.user.display_name || this.user.username}'s {this.queue.name}</h1>
      <Button onclick={() => Routing.navigateTo(`/queues/${this.queue.id}/upload`)}>Upload more files</Button>
      <table class="files">
        <tbody>
        {this.files.map(file => <tr><td>{file}</td></tr>)}
        </tbody>
      </table>
    </div>
  }
}