import { Authentication, JSXFactory, Modal, render, Visual } from '@battis/web-app-client';
import ServerInfo from '../objects/ServerInfo';
import Queue from '../objects/Queue/Queue';
import { PageHandler } from 'vanilla-router';
import MultiEmbed from './MultiEmbed';

const Home: PageHandler = async (error?) => {
  const queues = await Queue.list();
  error && console.log(error);
  MultiEmbed(queues.map(queue => queue.id).join('+'))
};

export default Home;