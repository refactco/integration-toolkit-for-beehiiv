import { useState } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";
import { __ } from "@wordpress/i18n";

import {
  __experimentalInputControl as InputControl,
  Button,
  Panel,
  PanelBody,
  PanelRow,
  Snackbar,
  __experimentalGrid as Grid,
} from "@wordpress/components";

const Tabs = (props) => {
  const [saving, setSaving] = useState(false);
  const [loading, setLoading] = useState(false);
  const [apiKey, setApiKey] = useState(re_beehiiv_settings.api_key);
  const [publicationId, setPublicationId] = useState(
    re_beehiiv_settings.publication_id
  );
  const [status, setStatus] = useState(re_beehiiv_settings.api_status);
  const [onSaveMessage, setOnSaveMessage] = useState("");


  const saveSettings = () => {
    const settings = {
      apiKey: apiKey,
      publicationId: publicationId,
      status: 'connected'
    };

    setSaving(true);
    apiFetch({
      path: "/rebeehiiv/v1/save_settings",
      method: "POST",
      data: settings,
    })
      .then((response) => {
        if (!response.success) {
          setSaving(false);
        } else {
          setStatus('connected');
          setSaving(false);
        }

        setOnSaveMessage(response.message);
      })
      .catch((error) => {
        setSaving(false);
        console.log(error);
      });
  };

  const removeAPIKey = () => {
    apiFetch({
      path: "/rebeehiiv/v1/disconnect_api",
      method: "POST",
    })
      .then((response) => {
        if (!response.success) {
          setLoading(false);
        } else {
          setStatus(false);
          setApiKey("");
          setPublicationId("");
        }

        setOnSaveMessage(response.message);
      })
      .catch((error) => {
        setLoading(false);
        console.log(error);
      });
  };
  return (
    <>
    <div className="re-beehiiv-heading">
      <h1>
        Settings
      </h1>
			<p>
        {__('Connect your Beehiiv account to import your content to WordPress.', 're-beehiiv')}
      </p>
	  </div>
    <div className="re-beehiiv-tabs">
      <nav className="nav-tab-wrapper">
        <a className="re-nav-tab re-nav-tab-active" data-tab="re-beehiiv-credentials" href="#">
          { __('Beehiiv Credentials', 're-beehiiv') }
        </a>
      </nav>
    </div>
    <div className="re-beehiiv-settings-tabs re-beehiiv-wrapper" key="settings-tabs">
      <div className="re-beehiiv-settings-tabs-menu" key="settings-tabs"></div>
      {onSaveMessage && (
        <Snackbar
          className="re-beehiiv-snackbar re-beehiiv-snackbar-settings"
          explicitDismiss
          onDismiss={() => setOnSaveMessage("")}
          status="success"
        >
          {onSaveMessage}
        </Snackbar>
      )}
            <PanelRow className="mt-0">
              <Grid columns={1} style={{ width: "100%" }}>
                <InputControl
                  type="password"
                  help={__("The API key provided by Beehiiv.", 're-beehiiv')}
                  label={__("API Key", 're-beehiiv')}
                  onChange={(value) => setApiKey(value)}
                  placeholder={__("Enter your API key", 're-beehiiv')}
                  value={apiKey}
                />
                <InputControl
                  type="password"
                  help={__("The unique publication ID associated with your Beehiiv account.", 're-beehiiv')}
                  label={__("Publication ID", 're-beehiiv')}
                  placeholder={__("Enter your publication ID", 're-beehiiv')}
                  onChange={(value) => setPublicationId(value)}
                  value={publicationId}
                />
              </Grid>
            </PanelRow>
        <div className="re-beehiiv-settings-tabs-contents-actions">
          <Button
            isPrimary
            style={{ marginRight: "1em" }}
            onClick={() => saveSettings()}
            isBusy={saving}
            disabled={status == 'connected'}
            className="re-beehiiv-settings-save"
          >
            {__('Save', 're-beehiiv')}
          </Button>
          {status && (
            <Button
              style={{ marginRight: "1em" }}
              isDestructive
              onClick={() => removeAPIKey()}
              className="re-beehiiv-settings-disconnect"
            >
              {__("Disconnect", 're-beehiiv')}
            </Button>
          )}
          <a href="https://app.beehiiv.com/settings/integrations" target="_blank">
            {__("Get your API key", 're-beehiiv')}
          </a>
          
        </div>
    </div>
    </>
  );
};

export default Tabs;
