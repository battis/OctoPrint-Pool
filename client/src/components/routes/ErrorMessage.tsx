import { PageHandler } from 'vanilla-router';
import Home from './Home';

const ErrorMessage: PageHandler = error => {
  Home(error)
}

export default ErrorMessage;