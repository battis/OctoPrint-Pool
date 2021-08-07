import { Button, HideableValue, JSXFactory, Nullable, ServerComponent } from '@battis/web-app-client';
import './File.scss';
import Icon from '../ui/Icon';

export default class File extends ServerComponent {
  public static serverPath = '/files';

  public get queue_id(): string {
    return this.state.queue_id;
  }

  public get filename(): string {
    return this.state.filename;
  }

  public get comment(): Nullable<string> {
    return this.state.comment;
  }

  public get filesize(): string {
    return this.state.filesize;
  }

  public get tags(): Nullable<string[]> {
    return this.state.tags;
  }

  public get available(): boolean {
    return this.state.available;
  }

  public get queued(): boolean {
    return this.state.queued;
  }

  public get dequeued(): Nullable<Date> {
    return this.state.dequeued;
  }

  public get created() {
    return this.state.created;
  }

  public async removeFromQueue() {
    this.element.classList.remove('queued');
    await this.update({ queued: false });
  }

  public async returnToQueue() {
    this.element.classList.add('queued');
    await this.update({ queued: true });
  }

  protected updateState(data) {
    super.updateState(data);
    if (this.queued) {
      this.element.classList.add('queued');
    } else {
      this.element.classList.remove('queued');
    }
  }

  render() {
    return this.element = <div class={`file ${this.queued ? 'queued' : ''}`}>
      <div><span class='trash'><Icon.File /></span><span class='restore'><Icon.File /></span>&nbsp;
        <div class='user-info'>
          <span class='filename'>{this.filename}</span>
          <HideableValue class='comment' isShown={() => !!this.comment} tagName='div'>{this.comment}</HideableValue>
          <HideableValue isShown={() => !!this.tags} tagName='div'>
            <label>tags</label>
            <span class='tags'>{this.tags?.join(', ')}</span>
          </HideableValue>
        </div>&nbsp;(<span class='filesize'>{this.filesize}</span>, uploaded <span class="created">{this.created}</span>)
      </div>
      <div class='buttons'>
        <Button class='trash' onclick={this.removeFromQueue.bind(this)}>
          <Icon.Trash />
        </Button>
        <Button class='restore' onclick={this.returnToQueue.bind(this)}>
          <Icon.Restore />
        </Button>
      </div>
    </div>;
  }
}