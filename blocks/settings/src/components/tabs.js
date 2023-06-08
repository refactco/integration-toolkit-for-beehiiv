import { useState } from "@wordpress/element";
import apiFetch from "@wordpress/api-fetch";

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
      status: status,
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
    <div className="re-beehiiv-settings-tabs" key="settings-tabs">
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

      <div
        className="re-beehiiv-settings-tabs-contents"
        key="settings-tabs-content"
      >
        <Panel>
          <PanelBody
            title="Beehive Credentials"
            initialOpen={true}
            buttonProps={{ disabled: !status }}
          >
            <PanelRow>Enter your credentials</PanelRow>
            <PanelRow>
              <Grid columns={1} style={{ width: "100%" }}>
                <InputControl
                  type="password"
                  help="Please enter your API key here"
                  label="API Key"
                  onChange={(value) => setApiKey(value)}
                  value={apiKey}
                />
                <InputControl
                  type="password"
                  help="Please enter your publication ID"
                  label="Publication ID"
                  onChange={(value) => setPublicationId(value)}
                  value={publicationId}
                />
              </Grid>
            </PanelRow>
          </PanelBody>
        </Panel>
        <div className="re-beehiiv-settings-tabs-contents-actions">
        <Button
            isPrimary
            style={{ marginRight: "1em" }}
            onClick={() => saveSettings()}
            isBusy={saving}
            disabled={!status}
          >
            Save Settings
          </Button>
          {status && (
            <Button
              style={{ marginRight: "1em" }}
              isDestructive
              onClick={() => removeAPIKey()}
            >
              Disconnect
            </Button>
          )}
          <a href="https://app.beehiiv.com/settings/integrations" target="_blank">
            Get your API key
          </a>
          
        </div>
      </div>
    </div>
  );
};

export default Tabs;
