/*jslint esversion: 8 */
/*global OctoPrint */

// TODO um... maybe webpack this bad boy _a bit_?

/**
 * Declare OctoPrint global
 *
 * @typedef {Object} OctoPrint_Settings
 * @property {function(string): Object} getPluginSettings
 * @property {function(string, Object): Object} savePluginSettings
 *
 * @typedef {Object} OctoPrint
 * @property {OctoPrint_Settings} settings
 */

// noinspection JSUnusedGlobalSymbols
/** I'm a little teapot, short and stout, here just to make intelliSense happy */
class OctoPrintPool_AuthorizationRequest {
  /**
   * @param {OctoPrintPool_API} api
   */
  constructor(api) {
    this.response_type = 'code';
    this.client_id = api.client_id;
    this.state = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
    this.redirect_uri = new URL(api.endpointUrl(`/oauth2/state/${this.state}`));
  }
}

// noinspection JSUnusedGlobalSymbols
class OctoPrintPool_Authorization {
  /**
   * @param {string} authorization_code
   * @param {string} expires
   * @param {string} state
   */
  constructor({authorization_code, expires, state}) {
    this.authorization_code = authorization_code;
    this.expires = expires;
    this.state = state;
  }
}

// noinspection JSUnusedGlobalSymbols
class OctoPrintPool_AuthorizationCodeGrantRequest {
  /**
   * @param {OctoPrintPool_AuthorizationRequest} authorizationRequest
   * @param {OctoPrintPool_Authorization} authorization
   * @param {string} client_secret
   */
  constructor({authorizationRequest, authorization, client_secret}) {
    this.grant_type = 'authorization_code';
    this.code = authorization.authorization_code;
    this.client_id = authorizationRequest.client_id;
    this.client_secret = client_secret;
    this.redirect_uri = authorizationRequest.redirect_uri.pathname;
  }
}

// noinspection JSUnusedGlobalSymbols
class OctoPrintPool_RefreshTokenGrantRequest {
  /**
   * @param {string} refresh_token
   * @param {string} client_id
   * @param {string} client_secret
   */
  constructor({refresh_token, client_id, client_secret}) {
    this.grant_type = 'refresh_token';
    this.refresh_token = refresh_token;
    this.client_id = client_id;
    this.client_secret = client_secret;
  }
}

// noinspection JSUnusedGlobalSymbols
class OctoPrintPool_TokenData {
  /**
   * @param {string} access_token
   * @param {string|number} expires_in
   * @param {string} refresh_token
   * @param scope
   * @param {string} token_type
   */
  constructor({access_token, expires_in, refresh_token, scope, token_type}) {
    this.access_token = access_token;
    this.expires_in = expires_in;
    this.refresh_token = refresh_token;
    this.scope = scope;
    this.token_type = token_type;
  }
}

class OctoPrintPool_API {
  /**
   * @param {string} plugin_id
   * @param {string} pool_url
   * @param {string} client_id
   * @param {string} client_secret
   */
  constructor({plugin_id, pool_url, client_id, client_secret}) {
    this.plugin_id = plugin_id;
    this.pool_url = new URL(pool_url);
    this.client_id = client_id;
    this.client_secret = client_secret;

    // TODO is there a more elegant solution?
    // eslint-disable-next-line @typescript-eslint/no-this-alias
    const api = this;
    this.credentialRetryStrategies = {
      get CACHED() {
        return api.refreshTokenGrant;
      },
      get REFRESH_TOKEN() {
        return api.authorizationCodeGrant;
      },
      get AUTHORIZATION_CODE() {
        return () => {
          throw new Error("Authorization failure. Check OAuth configuration and OctoPrint Pool" +
            " credentials.");
        };
      }
    };
    this.credentialRetryStrategy = undefined;
  }

  get authorize_url() {
    // FIXME trailing slash in pool_url could be a problem
    return this.pool_url + '/oauth2/authorize';
  }

  /**
   * @return {Promise<void>}
   */
  async updateTokens() {
    if (this.access_token) {
      return;
    }
    const settings = await OctoPrint.settings.getPluginSettings(this.plugin_id);
    this.access_token = settings.access_token ? String(settings.access_token) : undefined;
    this.access_token_expiration = settings.access_token_expiration ? Number(settings.access_token_expiration) : undefined;
    this.refresh_token = settings.refresh_token ? String(settings.refresh_token) : undefined;
  }

  /**
   * @param {Object} tokenData
   * @return {Promise<string>}
   */
  async saveAccessToken(tokenData) {
    ({
      access_token: this.access_token,
      access_token_expiration: this.access_token_expiration,
      refresh_token: this.refresh_token
    } = (new OctoPrintPool_TokenData(tokenData)));
    try {
      await OctoPrint.settings.savePluginSettings(this.plugin_id, {
        access_token: tokenData.access_token,
        access_token_expiration: Date.now() + 1000 * tokenData.expires_in,
        refresh_token: tokenData.refresh_token
      });
    } catch (e) {
      // TODO maybe don't ignore that we can't cache a token?
    }
    return tokenData.access_token;
  }

  /**
   * @return {Promise<string>}
   */
  async refreshTokenGrant() {
    return await this.saveAccessToken(
      await this.call({
        endpoint: '/oauth2/token',
        method: 'POST',
        body: new OctoPrintPool_RefreshTokenGrantRequest({
          refresh_token: this.refresh_token,
          client_id: this.client_id,
          client_secret: this.client_secret
        }),
        requiresAuthorization: false
      })
    );
  }

