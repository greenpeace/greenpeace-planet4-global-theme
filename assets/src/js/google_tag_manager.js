/* global dataLayer, googleTagManagerData */

if (googleTagManagerData?.google_tag_value) {

  const google_tag_value = googleTagManagerData.google_tag_value;
  const google_tag_domain = googleTagManagerData.google_tag_domain;
  const consent_default_analytics_storage = googleTagManagerData.consent_default_analytics_storage;
  const consent_default_ad_storage = googleTagManagerData.consent_default_ad_storage;
  const consent_default_ad_user_data = googleTagManagerData.consent_default_ad_user_data;
  const consent_default_ad_personalization = googleTagManagerData.consent_default_ad_personalization;
  window.dataLayer = window.dataLayer || [];

  function gtag() { dataLayer.push(arguments); };

  const cookie_content = document.cookie.split(';').map(s => s.trim());
  function cookie_contains(value) {
    return cookie_content.indexOf(value) !== -1;
  };
  const no_track = cookie_contains('no_track=1');
  const active_consent = cookie_contains('active_consent_choice=1');
  const marketing_consent = !no_track && active_consent &&
        (cookie_contains('greenpeace=2') || cookie_contains('greenpeace=4'));
  const analytical_consent = !no_track && active_consent &&
        (cookie_contains('greenpeace=3') || cookie_contains('greenpeace=4'));
  const cookie_consent = marketing_consent || analytical_consent;

  // If Google Consent Mode is enabled, set default ad storage and analytics storage
  // to 'denied' as first action on every page until consent is given.
  // If consent given, update consent on every page.
  if (googleTagManagerData?.cookies_enable_google_consent_mode) {
    let capabilities = {
      ad_storage: consent_default_ad_storage,
      ad_user_data: consent_default_ad_user_data,
      ad_personalization: consent_default_ad_personalization,
      analytics_storage: consent_default_analytics_storage,
    };

    if (cookie_consent) {
      capabilities = {
        ad_storage: marketing_consent ? 'granted' : 'denied',
        ad_user_data: marketing_consent ? 'granted' : 'denied',
        ad_personalization: marketing_consent ? 'granted' : 'denied',
        analytics_storage: analytical_consent ? 'granted' : 'denied',
      };
    }
    gtag('consent', 'default', capabilities);
    gtag('set', 'url_passthrough', true);
    gtag('set', 'ads_data_redaction', capabilities.ad_storage === 'denied');
    dataLayer.push({event: 'defaultConsent', ...capabilities});
  }

  dataLayer.push({
    'pageType' : googleTagManagerData.page_category,
    'signedIn' : googleTagManagerData.p4_signedin_status,
    'visitorType' : googleTagManagerData.p4_visitor_type,
    'userID' : '',
    'post_tags': googleTagManagerData.post_tags,
    'gPlatform': 'Planet 4',
    'p4_blocks': googleTagManagerData.p4_blocks,
    'post_categories': googleTagManagerData.post_categories,
    'reading_time': googleTagManagerData.reading_time ? `#${googleTagManagerData.reading_time}` : '',
    'page_date': googleTagManagerData.page_date ? `#${googleTagManagerData.page_date}` : '',
  });

  if (!googleTagManagerData?.post_password_required) {
    const cf_campaign_name = googleTagManagerData.cf_campaign_name ?? googleTagManagerData.cf_campaign_name;
    const cf_project_id    = googleTagManagerData.cf_project_id ?? googleTagManagerData.cf_project_id;
    const cf_local_project = googleTagManagerData.cf_local_project ?? googleTagManagerData.cf_local_project;
    const cf_basket_name   = googleTagManagerData.cf_basket_name ?? googleTagManagerData.cf_basket_name;
    const cf_scope         = googleTagManagerData.cf_scope ?? googleTagManagerData.cf_scope;
    const cf_department    = googleTagManagerData.cf_department ?? googleTagManagerData.cf_department;

    if (cf_campaign_name || cf_basket_name || cf_scope || cf_department) {
      dataLayer.push({
        'gCampaign' : cf_campaign_name,
        'gLocalProject' : cf_local_project,
        'projectID' : cf_project_id,
        'gBasket' : cf_basket_name,
        'gScope': cf_scope,
        'gDepartment': cf_department,
      });
    }
  }

  const gtm_allow = googleTagManagerData.enforce_cookies_policy ? cookie_consent : true;

  if (google_tag_value && gtm_allow) {
    (function (w, d, s, l, i) {
      w[l] = w[l] || [];
      w[l].push({'gtm.start': new Date().getTime(), event: 'gtm.js'});
      const f = d.getElementsByTagName(s)[0],
        j = d.createElement(s),
        // eslint-disable-next-line eqeqeq
        dl = l != 'dataLayer' ? '&l=' + l : '';
      j.async = true;
      j.src = 'https://' + google_tag_domain + '/gtm.js?id=' + i + dl;
      f.parentNode.insertBefore(j, f);
    })(window, document, 'script', 'dataLayer', google_tag_value);
  }
}
