import {
  API,
  Authentication,
  JSXFactory,
  Modal,
  Routing,
  Tabbed
} from '@battis/web-app-client';
import './Login.scss'

export default class Login extends Authentication.Login{
  public render() {
    return this.element = <Modal title={this.client.display_name} closeable={false}>
      {this.client.description && <p>{this.client.description}</p>}
      <Tabbed>
        <Tabbed.Tab name={'Email-only'}>
          <form class="octoprint-login" method='post' action={API.buildUrl('/oauth2/weak-authorize')}>
            {Object.getOwnPropertyNames(this.request).map(key => <input name={key} type='hidden'
                                                                        value={this.request[key]} />)}
            <div class='form-controls'>
              <label>{process.env.WEAK_AUTHORIZE_USERNAME_LABEL}</label>
              <input name='username' type='text' />
              <div class="input placeholder">&nbsp;</div>
            </div>
            <div class='buttons'>
              <button class='default' type='submit' name='authorized' value='yes'>Login</button>
              <button type='button' onclick={() => Routing.redirectTo('/')}>Cancel
              </button>
            </div>
          </form>
        </Tabbed.Tab>
        <Tabbed.Tab name={'Full Login'}>
          <form method='post' action={this.authorize_uri}>
            {Object.getOwnPropertyNames(this.request).map(key => <input name={key} type='hidden'
                                                                        value={this.request[key]} />)}
            <div class='form-controls'>
              <label>username</label>
              <input name='username' type='text' />
              <label>password</label>
              <input name='password' type='password' />
            </div>
            <div class='buttons'>
              <button class='default' type='submit' name='authorized' value='yes'>Login</button>
              <button type='button' onclick={() => Routing.redirectTo('/')}>Cancel
              </button>
            </div>
          </form>
        </Tabbed.Tab>
      </Tabbed>
    </Modal>
  }
}