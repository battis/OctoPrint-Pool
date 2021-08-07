import { Embed, JSXFactory, render, renderLoadingPage, Routing } from '@battis/web-app-client';
import Queue from '../objects/Queue/Queue';
import Uploader from '../objects/Queue/Uploader';
import { PageHandler } from 'vanilla-router';

// TODO tags
const Upload: PageHandler = async id => {
  renderLoadingPage();
  const queue = await Queue.get(id);
  if (queue) {
    render(<Embed><Uploader queue={queue} /></Embed>);
  } else {
    Routing.navigateTo('/error?error=unknown+queue');
  }
};

export default Upload;