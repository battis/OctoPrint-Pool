import { JSXFactory, JSXFunction } from '@battis/web-app-client';

const Icon: {[key: string]: JSXFunction} = {
  File: () => <i class='far fa-file'/>,
  Restore: () => <i class='fas fa-undo' />,
  Trash: () => <i class='far fa-trash-alt' />,
  Close: () => <i class="fas fa-times fail"/>,
  Check: () => <i class="fas fa-check success"/>,
  Loading: () => <i class="fas fa-spinner fa-spin"/>
};

export default Icon;