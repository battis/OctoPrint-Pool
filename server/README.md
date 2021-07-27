# OctoPrint Pool

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/battis/OctoPrint-Pool/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/battis/OctoPrint-Pool/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/battis/OctoPrint-Pool/badges/build.png?b=master)](https://scrutinizer-ci.com/g/battis/OctoPrint-Pool/build-status/master)
[![Code Coverage](https://scrutinizer-ci.com/g/battis/OctoPrint-Pool/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/battis/OctoPrint-Pool/?branch=master)

Manage a printer pool of OctoPrint instances

### Installation

  1. `composer install --no-dev --prefer-dist`
  2. Edit `env/.env`
  3. Optional: copy env/99-octoprint-pool.ini to `/etc/php/VERSION/apache2/conf.d/` or edit `/etc/php/VERSION/apache2/php.ini` to increase maximum file upload size.
