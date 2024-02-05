import { render } from "@wordpress/element";

import "./components/settings.css";
import Header from "./components/header";
import Tabs from "./components/tabs";
const Settings = (props) => {

  return (
    <div className="wp-to-beehiiv-integration-settings-wrap">
      <Header />
      <Tabs />
    </div>
  );
};

var rootElement = document.getElementById("wp-to-beehiiv-integration-settings");

if (rootElement) {
  render(<Settings scope="global" />, rootElement);
}