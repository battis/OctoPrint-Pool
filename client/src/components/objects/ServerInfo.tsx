import { API, Button, Component, Icon, JSXFactory, render, Routing } from '@battis/web-app-client';

export default class ServerInfo extends Component {
  public render() {
    const display_name=<span><Icon.Loading/></span>
    const maximum_upload_size = <span><Icon.Loading/></span>
    this.element = <div class='server-info'>
      <p>Logged in as {display_name} <Button onclick={() => Routing.navigateTo('/logout')}>Logout</Button></p>
      <p>Maximum upload size: {maximum_upload_size}</p>
    </div>;

    API.get({ endpoint: '/users/me' })
      .then(me => render(display_name, me.display_name ? `${me.display_name} (${me.username})` : me.username));
    API.get({endpoint: '/anonymous/info'})
      .then(server => render(maximum_upload_size, server.max_upload_size))

    return this.element;
  }
}
