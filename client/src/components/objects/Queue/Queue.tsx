import { JSXFactory, ServerComponent } from '@battis/web-app-client';
import './Queue.scss';


export default class Queue extends ServerComponent {
  public static serverPath = '/queues';

  public get name() {
    return this.state.name;
  }

  public set name(name) {
    this.state.name = name;
  }

  public get description() {
    return this.state.description;
  }

  public set description(description) {
    this.state.description = description;
  }

  public get comment() {
    return this.state.comment;
  }

  public set comment(comment) {
    this.state.comment = comment;
  }

  public get manageable(): boolean {
    return this.state.manageable;
  }
}