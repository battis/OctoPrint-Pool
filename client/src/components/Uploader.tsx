import { API, Component, Icon, JSXFactory, Nullable, Query, render, Text, Visual } from '@battis/web-app-client';
import './Uploader.scss';
import path from 'path';
import { PageHandler } from 'vanilla-router';

type UploaderConfig = { queue: string, tags: string[] } & object;

export default class Uploader extends Component {

  public static ROUTE = /^upload\/(\w+)(\/?.*)\/?$/;

  private readonly queue: string;
  private readonly tags: string[];

  private params?;

  private _uploaded?: HTMLElement;

  private PlaceholderString = class P extends Text.PlaceholderString {
    static FILENAMES = '{filenames}';
    static QUEUE = '{queue}';

    constructor(target: Uploader, ...rest) {
      super({
        [P.FILENAMES]: target.placeholderFileNames.bind(target),
        [P.QUEUE]: target.placeholderQueue.bind(target)
      }, ...rest);
    }
  };

  protected placeholderFileNames(files: FileList) {
    return files && Text.oxfordCommaList(Array.from(files)
      .map(file => file.name)) || '';
  }

  protected placeholderQueue() {
    return this.queue;
  }

  protected get button() {
    if (!this.params) {
      this.params = Query.parseGetParameters();
    }
    return new this.PlaceholderString(this, this.params.button || this.params.b || `Upload to ${this.PlaceholderString.QUEUE}`);
  }

  protected get message() {
    if (!this.params) {
      this.params = Query.parseGetParameters();
    }
    const m = this.params.message || this.params.m || false;
    return m && !Text.isBooleanValue(m) && new this.PlaceholderString(this, m) || false;
  }

  protected get comment() {
    if (!this.params) {
      this.params = Query.parseGetParameters();
    }
    const c = this.params.comment || this.params.c;
    if (Text.scalarToBool(c)) {
      if (typeof c === 'string') {
        if (Text.isBooleanValue(c)) {
          return new this.PlaceholderString(this, `What notes or instructions do you need to include with ${this.PlaceholderString.FILENAMES}?`);
        } else {
          return new this.PlaceholderString(this, c);
        }
      } else {
        return false;
      }
    }
  }

  private get uploaded() {
    if (!this._uploaded || !this._uploaded.isConnected) {
      this._uploaded = this.element.querySelector('.uploaded') as HTMLElement;
    }
    return this._uploaded;
  }

  public constructor({ queue, tags = [], ...rest }: UploaderConfig) {
    super({ ...rest });
    this.queue = queue;
    this.tags = tags;
  }

  public static pageHandler: PageHandler = (queue: string, tags: string) => {
    render(new Uploader({ queue, tags: tags.split('/').filter(tag => tag.length > 0) }));
  };

  public render() {
    this.params = Query.parseGetParameters();
    this.element = <div class='uploader'>
      {Visual.goldenCenter(<>
        <form>
          <input type='file' name='file' multiple={true} onchange={this.handleFileDrop.bind(this)} />
          {this.message && <p class='message'>{this.message.finalize()}</p>}
          <button
            onclick={this.selectFiles.bind(this)}>{this.button.finalize()}</button>
        </form>
        <div class='uploaded' />
      </>)}
    </div>;

    this.enableDragAndDrop();
    this.element.addEventListener('dragenter', this.highlightTarget.bind(this), false);
    this.element.addEventListener('dragover', this.highlightTarget.bind(this), false);
    this.element.addEventListener('dragleave', this.unhighlightTarget.bind(this), false);
    this.element.addEventListener('drop', this.handleFileDrop.bind(this), false);

    return this.element;
  }

  private enableDragAndDrop() {
    const enable = event => {
      event.preventDefault();
    };

    this.element.addEventListener('dragenter', enable, false);
    this.element.addEventListener('dragover', enable, false);
  }

  private highlightTarget() {
    this.element.classList.add('target');
  }

  private unhighlightTarget() {
    this.element.classList.remove('target');
  }

  private selectFiles(event) {
    event.stopPropagation();
    this.element.querySelector('input')?.click();
  }

  private handleFileDrop(event) {
    event.preventDefault();
    this.unhighlightTarget();
    const files = event.dataTransfer?.files || event.target.files;
    files && this.uploadFiles(files);
  }

  private uploadFiles(files) {
    const uploadFile = async (file: File, comment, container) => {
      const status = container.appendChild(<div class='file'><Icon.Loading /> {file.name}</div>);
      const response: [] = await API.post({
        endpoint: path.join('anonymous/queue', this.queue),
        body: Query.getFormData({
          file, comment, 'tags[]': this.tags
        })
      }, false);
      if (response.length === 0) {
        render(status, <><Icon.Close class={'danger'} /> {file.name}</>);
      } else {
        for (const item of response) {
          render(status, <><Icon.Checked /> {item['filename']}</>);
        }
      }
    };

    let comment: Nullable<string> = null;
    if (this.comment) {
      comment = window.prompt(this.comment.finalize(files));
      if (comment === null) {
        return;
      }
    }

    const container = <div class='files' />;
    this.uploaded.appendChild<HTMLElement>(
      <div class='batch'>
        {comment?.length && <p class='comment'>{comment}</p>}
        {container}
      </div>
    ).scrollIntoView();

    for (const file of files) {
      uploadFile(file, comment, container).then();
    }
  }
}
