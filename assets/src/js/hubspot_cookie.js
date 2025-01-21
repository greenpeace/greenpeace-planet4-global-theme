function getCookieValue(name) {
  return document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)')?.pop() || '';
}

// Push event to revoke cookie consent as the first thing on the _hsp queue.
// This results in HubSpot complying to our cookie consent
if (getCookieValue('greenpeace') !== '2') {
  (window._hsp = window._hsp || []).push(['revokeCookieConsent']);
}
