import { PageHandler } from 'vanilla-router';
import { Embed, JSXFactory, render, renderLoadingPage, Routing } from '@battis/web-app-client';
import ServerInfo from '../objects/ServerInfo';
import './MultiEmbed.scss';
import * as path from 'path';

// TODO tags
const MultiEmbed: PageHandler = async (id_list: string) => {
  renderLoadingPage();
  const queues = id_list.split('+').filter(id => id.length > 0);
  const serverInfo = <ServerInfo/>
  const element: HTMLElement = <Embed>
    {serverInfo}
    <div class="multi-embed">
      {queues.map(id => <iframe src={Routing.buildUrl(`/queues/${id}/upload`)}/>)}
    </div>
  </Embed>;
  element.style.setProperty('--embeds', `${queues.length}`);
  render(element)
}

export default MultiEmbed;