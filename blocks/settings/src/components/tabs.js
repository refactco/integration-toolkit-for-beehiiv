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
  const [apiKey, setApiKey] = useState(wp_to_beehiiv_integration_settings.api_key);
  const [publicationId, setPublicationId] = useState(
    wp_to_beehiiv_integration_settings.publication_id
  );
  const [status, setStatus] = useState(wp_to_beehiiv_integration_settings.api_status);
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
    <div className="wp-to-beehiiv-integration-heading">
      <h1>
        Settings
      </h1>
			<p>
        {__('Establish a connection between your WordPress website and Beehiiv by providing the necessary credentials.', 'wp-to-beehiiv-integration')}
      </p>
	  </div>
    <div className="wp-to-beehiiv-integration-tabs">
      <nav className="nav-tab-wrapper">
        <a className="re-nav-tab re-nav-tab-active" data-tab="wp-to-beehiiv-integration-credentials" href="#">
          { __('Beehiiv Credentials', 'wp-to-beehiiv-integration') }
        </a>
      </nav>
    </div>
    <div className="wp-to-beehiiv-integration-settings-tabs wp-to-beehiiv-integration-wrapper" key="settings-tabs">
      <div className="wp-to-beehiiv-integration-settings-tabs-menu" key="settings-tabs"></div>
      {onSaveMessage && (
        <Snackbar
          className="wp-to-beehiiv-integration-snackbar wp-to-beehiiv-integration-snackbar-settings"
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
                  help={__("Enter the unique API key you received from Beehiiv. This key authorizes and facilitates the communication between your WordPress website and Beehiiv.", 'wp-to-beehiiv-integration')}
                  label={__("API Key", 'wp-to-beehiiv-integration')}
                  onChange={(value) => setApiKey(value)}
                  placeholder={__("Enter your API key", 'wp-to-beehiiv-integration')}
                  value={apiKey}
                />
                <InputControl
                  type="password"
                  help={__("Input the specific ID related to the content or publication you intend to import. This helps in pinpointing the exact data you want to fetch from Beehiiv.", 'wp-to-beehiiv-integration')}
                  label={__("Publication ID", 'wp-to-beehiiv-integration')}
                  placeholder={__("Enter your publication ID", 'wp-to-beehiiv-integration')}
                  onChange={(value) => setPublicationId(value)}
                  value={publicationId}
                />
              </Grid>
            </PanelRow>
        <div className="wp-to-beehiiv-integration-settings-tabs-contents-actions">
          <Button
            isPrimary
            style={{ marginRight: "1em" }}
            onClick={() => saveSettings()}
            isBusy={saving}
            disabled={status == 'connected'}
            className="wp-to-beehiiv-integration-settings-save"
          >
            {__('Save', 'wp-to-beehiiv-integration')}
          </Button>
          {status && (
            <Button
              style={{ marginRight: "1em" }}
              isDestructive
              onClick={() => removeAPIKey()}
              className="wp-to-beehiiv-integration-settings-disconnect"
            >
              {__("Disconnect", 'wp-to-beehiiv-integration')}
            </Button>
          )}
          <a href="https://app.beehiiv.com/settings/integrations" target="_blank">
            {__("Get your API key", 'wp-to-beehiiv-integration')}
          </a>
          
        </div>
    </div>
    </>
  );
};

export default Tabs;
