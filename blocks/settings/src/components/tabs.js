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
  const [apiKey, setApiKey] = useState(integration_toolkit_for_beehiiv_settings.api_key);
  const [publicationId, setPublicationId] = useState(
    integration_toolkit_for_beehiiv_settings.publication_id
  );
  const [status, setStatus] = useState(integration_toolkit_for_beehiiv_settings.api_status);
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
    <div className="integration-toolkit-for-beehiiv-heading">
      <h1>
        Settings
      </h1>
			<p>
        {__('Establish a connection between your WordPress website and Beehiiv by providing the necessary credentials.', 'integration-toolkit-for-beehiiv')}
      </p>
	  </div>
    <div className="integration-toolkit-for-beehiiv-tabs">
      <nav className="nav-tab-wrapper">
        <a className="re-nav-tab re-nav-tab-active" data-tab="integration-toolkit-for-beehiiv-credentials" href="#">
          { __('Beehiiv Credentials', 'integration-toolkit-for-beehiiv') }
        </a>
      </nav>
    </div>
    <div className="integration-toolkit-for-beehiiv-settings-tabs integration-toolkit-for-beehiiv-wrapper" key="settings-tabs">
      <div className="integration-toolkit-for-beehiiv-settings-tabs-menu" key="settings-tabs"></div>
      {onSaveMessage && (
        <Snackbar
          className="integration-toolkit-for-beehiiv-snackbar integration-toolkit-for-beehiiv-snackbar-settings"
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
                  help={__("Enter the unique API key you received from Beehiiv. This key authorizes and facilitates the communication between your WordPress website and Beehiiv.", 'integration-toolkit-for-beehiiv')}
                  label={__("API Key", 'integration-toolkit-for-beehiiv')}
                  onChange={(value) => setApiKey(value)}
                  placeholder={__("Enter your API key", 'integration-toolkit-for-beehiiv')}
                  value={apiKey}
                />
                <InputControl
                  type="password"
                  help={__("Input the specific ID related to the content or publication you intend to import. This helps in pinpointing the exact data you want to fetch from Beehiiv.", 'integration-toolkit-for-beehiiv')}
                  label={__("Publication ID", 'integration-toolkit-for-beehiiv')}
                  placeholder={__("Enter your publication ID", 'integration-toolkit-for-beehiiv')}
                  onChange={(value) => setPublicationId(value)}
                  value={publicationId}
                />
              </Grid>
            </PanelRow>
        <div className="integration-toolkit-for-beehiiv-settings-tabs-contents-actions">
          <Button
            isPrimary
            style={{ marginRight: "1em" }}
            onClick={() => saveSettings()}
            isBusy={saving}
            disabled={status == 'connected'}
            className="integration-toolkit-for-beehiiv-settings-save"
          >
            {__('Save', 'integration-toolkit-for-beehiiv')}
          </Button>
          {status && (
            <Button
              style={{ marginRight: "1em" }}
              isDestructive
              onClick={() => removeAPIKey()}
              className="integration-toolkit-for-beehiiv-settings-disconnect"
            >
              {__("Disconnect", 'integration-toolkit-for-beehiiv')}
            </Button>
          )}
          <a href="https://app.beehiiv.com/settings/integrations" target="_blank">
            {__("Get your API key", 'integration-toolkit-for-beehiiv')}
          </a>
          
        </div>
    </div>
    </>
  );
};

export default Tabs;
