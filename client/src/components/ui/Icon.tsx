import { JSXFactory, JSXFunction } from '@battis/web-app-client';

const Icon: {[key: string]: JSXFunction} = {
  File: () => <i class='far fa-file'/>,
  Restore: () => <i class='fas fa-undo' />,
  Trash: () => <i class='far fa-trash-alt' />,
  Close: () => <i class="fas fa-times"/>,
  Check: () => <i class="fas fa-check"/>,
  Loading: () => <i class="fas fa-spinner fa-spin"/>
};

export default Icon;