import {
  API,
  Component,
  Icon,
  JSXFactory,
  Modal,
  Nullable,
  Query,
  render,
  renderLoadingPage,
  Routing,
  ServerComponent,
  Text,
  Visual
} from '@battis/web-app-client';
import path from 'path';
import './Queue.scss';

type UploaderConfig = { queue: Queue, tags?: string[] }

export default class Queue extends ServerComponent {
  public static serverPath = '/queues';

  public getUploader(tags: string[] = []): Component {
    return new Queue.Uploader({ queue: this, tags });
  }

  private static Uploader = class Uploader extends Component {
    private queue: Queue;
    private readonly tags: string[];

    private params?;

    private _uploaded?: HTMLElement;

    public constructor({ queue, tags = [] }: UploaderConfig) {
      super();
      this.queue = queue;
      this.tags = tags;
    }

    protected PlaceholderString = class P extends Text.PlaceholderString {
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
      return this.queue.name;
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

    public render() {
      this.element = <div class='queue uploader'>
        {Visual.goldenCenter(<>
          <form>
            <input type='file' name='file' multiple={true} onchange={this.handleFileDrop.bind(this)} />
            {this.queue.description && <p class='message'>{this.queue.description}</p>}
            <button
              onclick={this.selectFiles.bind(this)}>Upload to {this.queue.name}</button>
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
          endpoint: path.join(Queue.serverPath, this.queue.id, 'files'),
          body: Query.getFormData({
            file, comment, 'tags[]': this.tags
          })
        });
        if (response.length === 0) {
          render(status, <><Icon.Close class={'danger'} /> {file.name}</>);
        } else {
          for (const item of response) {
            render(status, <><Icon.Checked /> {item['filename']}</>);
          }
        }
      };

      let comment: Nullable<string> = null;
      if (this.queue.comment) {
        const c = new this.PlaceholderString(this, this.queue.comment)
        comment = window.prompt(c.finalize(files));
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

    private get uploaded() {
      if (!this._uploaded || !this._uploaded.isConnected) {
        this._uploaded = this.element.querySelector('.uploaded') as HTMLElement;
      }
      return this._uploaded;
    }

  };

  public static init() {
    Routing.add(/^upload\/(\w+)(\/?.*)\/?$/, async (queue_id, tags) => {
      renderLoadingPage();
      const queue = await Queue.get(queue_id);
      // TODO fallback to searching for queue by name?
      if (queue) {
        render(queue?.getUploader(tags.split('/').filter(tag => tag.length > 0)));
      } else {
        render(<Modal title='Queue Not Found' closeable={false}>
          <p>Queue ID#{queue_id} could not be found.</p>
          <p><a href='/' onclick={e => {
            e.preventDefault();
            Routing.navigateTo('/');
          }}>Go Home</a></p>
        </Modal>);
      }
    });
  }

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
}