  /**
   * @return {Promise<string>}
   */
  async authorizationCodeGrant() {
    const authorizationRequest = new OctoPrintPool_AuthorizationRequest(this);
    const authForm = document.createElement('form');
    authForm.target = '_blank';
    authForm.method = 'GET';
    authForm.action = this.authorize_url;
    for (const field of Object.keys(authorizationRequest)) {
      let value = authorizationRequest[field];
      if (field === 'redirect_uri') {
        value = authorizationRequest.redirect_uri.pathname;
      }
      authForm.innerHTML += `<input type="hidden" name="${field}" value="${value}">`;
    }
    document.body.appendChild(authForm);
    authForm.submit();
    // TODO throw up modal message explaining that the auth form should be in another tab
    document.body.removeChild(authForm);

    const authorization = new OctoPrintPool_Authorization(await this.call({
      endpoint: authorizationRequest.redirect_uri,
      requiresAuthorization: false
    }));

    return await this.saveAccessToken(
      await this.call({
        endpoint: '/oauth2/token',
        method: 'POST',
        body: new OctoPrintPool_AuthorizationCodeGrantRequest({
          authorizationRequest,
          authorization,
          client_secret: this.client_secret
        }),
        requiresAuthorization: false
      })
    );
  }

  /**
   * @return {Promise<string>}
   */
  async accessToken() {
    await this.updateTokens();
    if (this.access_token && this.access_token_expiration > Date.now()) {
      this.credentialRetryStrategy = this.credentialRetryStrategies.CACHED;
      return this.access_token;
    }

    if (this.refresh_token) {
      this.credentialRetryStrategy = this.credentialRetryStrategies.REFRESH_TOKEN;
      return await this.refreshTokenGrant();
    }

    if (this.pool_url) {
      this.credentialRetryStrategy = this.credentialRetryStrategies.AUTHORIZATION_CODE;
      return await this.authorizationCodeGrant();
    }

    // TODO ...and if none of that works, point out that the configuration needs to be completed
  }

  /**
   * @link https://stackoverflow.com/a/46427607 vanilla-js equivalent to os.path.join
   * @param args
   * @return {string}
   */
  static build_path(...args) {
    return args.map((part, i) => {
      if (i === 0) {
        return String(part).trim().replace(/[\/]*$/g, '');
      } else {
        return String(part).trim().replace(/(^[\/]*|[\/]*$)/g, '');
      }
    }).filter(x => x.length).join('/');
  }

  /**
   * @param {string} endpoint
   * @return {string}
   */
  endpointUrl(endpoint) {
    return OctoPrintPool_API.build_path(this.pool_url, '/api/v1', endpoint);
  }

  /**
   * @param {string} endpoint
   * @param {string} method
   * @param {Object|Headers} headers
   * @param {Object|FormData} body
   * @param {boolean} requiresAuthorization
   * @param {boolean} json
   * @return {Promise<Response|any>}
   */
  async call({endpoint, method, headers = {}, body, requiresAuthorization = true, json = true}) {
    if (endpoint === undefined) {
      throw "endpoint undefined";
    }
    if (endpoint instanceof URL) {
      endpoint = endpoint.toString();
    } else {
      endpoint = this.endpointUrl(endpoint);
    }

    // convert body object to FormData object for API
    if (body !== undefined && false === FormData.isPrototypeOf(body)) {
      const formData = new FormData();
      for (const prop of Object.keys(body)) {
        formData.append(prop, body[prop]);
      }
      body = formData;
    }
    if (requiresAuthorization) {
      headers.Authorization = `Bearer ${await this.accessToken()}`;
    }

    const response = await fetch(endpoint, {
      method: method,
      headers: headers,
      mode: 'cors',
      credentials: 'include',
      body: body
    });

    if (!response.ok && response.status >= 400 && response.status < 500) {
      // FIXME does not retry successfully if access_token is invalid, but can be refreshed
      // FIXME does not retry successfully if authorization code grant must be performed
      try {
        this.credentialRetryStrategy();
      } catch (e) {
        return undefined;
      }
      return this.call({endpoint, method, body, json, headers, requiresAuthorization});
    }

    if (json) {
      return await response.json();
    }
    return response;
  }
}

class OctoPrintPool_Plugin {
  /**
   * @param {string} plugin_id
   * @param {string} pool_url
   * @param {string} client_id
   * @param {string} client_secret
   */
  constructor({plugin_id, pool_url, client_id, client_secret}) {
    this.api = new OctoPrintPool_API({
      plugin_id,
      pool_url,
      client_id,
      client_secret,
    });
  }
}

class OctoPrintPool_Queue extends OctoPrintPool_Plugin {

  constructor({queue_id, plugin_id, pool_url, client_id, client_secret}) {
    super({plugin_id, pool_url, client_id, client_secret});
    this.queue_id = queue_id;
  }

  /**
   * @return {Promise<Object[]>}
   */
  async list() {
    return await this.api.call({
      endpoint: `/queues/${this.queue_id}/files?queued=true&available=true`
    });
  }

  /**
   * @param {string} file_id
   * @return {Promise<Blob>}
   */
  async dequeue(file_id) {
    return (await this.api.call({
      endpoint: `/queues/${this.queue_id}/files/${file_id}`,
      method: 'DELETE',
      json: false
    }))
      .blob();
  }
}
